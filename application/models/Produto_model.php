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

    private function _filtro($busca, $grupo = '', $subgrupo = '', $situacao = '')
    {
        $onde   = array();
        $params = array();

        if ($busca !== '')
        {
            $sem_acento = "translate(lower(%s), 'áàãâäéèêëíìîïóòõôöúùûüç', 'aaaaaeeeeiiiiooooouuuuc')";
            $termo      = '%' . mb_strtolower($busca, 'UTF-8') . '%';

            $onde[] = '(' . sprintf($sem_acento, 'p.descricao') . ' LIKE ?'
                    . ' OR ' . sprintf($sem_acento, 'p.marca') . ' LIKE ?'
                    . ' OR lower(p.codigo) LIKE ?'
                    . ' OR p.grupo = ?'
                    . ' OR p.subgrupo = ?)';

            $params[] = $termo;
            $params[] = $termo;
            $params[] = $termo;
            $params[] = mb_strtoupper($busca, 'UTF-8');
            $params[] = mb_strtoupper($busca, 'UTF-8');
        }

        if ($grupo !== '')
        {
            $onde[]   = 'p.grupo = ?';
            $params[] = $grupo;
        }

        if ($subgrupo !== '')
        {
            $onde[]   = 'p.subgrupo = ?';
            $params[] = $subgrupo;
        }

        if ($situacao === 'ativos')
        {
            $onde[] = 'p.ativo';
        }
        elseif ($situacao === 'inativos')
        {
            $onde[] = 'NOT p.ativo';
        }

        return array($onde ? 'WHERE ' . implode(' AND ', $onde) : '', $params);
    }

    public function listar($busca = '', $grupo = '', $subgrupo = '', $situacao = '', $limit = NULL, $offset = NULL)
    {
        list($where, $params) = $this->_filtro($busca, $grupo, $subgrupo, $situacao);

        $sql = "SELECT p.id, p.codigo, p.descricao, p.unidade,
                        p.grupo, g.nome AS grupo_nome,
                        p.subgrupo, sg.nome AS subgrupo_nome,
                        p.valor_compra, p.valor_venda,
                        CASE WHEN p.valor_compra > 0
                             THEN round(((p.valor_venda - p.valor_compra) / p.valor_compra) * 100, 2)
                        END AS perc_lucro,
                        COALESCE(s.quantidade, 0) AS saldo,
                        p.marca, p.ativo
                   FROM {$this->tabela} p
                   LEFT JOIN " . T_ESTOQUE_GRUPOS . " g ON g.codigo = p.grupo
                   LEFT JOIN " . T_ESTOQUE_SUBGRUPOS . " sg
                          ON sg.grupo_codigo = p.grupo AND sg.codigo = p.subgrupo
                   LEFT JOIN " . T_ESTOQUE_SALDOS . " s ON s.produto_id = p.id
                   {$where}
                  ORDER BY p.descricao";

        if ($limit !== NULL)
        {
            $sql .= " LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
        }

        return $this->db->query($sql, $params)->result_array();
    }

    public function total($busca = '', $grupo = '', $subgrupo = '', $situacao = '')
    {
        list($where, $params) = $this->_filtro($busca, $grupo, $subgrupo, $situacao);

        $row = $this->db->query("SELECT count(*) AS total FROM {$this->tabela} p {$where}", $params)->row_array();
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
    // Cadastro (novo / atualizar)
    // ------------------------------------------------------------------

    private $campos_cadastro = array(
        'codigo', 'descricao', 'unidade', 'estoque_minimo', 'valor_compra',
        'valor_venda', 'percentual_lucro', 'valor_ultima_compra', 'grupo',
        'subgrupo', 'item', 'estante', 'prateleira', 'marca', 'cor',
        'referencia', 'tamanho', 'capacidade', 'aplicacao', 'situacao', 'ativo',
    );

    public function proximo_codigo()
    {
        return $this->proximo_id();
    }

    public function ultimo_cadastrado()
    {
        $row = $this->db->query(
            "SELECT id FROM {$this->tabela} ORDER BY id DESC LIMIT 1"
        )->row_array();

        return empty($row) ? NULL : (int) $row['id'];
    }

    /**
     * Ids anterior/proximo na mesma ordem da listagem (descricao, id).
     */
    public function vizinhos($id)
    {
        $id   = (int) $id;
        $atual = $this->db->query(
            "SELECT descricao FROM {$this->tabela} WHERE id = ?", array($id)
        )->row_array();

        if (empty($atual))
        {
            return array('primeiro' => NULL, 'anterior' => NULL, 'proximo' => NULL, 'ultimo' => NULL);
        }

        $descricao = $atual['descricao'];

        $anterior = $this->db->query(
            "SELECT id FROM {$this->tabela}
              WHERE descricao < ? OR (descricao = ? AND id < ?)
              ORDER BY descricao DESC, id DESC LIMIT 1",
            array($descricao, $descricao, $id)
        )->row_array();

        $proximo = $this->db->query(
            "SELECT id FROM {$this->tabela}
              WHERE descricao > ? OR (descricao = ? AND id > ?)
              ORDER BY descricao, id LIMIT 1",
            array($descricao, $descricao, $id)
        )->row_array();

        $primeiro = $this->db->query(
            "SELECT id FROM {$this->tabela} ORDER BY descricao, id LIMIT 1"
        )->row_array();

        $ultimo = $this->db->query(
            "SELECT id FROM {$this->tabela} ORDER BY descricao DESC, id DESC LIMIT 1"
        )->row_array();

        return array(
            'primeiro' => empty($primeiro) ? NULL : (int) $primeiro['id'],
            'anterior' => empty($anterior) ? NULL : (int) $anterior['id'],
            'proximo'  => empty($proximo) ? NULL : (int) $proximo['id'],
            'ultimo'   => empty($ultimo) ? NULL : (int) $ultimo['id'],
        );
    }

    public function salvar_cadastro($dados, $id = NULL)
    {
        $limpo = array();

        foreach ($this->campos_cadastro as $campo)
        {
            if (array_key_exists($campo, $dados))
            {
                $limpo[$campo] = $dados[$campo];
            }
        }

        if ($id === NULL)
        {
            $limpo['id'] = isset($dados['id']) ? (int) $dados['id'] : $this->proximo_id();

            $this->inserir($limpo);
            return (int) $limpo['id'];
        }

        $this->atualizar((int) $id, $limpo);
        return (int) $id;
    }

    /**
     * Recalcula o preco de venda a partir do custo e do percentual informado.
     * $percentual NULL usa o percentual_lucro gravado no produto.
     */
    public function atualizar_precos($percentual = NULL, $grupo = '')
    {
        $onde   = array();
        $params = array();

        if ($grupo !== '')
        {
            $onde[]   = 'p.grupo = ?';
            $params[] = $grupo;
        }

        if ($percentual !== NULL)
        {
            $valor = round((float) $percentual / 100, 6);
        }
        else
        {
            $valor = 'COALESCE(p.percentual_lucro, 0) / 100.0';
        }

        $sql = "UPDATE {$this->tabela} p
                   SET valor_venda = round(p.valor_compra * (1 + " . $valor . "), 2),
                       updated_at = now()";

        if ($onde)
        {
            $sql .= ' WHERE ' . implode(' AND ', $onde);
        }

        $this->db->query($sql, $params);

        return $this->db->affected_rows();
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
