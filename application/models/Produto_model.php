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

    private function _filtro_grupos(array $grupos, $subgrupo = '', $busca = '')
    {
        $codigos = array();

        foreach ($grupos as $grupo)
        {
            $codigo = ltrim(trim((string) $grupo), '0');

            if ($codigo === '')
            {
                continue;
            }

            $codigos[] = $this->db->escape($codigo);
        }

        if ( ! $codigos)
        {
            $codigos[] = "''";
        }

        $onde   = array("ltrim(p.grupo, '0') IN (" . implode(', ', $codigos) . ')');
        $params = array();

        if ($subgrupo !== '')
        {
            $onde[]   = "ltrim(p.subgrupo, '0') = ?";
            $params[] = ltrim($subgrupo, '0');
        }

        if ($busca !== '')
        {
            $sem_acento = "translate(lower(%s), 'áàãâäéèêëíìîïóòõôöúùûüç', 'aaaaaeeeeiiiiooooouuuuc')";
            $termo      = '%' . strtolower($busca) . '%';

            $onde[] = '(' . sprintf($sem_acento, 'p.descricao') . ' LIKE ?'
                    . ' OR ' . sprintf($sem_acento, 'p.marca') . ' LIKE ?'
                    . ' OR p.codigo LIKE ?)';

            $params[] = $termo;
            $params[] = $termo;
            $params[] = $termo;
        }

        return array('WHERE ' . implode(' AND ', $onde), $params);
    }

    public function listar_por_grupos(array $grupos, $subgrupo = '', $busca = '', $limit = NULL, $offset = NULL)
    {
        list($where, $params) = $this->_filtro_grupos($grupos, $subgrupo, $busca);

        $sql = "SELECT p.id, p.codigo, p.descricao, p.unidade, p.marca,
                       p.grupo, p.subgrupo, p.valor_compra, p.valor_venda, p.ativo,
                       COALESCE(s.quantidade, 0) AS saldo
                  FROM {$this->tabela} p
                  LEFT JOIN " . T_ESTOQUE_SALDOS . " s ON s.produto_id = p.id
                  {$where}
                 ORDER BY p.descricao";

        if ($limit !== NULL)
        {
            $sql .= " LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
        }

        return $this->db->query($sql, $params)->result_array();
    }

    public function total_por_grupos(array $grupos, $subgrupo = '', $busca = '')
    {
        list($where, $params) = $this->_filtro_grupos($grupos, $subgrupo, $busca);

        $row = $this->db->query("SELECT count(*) AS total FROM {$this->tabela} p {$where}", $params)->row_array();
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
