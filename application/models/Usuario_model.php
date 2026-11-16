<?php
class Usuario_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->tabela = T_USUARIOS;
        $this->pk     = 'id';
    }

    // ------------------------------------------------------------------

    public function listar($busca = '', $limit = NULL, $offset = NULL)
    {
        $where  = '';
        $params = array();

        if ($busca !== '')
        {
            $where = "WHERE translate(lower(u.login), 'áàãâäéèêëíìîïóòõôöúùûüç', 'aaaaaeeeeiiiiooooouuuuc') LIKE ?
                         OR translate(lower(u.nome),  'áàãâäéèêëíìîïóòõôöúùûüç', 'aaaaaeeeeiiiiooooouuuuc') LIKE ?";
            $params[] = '%' . strtolower($busca) . '%';
            $params[] = '%' . strtolower($busca) . '%';
        }

        $sql = "SELECT u.*, s.nome AS setor_nome
                  FROM {$this->tabela} u
                  LEFT JOIN " . T_SETORES . " s ON s.id = u.setor_id
                  {$where}
                 ORDER BY u.nome, u.login";

        if ($limit !== NULL)
        {
            $sql .= " LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
        }

        return $this->db->query($sql, $params)->result_array();
    }

    public function total($busca = '')
    {
        if ($busca === '')
        {
            return $this->contar();
        }

        $sql = "SELECT count(*) AS total FROM {$this->tabela} u
                 WHERE translate(lower(u.login), 'áàãâäéèêëíìîïóòõôöúùûüç', 'aaaaaeeeeiiiiooooouuuuc') LIKE ?
                    OR translate(lower(u.nome),  'áàãâäéèêëíìîïóòõôöúùûüç', 'aaaaaeeeeiiiiooooouuuuc') LIKE ?";

        $row = $this->db->query($sql, array('%' . strtolower($busca) . '%', '%' . strtolower($busca) . '%'))->row_array();
        return (int) $row['total'];
    }

    // ------------------------------------------------------------------

    public function por_login($login)
    {
        return $this->db->query(
            "SELECT * FROM {$this->tabela} WHERE upper(login) = ?",
            array(strtoupper(trim($login)))
        )->row_array();
    }

    public function login_existe($login, $ignorar_id = NULL)
    {
        $sql    = "SELECT count(*) AS total FROM {$this->tabela} WHERE upper(login) = ?";
        $params = array(strtoupper(trim($login)));

        if ($ignorar_id !== NULL)
        {
            $sql .= " AND id <> ?";
            $params[] = (int) $ignorar_id;
        }

        $row = $this->db->query($sql, $params)->row_array();
        return ((int) $row['total']) > 0;
    }

    // ------------------------------------------------------------------

    public function criar($dados)
    {
        $sql = "INSERT INTO {$this->tabela}
                    (login, nome, senha_hash, perfil, setor_id, acesso_almoxarifado, ativo, senha_provisoria)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->query($sql, array(
            strtoupper(trim($dados['login'])),
            $dados['nome'],
            password_hash($dados['senha'], PASSWORD_BCRYPT),
            $dados['perfil'],
            $dados['setor_id'],
            $dados['acesso_almoxarifado'] ? 'true' : 'false',
            $dados['ativo'] ? 'true' : 'false',
            'true',
        ));
    }

    public function alterar($id, $dados)
    {
        $sql = "UPDATE {$this->tabela}
                   SET nome = ?, login = ?, perfil = ?, setor_id = ?,
                       acesso_almoxarifado = ?, ativo = ?, updated_at = now()
                 WHERE id = ?";

        $this->db->query($sql, array(
            $dados['nome'],
            strtoupper(trim($dados['login'])),
            $dados['perfil'],
            $dados['setor_id'],
            $dados['acesso_almoxarifado'] ? 'true' : 'false',
            $dados['ativo'] ? 'true' : 'false',
            (int) $id,
        ));
    }

    public function alterar_senha($id, $senha)
    {
        $this->db->query(
            "UPDATE {$this->tabela} SET senha_hash = ?, senha_provisoria = false, updated_at = now() WHERE id = ?",
            array(password_hash($senha, PASSWORD_BCRYPT), (int) $id)
        );
    }

    public function alternar_ativo($id)
    {
        $this->db->query(
            "UPDATE {$this->tabela} SET ativo = NOT ativo, updated_at = now() WHERE id = ?",
            array((int) $id)
        );
    }

    public function por_setor($setor_id)
    {
        $sql = "SELECT u.id, u.nome, u.login, u.perfil
                  FROM {$this->tabela} u
                 WHERE u.setor_id = ? AND u.ativo
                 ORDER BY u.nome";

        return $this->db->query($sql, array((int) $setor_id))->result_array();
    }
}
