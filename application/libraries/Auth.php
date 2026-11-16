<?php
/**
 * Auth - autenticacao com sessao nativa do PHP
 *
 * Nao usa a library Session do CodeIgniter 2.1.2 porque ela depende de mcrypt,
 * removido do PHP 7.4.
 */
class Auth
{
    private $CI;
    private $usuario = NULL;

    /** motivo da ultima falha de autenticacao */
    protected $motivo = '';

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->_iniciar_sessao();

        if (isset($_SESSION['biostech_usuario']))
        {
            $this->usuario = $_SESSION['biostech_usuario'];
        }
    }

    // ------------------------------------------------------------------

    private function _iniciar_sessao()
    {
        if (session_id() !== '')
        {
            return;
        }

        $path = APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'sessions';
        if ( ! is_dir($path))
        {
            @mkdir($path, 0777, TRUE);
        }
        if (is_dir($path) && is_writable($path))
        {
            session_save_path($path);
        }

        if (function_exists('ini_set'))
        {
            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_only_cookies', 1);
        }

        session_name('biostech_sid');
        session_set_cookie_params(array(
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => TRUE,
            'samesite' => 'Lax',
        ));

        session_start();
    }

    // ------------------------------------------------------------------

    public function logado()
    {
        return ! empty($this->usuario);
    }

    public function usuario()
    {
        return $this->usuario;
    }

    public function id()
    {
        return $this->logado() ? (int) $this->usuario['id'] : 0;
    }

    public function motivo()
    {
        return $this->motivo;
    }

    public function eh_admin()
    {
        return $this->logado() && $this->usuario['perfil'] === PERFIL_ADMIN;
    }

    public function pode_gerenciar()
    {
        return $this->logado() && in_array($this->usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE);
    }

    // ------------------------------------------------------------------

    /**
     * Tenta autenticar. Retorna TRUE em caso de sucesso.
     */
    public function tentar($login, $senha)
    {
        $this->motivo = '';
        $login = strtoupper(trim($login));

        if ($login === '' || $senha === '')
        {
            return FALSE;
        }

        $sql = "SELECT u.*, s.nome AS setor_nome
                  FROM " . T_USUARIOS . " u
                  LEFT JOIN " . T_SETORES . " s ON s.id = u.setor_id
                 WHERE upper(u.login) = ?
                 LIMIT 1";

        $usuario = $this->CI->db->query($sql, array($login))->row_array();

        if (empty($usuario))
        {
            return FALSE;
        }

        if ( ! $usuario['ativo'])
        {
            $this->motivo = 'Usuario inativo. Procure o administrador.';
            return FALSE;
        }

        $hash = $usuario['senha_hash'];

        // Hash bcrypt normal
        if (strpos($hash, '$2y$') === 0 || strpos($hash, '$2a$') === 0)
        {
            if ( ! password_verify($senha, $hash))
            {
                return FALSE;
            }
        }
        // Compatibilidade com senha legada em texto puro: valida e re-hash
        elseif (hash_equals($hash, $senha))
        {
            $novo = password_hash($senha, PASSWORD_BCRYPT);
            $this->CI->db->query("UPDATE " . T_USUARIOS . " SET senha_hash = ? WHERE id = ?", array($novo, $usuario['id']));
        }
        else
        {
            return FALSE;
        }

        // Guarda apenas o necessario na sessao
        $this->usuario = array(
            'id'       => (int) $usuario['id'],
            'login'    => $usuario['login'],
            'nome'     => $usuario['nome'],
            'perfil'   => $usuario['perfil'],
            'setor_id' => $usuario['setor_id'] === NULL ? NULL : (int) $usuario['setor_id'],
            'setor'    => $usuario['setor_nome'],
        );

        $_SESSION['biostech_usuario'] = $this->usuario;

        session_regenerate_id(TRUE);

        $this->CI->db->query(
            "UPDATE " . T_USUARIOS . " SET ultimo_acesso = now() WHERE id = ?",
            array($usuario['id'])
        );

        $this->_auditar_login($usuario);

        return TRUE;
    }

    // ------------------------------------------------------------------

    private function _auditar_login($usuario)
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : NULL;

        $this->CI->db->query(
            "INSERT INTO " . T_AUDITORIA . " (usuario_id, usuario_login, acao, entidade, entidade_id, detalhe, ip)
             VALUES (?, ?, 'login', 'usuario', ?, ?::jsonb, ?)",
            array(
                (int) $usuario['id'],
                $usuario['login'],
                (string) $usuario['id'],
                json_encode(array('perfil' => $usuario['perfil'])),
                $ip,
            )
        );
    }

    // ------------------------------------------------------------------

    public function sair()
    {
        if ($this->logado())
        {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : NULL;
            $this->CI->db->query(
                "INSERT INTO " . T_AUDITORIA . " (usuario_id, usuario_login, acao, entidade, entidade_id, ip)
                 VALUES (?, ?, 'logout', 'usuario', ?, ?)",
                array($this->usuario['id'], $this->usuario['login'], (string) $this->usuario['id'], $ip)
            );
        }

        $this->usuario = NULL;
        unset($_SESSION['biostech_usuario']);
        @session_destroy();
    }
}
