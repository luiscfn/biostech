<?php
/**
 * Auditoria - trilha de alteracoes
 */
class Auditoria
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Registra um evento.
     *
     * @param string $acao        criar | alterar | excluir | login | logout
     * @param string $entidade    setor | usuario | cliente | produto ...
     * @param mixed  $entidade_id
     * @param array  $detalhe
     */
    public function registrar($acao, $entidade, $entidade_id = NULL, $detalhe = NULL)
    {
        $usuario = isset($_SESSION['biostech_usuario']) ? $_SESSION['biostech_usuario'] : array();
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : NULL;

        $sql = "INSERT INTO " . T_AUDITORIA . "
                    (usuario_id, usuario_login, acao, entidade, entidade_id, detalhe, ip)
                VALUES (?, ?, ?, ?, ?, ?::jsonb, ?)";

        $this->CI->db->query($sql, array(
            isset($usuario['id']) ? (int) $usuario['id'] : NULL,
            isset($usuario['login']) ? $usuario['login'] : NULL,
            $acao,
            $entidade,
            $entidade_id === NULL ? NULL : (string) $entidade_id,
            $detalhe === NULL ? NULL : json_encode($detalhe, JSON_UNESCAPED_UNICODE),
            $ip,
        ));
    }

    /**
     * Ultimos eventos.
     */
    public function ultimos($limite = 20)
    {
        $sql = "SELECT * FROM " . T_AUDITORIA . " ORDER BY created_at DESC, id DESC LIMIT ?";
        return $this->CI->db->query($sql, array((int) $limite))->result_array();
    }
}
