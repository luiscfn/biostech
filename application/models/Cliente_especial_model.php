<?php
/**
 * Precos especiais por cliente (origem: PRODUTO_CLIENTES_ESPECIAIS_01).
 *
 * Um cliente e considerado "cliente especial" quando possui pelo menos uma
 * linha nesta tabela (o Access nao tem flag equivalente em CLIENTES).
 */
class Cliente_especial_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->tabela = T_CLIENTES_ESPECIAIS;
    }

    // ------------------------------------------------------------------

    private function _filtro($busca)
    {
        if ($busca === '')
        {
            return array('', array());
        }

        $sem_acento = "translate(lower(%s), 'áàãâäéèêëíìîïóòõôöúùûüç', 'aaaaaeeeeiiiiooooouuuuc')";
        $like       = '%' . strtolower($busca) . '%';

        return array(
            ' AND (' . sprintf($sem_acento, 'p.descricao') . " LIKE ? OR p.codigo LIKE ?)",
            array($like, $like),
        );
    }

    // ------------------------------------------------------------------

    public function listar($cliente_id, $busca = '', $limit = NULL, $offset = NULL)
    {
        list($where, $params) = $this->_filtro($busca);

        array_unshift($params, (int) $cliente_id);

        $sql = "SELECT e.produto_id, e.perc_lucro, e.valor_venda, e.updated_at,
                       p.codigo, p.descricao, p.unidade, p.valor_compra,
                       p.valor_venda AS valor_tabela, p.ativo
                  FROM {$this->tabela} e
                  JOIN " . T_PRODUTOS . " p ON p.id = e.produto_id
                 WHERE e.cliente_id = ?{$where}
                 ORDER BY p.descricao";

        if ($limit !== NULL)
        {
            $sql .= " LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
        }

        return $this->db->query($sql, $params)->result_array();
    }

    public function total($cliente_id, $busca = '')
    {
        list($where, $params) = $this->_filtro($busca);

        array_unshift($params, (int) $cliente_id);

        $sql = "SELECT count(*) AS total
                  FROM {$this->tabela} e
                  JOIN " . T_PRODUTOS . " p ON p.id = e.produto_id
                 WHERE e.cliente_id = ?{$where}";

        $row = $this->db->query($sql, $params)->row_array();

        return (int) $row['total'];
    }

    // ------------------------------------------------------------------

    public function quantidade($cliente_id)
    {
        $row = $this->db->query(
            "SELECT count(*) AS total FROM {$this->tabela} WHERE cliente_id = ?",
            array((int) $cliente_id)
        )->row_array();

        return (int) $row['total'];
    }

    public function tem($cliente_id)
    {
        return $this->quantidade($cliente_id) > 0;
    }

    // ------------------------------------------------------------------

    public function por_produto($cliente_id, $produto_id)
    {
        return $this->db->query(
            "SELECT * FROM {$this->tabela} WHERE cliente_id = ? AND produto_id = ?",
            array((int) $cliente_id, (int) $produto_id)
        )->row_array();
    }

    /**
     * Preco especial cadastrado para o produto (NULL quando nao ha).
     */
    public function valor($cliente_id, $produto_id)
    {
        $row = $this->por_produto($cliente_id, $produto_id);

        if (empty($row) || $row['valor_venda'] === NULL)
        {
            return NULL;
        }

        return (float) $row['valor_venda'];
    }

    // ------------------------------------------------------------------

    public function salvar($cliente_id, $produto_id, $perc_lucro, $valor_venda)
    {
        $this->db->query(
            "INSERT INTO {$this->tabela} (cliente_id, produto_id, perc_lucro, valor_venda)
             VALUES (?, ?, ?, ?)
             ON CONFLICT (cliente_id, produto_id)
             DO UPDATE SET perc_lucro = EXCLUDED.perc_lucro,
                           valor_venda = EXCLUDED.valor_venda,
                           updated_at = now()",
            array((int) $cliente_id, (int) $produto_id, $perc_lucro, $valor_venda)
        );

        return TRUE;
    }

    // ------------------------------------------------------------------

    public function excluir_produto($cliente_id, $produto_id)
    {
        $this->db->query(
            "DELETE FROM {$this->tabela} WHERE cliente_id = ? AND produto_id = ?",
            array((int) $cliente_id, (int) $produto_id)
        );

        return $this->db->affected_rows();
    }

    public function excluir_todos($cliente_id)
    {
        $this->db->query(
            "DELETE FROM {$this->tabela} WHERE cliente_id = ?",
            array((int) $cliente_id)
        );

        return $this->db->affected_rows();
    }

    /**
     * Insere todos os produtos ativos na tabela de precos do cliente.
     * O valor de venda e calculado a partir do custo e do percentual informado.
     * Registros ja existentes sao preservados (ON CONFLICT DO NOTHING).
     */
    public function incluir_todos($cliente_id, $perc_lucro = 15)
    {
        $perc = ($perc_lucro === NULL || $perc_lucro === '') ? 15 : (float) $perc_lucro;

        $this->db->query(
            "INSERT INTO {$this->tabela} (cliente_id, produto_id, perc_lucro, valor_venda)
             SELECT ?, p.id, ?, round(p.valor_compra * (1 + (? / 100.0)), 2)
               FROM " . T_PRODUTOS . " p
              WHERE p.ativo IS NOT FALSE
             ON CONFLICT (cliente_id, produto_id) DO NOTHING",
            array((int) $cliente_id, $perc, $perc)
        );

        return $this->db->affected_rows();
    }
}
