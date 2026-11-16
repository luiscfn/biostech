<?php
class Setor_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->tabela = T_SETORES;
        $this->pk     = 'id';
    }

    // ------------------------------------------------------------------

    public function listar($busca = '', $limit = NULL, $offset = NULL)
    {
        $where  = '';
        $params = array();

        if ($busca !== '')
        {
            $where  = "WHERE translate(lower(nome), 'áàãâäéèêëíìîïóòõôöúùûüç', 'aaaaaeeeeiiiiooooouuuuc') LIKE ?";
            $params[] = '%' . strtolower($busca) . '%';
        }

        $sql = "SELECT * FROM {$this->tabela} {$where} ORDER BY nome";

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

        $sql = "SELECT count(*) AS total FROM {$this->tabela}
                 WHERE translate(lower(nome), 'áàãâäéèêëíìîïóòõôöúùûüç', 'aaaaaeeeeiiiiooooouuuuc') LIKE ?";

        $row = $this->db->query($sql, array('%' . strtolower($busca) . '%'))->row_array();
        return (int) $row['total'];
    }

    // ------------------------------------------------------------------

    public function em_uso($id)
    {
        $row = $this->db->query(
            "SELECT count(*) AS total FROM " . T_USUARIOS . " WHERE setor_id = ?",
            array((int) $id)
        )->row_array();

        return ((int) $row['total']) > 0;
    }

    public function opcoes()
    {
        return $this->db->query("SELECT id, nome FROM {$this->tabela} WHERE ativo ORDER BY nome")->result_array();
    }
}
