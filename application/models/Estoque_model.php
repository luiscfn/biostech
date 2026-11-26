<?php
/**
 * Estoque_model - saldos, movimentos, entradas, saidas, ajustes e catalogos.
 *
 * Regras:
 *  - saldo nunca negativo (D-04);
 *  - movimentos imutaveis: correcao por estorno, nao por UPDATE;
 *  - entrada atualiza o custo de compra do produto;
 *  - saida soma em quantidade_consumida;
 *  - ajuste de inventario grava cabecalho + item + movimento.
 */
class Estoque_model extends MY_Model
{
    /** tipos de movimento */
    private static $tipos = array('entrada', 'saida', 'ajuste');

    public function __construct()
    {
        parent::__construct();
        $this->tabela = T_ESTOQUE_MOVIMENTOS;
        $this->pk     = 'id';
    }

    // ==================================================================
    // Saldos
    // ==================================================================

    private function _from_saldos()
    {
        return "FROM " . T_PRODUTOS . " p
                 LEFT JOIN " . T_ESTOQUE_SALDOS . " s ON s.produto_id = p.id
                 LEFT JOIN " . T_ESTOQUE_GRUPOS . " g ON g.codigo = p.grupo
                 LEFT JOIN " . T_ESTOQUE_SUBGRUPOS . " sg
                        ON sg.grupo_codigo = p.grupo AND sg.codigo = p.subgrupo
                 LEFT JOIN " . T_ESTOQUE_ESTANTES . " e ON e.codigo = p.estante
                 LEFT JOIN " . T_ESTOQUE_PRATELEIRAS . " pr
                        ON pr.estante_codigo = p.estante AND pr.codigo = p.prateleira";
    }

    private function _filtro_saldos($filtros)
    {
        $onde   = array();
        $params = array();

        $busca = isset($filtros['busca']) ? trim((string) $filtros['busca']) : '';

        if ($busca !== '')
        {
            $sem_acento = "translate(lower(%s), 'áàãâäéèêëíìîïóòõôöúùûüç', 'aaaaaeeeeiiiiooooouuuuc')";
            $termo      = '%' . mb_strtolower($busca, 'UTF-8') . '%';

            $onde[] = '(' . sprintf($sem_acento, 'p.descricao') . ' LIKE ?'
                    . ' OR ' . sprintf($sem_acento, 'p.marca') . ' LIKE ?'
                    . ' OR lower(p.codigo) LIKE ?)';

            $params[] = $termo;
            $params[] = $termo;
            $params[] = $termo;
        }

        if ( ! empty($filtros['grupo']))
        {
            $onde[]   = 'p.grupo = ?';
            $params[] = $filtros['grupo'];
        }

        if ( ! empty($filtros['subgrupo']))
        {
            $onde[]   = 'p.subgrupo = ?';
            $params[] = $filtros['subgrupo'];
        }

        if ( ! empty($filtros['estante']))
        {
            $onde[]   = 'p.estante = ?';
            $params[] = (int) $filtros['estante'];
        }

        $situacao = isset($filtros['situacao']) ? $filtros['situacao'] : '';

        if ($situacao === 'ativos')
        {
            $onde[] = 'p.ativo';
        }
        elseif ($situacao === 'inativos')
        {
            $onde[] = 'NOT p.ativo';
        }
        elseif ($situacao === 'abaixo')
        {
            $onde[] = 'p.ativo AND COALESCE(s.quantidade, 0) <= p.estoque_minimo';
        }
        elseif ($situacao === 'zerado')
        {
            $onde[] = 'COALESCE(s.quantidade, 0) = 0';
        }
        elseif ($situacao === 'saldo')
        {
            $onde[] = 'COALESCE(s.quantidade, 0) > 0';
        }

        return array(
            $onde ? 'WHERE ' . implode(' AND ', $onde) : '',
            $params,
        );
    }

    public function listar_saldos($filtros = array(), $limit = NULL, $offset = NULL, $ordem = 'produto')
    {
        list($where, $params) = $this->_filtro_saldos($filtros);

        $ordens = array(
            'produto'  => 'p.descricao',
            'saldo'    => 'COALESCE(s.quantidade, 0), p.descricao',
            'menor'    => 'COALESCE(s.quantidade, 0) ASC, p.descricao',
            'maior'    => 'COALESCE(s.quantidade, 0) DESC, p.descricao',
            'valor'    => '(COALESCE(s.quantidade, 0) * p.valor_compra) DESC, p.descricao',
            'codigo'   => 'p.codigo NULLS LAST, p.descricao',
        );

        $order = isset($ordens[$ordem]) ? $ordens[$ordem] : $ordens['produto'];

        $sql = "SELECT p.id, p.codigo, p.descricao, p.unidade, p.grupo, g.nome AS grupo_nome,
                       p.subgrupo, sg.nome AS subgrupo_nome,
                       p.estante, e.nome AS estante_nome,
                       p.prateleira, pr.nome AS prateleira_nome,
                       p.estoque_minimo, p.valor_compra, p.valor_venda,
                       p.marca, p.ativo,
                       COALESCE(s.quantidade, 0) AS saldo,
                       COALESCE(s.quantidade_consumida, 0) AS consumida
                  " . $this->_from_saldos() . "
                  {$where}
                 ORDER BY {$order}";

        if ($limit !== NULL)
        {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        return $this->db->query($sql, $params)->result_array();
    }

    public function total_saldos($filtros = array())
    {
        list($where, $params) = $this->_filtro_saldos($filtros);

        $sql = "SELECT count(*) AS total " . $this->_from_saldos() . " {$where}";
        $row = $this->db->query($sql, $params)->row_array();

        return (int) $row['total'];
    }

    public function stats()
    {
        $sql = "SELECT count(*) AS produtos,
                       count(*) FILTER (WHERE p.ativo) AS ativos,
                       count(*) FILTER (WHERE p.ativo AND COALESCE(s.quantidade, 0) <= p.estoque_minimo) AS abaixo_minimo,
                       count(*) FILTER (WHERE COALESCE(s.quantidade, 0) = 0) AS zerados,
                       COALESCE(sum(COALESCE(s.quantidade, 0) * p.valor_compra), 0) AS valor_estoque
                  FROM " . T_PRODUTOS . " p
                  LEFT JOIN " . T_ESTOQUE_SALDOS . " s ON s.produto_id = p.id";

        $row = $this->db->query($sql)->row_array();

        $mov = $this->db->query(
            "SELECT count(*) AS movimentos
               FROM " . T_ESTOQUE_MOVIMENTOS . "
              WHERE created_at >= date_trunc('month', now())"
        )->row_array();

        $row['movimentos_mes'] = (int) $mov['movimentos'];

        return $row;
    }

    // ==================================================================
    // Produto / ficha de estoque
    // ==================================================================

    public function por_produto($id)
    {
        $sql = "SELECT p.*, COALESCE(s.quantidade, 0) AS saldo,
                       COALESCE(s.quantidade_consumida, 0) AS consumida,
                       g.nome AS grupo_nome, sg.nome AS subgrupo_nome,
                       e.nome AS estante_nome, pr.nome AS prateleira_nome
                  FROM " . T_PRODUTOS . " p
                  LEFT JOIN " . T_ESTOQUE_SALDOS . " s ON s.produto_id = p.id
                  LEFT JOIN " . T_ESTOQUE_GRUPOS . " g ON g.codigo = p.grupo
                  LEFT JOIN " . T_ESTOQUE_SUBGRUPOS . " sg
                         ON sg.grupo_codigo = p.grupo AND sg.codigo = p.subgrupo
                  LEFT JOIN " . T_ESTOQUE_ESTANTES . " e ON e.codigo = p.estante
                  LEFT JOIN " . T_ESTOQUE_PRATELEIRAS . " pr
                         ON pr.estante_codigo = p.estante AND pr.codigo = p.prateleira
                 WHERE p.id = ?";

        return $this->db->query($sql, array((int) $id))->row_array();
    }

    public function movimentos_produto($id, $limit = 50)
    {
        return $this->db->query(
            "SELECT m.*, f.razao_social AS fornecedor_nome
               FROM " . T_ESTOQUE_MOVIMENTOS . " m
               LEFT JOIN " . T_FORNECEDORES . " f ON f.codigo = m.fornecedor_id
              WHERE m.produto_id = ?
              ORDER BY m.created_at DESC, m.id DESC
              LIMIT " . (int) $limit,
            array((int) $id)
        )->result_array();
    }

    // ==================================================================
    // Movimentos
    // ==================================================================

    private function _filtro_movimentos($filtros)
    {
        $onde   = array();
        $params = array();

        $busca = isset($filtros['busca']) ? trim((string) $filtros['busca']) : '';

        if ($busca !== '')
        {
            $sem_acento = "translate(lower(%s), 'áàãâäéèêëíìîïóòõôöúùûüç', 'aaaaaeeeeiiiiooooouuuuc')";
            $termo      = '%' . mb_strtolower($busca, 'UTF-8') . '%';

            $onde[] = '(' . sprintf($sem_acento, 'p.descricao') . ' LIKE ? OR lower(p.codigo) LIKE ?)';
            $params[] = $termo;
            $params[] = $termo;
        }

        if ( ! empty($filtros['tipo']) && in_array($filtros['tipo'], self::$tipos, TRUE))
        {
            $onde[]   = 'm.tipo = ?';
            $params[] = $filtros['tipo'];
        }

        if ( ! empty($filtros['origem']))
        {
            $onde[]   = 'm.origem = ?';
            $params[] = $filtros['origem'];
        }

        if ( ! empty($filtros['produto_id']))
        {
            $onde[]   = 'm.produto_id = ?';
            $params[] = (int) $filtros['produto_id'];
        }

        if ( ! empty($filtros['chamado']))
        {
            $onde[]   = 'm.chamado_numero = ?';
            $params[] = $filtros['chamado'];
        }

        if ( ! empty($filtros['data_ini']))
        {
            $onde[]   = 'm.created_at::date >= ?';
            $params[] = $filtros['data_ini'];
        }

        if ( ! empty($filtros['data_fim']))
        {
            $onde[]   = 'm.created_at::date <= ?';
            $params[] = $filtros['data_fim'];
        }

        return array(
            $onde ? 'WHERE ' . implode(' AND ', $onde) : '',
            $params,
        );
    }

    public function listar_movimentos($filtros = array(), $limit = NULL, $offset = NULL)
    {
        list($where, $params) = $this->_filtro_movimentos($filtros);

        $sql = "SELECT m.*, p.descricao AS produto, p.codigo AS produto_codigo,
                       p.unidade, f.razao_social AS fornecedor_nome,
                       s.nome AS setor_nome
                  FROM " . T_ESTOQUE_MOVIMENTOS . " m
                  JOIN " . T_PRODUTOS . " p ON p.id = m.produto_id
                  LEFT JOIN " . T_FORNECEDORES . " f ON f.codigo = m.fornecedor_id
                  LEFT JOIN " . T_SETORES . " s ON s.id = m.setor_id
                  {$where}
                 ORDER BY m.created_at DESC, m.id DESC";

        if ($limit !== NULL)
        {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        return $this->db->query($sql, $params)->result_array();
    }

    public function total_movimentos($filtros = array())
    {
        list($where, $params) = $this->_filtro_movimentos($filtros);

        $sql = "SELECT count(*) AS total
                  FROM " . T_ESTOQUE_MOVIMENTOS . " m
                  JOIN " . T_PRODUTOS . " p ON p.id = m.produto_id
                  {$where}";

        $row = $this->db->query($sql, $params)->row_array();

        return (int) $row['total'];
    }

    public function movimento($id)
    {
        return $this->db->query(
            "SELECT m.*, p.descricao AS produto
               FROM " . T_ESTOQUE_MOVIMENTOS . " m
               JOIN " . T_PRODUTOS . " p ON p.id = m.produto_id
              WHERE m.id = ?",
            array((int) $id)
        )->row_array();
    }

    // ==================================================================
    // Entradas / saidas
    // ==================================================================

    public function entrada($itens, $cabecalho, $usuario, $transacao = TRUE)
    {
        return $this->_movimentar('entrada', $itens, $cabecalho, $usuario, $transacao);
    }

    public function saida($itens, $cabecalho, $usuario, $transacao = TRUE)
    {
        return $this->_movimentar('saida', $itens, $cabecalho, $usuario, $transacao);
    }

    private function _movimentar($tipo, $itens, $cabecalho, $usuario, $transacao = TRUE)
    {
        if ( ! in_array($tipo, array('entrada', 'saida'), TRUE))
        {
            return array('ok' => FALSE, 'erro' => 'Tipo de movimento invalido.');
        }

        $itens = $this->_normalizar_itens($itens);

        if (empty($itens))
        {
            return array('ok' => FALSE, 'erro' => 'Informe pelo menos um produto.');
        }

        $lote     = $this->_proximo_lote();
        $documento = isset($cabecalho['documento']) ? trim((string) $cabecalho['documento']) : NULL;
        $fornecedor = ! empty($cabecalho['fornecedor_id']) ? (int) $cabecalho['fornecedor_id'] : NULL;
        $setor      = ! empty($cabecalho['setor_id']) ? (int) $cabecalho['setor_id'] : NULL;
        $funcionario = ! empty($cabecalho['funcionario_id']) ? (int) $cabecalho['funcionario_id'] : NULL;
        $motivo     = isset($cabecalho['motivo']) ? trim((string) $cabecalho['motivo']) : NULL;

        $origem = isset($cabecalho['origem']) ? strtolower(trim((string) $cabecalho['origem'])) : 'manual';

        if ( ! in_array($origem, array('manual', 'chamado', 'compra', 'inventario'), TRUE))
        {
            $origem = 'manual';
        }

        $origem_id = isset($cabecalho['origem_id']) && trim((string) $cabecalho['origem_id']) !== ''
            ? trim((string) $cabecalho['origem_id'])
            : NULL;

        if ($transacao)
        {
            $this->db->trans_start();
        }

        foreach ($itens as $item)
        {
            $produto = $this->db->query(
                "SELECT id, descricao, valor_compra FROM " . T_PRODUTOS . " WHERE id = ?",
                array($item['produto_id'])
            )->row_array();

            if (empty($produto))
            {
                if ($transacao)
                {
                    $this->db->trans_rollback();
                }

                return array('ok' => FALSE, 'erro' => 'Produto ' . $item['produto_id'] . ' nao encontrado.');
            }

            $this->db->query(
                "INSERT INTO " . T_ESTOQUE_SALDOS . " (produto_id, quantidade, quantidade_consumida)
                 VALUES (?, 0, 0)
                 ON CONFLICT (produto_id) DO NOTHING",
                array($item['produto_id'])
            );

            $saldo = $this->db->query(
                "SELECT quantidade, quantidade_consumida
                   FROM " . T_ESTOQUE_SALDOS . "
                  WHERE produto_id = ?
                  FOR UPDATE",
                array($item['produto_id'])
            )->row_array();

            $anterior  = (int) $saldo['quantidade'];
            $consumida = (int) $saldo['quantidade_consumida'];
            $qtd       = $item['quantidade'];

            if ($tipo === 'entrada')
            {
                $posterior  = $anterior + $qtd;
                $consumida  = $consumida;
            }
            else
            {
                $posterior = $anterior - $qtd;

                if ($posterior < 0)
                {
                    if ($transacao)
                    {
                        $this->db->trans_rollback();
                    }

                    return array(
                        'ok'   => FALSE,
                        'erro' => $produto['descricao'] . ': saldo insuficiente (disponivel ' . $anterior . ', solicitado ' . $qtd . ').',
                    );
                }

                $consumida += $qtd;
            }

            $this->_inserir_movimento(array(
                'produto_id'      => $item['produto_id'],
                'chamado_numero'  => NULL,
                'tipo'            => $tipo,
                'quantidade'      => $qtd,
                'saldo_anterior'  => $anterior,
                'saldo_posterior' => $posterior,
                'usuario'         => $usuario['login'],
                'observacao'      => isset($cabecalho['observacao']) ? $cabecalho['observacao'] : NULL,
                'lote'            => $lote,
                'documento'       => $documento,
                'fornecedor_id'   => $tipo === 'entrada' ? $fornecedor : NULL,
                'setor_id'        => $setor,
                'funcionario_id'  => $funcionario,
                'custo_unitario'  => $item['custo_unitario'],
                'motivo'          => $motivo,
                'origem'          => $origem,
                'origem_id'       => $origem_id,
            ));

            $this->db->query(
                "UPDATE " . T_ESTOQUE_SALDOS . "
                    SET quantidade = ?, quantidade_consumida = ?, updated_at = now()
                  WHERE produto_id = ?",
                array($posterior, $consumida, $item['produto_id'])
            );

            if ($tipo === 'entrada' && $item['custo_unitario'] !== NULL && $item['custo_unitario'] > 0)
            {
                $this->db->query(
                    "UPDATE " . T_PRODUTOS . " SET valor_compra = ?, updated_at = now() WHERE id = ?",
                    array($item['custo_unitario'], $item['produto_id'])
                );
            }
        }

        if ($transacao)
        {
            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE)
            {
                return array('ok' => FALSE, 'erro' => 'Falha ao gravar o movimento.');
            }
        }

        return array('ok' => TRUE, 'erro' => NULL, 'lote' => $lote, 'itens' => count($itens));
    }

    // ==================================================================
    // Ajuste / inventario
    // ==================================================================

    public function ajustar($produto_id, $quantidade_contada, $motivo, $usuario, $data = NULL)
    {
        $produto_id = (int) $produto_id;
        $contada    = (int) $quantidade_contada;

        if ($contada < 0)
        {
            return array('ok' => FALSE, 'erro' => 'A quantidade contada nao pode ser negativa.');
        }

        $produto = $this->db->query(
            "SELECT id, descricao FROM " . T_PRODUTOS . " WHERE id = ?",
            array($produto_id)
        )->row_array();

        if (empty($produto))
        {
            return array('ok' => FALSE, 'erro' => 'Produto nao encontrado.');
        }

        $this->db->trans_start();

        $this->db->query(
            "INSERT INTO " . T_ESTOQUE_SALDOS . " (produto_id, quantidade, quantidade_consumida)
             VALUES (?, 0, 0)
             ON CONFLICT (produto_id) DO NOTHING",
            array($produto_id)
        );

        $saldo = $this->db->query(
            "SELECT quantidade, quantidade_consumida
               FROM " . T_ESTOQUE_SALDOS . "
              WHERE produto_id = ?
              FOR UPDATE",
            array($produto_id)
        )->row_array();

        $anterior  = (int) $saldo['quantidade'];
        $consumida = (int) $saldo['quantidade_consumida'];
        $diferenca = $contada - $anterior;

        $data = $data ? $data : date('Y-m-d');

        $this->db->query(
            "INSERT INTO " . T_ESTOQUE_INVENTARIOS . " (data, usuario, status, observacao, fechado_at)
             VALUES (?, ?, 'fechado', ?, now())",
            array($data, $usuario['login'], $motivo)
        );

        $inventario_id = (int) $this->db->query(
            "SELECT currval(pg_get_serial_sequence('" . T_ESTOQUE_INVENTARIOS . "', 'id')) AS id"
        )->row_array()['id'];

        $this->db->query(
            "INSERT INTO " . T_ESTOQUE_INVENTARIO_ITENS . "
                (inventario_id, produto_id, saldo_sistema, quantidade_contada, diferenca)
             VALUES (?, ?, ?, ?, ?)",
            array($inventario_id, $produto_id, $anterior, $contada, $diferenca)
        );

        if ($diferenca !== 0)
        {
            if ($diferenca < 0)
            {
                $consumida += abs($diferenca);
            }
            else
            {
                $consumida = max(0, $consumida - $diferenca);
            }

            $this->_inserir_movimento(array(
                'produto_id'      => $produto_id,
                'chamado_numero'  => NULL,
                'tipo'            => 'ajuste',
                'quantidade'      => abs($diferenca),
                'saldo_anterior'  => $anterior,
                'saldo_posterior' => $contada,
                'usuario'         => $usuario['login'],
                'observacao'      => $motivo,
                'lote'            => $this->_proximo_lote(),
                'documento'       => NULL,
                'fornecedor_id'   => NULL,
                'setor_id'        => NULL,
                'funcionario_id'  => NULL,
                'custo_unitario'  => NULL,
                'motivo'          => $motivo ? $motivo : 'Ajuste de inventario',
                'origem'          => 'inventario',
                'inventario_id'   => $inventario_id,
            ));
        }

        $this->db->query(
            "UPDATE " . T_ESTOQUE_SALDOS . "
                SET quantidade = ?, quantidade_consumida = ?, updated_at = now()
              WHERE produto_id = ?",
            array($contada, $consumida, $produto_id)
        );

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return array('ok' => FALSE, 'erro' => 'Falha ao gravar o ajuste.');
        }

        return array(
            'ok'            => TRUE,
            'erro'          => NULL,
            'inventario_id' => $inventario_id,
            'diferenca'     => $diferenca,
            'saldo'         => $contada,
        );
    }

    public function listar_inventarios($limit = NULL, $offset = NULL)
    {
        $sql = "SELECT i.*, count(it.produto_id) AS itens,
                       COALESCE(sum(abs(it.diferenca)), 0) AS diferenca_total
                  FROM " . T_ESTOQUE_INVENTARIOS . " i
                  LEFT JOIN " . T_ESTOQUE_INVENTARIO_ITENS . " it ON it.inventario_id = i.id
                 GROUP BY i.id
                 ORDER BY i.data DESC, i.id DESC";

        if ($limit !== NULL)
        {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        return $this->db->query($sql)->result_array();
    }

    public function inventarios_produto($produto_id, $limit = 20)
    {
        return $this->db->query(
            "SELECT it.*, i.data, i.usuario, i.observacao
               FROM " . T_ESTOQUE_INVENTARIO_ITENS . " it
               JOIN " . T_ESTOQUE_INVENTARIOS . " i ON i.id = it.inventario_id
              WHERE it.produto_id = ?
              ORDER BY i.data DESC, i.id DESC
              LIMIT " . (int) $limit,
            array((int) $produto_id)
        )->result_array();
    }

    // ==================================================================
    // Apoio (setores, funcionarios)
    // ==================================================================

    public function setores()
    {
        return $this->db->query(
            "SELECT id, nome FROM " . T_SETORES . " WHERE ativo ORDER BY nome"
        )->result_array();
    }

    public function funcionarios()
    {
        return $this->db->query(
            "SELECT DISTINCT funcionario_id AS id, nome
               FROM " . T_USUARIOS . "
              WHERE ativo AND funcionario_id IS NOT NULL
              ORDER BY nome"
        )->result_array();
    }

    public function estornar($movimento_id, $motivo, $usuario, $transacao = TRUE)
    {
        $movimento_id = (int) $movimento_id;

        if ($transacao)
        {
            $this->db->trans_start();
        }

        $m = $this->db->query(
            "SELECT * FROM " . T_ESTOQUE_MOVIMENTOS . " WHERE id = ? FOR UPDATE",
            array($movimento_id)
        )->row_array();

        if (empty($m))
        {
            if ($transacao)
            {
                $this->db->trans_rollback();
            }

            return array('ok' => FALSE, 'erro' => 'Movimento nao encontrado.');
        }

        if (bd_bool($m['estornado']))
        {
            if ($transacao)
            {
                $this->db->trans_rollback();
            }

            return array('ok' => FALSE, 'erro' => 'Este movimento ja foi estornado.');
        }

        if ($m['origem'] === 'chamado')
        {
            if ($transacao)
            {
                $this->db->trans_rollback();
            }

            return array('ok' => FALSE, 'erro' => 'Movimento de Chamado/OS deve ser estornado pelo cancelamento do encerramento.');
        }

        $saldo = $this->db->query(
            "SELECT quantidade, quantidade_consumida
               FROM " . T_ESTOQUE_SALDOS . "
              WHERE produto_id = ?
              FOR UPDATE",
            array((int) $m['produto_id'])
        )->row_array();

        if (empty($saldo))
        {
            if ($transacao)
            {
                $this->db->trans_rollback();
            }

            return array('ok' => FALSE, 'erro' => 'Saldo do produto nao encontrado.');
        }

        $anterior  = (int) $saldo['quantidade'];
        $consumida = (int) $saldo['quantidade_consumida'];
        $qtd       = (int) $m['quantidade'];

        if ($m['tipo'] === 'entrada')
        {
            $tipo      = 'saida';
            $posterior = $anterior - $qtd;

            if ($posterior < 0)
            {
                if ($transacao)
                {
                    $this->db->trans_rollback();
                }

                return array('ok' => FALSE, 'erro' => 'Estorno deixaria o saldo negativo (disponivel ' . $anterior . ').');
            }

            $consumida += $qtd;
        }
        elseif ($m['tipo'] === 'saida')
        {
            $tipo      = 'entrada';
            $posterior = $anterior + $qtd;
            $consumida = max(0, $consumida - $qtd);
        }
        else
        {
            $tipo = 'ajuste';

            if ((int) $m['saldo_posterior'] >= (int) $m['saldo_anterior'])
            {
                $posterior = $anterior - $qtd;

                if ($posterior < 0)
                {
                    if ($transacao)
                    {
                        $this->db->trans_rollback();
                    }

                    return array('ok' => FALSE, 'erro' => 'Estorno deixaria o saldo negativo.');
                }
            }
            else
            {
                $posterior = $anterior + $qtd;
            }
        }

        $lote = $this->_proximo_lote();

        $this->_inserir_movimento(array(
            'produto_id'      => (int) $m['produto_id'],
            'chamado_numero'  => NULL,
            'tipo'            => $tipo,
            'quantidade'      => $qtd,
            'saldo_anterior'  => $anterior,
            'saldo_posterior' => $posterior,
            'usuario'         => $usuario['login'],
            'observacao'      => $motivo,
            'lote'            => $lote,
            'documento'       => $m['documento'],
            'fornecedor_id'   => $m['fornecedor_id'],
            'setor_id'        => $m['setor_id'],
            'funcionario_id'  => $m['funcionario_id'],
            'custo_unitario'  => $m['custo_unitario'],
            'motivo'          => mb_substr(
                'Estorno do movimento #' . $movimento_id . ($motivo ? ' - ' . $motivo : ''),
                0, 80, 'UTF-8'
            ),
            'origem'          => 'estorno',
        ));

        $novo_id = (int) $this->db->query(
            "SELECT currval(pg_get_serial_sequence('" . T_ESTOQUE_MOVIMENTOS . "', 'id')) AS id"
        )->row_array()['id'];

        $this->db->query(
            "UPDATE " . T_ESTOQUE_SALDOS . "
                SET quantidade = ?, quantidade_consumida = ?, updated_at = now()
              WHERE produto_id = ?",
            array($posterior, $consumida, (int) $m['produto_id'])
        );

        $this->db->query(
            "UPDATE " . T_ESTOQUE_MOVIMENTOS . "
                SET estornado = true, estornado_por = ?
              WHERE id = ?",
            array($novo_id, $movimento_id)
        );

        if ($transacao)
        {
            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE)
            {
                return array('ok' => FALSE, 'erro' => 'Falha ao estornar o movimento.');
            }
        }

        return array('ok' => TRUE, 'erro' => NULL, 'movimento' => $novo_id);
    }

    // ==================================================================
    // Catalogos
    // ==================================================================

    public function grupos()
    {
        return $this->db->query(
            "SELECT g.*, count(p.id) AS produtos
               FROM " . T_ESTOQUE_GRUPOS . " g
               LEFT JOIN " . T_PRODUTOS . " p ON p.grupo = g.codigo
              GROUP BY g.codigo
              ORDER BY g.codigo"
        )->result_array();
    }

    public function subgrupos($grupo = NULL)
    {
        $sql = "SELECT sg.*, g.nome AS grupo_nome, count(p.id) AS produtos
                  FROM " . T_ESTOQUE_SUBGRUPOS . " sg
                  LEFT JOIN " . T_ESTOQUE_GRUPOS . " g ON g.codigo = sg.grupo_codigo
                  LEFT JOIN " . T_PRODUTOS . " p
                         ON p.grupo = sg.grupo_codigo AND p.subgrupo = sg.codigo";

        if ($grupo !== NULL && $grupo !== '')
        {
            $sql .= " WHERE sg.grupo_codigo = " . $this->db->escape($grupo);
        }

        $sql .= " GROUP BY sg.grupo_codigo, sg.codigo, g.nome
                  ORDER BY sg.grupo_codigo, sg.codigo";

        return $this->db->query($sql)->result_array();
    }

    public function subgrupos_por_grupos(array $grupos)
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
            return array();
        }

        $sql = "SELECT sg.*, g.nome AS grupo_nome, count(p.id) AS produtos
                  FROM " . T_ESTOQUE_SUBGRUPOS . " sg
                  LEFT JOIN " . T_ESTOQUE_GRUPOS . " g ON g.codigo = sg.grupo_codigo
                  LEFT JOIN " . T_PRODUTOS . " p
                         ON ltrim(p.grupo, '0') = ltrim(sg.grupo_codigo, '0')
                        AND ltrim(p.subgrupo, '0') = ltrim(sg.codigo, '0')
                 WHERE ltrim(sg.grupo_codigo, '0') IN (" . implode(', ', $codigos) . ")
                 GROUP BY sg.grupo_codigo, sg.codigo, g.nome
                 ORDER BY sg.grupo_codigo, sg.codigo";

        return $this->db->query($sql)->result_array();
    }

    public function posicoes()
    {
        return $this->db->query("SELECT * FROM " . T_ESTOQUE_POSICOES . " ORDER BY codigo")->result_array();
    }

    public function estantes()
    {
        return $this->db->query(
            "SELECT e.*, count(p.id) AS produtos
               FROM " . T_ESTOQUE_ESTANTES . " e
               LEFT JOIN " . T_PRODUTOS . " p ON p.estante = e.codigo
              GROUP BY e.codigo
              ORDER BY e.codigo"
        )->result_array();
    }

    public function prateleiras_por_estante($estante)
    {
        if ($estante === '' || $estante === NULL)
        {
            return array();
        }

        return $this->db->query(
            "SELECT pr.*, e.nome AS estante_nome
               FROM " . T_ESTOQUE_PRATELEIRAS . " pr
               LEFT JOIN " . T_ESTOQUE_ESTANTES . " e ON e.codigo = pr.estante_codigo
              WHERE pr.estante_codigo = ?
              ORDER BY pr.codigo",
            array((int) $estante)
        )->result_array();
    }

    public function prateleiras()
    {
        return $this->db->query(
            "SELECT pr.*, e.nome AS estante_nome, count(p.id) AS produtos
               FROM " . T_ESTOQUE_PRATELEIRAS . " pr
               LEFT JOIN " . T_ESTOQUE_ESTANTES . " e ON e.codigo = pr.estante_codigo
               LEFT JOIN " . T_PRODUTOS . " p
                      ON p.estante = pr.estante_codigo AND p.prateleira = pr.codigo
              GROUP BY pr.estante_codigo, pr.codigo, e.nome
              ORDER BY pr.estante_codigo, pr.codigo"
        )->result_array();
    }

    public function finalidades()
    {
        return $this->db->query("SELECT * FROM " . T_ESTOQUE_FINALIDADES . " ORDER BY codigo")->result_array();
    }

    public function salvar_catalogo($tipo, $dados)
    {
        $tipo = (string) $tipo;

        if ($tipo === 'grupo')
        {
            $codigo = mb_strtoupper(trim((string) $this->_v($dados, 'codigo')), 'UTF-8');
            $nome   = trim((string) $this->_v($dados, 'nome'));

            if ($codigo === '' || $nome === '')
            {
                return array('ok' => FALSE, 'erro' => 'Informe o codigo e o nome do grupo.');
            }

            $this->db->query(
                "INSERT INTO " . T_ESTOQUE_GRUPOS . " (codigo, nome, percentual_venda)
                 VALUES (?, ?, ?)
                 ON CONFLICT (codigo) DO UPDATE SET
                    nome = EXCLUDED.nome,
                    percentual_venda = EXCLUDED.percentual_venda",
                array(mb_substr($codigo, 0, 3, 'UTF-8'), mb_strtoupper($nome, 'UTF-8'), $this->_decimal($dados, 'percentual_venda'))
            );

            return array('ok' => TRUE, 'erro' => NULL);
        }

        if ($tipo === 'subgrupo')
        {
            $grupo  = mb_strtoupper(trim((string) $this->_v($dados, 'grupo_codigo')), 'UTF-8');
            $codigo = mb_strtoupper(trim((string) $this->_v($dados, 'codigo')), 'UTF-8');
            $nome   = trim((string) $this->_v($dados, 'nome'));

            if ($grupo === '' || $codigo === '' || $nome === '')
            {
                return array('ok' => FALSE, 'erro' => 'Informe o grupo, o codigo e o nome do subgrupo.');
            }

            $this->db->query(
                "INSERT INTO " . T_ESTOQUE_SUBGRUPOS . " (grupo_codigo, codigo, nome)
                 VALUES (?, ?, ?)
                 ON CONFLICT (grupo_codigo, codigo) DO UPDATE SET nome = EXCLUDED.nome",
                array(mb_substr($grupo, 0, 3, 'UTF-8'), mb_substr($codigo, 0, 3, 'UTF-8'), mb_strtoupper($nome, 'UTF-8'))
            );

            return array('ok' => TRUE, 'erro' => NULL);
        }

        if ($tipo === 'posicao' || $tipo === 'estante' || $tipo === 'finalidade')
        {
            $tabela = $tipo === 'posicao' ? T_ESTOQUE_POSICOES : ($tipo === 'estante' ? T_ESTOQUE_ESTANTES : T_ESTOQUE_FINALIDADES);
            $codigo = $this->_v($dados, 'codigo');
            $nome   = trim((string) $this->_v($dados, 'nome'));

            if ($nome === '')
            {
                return array('ok' => FALSE, 'erro' => 'Informe o nome.');
            }

            if ($this->_vazio($codigo))
            {
                $codigo = $this->proximo_codigo($tabela, 'codigo');
            }

            $this->db->query(
                "INSERT INTO {$tabela} (codigo, nome) VALUES (?, ?)
                 ON CONFLICT (codigo) DO UPDATE SET nome = EXCLUDED.nome",
                array((int) $codigo, mb_strtoupper($nome, 'UTF-8'))
            );

            return array('ok' => TRUE, 'erro' => NULL);
        }

        if ($tipo === 'prateleira')
        {
            $estante = $this->_v($dados, 'estante_codigo');
            $codigo  = $this->_v($dados, 'codigo');
            $nome    = trim((string) $this->_v($dados, 'nome'));

            if ($this->_vazio($estante) || $nome === '')
            {
                return array('ok' => FALSE, 'erro' => 'Informe a estante e o nome da prateleira.');
            }

            if ($this->_vazio($codigo))
            {
                $row = $this->db->query(
                    "SELECT COALESCE(max(codigo), 0) + 1 AS proximo
                       FROM " . T_ESTOQUE_PRATELEIRAS . " WHERE estante_codigo = ?",
                    array((int) $estante)
                )->row_array();

                $codigo = (int) $row['proximo'];
            }

            $this->db->query(
                "INSERT INTO " . T_ESTOQUE_PRATELEIRAS . " (estante_codigo, codigo, nome)
                 VALUES (?, ?, ?)
                 ON CONFLICT (estante_codigo, codigo) DO UPDATE SET nome = EXCLUDED.nome",
                array((int) $estante, (int) $codigo, mb_strtoupper($nome, 'UTF-8'))
            );

            return array('ok' => TRUE, 'erro' => NULL);
        }

        return array('ok' => FALSE, 'erro' => 'Tipo de catalogo invalido.');
    }

    public function excluir_catalogo($tipo, $chaves, $usuario)
    {
        $tipo = (string) $tipo;

        $uso = $this->_uso_catalogo($tipo, $chaves);

        if ($uso > 0)
        {
            return array('ok' => FALSE, 'erro' => 'Nao e possivel excluir: existem ' . $uso . ' produto(s) usando este cadastro.');
        }

        if ($tipo === 'grupo')
        {
            $this->db->query("DELETE FROM " . T_ESTOQUE_GRUPOS . " WHERE codigo = ?", array($this->_v($chaves, 'codigo')));
        }
        elseif ($tipo === 'subgrupo')
        {
            $this->db->query(
                "DELETE FROM " . T_ESTOQUE_SUBGRUPOS . " WHERE grupo_codigo = ? AND codigo = ?",
                array($this->_v($chaves, 'grupo_codigo'), $this->_v($chaves, 'codigo'))
            );
        }
        elseif ($tipo === 'prateleira')
        {
            $this->db->query(
                "DELETE FROM " . T_ESTOQUE_PRATELEIRAS . " WHERE estante_codigo = ? AND codigo = ?",
                array((int) $this->_v($chaves, 'estante_codigo'), (int) $this->_v($chaves, 'codigo'))
            );
        }
        else
        {
            $tabelas = array(
                'posicao'    => T_ESTOQUE_POSICOES,
                'estante'    => T_ESTOQUE_ESTANTES,
                'finalidade' => T_ESTOQUE_FINALIDADES,
            );

            if ( ! isset($tabelas[$tipo]))
            {
                return array('ok' => FALSE, 'erro' => 'Tipo de catalogo invalido.');
            }

            $this->db->query(
                "DELETE FROM " . $tabelas[$tipo] . " WHERE codigo = ?",
                array((int) $this->_v($chaves, 'codigo'))
            );
        }

        return array('ok' => TRUE, 'erro' => NULL);
    }

    private function _uso_catalogo($tipo, $chaves)
    {
        if ($tipo === 'grupo')
        {
            $row = $this->db->query(
                "SELECT count(*) AS total FROM " . T_PRODUTOS . " WHERE grupo = ?",
                array($this->_v($chaves, 'codigo'))
            )->row_array();
        }
        elseif ($tipo === 'subgrupo')
        {
            $row = $this->db->query(
                "SELECT count(*) AS total FROM " . T_PRODUTOS . " WHERE grupo = ? AND subgrupo = ?",
                array($this->_v($chaves, 'grupo_codigo'), $this->_v($chaves, 'codigo'))
            )->row_array();
        }
        elseif ($tipo === 'estante')
        {
            $row = $this->db->query(
                "SELECT (SELECT count(*) FROM " . T_PRODUTOS . " WHERE estante = ?)
                      + (SELECT count(*) FROM " . T_ESTOQUE_PRATELEIRAS . " WHERE estante_codigo = ?) AS total",
                array((int) $this->_v($chaves, 'codigo'), (int) $this->_v($chaves, 'codigo'))
            )->row_array();
        }
        elseif ($tipo === 'prateleira')
        {
            $row = $this->db->query(
                "SELECT count(*) AS total FROM " . T_PRODUTOS . " WHERE estante = ? AND prateleira = ?",
                array((int) $this->_v($chaves, 'estante_codigo'), (int) $this->_v($chaves, 'codigo'))
            )->row_array();
        }
        else
        {
            return 0;
        }

        return (int) $row['total'];
    }

    // ==================================================================
    // Autocomplete
    // ==================================================================

    public function buscar_produtos($termo, $limite = 20)
    {
        $termo = trim((string) $termo);

        if (mb_strlen($termo, 'UTF-8') < 1)
        {
            return array();
        }

        $sem_acento = "translate(lower(%s), 'áàãâäéèêëíìîïóòõôöúùûüç', 'aaaaaeeeeiiiiooooouuuuc')";
        $like       = '%' . mb_strtolower($termo, 'UTF-8') . '%';

        $sql = "SELECT p.id, p.codigo, p.descricao, p.unidade, p.valor_compra, p.valor_venda,
                       COALESCE(s.quantidade, 0) AS saldo, p.ativo
                  FROM " . T_PRODUTOS . " p
                  LEFT JOIN " . T_ESTOQUE_SALDOS . " s ON s.produto_id = p.id
                 WHERE (" . sprintf($sem_acento, 'p.descricao') . " LIKE ?
                        OR lower(p.codigo) LIKE ?
                        OR p.descricao LIKE ?)
                 ORDER BY p.ativo DESC, p.descricao
                 LIMIT " . (int) $limite;

        return $this->db->query($sql, array($like, $like, '%' . $termo . '%'))->result_array();
    }

    public function buscar_fornecedores($termo, $limite = 20)
    {
        $termo = trim((string) $termo);

        if ($termo === '')
        {
            return array();
        }

        $like = '%' . mb_strtolower($termo, 'UTF-8') . '%';

        $sql = "SELECT codigo AS id, razao_social AS nome, nome_fantasia, cnpj,
                        cidade, uf, endereco, numero AS endereco_numero, bairro, cep, fone
                  FROM " . T_FORNECEDORES . "
                 WHERE ativo
                   AND (lower(razao_social) LIKE ? OR lower(nome_fantasia) LIKE ? OR cnpj LIKE ?)
                 ORDER BY razao_social
                 LIMIT " . (int) $limite;

        return $this->db->query($sql, array($like, $like, '%' . $termo . '%'))->result_array();
    }

    // ==================================================================
    // Fornecedores
    // ==================================================================

    private function _filtro_fornecedor($busca)
    {
        $busca = trim((string) $busca);

        if ($busca === '')
        {
            return array('', array());
        }

        $sem_acento = "translate(lower(%s), 'áàãâäéèêëíìîïóòõôöúùûüç', 'aaaaaeeeeiiiiooooouuuuc')";
        $termo      = '%' . mb_strtolower($busca, 'UTF-8') . '%';

        $onde = '(' . sprintf($sem_acento, 'razao_social') . ' LIKE ?'
              . ' OR ' . sprintf($sem_acento, 'nome_fantasia') . ' LIKE ?'
              . ' OR cnpj LIKE ?'
              . ' OR cidade LIKE ?)';

        return array('WHERE ' . $onde, array($termo, $termo, '%' . $busca . '%', $termo));
    }

    public function listar_fornecedores($busca = '', $limit = NULL, $offset = NULL)
    {
        list($where, $params) = $this->_filtro_fornecedor($busca);

        $sql = "SELECT f.*,
                       (SELECT count(*) FROM " . T_PRODUTO_FORNECEDOR . " pf
                         WHERE pf.fornecedor_codigo = f.codigo) AS produtos
                  FROM " . T_FORNECEDORES . " f
                  {$where}
                 ORDER BY f.razao_social";

        if ($limit !== NULL)
        {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        return $this->db->query($sql, $params)->result_array();
    }

    public function total_fornecedores($busca = '')
    {
        list($where, $params) = $this->_filtro_fornecedor($busca);

        $row = $this->db->query(
            "SELECT count(*) AS total FROM " . T_FORNECEDORES . " {$where}",
            $params
        )->row_array();

        return (int) $row['total'];
    }

    public function fornecedor($codigo)
    {
        return $this->db->query(
            "SELECT * FROM " . T_FORNECEDORES . " WHERE codigo = ?",
            array((int) $codigo)
        )->row_array();
    }

    public function salvar_fornecedor($dados)
    {
        $codigo = $this->_v($dados, 'codigo');
        $razao  = trim((string) $this->_v($dados, 'razao_social'));

        if ($razao === '')
        {
            return array('ok' => FALSE, 'erro' => 'Informe a razao social do fornecedor.', 'codigo' => NULL);
        }

        if ($this->_vazio($codigo))
        {
            $codigo = $this->proximo_codigo(T_FORNECEDORES, 'codigo');
        }

        $this->db->query(
            "INSERT INTO " . T_FORNECEDORES . "
                (codigo, razao_social, nome_fantasia, inscricao_estadual, cnpj,
                 endereco, numero, complemento, bairro, cidade, cep, uf,
                 fone, fax, email, contato, ponto_referencia, ativo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?::boolean)
             ON CONFLICT (codigo) DO UPDATE SET
                razao_social = EXCLUDED.razao_social,
                nome_fantasia = EXCLUDED.nome_fantasia,
                inscricao_estadual = EXCLUDED.inscricao_estadual,
                cnpj = EXCLUDED.cnpj,
                endereco = EXCLUDED.endereco,
                numero = EXCLUDED.numero,
                complemento = EXCLUDED.complemento,
                bairro = EXCLUDED.bairro,
                cidade = EXCLUDED.cidade,
                cep = EXCLUDED.cep,
                uf = EXCLUDED.uf,
                fone = EXCLUDED.fone,
                fax = EXCLUDED.fax,
                email = EXCLUDED.email,
                contato = EXCLUDED.contato,
                ponto_referencia = EXCLUDED.ponto_referencia,
                ativo = EXCLUDED.ativo,
                updated_at = now()",
            array(
                (int) $codigo,
                mb_strtoupper($razao, 'UTF-8'),
                $this->_texto($dados, 'nome_fantasia', 100),
                $this->_texto($dados, 'inscricao_estadual', 20),
                $this->_texto($dados, 'cnpj', 20),
                $this->_texto($dados, 'endereco', 60),
                $this->_texto($dados, 'numero', 10),
                $this->_texto($dados, 'complemento', 50),
                $this->_texto($dados, 'bairro', 50),
                $this->_texto($dados, 'cidade', 20),
                $this->_texto($dados, 'cep', 10),
                $this->_texto($dados, 'uf', 2),
                $this->_texto($dados, 'fone', 15),
                $this->_texto($dados, 'fax', 15),
                $this->_texto($dados, 'email', 50),
                $this->_texto($dados, 'contato', 25),
                $this->_texto($dados, 'ponto_referencia'),
                isset($dados['ativo']) && $dados['ativo'] ? TRUE : FALSE,
            )
        );

        return array('ok' => TRUE, 'erro' => NULL, 'codigo' => (int) $codigo);
    }

    public function produtos_do_fornecedor($codigo)
    {
        return $this->db->query(
            "SELECT pf.*, p.descricao, p.unidade, p.valor_compra, p.valor_venda,
                    COALESCE(s.quantidade, 0) AS saldo
               FROM " . T_PRODUTO_FORNECEDOR . " pf
               JOIN " . T_PRODUTOS . " p ON p.id = pf.produto_id
               LEFT JOIN " . T_ESTOQUE_SALDOS . " s ON s.produto_id = p.id
              WHERE pf.fornecedor_codigo = ?
              ORDER BY p.descricao",
            array((int) $codigo)
        )->result_array();
    }

    public function fornecedores_do_produto($produto_id)
    {
        return $this->db->query(
            "SELECT pf.*, f.razao_social, f.fone, f.contato, f.email
               FROM " . T_PRODUTO_FORNECEDOR . " pf
               JOIN " . T_FORNECEDORES . " f ON f.codigo = pf.fornecedor_codigo
              WHERE pf.produto_id = ?
              ORDER BY f.razao_social",
            array((int) $produto_id)
        )->result_array();
    }

    public function vincular_produto_fornecedor($fornecedor, $produto_id, $codigo_forn, $nome_forn, $preco)
    {
        $fornecedor = (int) $fornecedor;
        $produto_id = (int) $produto_id;

        if ($fornecedor <= 0 || $produto_id <= 0)
        {
            return array('ok' => FALSE, 'erro' => 'Selecione o produto.');
        }

        $existe = $this->db->query(
            "SELECT 1 FROM " . T_PRODUTOS . " WHERE id = ?",
            array($produto_id)
        )->row_array();

        if (empty($existe))
        {
            return array('ok' => FALSE, 'erro' => 'Produto nao encontrado.');
        }

        $codigo_forn = trim((string) $codigo_forn);

        if ($codigo_forn === '')
        {
            $codigo_forn = (string) $produto_id;
        }

        $preco = ($preco === NULL || trim((string) $preco) === '')
            ? NULL
            : (float) str_replace(',', '.', (string) $preco);

        $this->db->query(
            "INSERT INTO " . T_PRODUTO_FORNECEDOR . "
                (fornecedor_codigo, produto_id, codigo_fornecedor, nome_fornecedor, preco_venda)
             VALUES (?, ?, ?, ?, ?)
             ON CONFLICT (fornecedor_codigo, produto_id) DO UPDATE SET
                codigo_fornecedor = EXCLUDED.codigo_fornecedor,
                nome_fornecedor = EXCLUDED.nome_fornecedor,
                preco_venda = EXCLUDED.preco_venda",
            array(
                $fornecedor,
                $produto_id,
                mb_substr($codigo_forn, 0, 25, 'UTF-8'),
                $nome_forn ? mb_substr(trim((string) $nome_forn), 0, 80, 'UTF-8') : NULL,
                $preco,
            )
        );

        return array('ok' => TRUE, 'erro' => NULL);
    }

    public function desvincular_produto_fornecedor($fornecedor, $produto_id)
    {
        $this->db->query(
            "DELETE FROM " . T_PRODUTO_FORNECEDOR . "
              WHERE fornecedor_codigo = ? AND produto_id = ?",
            array((int) $fornecedor, (int) $produto_id)
        );

        return array('ok' => TRUE, 'erro' => NULL);
    }

    // ==================================================================
    // Helpers
    // ==================================================================

    public function proximo_codigo($tabela, $campo = 'codigo')
    {
        $row = $this->db->query(
            "SELECT COALESCE(max({$campo}), 0) + 1 AS proximo FROM {$tabela}"
        )->row_array();

        return (int) $row['proximo'];
    }

    private function _normalizar_itens($itens)
    {
        $saida = array();

        if ( ! is_array($itens))
        {
            return $saida;
        }

        foreach ($itens as $item)
        {
            $produto = isset($item['produto_id']) ? (int) $item['produto_id'] : 0;
            $qtd     = isset($item['quantidade']) ? (int) $item['quantidade'] : 0;

            if ($produto <= 0 || $qtd <= 0)
            {
                continue;
            }

            $custo = isset($item['custo_unitario']) && $item['custo_unitario'] !== ''
                ? (float) str_replace(',', '.', (string) $item['custo_unitario'])
                : NULL;

            $saida[] = array(
                'produto_id'     => $produto,
                'quantidade'     => $qtd,
                'custo_unitario' => $custo,
            );
        }

        return $saida;
    }

    private function _proximo_lote()
    {
        $row = $this->db->query(
            "SELECT nextval('" . DB_SCHEMA . ".biostech_estoque_lote_seq') AS lote"
        )->row_array();

        return (int) $row['lote'];
    }

    private function _inserir_movimento($dados)
    {
        $colunas = array_keys($dados);
        $marcas  = rtrim(str_repeat('?, ', count($colunas)), ', ');

        $sql = "INSERT INTO " . T_ESTOQUE_MOVIMENTOS . "
                    (" . implode(', ', $colunas) . ")
                VALUES ({$marcas})";

        $this->db->query($sql, array_values($dados));
    }

    private function _v($dados, $chave)
    {
        return isset($dados[$chave]) ? $dados[$chave] : NULL;
    }

    /**
     * O input do CodeIgniter devolve FALSE quando o campo nao veio no POST.
     */
    private function _vazio($valor)
    {
        return $valor === NULL || $valor === FALSE || trim((string) $valor) === '';
    }

    private function _texto($dados, $chave, $limite = NULL)
    {
        $v = isset($dados[$chave]) ? trim((string) $dados[$chave]) : '';

        if ($v === '')
        {
            return NULL;
        }

        // Remove controles C1 e bytes invalidos herdados do Access.
        $v = preg_replace('/[\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}\x{007F}-\x{009F}]/u', ' ', $v);
        $v = trim(preg_replace('/[ ]{2,}/', ' ', $v));

        if ($v === '')
        {
            return NULL;
        }

        return $limite === NULL ? $v : mb_substr($v, 0, $limite, 'UTF-8');
    }

    private function _decimal($dados, $chave)
    {
        $v = $this->_v($dados, $chave);

        if ($v === NULL || trim((string) $v) === '')
        {
            return NULL;
        }

        return (float) str_replace(',', '.', (string) $v);
    }
}
