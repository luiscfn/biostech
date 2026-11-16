<?php
class Produto_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->tabela = T_PRODUTOS;
        $this->pk     = 'id';
    }

    // ------------------------------------------------------------------

    private function _filtro($busca)
    {
        if ($busca === '')
        {
            return array('', array());
        }

        $sem_acento = "translate(lower(%s), 'áàãâäéèêëíìîïóòõôöúùûüç', 'aaaaaeeeeiiiiooooouuuuc')";
        $termo      = '%' . strtolower($busca) . '%';

        $onde = array(
            sprintf($sem_acento, 'descricao') . ' LIKE ?',
            sprintf($sem_acento, 'marca')     . ' LIKE ?',
            'codigo LIKE ?',
            'grupo = ?',
            'subgrupo = ?',
        );

        return array('WHERE (' . implode(' OR ', $onde) . ')', array($termo, $termo, $termo, strtoupper($busca), strtoupper($busca)));
    }

    public function listar($busca = '', $limit = NULL, $offset = NULL)
    {
        list($where, $params) = $this->_filtro($busca);

        $sql = "SELECT id, codigo, descricao, unidade, estoque_minimo, valor_compra, valor_venda,
                       grupo, subgrupo, estante, prateleira, marca, ativo
                  FROM {$this->tabela}
                  {$where}
                 ORDER BY descricao";

        if ($limit !== NULL)
        {
            $sql .= " LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
        }

        return $this->db->query($sql, $params)->result_array();
    }

    public function total($busca = '')
    {
        list($where, $params) = $this->_filtro($busca);

        $row = $this->db->query("SELECT count(*) AS total FROM {$this->tabela} {$where}", $params)->row_array();
        return (int) $row['total'];
    }

    // ------------------------------------------------------------------

    public function stats()
    {
        return $this->db->query(
            "SELECT count(*) AS total,
                    count(*) FILTER (WHERE ativo) AS ativos,
                    COALESCE(sum(valor_compra), 0) AS valor_total
               FROM {$this->tabela}"
        )->row_array();
    }
}
