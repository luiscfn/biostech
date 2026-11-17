<?php
/**
 * Compras_model - pedidos de compra, itens, parcelas, cotacoes de precos,
 * solicitacoes internas, prazos de fornecedor e contas a pagar.
 *
 * Regras principais (ver docs/08-regras-compras.md):
 *  - somente pedido aberto pode ser alterado/excluido;
 *  - fechamento exige fornecedor e itens, gera estoque e contas a pagar;
 *  - parcelas de 1 a 12 com dias e vencimentos nao decrescentes;
 *  - soma das parcelas igual ao total do pedido (rateio com ajuste);
 *  - cancelamento do fechamento estorna o estoque e cancela os titulos;
 *  - exclusao de item/pedido somente enquanto aberto.
 */
class Compras_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('numeracao');
        $this->load->model('estoque_model');
    }

    // ==================================================================
    // Pedidos - consultas
    // ==================================================================

    public function stats()
    {
        $row = $this->db->query(
            "SELECT
                count(*) FILTER (WHERE situacao = 'aberto')   AS abertos,
                count(*) FILTER (WHERE situacao = 'fechado')  AS fechados,
                count(*) FILTER (WHERE situacao = 'cancelado') AS cancelados,
                COALESCE(sum(total) FILTER (WHERE situacao = 'aberto'), 0)  AS valor_aberto,
                COALESCE(sum(total) FILTER (WHERE situacao = 'fechado'
                          AND data_fechamento >= date_trunc('month', now())), 0) AS valor_mes,
                count(*) FILTER (WHERE data >= date_trunc('month', now())) AS pedidos_mes
               FROM " . T_COMPRAS_PEDIDOS . " "
        )->row_array();

        $cp = $this->db->query(
            "SELECT count(*) FILTER (WHERE situacao = 1) AS abertas,
                    COALESCE(sum(valor) FILTER (WHERE situacao = 1), 0) AS valor
               FROM " . T_CONTAS_PAGAR . " "
        )->row_array();

        $sc = $this->db->query(
            "SELECT count(*) FILTER (WHERE situacao = 'aberta') AS abertas
               FROM " . T_COMPRAS_SOLICITACOES . " "
        )->row_array();

        $cot = $this->db->query(
            "SELECT count(*) AS total
               FROM " . T_COMPRAS_COTACOES . "
              WHERE data_cotacao >= date_trunc('month', now())"
        )->row_array();

        return array(
            'abertos'         => (int) $row['abertos'],
            'fechados'        => (int) $row['fechados'],
            'cancelados'      => (int) $row['cancelados'],
            'valor_aberto'    => (float) $row['valor_aberto'],
            'valor_mes'       => (float) $row['valor_mes'],
            'pedidos_mes'     => (int) $row['pedidos_mes'],
            'cp_abertas'      => (int) $cp['abertas'],
            'cp_valor'        => (float) $cp['valor'],
            'sc_abertas'      => (int) $sc['abertas'],
            'cotacoes_mes'    => (int) $cot['total'],
        );
    }

    // ------------------------------------------------------------------

    private function _filtro_pedidos($f)
    {
        $onde   = array('1=1');
        $params = array();

        if ( ! empty($f['busca']))
        {
            $like     = '%' . mb_strtolower(trim($f['busca']), 'UTF-8') . '%';
            $onde[]   = "(p.numero::text LIKE ? OR lower(f.razao_social) LIKE ?
                          OR lower(f.nome_fantasia) LIKE ? OR lower(COALESCE(p.observacao, '')) LIKE ?
                          OR lower(COALESCE(p.num_nf, '')) LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ( ! empty($f['fornecedor']))
        {
            $onde[]   = 'p.fornecedor_codigo = ?';
            $params[] = (int) $f['fornecedor'];
        }

        if ( ! empty($f['situacao']) && in_array($f['situacao'], array('aberto', 'fechado', 'cancelado'), TRUE))
        {
            $onde[]   = 'p.situacao = ?';
            $params[] = $f['situacao'];
        }

        if ( ! empty($f['setor']))
        {
            $onde[]   = 'p.setor_id = ?';
            $params[] = (int) $f['setor'];
        }

        if ( ! empty($f['data_de']))
        {
            $onde[]   = 'p.data >= ?';
            $params[] = $f['data_de'];
        }

        if ( ! empty($f['data_ate']))
        {
            $onde[]   = 'p.data <= ?';
            $params[] = $f['data_ate'];
        }

        return array(implode(' AND ', $onde), $params);
    }

    // ------------------------------------------------------------------

    public function listar_pedidos($f = array(), $limit = NULL, $offset = NULL)
    {
        list($where, $params) = $this->_filtro_pedidos($f);

        $sql = "SELECT p.*,
                       f.razao_social AS fornecedor_nome,
                       f.nome_fantasia AS fornecedor_fantasia,
                       s.nome AS setor_nome,
                       COALESCE(i.itens, 0) AS qtd_itens
                  FROM " . T_COMPRAS_PEDIDOS . " p
                  LEFT JOIN " . T_FORNECEDORES . " f ON f.codigo = p.fornecedor_codigo
                  LEFT JOIN " . T_SETORES . " s ON s.id = p.setor_id
                  LEFT JOIN (SELECT pedido_numero, count(*) AS itens
                               FROM " . T_COMPRAS_ITENS . "
                              GROUP BY pedido_numero) i ON i.pedido_numero = p.numero
                 WHERE {$where}
                 ORDER BY p.data DESC, p.numero DESC";

        if ($limit !== NULL)
        {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        return $this->db->query($sql, $params)->result_array();
    }

    // ------------------------------------------------------------------

    public function total_pedidos($f = array())
    {
        list($where, $params) = $this->_filtro_pedidos($f);

        $row = $this->db->query(
            "SELECT count(*) AS total
               FROM " . T_COMPRAS_PEDIDOS . " p
               LEFT JOIN " . T_FORNECEDORES . " f ON f.codigo = p.fornecedor_codigo
              WHERE {$where}",
            $params
        )->row_array();

        return (int) $row['total'];
    }

    // ------------------------------------------------------------------

    public function pedido($numero)
    {
        return $this->db->query(
            "SELECT p.*,
                    f.razao_social AS fornecedor_nome,
                    f.nome_fantasia AS fornecedor_fantasia,
                    f.cnpj AS fornecedor_cnpj,
                    f.fone AS fornecedor_fone,
                    f.contato AS fornecedor_contato,
                    s.nome AS setor_nome,
                    u.nome AS funcionario_nome,
                    uc.nome AS usuario_cadastro_nome,
                    uf.nome AS usuario_fechamento_nome
               FROM " . T_COMPRAS_PEDIDOS . " p
               LEFT JOIN " . T_FORNECEDORES . " f ON f.codigo = p.fornecedor_codigo
               LEFT JOIN " . T_SETORES . " s ON s.id = p.setor_id
               LEFT JOIN " . T_USUARIOS . " u ON u.funcionario_id = p.funcionario_id
               LEFT JOIN " . T_USUARIOS . " uc ON uc.login = p.usuario_cadastro
               LEFT JOIN " . T_USUARIOS . " uf ON uf.login = p.usuario_fechamento
              WHERE p.numero = ?",
            array((int) $numero)
        )->row_array();
    }

    // ------------------------------------------------------------------

    public function itens_pedido($numero)
    {
        return $this->db->query(
            "SELECT i.*,
                    pr.descricao, pr.codigo AS produto_codigo, pr.unidade,
                    pr.valor_compra AS preco_compra, pr.valor_venda,
                    pr.percentual_lucro,
                    COALESCE(es.quantidade, 0) AS saldo
               FROM " . T_COMPRAS_ITENS . " i
               JOIN " . T_PRODUTOS . " pr ON pr.id = i.produto_id
               LEFT JOIN " . T_ESTOQUE_SALDOS . " es ON es.produto_id = i.produto_id
              WHERE i.pedido_numero = ?
              ORDER BY pr.descricao",
            array((int) $numero)
        )->result_array();
    }

    // ------------------------------------------------------------------

    public function parcelas($numero)
    {
        return $this->db->query(
            "SELECT * FROM " . T_COMPRAS_PARCELAS . "
              WHERE pedido_numero = ?
              ORDER BY numero_par",
            array((int) $numero)
        )->result_array();
    }

    // ------------------------------------------------------------------

    public function log_pedido($numero)
    {
        return $this->db->query(
            "SELECT * FROM " . T_COMPRAS_LOG . "
              WHERE pedido_numero = ?
              ORDER BY data_mov DESC, id DESC
              LIMIT 100",
            array((int) $numero)
        )->result_array();
    }

    // ------------------------------------------------------------------

    public function contas_pagar($numero)
    {
        return $this->db->query(
            "SELECT * FROM " . T_CONTAS_PAGAR . "
              WHERE origem = 'compra' AND origem_id = ?
              ORDER BY parcela",
            array((string) (int) $numero)
        )->result_array();
    }

    // ==================================================================
    // Pedidos - gravacao
    // ==================================================================

    public function salvar_pedido($dados, $itens, $usuario, $transacao = TRUE, $permite_sem_fornecedor = FALSE)
    {
        $numero     = isset($dados['numero']) ? (int) $dados['numero'] : 0;
        $fornecedor = $this->_int($this->_v($dados, 'fornecedor_codigo'));
        $data       = $this->_data($this->_v($dados, 'data'));

        if ($data === NULL)
        {
            return array('ok' => FALSE, 'erro' => 'Informe a data do pedido.', 'numero' => $numero);
        }

        if ($fornecedor === NULL && ! $permite_sem_fornecedor)
        {
            return array('ok' => FALSE, 'erro' => 'Informe o fornecedor do pedido.', 'numero' => $numero);
        }

        if ($fornecedor !== NULL)
        {
            $existe_forn = $this->db->query(
                "SELECT 1 FROM " . T_FORNECEDORES . " WHERE codigo = ?",
                array($fornecedor)
            )->row_array();

            if (empty($existe_forn))
            {
                return array('ok' => FALSE, 'erro' => 'Fornecedor nao encontrado.', 'numero' => $numero);
            }
        }

        $itens = $this->_normalizar_itens($itens);

        $produtos = array();

        foreach ($itens as $item)
        {
            if (isset($produtos[$item['produto_id']]))
            {
                return array(
                    'ok'     => FALSE,
                    'erro'   => 'O produto ' . $item['produto_id'] . ' esta repetido no pedido.',
                    'numero' => $numero,
                );
            }

            $produtos[$item['produto_id']] = TRUE;
        }

        if ( ! $this->_produtos_existem($itens))
        {
            return array('ok' => FALSE, 'erro' => 'Um ou mais produtos informados nao existem.', 'numero' => $numero);
        }

        $anterior = NULL;

        if ($numero > 0)
        {
            $anterior = $this->db->query(
                "SELECT * FROM " . T_COMPRAS_PEDIDOS . " WHERE numero = ?",
                array($numero)
            )->row_array();

            if (empty($anterior))
            {
                return array('ok' => FALSE, 'erro' => 'Pedido nao encontrado.', 'numero' => $numero);
            }

            if ($anterior['situacao'] !== 'aberto')
            {
                return array(
                    'ok'     => FALSE,
                    'erro'   => 'Somente pedidos abertos podem ser alterados.',
                    'numero' => $numero,
                );
            }
        }

        $bruto = 0.0;

        foreach ($itens as $item)
        {
            $bruto += $item['valor_total'];
        }

        $bruto  = round($bruto, 2);
        $perc   = $this->_dec($this->_v($dados, 'desconto_percentual'));
        $desc   = $this->_dec($this->_v($dados, 'desconto'));
        $desc   = $this->_desconto($bruto, $perc, $desc);
        $total  = max(0, round($bruto - $desc, 2));

        if ($transacao)
        {
            $this->db->trans_start();
        }

        if ($numero <= 0)
        {
            $numero = (int) $this->numeracao->proximo('PC');

            $this->db->query(
                "INSERT INTO " . T_COMPRAS_PEDIDOS . "
                    (numero, data, hora, setor_id, funcionario_id, fornecedor_codigo,
                     tipo, situacao, solicitacao_numero, observacao, local_entrega,
                     responsavel_recebimento, fone_responsavel, numero_proposta,
                     desconto, desconto_percentual, condicao_pagamento, prazo_entrega,
                     forma_recebimento, documento_recebimento, centro_custo,
                     data_vencimento, num_nf, data_nf, total_bruto, total,
                     usuario_cadastro, data_cadastro)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'aberto', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now())",
                array(
                    $numero,
                    $data,
                    $this->_hora($this->_v($dados, 'hora')) ?: date('H:i:s'),
                    $this->_int($this->_v($dados, 'setor_id')),
                    $this->_int($this->_v($dados, 'funcionario_id')),
                    $fornecedor,
                    $this->_int($this->_v($dados, 'tipo')),
                    $this->_int($this->_v($dados, 'solicitacao_numero')),
                    $this->_texto($this->_v($dados, 'observacao')),
                    $this->_texto($this->_v($dados, 'local_entrega')),
                    $this->_texto($this->_v($dados, 'responsavel_recebimento'), 80),
                    $this->_texto($this->_v($dados, 'fone_responsavel'), 20),
                    $this->_texto($this->_v($dados, 'numero_proposta'), 30),
                    $desc,
                    $perc,
                    $this->_int($this->_v($dados, 'condicao_pagamento')),
                    $this->_int($this->_v($dados, 'prazo_entrega')),
                    $this->_int($this->_v($dados, 'forma_recebimento')),
                    $this->_int($this->_v($dados, 'documento_recebimento')),
                    $this->_int($this->_v($dados, 'centro_custo')),
                    $this->_data($this->_v($dados, 'data_vencimento')),
                    $this->_texto($this->_v($dados, 'num_nf'), 30),
                    $this->_data($this->_v($dados, 'data_nf')),
                    $bruto,
                    $total,
                    $usuario['login'],
                )
            );

            $acao = 'I';
        }
        else
        {
            $this->db->query(
                "UPDATE " . T_COMPRAS_PEDIDOS . "
                    SET data = ?, hora = ?, setor_id = ?, funcionario_id = ?,
                        fornecedor_codigo = ?, tipo = ?, solicitacao_numero = ?,
                        observacao = ?, local_entrega = ?, responsavel_recebimento = ?,
                        fone_responsavel = ?, numero_proposta = ?, desconto = ?,
                        desconto_percentual = ?, condicao_pagamento = ?, prazo_entrega = ?,
                        forma_recebimento = ?, documento_recebimento = ?, centro_custo = ?,
                        data_vencimento = ?, num_nf = ?, data_nf = ?,
                        total_bruto = ?, total = ?, usuario_ultima_alteracao = ?,
                        data_ultima_alteracao = now(), updated_at = now()
                  WHERE numero = ?",
                array(
                    $data,
                    $this->_hora($this->_v($dados, 'hora')) ?: date('H:i:s'),
                    $this->_int($this->_v($dados, 'setor_id')),
                    $this->_int($this->_v($dados, 'funcionario_id')),
                    $fornecedor,
                    $this->_int($this->_v($dados, 'tipo')),
                    $this->_int($this->_v($dados, 'solicitacao_numero')),
                    $this->_texto($this->_v($dados, 'observacao')),
                    $this->_texto($this->_v($dados, 'local_entrega')),
                    $this->_texto($this->_v($dados, 'responsavel_recebimento'), 80),
                    $this->_texto($this->_v($dados, 'fone_responsavel'), 20),
                    $this->_texto($this->_v($dados, 'numero_proposta'), 30),
                    $desc,
                    $perc,
                    $this->_int($this->_v($dados, 'condicao_pagamento')),
                    $this->_int($this->_v($dados, 'prazo_entrega')),
                    $this->_int($this->_v($dados, 'forma_recebimento')),
                    $this->_int($this->_v($dados, 'documento_recebimento')),
                    $this->_int($this->_v($dados, 'centro_custo')),
                    $this->_data($this->_v($dados, 'data_vencimento')),
                    $this->_texto($this->_v($dados, 'num_nf'), 30),
                    $this->_data($this->_v($dados, 'data_nf')),
                    $bruto,
                    $total,
                    $usuario['login'],
                    $numero,
                )
            );

            $acao = 'A';
        }

        // Substitui os itens do pedido.
        $this->db->query(
            "DELETE FROM " . T_COMPRAS_ITENS . " WHERE pedido_numero = ?",
            array($numero)
        );

        foreach ($itens as $item)
        {
            $this->db->query(
                "INSERT INTO " . T_COMPRAS_ITENS . "
                    (pedido_numero, produto_id, quantidade, valor_unitario,
                     novo_valor_unitario, desconto_percentual, desconto_item,
                     valor_total, observacao, atualiza_preco_compra)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?::boolean)",
                array(
                    $numero,
                    $item['produto_id'],
                    $item['quantidade'],
                    $item['valor_unitario'],
                    $item['novo_valor_unitario'],
                    $item['desconto_percentual'],
                    $item['desconto_item'],
                    $item['valor_total'],
                    $item['observacao'],
                    $item['atualiza_preco_compra'] ? 'true' : 'false',
                )
            );
        }

        $detalhe = array(
            'data'        => $data,
            'fornecedor'  => $fornecedor,
            'itens'       => count($itens),
            'total_bruto' => $bruto,
            'desconto'    => $desc,
            'total'       => $total,
        );

        if ($anterior !== NULL)
        {
            $detalhe['anterior'] = array(
                'fornecedor' => $anterior['fornecedor_codigo'],
                'data'       => $anterior['data'],
                'total'      => (float) $anterior['total'],
            );
        }

        $this->_log($numero, NULL, $acao, $usuario, $detalhe);

        if ($transacao)
        {
            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE)
            {
                return array('ok' => FALSE, 'erro' => 'Falha ao gravar o pedido.', 'numero' => $numero);
            }
        }

        return array(
            'ok'      => TRUE,
            'erro'    => NULL,
            'numero'  => $numero,
            'criado'  => $acao === 'I',
            'itens'   => count($itens),
            'total'   => $total,
        );
    }

    // ------------------------------------------------------------------

    public function excluir_pedido($numero, $usuario)
    {
        $numero = (int) $numero;

        $p = $this->db->query(
            "SELECT * FROM " . T_COMPRAS_PEDIDOS . " WHERE numero = ? FOR UPDATE",
            array($numero)
        )->row_array();

        if (empty($p))
        {
            return array('ok' => FALSE, 'erro' => 'Pedido nao encontrado.');
        }

        if ($p['situacao'] !== 'aberto')
        {
            return array('ok' => FALSE, 'erro' => 'Somente pedidos abertos podem ser excluidos. Cancele o fechamento primeiro.');
        }

        $this->db->trans_start();

        $this->_log($numero, NULL, 'E', $usuario, array(
            'fornecedor' => $p['fornecedor_codigo'],
            'data'       => $p['data'],
            'total'      => (float) $p['total'],
        ));

        $this->db->query("DELETE FROM " . T_COMPRAS_PEDIDOS . " WHERE numero = ?", array($numero));

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return array('ok' => FALSE, 'erro' => 'Falha ao excluir o pedido.');
        }

        return array('ok' => TRUE, 'erro' => NULL);
    }

    // ------------------------------------------------------------------

    public function cancelar_pedido($numero, $motivo, $usuario)
    {
        $numero = (int) $numero;

        $p = $this->db->query(
            "SELECT * FROM " . T_COMPRAS_PEDIDOS . " WHERE numero = ? FOR UPDATE",
            array($numero)
        )->row_array();

        if (empty($p))
        {
            return array('ok' => FALSE, 'erro' => 'Pedido nao encontrado.');
        }

        if ($p['situacao'] !== 'aberto')
        {
            return array('ok' => FALSE, 'erro' => 'Somente pedidos abertos podem ser cancelados.');
        }

        $this->db->trans_start();

        $this->db->query(
            "UPDATE " . T_COMPRAS_PEDIDOS . "
                SET situacao = 'cancelado', motivo_cancelamento = ?,
                    usuario_cancelamento = ?, data_cancelamento = now(),
                    updated_at = now()
              WHERE numero = ?",
            array($this->_texto($motivo), $usuario['login'], $numero)
        );

        $this->_log($numero, NULL, 'C', $usuario, array('motivo' => $motivo));

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return array('ok' => FALSE, 'erro' => 'Falha ao cancelar o pedido.');
        }

        return array('ok' => TRUE, 'erro' => NULL);
    }

    // ------------------------------------------------------------------

    public function reabrir_pedido($numero, $usuario)
    {
        $numero = (int) $numero;

        $p = $this->db->query(
            "SELECT * FROM " . T_COMPRAS_PEDIDOS . " WHERE numero = ? FOR UPDATE",
            array($numero)
        )->row_array();

        if (empty($p))
        {
            return array('ok' => FALSE, 'erro' => 'Pedido nao encontrado.');
        }

        if ($p['situacao'] !== 'cancelado')
        {
            return array('ok' => FALSE, 'erro' => 'Somente pedidos cancelados podem ser reabertos.');
        }

        $this->db->trans_start();

        $this->db->query(
            "UPDATE " . T_COMPRAS_PEDIDOS . "
                SET situacao = 'aberto', motivo_cancelamento = NULL,
                    usuario_cancelamento = NULL, data_cancelamento = NULL,
                    updated_at = now()
              WHERE numero = ?",
            array($numero)
        );

        $this->_log($numero, NULL, 'R', $usuario, array('motivo' => 'Reabertura'));

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return array('ok' => FALSE, 'erro' => 'Falha ao reabrir o pedido.');
        }

        return array('ok' => TRUE, 'erro' => NULL);
    }

    // ------------------------------------------------------------------
    // Fechamento
    // ------------------------------------------------------------------

    public function fechar_pedido($numero, $dados, $lista_parcelas, $usuario)
    {
        $numero = (int) $numero;

        $p = $this->db->query(
            "SELECT * FROM " . T_COMPRAS_PEDIDOS . " WHERE numero = ? FOR UPDATE",
            array($numero)
        )->row_array();

        if (empty($p))
        {
            return array('ok' => FALSE, 'erro' => 'Pedido nao encontrado.');
        }

        if ($p['situacao'] !== 'aberto')
        {
            return array('ok' => FALSE, 'erro' => 'Somente pedidos abertos podem ser fechados.');
        }

        if (empty($p['fornecedor_codigo']))
        {
            return array('ok' => FALSE, 'erro' => 'Informe o fornecedor antes de fechar o pedido.');
        }

        $itens = $this->itens_pedido($numero);

        if (empty($itens))
        {
            return array('ok' => FALSE, 'erro' => 'Inclua pelo menos um item antes de fechar o pedido.');
        }

        $parcelas = $this->_normalizar_parcelas($lista_parcelas, $dados, $p['total']);

        if (isset($parcelas['erro']))
        {
            return array('ok' => FALSE, 'erro' => $parcelas['erro']);
        }

        $data_compra = $this->_data($this->_v($dados, 'data_compra')) ?: $p['data'];
        $num_nf      = $this->_texto($this->_v($dados, 'num_nf'), 30);
        $data_nf     = $this->_data($this->_v($dados, 'data_nf'));
        $gerar_cp    = $this->_bool($this->_v($dados, 'gerar_contas'), TRUE);
        $fornecedor_estoque = $this->_int($this->_v($dados, 'fornecedor_estoque')) ?: (int) $p['fornecedor_codigo'];

        $this->db->trans_start();

        // Atualiza o cabecalho.
        $this->db->query(
            "UPDATE " . T_COMPRAS_PEDIDOS . "
                SET situacao = 'fechado', num_nf = ?, data_nf = ?,
                    data_vencimento = ?, condicao_pagamento = ?,
                    forma_recebimento = ?, documento_recebimento = ?,
                    centro_custo = ?, prazo_entrega = ?,
                    usuario_fechamento = ?, data_fechamento = now(),
                    updated_at = now()
              WHERE numero = ?",
            array(
                $num_nf,
                $data_nf,
                $this->_data($this->_v($dados, 'data_vencimento')) ?: $parcelas[0]['vencimento'],
                $this->_int($this->_v($dados, 'condicao_pagamento')),
                $this->_int($this->_v($dados, 'forma_recebimento')),
                $this->_int($this->_v($dados, 'documento_recebimento')),
                $this->_int($this->_v($dados, 'centro_custo')),
                $this->_int($this->_v($dados, 'prazo_entrega')),
                $usuario['login'],
                $numero,
            )
        );

        // Regrava as parcelas.
        $this->db->query(
            "DELETE FROM " . T_COMPRAS_PARCELAS . " WHERE pedido_numero = ?",
            array($numero)
        );

        foreach ($parcelas as $parcela)
        {
            $this->db->query(
                "INSERT INTO " . T_COMPRAS_PARCELAS . "
                    (pedido_numero, numero_par, dias, vencimento, valor)
                 VALUES (?, ?, ?, ?, ?)",
                array(
                    $numero,
                    $parcela['numero_par'],
                    $parcela['dias'],
                    $parcela['vencimento'],
                    $parcela['valor'],
                )
            );
        }

        // Entrada no estoque (uma linha por item).
        $itens_estoque = array();

        foreach ($itens as $item)
        {
            $custo = $this->_preco_item($item);

            $itens_estoque[] = array(
                'produto_id'     => (int) $item['produto_id'],
                'quantidade'     => (float) $item['quantidade'],
                'custo_unitario' => $custo,
            );
        }

        $entrada = $this->estoque_model->entrada($itens_estoque, array(
            'documento'      => $num_nf,
            'fornecedor_id'  => $fornecedor_estoque,
            'setor_id'       => $p['setor_id'],
            'funcionario_id' => $p['funcionario_id'],
            'observacao'     => 'Fechamento do pedido de compra ' . $numero,
            'motivo'         => 'Compra',
            'origem'         => 'compra',
            'origem_id'      => (string) $numero,
        ), $usuario, FALSE);

        if ( ! $entrada['ok'])
        {
            $this->db->trans_rollback();
            return array('ok' => FALSE, 'erro' => 'Estoque: ' . $entrada['erro']);
        }

        // Atualiza os dados de compra dos produtos.
        foreach ($itens as $item)
        {
            $this->_atualizar_produto($item, $numero, $data_compra, $num_nf);
        }

        // Contas a pagar.
        $conta_pagar = NULL;

        if ($gerar_cp)
        {
            $conta_pagar = (int) $this->numeracao->proximo('CP');

            foreach ($parcelas as $parcela)
            {
                $historico = $this->_historico_conta($parcela, $p, $num_nf, $data_nf, $parcelas);

                $this->db->query(
                    "INSERT INTO " . T_CONTAS_PAGAR . "
                        (numero, parcela, total_parcelas, fornecedor_codigo,
                         portador_setor, emissao, lancamento, vencimento, valor,
                         forma_recebimento, documento_recebimento, centro_custo,
                         documento, situacao, historico, origem, origem_id,
                         usuario_cadastro, data_cadastro)
                     VALUES (?, ?, ?, ?, ?, ?, CURRENT_DATE, ?, ?, ?, ?, ?, ?, 1, ?, 'compra', ?, ?, now())",
                    array(
                        $conta_pagar,
                        $parcela['numero_par'],
                        count($parcelas),
                        (int) $p['fornecedor_codigo'],
                        $this->_int($p['setor_id']),
                        $data_compra,
                        $parcela['vencimento'],
                        $parcela['valor'],
                        $this->_int($this->_v($dados, 'forma_recebimento')),
                        $this->_int($this->_v($dados, 'documento_recebimento')),
                        $this->_int($this->_v($dados, 'centro_custo')),
                        $num_nf,
                        $historico,
                        (string) $numero,
                        $usuario['login'],
                    )
                );
            }

            $this->db->query(
                "UPDATE " . T_COMPRAS_PEDIDOS . " SET conta_pagar_numero = ? WHERE numero = ?",
                array($conta_pagar, $numero)
            );
        }

        $this->_log($numero, NULL, 'F', $usuario, array(
            'data_compra'  => $data_compra,
            'num_nf'       => $num_nf,
            'parcelas'     => count($parcelas),
            'conta_pagar'  => $conta_pagar,
            'estoque_lote' => $entrada['lote'],
            'total'        => (float) $p['total'],
        ));

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return array('ok' => FALSE, 'erro' => 'Falha ao fechar o pedido.');
        }

        return array(
            'ok'          => TRUE,
            'erro'        => NULL,
            'numero'      => $numero,
            'parcelas'    => $parcelas,
            'conta_pagar' => $conta_pagar,
            'lote'        => $entrada['lote'],
        );
    }

    // ------------------------------------------------------------------

    public function cancelar_fechamento($numero, $motivo, $usuario)
    {
        $numero = (int) $numero;

        $p = $this->db->query(
            "SELECT * FROM " . T_COMPRAS_PEDIDOS . " WHERE numero = ? FOR UPDATE",
            array($numero)
        )->row_array();

        if (empty($p))
        {
            return array('ok' => FALSE, 'erro' => 'Pedido nao encontrado.');
        }

        if ($p['situacao'] !== 'fechado')
        {
            return array('ok' => FALSE, 'erro' => 'Somente pedidos fechados podem ter o fechamento cancelado.');
        }

        $motivo = trim((string) $motivo);

        if ($motivo === '')
        {
            return array('ok' => FALSE, 'erro' => 'Informe o motivo do cancelamento do fechamento.');
        }

        $this->db->trans_start();

        // Estorna os movimentos de estoque gerados pelo fechamento.
        $movimentos = $this->db->query(
            "SELECT id FROM " . T_ESTOQUE_MOVIMENTOS . "
              WHERE origem = 'compra' AND origem_id = ? AND NOT estornado
              ORDER BY id",
            array((string) $numero)
        )->result_array();

        $estornos = 0;

        foreach ($movimentos as $m)
        {
            $r = $this->estoque_model->estornar((int) $m['id'], 'Cancelamento do fechamento do pedido ' . $numero . ': ' . $motivo, $usuario, FALSE);

            if ( ! $r['ok'])
            {
                $this->db->trans_rollback();
                return array('ok' => FALSE, 'erro' => 'Estoque: ' . $r['erro']);
            }

            $estornos++;
        }

        // Cancela os titulos gerados.
        $this->db->query(
            "UPDATE " . T_CONTAS_PAGAR . "
                SET situacao = 3, updated_at = now()
              WHERE origem = 'compra' AND origem_id = ? AND situacao = 1",
            array((string) $numero)
        );

        $this->db->query(
            "DELETE FROM " . T_COMPRAS_PARCELAS . " WHERE pedido_numero = ?",
            array($numero)
        );

        $this->db->query(
            "UPDATE " . T_COMPRAS_PEDIDOS . "
                SET situacao = 'aberto', usuario_fechamento = NULL,
                    data_fechamento = NULL, conta_pagar_numero = NULL,
                    motivo_cancelamento = ?, usuario_cancelamento = ?,
                    data_cancelamento = now(), updated_at = now()
              WHERE numero = ?",
            array($motivo, $usuario['login'], $numero)
        );

        $this->_log($numero, NULL, 'D', $usuario, array(
            'motivo'   => $motivo,
            'estornos' => $estornos,
            'titulos'  => 'cancelados',
        ));

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return array('ok' => FALSE, 'erro' => 'Falha ao cancelar o fechamento.');
        }

        return array('ok' => TRUE, 'erro' => NULL, 'estornos' => $estornos);
    }

    // ------------------------------------------------------------------

    public function replicar_pedido($numero, $usuario)
    {
        $numero = (int) $numero;
        $p      = $this->pedido($numero);

        if (empty($p))
        {
            return array('ok' => FALSE, 'erro' => 'Pedido nao encontrado.');
        }

        $itens = $this->itens_pedido($numero);

        if (empty($itens))
        {
            return array('ok' => FALSE, 'erro' => 'O pedido nao possui itens para replicar.');
        }

        $dados = array(
            'numero'               => 0,
            'data'                 => date('Y-m-d'),
            'hora'                 => date('H:i:s'),
            'setor_id'             => $p['setor_id'],
            'funcionario_id'       => $p['funcionario_id'],
            'fornecedor_codigo'    => $p['fornecedor_codigo'],
            'tipo'                 => $p['tipo'],
            'solicitacao_numero'   => $p['solicitacao_numero'],
            'observacao'           => trim('Replicado do pedido ' . $numero . '. ' . (string) $p['observacao']),
            'local_entrega'        => $p['local_entrega'],
            'responsavel_recebimento' => $p['responsavel_recebimento'],
            'fone_responsavel'     => $p['fone_responsavel'],
            'numero_proposta'      => $p['numero_proposta'],
            'desconto'             => 0,
            'desconto_percentual'  => $p['desconto_percentual'],
            'condicao_pagamento'   => $p['condicao_pagamento'],
            'prazo_entrega'        => $p['prazo_entrega'],
            'forma_recebimento'    => $p['forma_recebimento'],
            'documento_recebimento'=> $p['documento_recebimento'],
            'centro_custo'         => $p['centro_custo'],
            'data_vencimento'      => $p['data_vencimento'],
            'num_nf'               => NULL,
            'data_nf'              => NULL,
        );

        $novos = array();

        foreach ($itens as $item)
        {
            $novos[] = array(
                'produto_id'            => $item['produto_id'],
                'quantidade'            => $item['quantidade'],
                'valor_unitario'        => $item['valor_unitario'],
                'novo_valor_unitario'   => $item['novo_valor_unitario'],
                'desconto_percentual'   => $item['desconto_percentual'],
                'desconto_item'         => $item['desconto_item'],
                'observacao'            => $item['observacao'],
                'atualiza_preco_compra' => bd_bool($item['atualiza_preco_compra']),
            );
        }

        $r = $this->salvar_pedido($dados, $novos, $usuario);

        if ( ! $r['ok'])
        {
            return $r;
        }

        $this->_log($r['numero'], NULL, 'I', $usuario, array(
            'replicado_de' => $numero,
        ));

        return $r;
    }

    // ------------------------------------------------------------------

    public function transferir_item($origem, $destino, $produto_id, $usuario)
    {
        $origem    = (int) $origem;
        $destino   = (int) $destino;
        $produto_id = (int) $produto_id;

        if ($origem <= 0 || $destino <= 0 || $produto_id <= 0)
        {
            return array('ok' => FALSE, 'erro' => 'Informe os pedidos e o produto.');
        }

        if ($origem === $destino)
        {
            return array('ok' => FALSE, 'erro' => 'Os pedidos de origem e destino sao iguais.');
        }

        foreach (array($origem, $destino) as $n)
        {
            $p = $this->db->query(
                "SELECT numero, situacao FROM " . T_COMPRAS_PEDIDOS . " WHERE numero = ?",
                array($n)
            )->row_array();

            if (empty($p))
            {
                return array('ok' => FALSE, 'erro' => 'Pedido ' . $n . ' nao encontrado.');
            }

            if ($p['situacao'] !== 'aberto')
            {
                return array('ok' => FALSE, 'erro' => 'O pedido ' . $n . ' nao esta aberto.');
            }
        }

        $item = $this->db->query(
            "SELECT * FROM " . T_COMPRAS_ITENS . " WHERE pedido_numero = ? AND produto_id = ?",
            array($origem, $produto_id)
        )->row_array();

        if (empty($item))
        {
            return array('ok' => FALSE, 'erro' => 'Item nao encontrado no pedido de origem.');
        }

        $existe = $this->db->query(
            "SELECT 1 FROM " . T_COMPRAS_ITENS . " WHERE pedido_numero = ? AND produto_id = ?",
            array($destino, $produto_id)
        )->row_array();

        if ( ! empty($existe))
        {
            return array('ok' => FALSE, 'erro' => 'O produto ja existe no pedido de destino.');
        }

        $this->db->trans_start();

        $this->db->query(
            "UPDATE " . T_COMPRAS_ITENS . " SET pedido_numero = ? WHERE id = ?",
            array($destino, (int) $item['id'])
        );

        $this->_recalcular_totais($origem);
        $this->_recalcular_totais($destino);

        $this->_log($origem, NULL, 'A', $usuario, array(
            'transferencia' => 'saida', 'produto' => $produto_id, 'destino' => $destino,
        ));
        $this->_log($destino, NULL, 'A', $usuario, array(
            'transferencia' => 'entrada', 'produto' => $produto_id, 'origem' => $origem,
        ));

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return array('ok' => FALSE, 'erro' => 'Falha ao transferir o item.');
        }

        return array('ok' => TRUE, 'erro' => NULL);
    }

    // ------------------------------------------------------------------

    public function excluir_item($numero, $produto_id, $usuario)
    {
        $numero     = (int) $numero;
        $produto_id = (int) $produto_id;

        $p = $this->db->query(
            "SELECT numero, situacao FROM " . T_COMPRAS_PEDIDOS . " WHERE numero = ?",
            array($numero)
        )->row_array();

        if (empty($p))
        {
            return array('ok' => FALSE, 'erro' => 'Pedido nao encontrado.');
        }

        if ($p['situacao'] !== 'aberto')
        {
            return array('ok' => FALSE, 'erro' => 'Somente pedidos abertos podem ter itens excluidos.');
        }

        $item = $this->db->query(
            "SELECT * FROM " . T_COMPRAS_ITENS . " WHERE pedido_numero = ? AND produto_id = ?",
            array($numero, $produto_id)
        )->row_array();

        if (empty($item))
        {
            return array('ok' => FALSE, 'erro' => 'Item nao encontrado.');
        }

        $this->db->trans_start();

        $this->db->query(
            "DELETE FROM " . T_COMPRAS_ITENS . " WHERE pedido_numero = ? AND produto_id = ?",
            array($numero, $produto_id)
        );

        $this->_recalcular_totais($numero);

        $this->_log($numero, NULL, 'A', $usuario, array(
            'item_excluido' => $produto_id,
            'quantidade'    => (float) $item['quantidade'],
            'valor'         => (float) $item['valor_total'],
        ));

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return array('ok' => FALSE, 'erro' => 'Falha ao excluir o item.');
        }

        return array('ok' => TRUE, 'erro' => NULL);
    }

    // ==================================================================
    // Cotacoes
    // ==================================================================

    private function _filtro_cotacoes($f)
    {
        $onde   = array('1=1');
        $params = array();

        if ( ! empty($f['busca']))
        {
            $like     = '%' . mb_strtolower(trim($f['busca']), 'UTF-8') . '%';
            $onde[]   = "(c.codigo::text LIKE ? OR lower(COALESCE(c.observacao, '')) LIKE ?
                          OR lower(COALESCE(c.num_os, '')) LIKE ?
                          OR EXISTS (SELECT 1 FROM " . T_COMPRAS_COTACAO_ITENS . " ci
                                       JOIN " . T_PRODUTOS . " pr ON pr.id = ci.produto_id
                                      WHERE ci.cotacao_codigo = c.codigo
                                        AND lower(pr.descricao) LIKE ?))";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ( ! empty($f['data_de']))
        {
            $onde[]   = 'c.data_cotacao >= ?';
            $params[] = $f['data_de'];
        }

        if ( ! empty($f['data_ate']))
        {
            $onde[]   = 'c.data_cotacao <= ?';
            $params[] = $f['data_ate'];
        }

        return array(implode(' AND ', $onde), $params);
    }

    // ------------------------------------------------------------------

    public function listar_cotacoes($f = array(), $limit = NULL, $offset = NULL)
    {
        list($where, $params) = $this->_filtro_cotacoes($f);

        $sql = "SELECT c.*,
                       u.nome AS funcionario_nome,
                       COALESCE(i.itens, 0) AS qtd_itens
                  FROM " . T_COMPRAS_COTACOES . " c
                  LEFT JOIN " . T_USUARIOS . " u ON u.funcionario_id = c.funcionario_id
                  LEFT JOIN (SELECT cotacao_codigo, count(*) AS itens
                               FROM " . T_COMPRAS_COTACAO_ITENS . "
                              GROUP BY cotacao_codigo) i ON i.cotacao_codigo = c.codigo
                 WHERE {$where}
                 ORDER BY c.data_cotacao DESC, c.codigo DESC";

        if ($limit !== NULL)
        {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        return $this->db->query($sql, $params)->result_array();
    }

    // ------------------------------------------------------------------

    public function total_cotacoes($f = array())
    {
        list($where, $params) = $this->_filtro_cotacoes($f);

        $row = $this->db->query(
            "SELECT count(*) AS total FROM " . T_COMPRAS_COTACOES . " c WHERE {$where}",
            $params
        )->row_array();

        return (int) $row['total'];
    }

    // ------------------------------------------------------------------

    public function cotacao($codigo)
    {
        return $this->db->query(
            "SELECT c.*,
                    u.nome AS funcionario_nome,
                    s.nome AS solicitante_nome
               FROM " . T_COMPRAS_COTACOES . " c
               LEFT JOIN " . T_USUARIOS . " u ON u.funcionario_id = c.funcionario_id
               LEFT JOIN " . T_USUARIOS . " s ON s.funcionario_id = c.solicitante_id
              WHERE c.codigo = ?",
            array((int) $codigo)
        )->row_array();
    }

    // ------------------------------------------------------------------

    public function itens_cotacao($codigo)
    {
        return $this->db->query(
            "SELECT ci.*,
                    pr.descricao, pr.codigo AS produto_codigo, pr.unidade,
                    pr.valor_compra, pr.valor_venda,
                    f1.razao_social AS fornecedor_1_nome,
                    f2.razao_social AS fornecedor_2_nome,
                    f3.razao_social AS fornecedor_3_nome,
                    fe.razao_social AS fornecedor_escolhido_nome
               FROM " . T_COMPRAS_COTACAO_ITENS . " ci
               JOIN " . T_PRODUTOS . " pr ON pr.id = ci.produto_id
               LEFT JOIN " . T_FORNECEDORES . " f1 ON f1.codigo = ci.fornecedor_1
               LEFT JOIN " . T_FORNECEDORES . " f2 ON f2.codigo = ci.fornecedor_2
               LEFT JOIN " . T_FORNECEDORES . " f3 ON f3.codigo = ci.fornecedor_3
               LEFT JOIN " . T_FORNECEDORES . " fe ON fe.codigo = ci.fornecedor_escolhido
              WHERE ci.cotacao_codigo = ?
              ORDER BY pr.descricao",
            array((int) $codigo)
        )->result_array();
    }

    // ------------------------------------------------------------------

    public function salvar_cotacao($dados, $itens, $usuario)
    {
        $codigo = isset($dados['codigo']) ? (int) $dados['codigo'] : 0;
        $data   = $this->_data($this->_v($dados, 'data_cotacao'));

        if ($data === NULL)
        {
            return array('ok' => FALSE, 'erro' => 'Informe a data da cotacao.', 'codigo' => $codigo);
        }

        $funcionario = $this->_int($this->_v($dados, 'funcionario_id'));

        if ($funcionario === NULL)
        {
            return array('ok' => FALSE, 'erro' => 'Informe o funcionario que fez a cotacao.', 'codigo' => $codigo);
        }

        $itens = $this->_normalizar_itens_cotacao($itens);

        $vistos = array();

        foreach ($itens as $item)
        {
            if (isset($vistos[$item['produto_id']]))
            {
                return array('ok' => FALSE, 'erro' => 'Produto repetido na cotacao.', 'codigo' => $codigo);
            }

            $vistos[$item['produto_id']] = TRUE;
        }

        if ( ! $this->_produtos_existem($itens))
        {
            return array('ok' => FALSE, 'erro' => 'Um ou mais produtos informados nao existem.', 'codigo' => $codigo);
        }

        $this->db->trans_start();

        if ($codigo <= 0)
        {
            $codigo = (int) $this->numeracao->proximo('COT');

            $this->db->query(
                "INSERT INTO " . T_COMPRAS_COTACOES . "
                    (codigo, data_cotacao, num_os, solicitante_id, funcionario_id,
                     observacao, solicitacao_numero, usuario_cadastro, data_cadastro)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, now())",
                array(
                    $codigo,
                    $data,
                    $this->_texto($this->_v($dados, 'num_os'), 16),
                    $this->_int($this->_v($dados, 'solicitante_id')),
                    $funcionario,
                    $this->_texto($this->_v($dados, 'observacao')),
                    $this->_int($this->_v($dados, 'solicitacao_numero')),
                    $usuario['login'],
                )
            );
        }
        else
        {
            $this->db->query(
                "UPDATE " . T_COMPRAS_COTACOES . "
                    SET data_cotacao = ?, num_os = ?, solicitante_id = ?,
                        funcionario_id = ?, observacao = ?, solicitacao_numero = ?,
                        usuario_ultima_alteracao = ?, data_ultima_alteracao = now(),
                        updated_at = now()
                  WHERE codigo = ?",
                array(
                    $data,
                    $this->_texto($this->_v($dados, 'num_os'), 16),
                    $this->_int($this->_v($dados, 'solicitante_id')),
                    $funcionario,
                    $this->_texto($this->_v($dados, 'observacao')),
                    $this->_int($this->_v($dados, 'solicitacao_numero')),
                    $usuario['login'],
                    $codigo,
                )
            );
        }

        $this->db->query(
            "DELETE FROM " . T_COMPRAS_COTACAO_ITENS . " WHERE cotacao_codigo = ?",
            array($codigo)
        );

        foreach ($itens as $item)
        {
            $this->db->query(
                "INSERT INTO " . T_COMPRAS_COTACAO_ITENS . "
                    (cotacao_codigo, produto_id, quantidade, fornecedor_1, preco_1,
                     fornecedor_2, preco_2, fornecedor_3, preco_3,
                     fornecedor_escolhido, numero_pedido)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                array(
                    $codigo,
                    $item['produto_id'],
                    $item['quantidade'],
                    $item['fornecedor_1'],
                    $item['preco_1'],
                    $item['fornecedor_2'],
                    $item['preco_2'],
                    $item['fornecedor_3'],
                    $item['preco_3'],
                    $item['fornecedor_escolhido'],
                    $item['numero_pedido'],
                )
            );
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return array('ok' => FALSE, 'erro' => 'Falha ao gravar a cotacao.', 'codigo' => $codigo);
        }

        return array('ok' => TRUE, 'erro' => NULL, 'codigo' => $codigo, 'itens' => count($itens));
    }

    // ------------------------------------------------------------------

    public function excluir_cotacao($codigo, $usuario)
    {
        $codigo = (int) $codigo;

        $c = $this->db->query(
            "SELECT * FROM " . T_COMPRAS_COTACOES . " WHERE codigo = ?",
            array($codigo)
        )->row_array();

        if (empty($c))
        {
            return array('ok' => FALSE, 'erro' => 'Cotacao nao encontrada.');
        }

        $this->db->query("DELETE FROM " . T_COMPRAS_COTACOES . " WHERE codigo = ?", array($codigo));

        return array('ok' => TRUE, 'erro' => NULL);
    }

    // ------------------------------------------------------------------

    /**
     * Importa os itens de uma solicitacao interna para uma nova cotacao
     * (equivale a F_CONFIRMA_COTACAO_PRECO_01 do Access).
     */
    public function importar_solicitacao_cotacao($solicitacao, $usuario)
    {
        $solicitacao = (int) $solicitacao;
        $s = $this->solicitacao($solicitacao);

        if (empty($s))
        {
            return array('ok' => FALSE, 'erro' => 'Solicitacao nao encontrada.');
        }

        if ( ! empty($s['cotacao_codigo']))
        {
            return array('ok' => TRUE, 'erro' => NULL, 'codigo' => (int) $s['cotacao_codigo'], 'existente' => TRUE);
        }

        $itens = $this->itens_solicitacao($solicitacao);

        if (empty($itens))
        {
            return array('ok' => FALSE, 'erro' => 'A solicitacao nao possui itens.');
        }

        $this->db->trans_start();

        $codigo = (int) $this->numeracao->proximo('COT');

        $this->db->query(
            "INSERT INTO " . T_COMPRAS_COTACOES . "
                (codigo, data_cotacao, solicitante_id, funcionario_id, observacao,
                 solicitacao_numero, usuario_cadastro, data_cadastro)
             VALUES (?, CURRENT_DATE, ?, ?, ?, ?, ?, now())",
            array(
                $codigo,
                $s['funcionario_id'],
                $s['funcionario_id'],
                'Importado da solicitacao interna de compra no ' . $solicitacao,
                $solicitacao,
                $usuario['login'],
            )
        );

        foreach ($itens as $item)
        {
            $this->db->query(
                "INSERT INTO " . T_COMPRAS_COTACAO_ITENS . "
                    (cotacao_codigo, produto_id, quantidade)
                 VALUES (?, ?, ?)",
                array($codigo, $item['produto_id'], $item['quantidade'])
            );
        }

        $this->db->query(
            "UPDATE " . T_COMPRAS_SOLICITACOES . " SET cotacao_codigo = ?, updated_at = now() WHERE numero = ?",
            array($codigo, $solicitacao)
        );

        $this->_log(NULL, $solicitacao, 'A', $usuario, array(
            'cotacao_gerada' => $codigo,
        ));

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return array('ok' => FALSE, 'erro' => 'Falha ao importar a solicitacao.');
        }

        return array('ok' => TRUE, 'erro' => NULL, 'codigo' => $codigo, 'existente' => FALSE);
    }

    // ==================================================================
    // Solicitacoes internas
    // ==================================================================

    private function _filtro_solicitacoes($f)
    {
        $onde   = array('1=1');
        $params = array();

        if ( ! empty($f['busca']))
        {
            $like     = '%' . mb_strtolower(trim($f['busca']), 'UTF-8') . '%';
            $onde[]   = "(s.numero::text LIKE ? OR lower(COALESCE(s.observacao, '')) LIKE ?
                          OR lower(COALESCE(s.local_entrega, '')) LIKE ?
                          OR EXISTS (SELECT 1 FROM " . T_COMPRAS_SOLICITACAO_ITENS . " si
                                       JOIN " . T_PRODUTOS . " pr ON pr.id = si.produto_id
                                      WHERE si.solicitacao_numero = s.numero
                                        AND lower(pr.descricao) LIKE ?))";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ( ! empty($f['situacao']) && in_array($f['situacao'], array('aberta', 'fechada', 'cancelada'), TRUE))
        {
            $onde[]   = 's.situacao = ?';
            $params[] = $f['situacao'];
        }

        if ( ! empty($f['setor']))
        {
            $onde[]   = 's.setor_id = ?';
            $params[] = (int) $f['setor'];
        }

        if ( ! empty($f['data_de']))
        {
            $onde[]   = 's.data >= ?';
            $params[] = $f['data_de'];
        }

        if ( ! empty($f['data_ate']))
        {
            $onde[]   = 's.data <= ?';
            $params[] = $f['data_ate'];
        }

        return array(implode(' AND ', $onde), $params);
    }

    // ------------------------------------------------------------------

    public function listar_solicitacoes($f = array(), $limit = NULL, $offset = NULL)
    {
        list($where, $params) = $this->_filtro_solicitacoes($f);

        $sql = "SELECT s.*,
                       se.nome AS setor_nome,
                       u.nome AS funcionario_nome,
                       COALESCE(i.itens, 0) AS qtd_itens
                  FROM " . T_COMPRAS_SOLICITACOES . " s
                  LEFT JOIN " . T_SETORES . " se ON se.id = s.setor_id
                  LEFT JOIN " . T_USUARIOS . " u ON u.funcionario_id = s.funcionario_id
                  LEFT JOIN (SELECT solicitacao_numero, count(*) AS itens
                               FROM " . T_COMPRAS_SOLICITACAO_ITENS . "
                              GROUP BY solicitacao_numero) i ON i.solicitacao_numero = s.numero
                 WHERE {$where}
                 ORDER BY s.data DESC, s.numero DESC";

        if ($limit !== NULL)
        {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        return $this->db->query($sql, $params)->result_array();
    }

    // ------------------------------------------------------------------

    public function total_solicitacoes($f = array())
    {
        list($where, $params) = $this->_filtro_solicitacoes($f);

        $row = $this->db->query(
            "SELECT count(*) AS total FROM " . T_COMPRAS_SOLICITACOES . " s WHERE {$where}",
            $params
        )->row_array();

        return (int) $row['total'];
    }

    // ------------------------------------------------------------------

    public function solicitacao($numero)
    {
        return $this->db->query(
            "SELECT s.*,
                    se.nome AS setor_nome,
                    u.nome AS funcionario_nome,
                    uc.nome AS usuario_cadastro_nome,
                    p.numero AS pedido_gerado,
                    p.situacao AS pedido_situacao
               FROM " . T_COMPRAS_SOLICITACOES . " s
               LEFT JOIN " . T_SETORES . " se ON se.id = s.setor_id
               LEFT JOIN " . T_USUARIOS . " u ON u.funcionario_id = s.funcionario_id
               LEFT JOIN " . T_USUARIOS . " uc ON uc.login = s.usuario_cadastro
               LEFT JOIN " . T_COMPRAS_PEDIDOS . " p ON p.numero = s.pedido_numero
              WHERE s.numero = ?",
            array((int) $numero)
        )->row_array();
    }

    // ------------------------------------------------------------------

    public function itens_solicitacao($numero)
    {
        return $this->db->query(
            "SELECT si.*,
                    pr.descricao, pr.codigo AS produto_codigo, pr.unidade,
                    pr.valor_compra, pr.valor_venda,
                    COALESCE(es.quantidade, 0) AS saldo,
                    pr.estoque_minimo
               FROM " . T_COMPRAS_SOLICITACAO_ITENS . " si
               JOIN " . T_PRODUTOS . " pr ON pr.id = si.produto_id
               LEFT JOIN " . T_ESTOQUE_SALDOS . " es ON es.produto_id = si.produto_id
              WHERE si.solicitacao_numero = ?
              ORDER BY pr.descricao",
            array((int) $numero)
        )->result_array();
    }

    // ------------------------------------------------------------------

    public function salvar_solicitacao($dados, $itens, $usuario)
    {
        $numero = isset($dados['numero']) ? (int) $dados['numero'] : 0;
        $data   = $this->_data($this->_v($dados, 'data'));

        if ($data === NULL)
        {
            return array('ok' => FALSE, 'erro' => 'Informe a data da solicitacao.', 'numero' => $numero);
        }

        $itens = $this->_normalizar_itens_solicitacao($itens);

        if (empty($itens))
        {
            return array('ok' => FALSE, 'erro' => 'Inclua pelo menos um produto na solicitacao.', 'numero' => $numero);
        }

        $vistos = array();

        foreach ($itens as $item)
        {
            if (isset($vistos[$item['produto_id']]))
            {
                return array('ok' => FALSE, 'erro' => 'Produto repetido na solicitacao.', 'numero' => $numero);
            }

            $vistos[$item['produto_id']] = TRUE;
        }

        if ( ! $this->_produtos_existem($itens))
        {
            return array('ok' => FALSE, 'erro' => 'Um ou mais produtos informados nao existem.', 'numero' => $numero);
        }

        if ($numero > 0)
        {
            $s = $this->db->query(
                "SELECT * FROM " . T_COMPRAS_SOLICITACOES . " WHERE numero = ?",
                array($numero)
            )->row_array();

            if (empty($s))
            {
                return array('ok' => FALSE, 'erro' => 'Solicitacao nao encontrada.', 'numero' => $numero);
            }

            if ($s['situacao'] !== 'aberta')
            {
                return array('ok' => FALSE, 'erro' => 'Somente solicitacoes abertas podem ser alteradas.', 'numero' => $numero);
            }
        }

        $novo = ($numero <= 0);

        $this->db->trans_start();

        if ($novo)
        {
            $numero = (int) $this->numeracao->proximo('SC');

            $this->db->query(
                "INSERT INTO " . T_COMPRAS_SOLICITACOES . "
                    (numero, data, hora, setor_id, funcionario_id, situacao,
                     local_entrega, responsavel_recebimento, fone_responsavel,
                     observacao, usuario_cadastro, data_cadastro)
                 VALUES (?, ?, ?, ?, ?, 'aberta', ?, ?, ?, ?, ?, now())",
                array(
                    $numero,
                    $data,
                    $this->_hora($this->_v($dados, 'hora')) ?: date('H:i:s'),
                    $this->_int($this->_v($dados, 'setor_id')),
                    $this->_int($this->_v($dados, 'funcionario_id')),
                    $this->_texto($this->_v($dados, 'local_entrega')),
                    $this->_texto($this->_v($dados, 'responsavel_recebimento'), 80),
                    $this->_texto($this->_v($dados, 'fone_responsavel'), 20),
                    $this->_texto($this->_v($dados, 'observacao')),
                    $usuario['login'],
                )
            );
        }
        else
        {
            $this->db->query(
                "UPDATE " . T_COMPRAS_SOLICITACOES . "
                    SET data = ?, hora = ?, setor_id = ?, funcionario_id = ?,
                        local_entrega = ?, responsavel_recebimento = ?,
                        fone_responsavel = ?, observacao = ?,
                        usuario_ultima_alteracao = ?, data_ultima_alteracao = now(),
                        updated_at = now()
                  WHERE numero = ?",
                array(
                    $data,
                    $this->_hora($this->_v($dados, 'hora')) ?: date('H:i:s'),
                    $this->_int($this->_v($dados, 'setor_id')),
                    $this->_int($this->_v($dados, 'funcionario_id')),
                    $this->_texto($this->_v($dados, 'local_entrega')),
                    $this->_texto($this->_v($dados, 'responsavel_recebimento'), 80),
                    $this->_texto($this->_v($dados, 'fone_responsavel'), 20),
                    $this->_texto($this->_v($dados, 'observacao')),
                    $usuario['login'],
                    $numero,
                )
            );
        }

        $this->db->query(
            "DELETE FROM " . T_COMPRAS_SOLICITACAO_ITENS . " WHERE solicitacao_numero = ?",
            array($numero)
        );

        foreach ($itens as $item)
        {
            $this->db->query(
                "INSERT INTO " . T_COMPRAS_SOLICITACAO_ITENS . "
                    (solicitacao_numero, produto_id, quantidade, repor_estoque,
                     data_utilizacao, periodo, selecionado, exportado)
                 VALUES (?, ?, ?, ?::boolean, ?, ?, ?::boolean, ?::boolean)",
                array(
                    $numero,
                    $item['produto_id'],
                    $item['quantidade'],
                    $item['repor_estoque'] ? 'true' : 'false',
                    $item['data_utilizacao'],
                    $item['periodo'],
                    $item['selecionado'] ? 'true' : 'false',
                    'false',
                )
            );
        }

        $this->_log(NULL, $numero, $novo ? 'I' : 'A', $usuario, array(
            'data'  => $data,
            'itens' => count($itens),
        ));

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return array('ok' => FALSE, 'erro' => 'Falha ao gravar a solicitacao.', 'numero' => $numero);
        }

        return array('ok' => TRUE, 'erro' => NULL, 'numero' => $numero, 'itens' => count($itens));
    }

    // ------------------------------------------------------------------

    public function excluir_solicitacao($numero, $usuario)
    {
        $numero = (int) $numero;

        $s = $this->db->query(
            "SELECT * FROM " . T_COMPRAS_SOLICITACOES . " WHERE numero = ? FOR UPDATE",
            array($numero)
        )->row_array();

        if (empty($s))
        {
            return array('ok' => FALSE, 'erro' => 'Solicitacao nao encontrada.');
        }

        if ($s['situacao'] !== 'aberta' || ! empty($s['pedido_numero']))
        {
            return array('ok' => FALSE, 'erro' => 'Solicitacao ja gerou pedido ou nao esta aberta.');
        }

        $this->db->trans_start();

        $this->_log(NULL, $numero, 'E', $usuario, array('data' => $s['data']));

        $this->db->query("DELETE FROM " . T_COMPRAS_SOLICITACOES . " WHERE numero = ?", array($numero));

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return array('ok' => FALSE, 'erro' => 'Falha ao excluir a solicitacao.');
        }

        return array('ok' => TRUE, 'erro' => NULL);
    }

    // ------------------------------------------------------------------

    public function fechar_solicitacao($numero, $usuario)
    {
        $numero = (int) $numero;

        $s = $this->db->query(
            "SELECT * FROM " . T_COMPRAS_SOLICITACOES . " WHERE numero = ? FOR UPDATE",
            array($numero)
        )->row_array();

        if (empty($s))
        {
            return array('ok' => FALSE, 'erro' => 'Solicitacao nao encontrada.');
        }

        if ($s['situacao'] !== 'aberta')
        {
            return array('ok' => FALSE, 'erro' => 'A solicitacao nao esta aberta.');
        }

        $this->db->trans_start();

        $this->db->query(
            "UPDATE " . T_COMPRAS_SOLICITACOES . "
                SET situacao = 'fechada', usuario_fechamento = ?, data_fechamento = now(),
                    updated_at = now()
              WHERE numero = ?",
            array($usuario['login'], $numero)
        );

        $this->_log(NULL, $numero, 'F', $usuario, array());

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return array('ok' => FALSE, 'erro' => 'Falha ao fechar a solicitacao.');
        }

        return array('ok' => TRUE, 'erro' => NULL);
    }

    // ------------------------------------------------------------------

    /**
     * Gera pedido de compra a partir da solicitacao interna
     * (equivale a F_PEDIDO_COMPRA_01A do Access).
     */
    public function gerar_pedido_solicitacao($numero, $usuario, $somente_selecionados = FALSE)
    {
        $numero = (int) $numero;
        $s = $this->solicitacao($numero);

        if (empty($s))
        {
            return array('ok' => FALSE, 'erro' => 'Solicitacao nao encontrada.');
        }

        if ($s['situacao'] !== 'aberta' || ! empty($s['pedido_numero']))
        {
            return array('ok' => FALSE, 'erro' => 'A solicitacao ja foi atendida ou nao esta aberta.');
        }

        $itens = $this->itens_solicitacao($numero);
        $lista = array();

        foreach ($itens as $item)
        {
            if ($somente_selecionados && ! bd_bool($item['selecionado']))
            {
                continue;
            }

            if (bd_bool($item['exportado']))
            {
                continue;
            }

            $lista[] = array(
                'produto_id'            => $item['produto_id'],
                'quantidade'            => $item['quantidade'],
                'valor_unitario'        => $item['valor_compra'],
                'novo_valor_unitario'   => NULL,
                'desconto_percentual'   => NULL,
                'desconto_item'         => 0,
                'observacao'            => NULL,
                'atualiza_preco_compra' => FALSE,
            );
        }

        if (empty($lista))
        {
            return array('ok' => FALSE, 'erro' => 'Nenhum item pendente para gerar o pedido.');
        }

        $dados = array(
            'numero'                  => 0,
            'data'                    => date('Y-m-d'),
            'hora'                    => date('H:i:s'),
            'setor_id'                => $s['setor_id'],
            'funcionario_id'          => $s['funcionario_id'],
            'fornecedor_codigo'       => NULL,
            'tipo'                    => NULL,
            'solicitacao_numero'      => $numero,
            'observacao'              => 'Gerado da solicitacao interna ' . $numero . '. ' . (string) $s['observacao'],
            'local_entrega'           => $s['local_entrega'],
            'responsavel_recebimento' => $s['responsavel_recebimento'],
            'fone_responsavel'        => $s['fone_responsavel'],
        );

        $this->db->trans_start();

        $r = $this->salvar_pedido($dados, $lista, $usuario, FALSE, TRUE);

        if ( ! $r['ok'])
        {
            $this->db->trans_rollback();
            return $r;
        }

        $this->db->query(
            "UPDATE " . T_COMPRAS_SOLICITACOES . "
                SET pedido_numero = ?, situacao = 'fechada',
                    usuario_fechamento = ?, data_fechamento = now(), updated_at = now()
              WHERE numero = ?",
            array($r['numero'], $usuario['login'], $numero)
        );

        $this->db->query(
            "UPDATE " . T_COMPRAS_SOLICITACAO_ITENS . "
                SET exportado = 'true', updated_at = now()
              WHERE solicitacao_numero = ?
                AND EXISTS (SELECT 1 FROM " . T_COMPRAS_ITENS . " i
                             WHERE i.pedido_numero = ? AND i.produto_id = " . T_COMPRAS_SOLICITACAO_ITENS . ".produto_id)",
            array($numero, $r['numero'])
        );

        $this->_log(NULL, $numero, 'F', $usuario, array('pedido_gerado' => $r['numero']));

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return array('ok' => FALSE, 'erro' => 'Falha ao gerar o pedido.');
        }

        return array('ok' => TRUE, 'erro' => NULL, 'pedido' => $r['numero']);
    }

    // ==================================================================
    // Prazos de fornecedor
    // ==================================================================

    public function prazos($fornecedor)
    {
        return $this->db->query(
            "SELECT * FROM " . T_FORNECEDOR_PRAZOS . "
              WHERE fornecedor_codigo = ?
              ORDER BY codigo",
            array((int) $fornecedor)
        )->result_array();
    }

    // ------------------------------------------------------------------

    public function salvar_prazo($dados)
    {
        $fornecedor = $this->_int($this->_v($dados, 'fornecedor_codigo'));

        if ($fornecedor === NULL)
        {
            return array('ok' => FALSE, 'erro' => 'Informe o fornecedor.');
        }

        $codigo = $this->_int($this->_v($dados, 'codigo'));

        if ($codigo === NULL)
        {
            $row = $this->db->query(
                "SELECT COALESCE(max(codigo), 0) + 1 AS proximo
                   FROM " . T_FORNECEDOR_PRAZOS . " WHERE fornecedor_codigo = ?",
                array($fornecedor)
            )->row_array();

            $codigo = (int) $row['proximo'];
        }

        $this->db->query(
            "INSERT INTO " . T_FORNECEDOR_PRAZOS . "
                (fornecedor_codigo, codigo, forma_pagamento, prazo)
             VALUES (?, ?, ?, ?)
             ON CONFLICT (fornecedor_codigo, codigo) DO UPDATE SET
                forma_pagamento = EXCLUDED.forma_pagamento,
                prazo = EXCLUDED.prazo",
            array(
                $fornecedor,
                $codigo,
                $this->_texto($this->_v($dados, 'forma_pagamento'), 40),
                $this->_texto($this->_v($dados, 'prazo'), 60),
            )
        );

        return array('ok' => TRUE, 'erro' => NULL, 'codigo' => $codigo);
    }

    // ------------------------------------------------------------------

    public function excluir_prazo($fornecedor, $codigo)
    {
        $this->db->query(
            "DELETE FROM " . T_FORNECEDOR_PRAZOS . " WHERE fornecedor_codigo = ? AND codigo = ?",
            array((int) $fornecedor, (int) $codigo)
        );

        return array('ok' => TRUE, 'erro' => NULL);
    }

    // ==================================================================
    // Auxiliares e autocomplete
    // ==================================================================

    public function setores()
    {
        return $this->estoque_model->setores();
    }

    // ------------------------------------------------------------------

    public function dominios($tipo)
    {
        return $this->db->query(
            "SELECT codigo, nome FROM " . T_DOMINIOS . "
              WHERE tipo = ? AND ativo
              ORDER BY ordem, nome",
            array((string) $tipo)
        )->result_array();
    }

    public function funcionarios()
    {
        return $this->estoque_model->funcionarios();
    }

    public function fornecedor($codigo)
    {
        return $this->estoque_model->fornecedor($codigo);
    }

    public function buscar_produtos($termo, $limite = 25)
    {
        return $this->estoque_model->buscar_produtos($termo, $limite);
    }

    public function buscar_fornecedores($termo, $limite = 25)
    {
        return $this->estoque_model->buscar_fornecedores($termo, $limite);
    }

    // ------------------------------------------------------------------

    public function produtos_abaixo_minimo($limite = 50)
    {
        return $this->db->query(
            "SELECT pr.id, pr.codigo, pr.descricao, pr.unidade, pr.estoque_minimo,
                    pr.valor_compra, COALESCE(es.quantidade, 0) AS saldo
               FROM " . T_PRODUTOS . " pr
               LEFT JOIN " . T_ESTOQUE_SALDOS . " es ON es.produto_id = pr.id
              WHERE pr.ativo
                AND COALESCE(es.quantidade, 0) <= pr.estoque_minimo
              ORDER BY (COALESCE(es.quantidade, 0) - pr.estoque_minimo), pr.descricao
              LIMIT " . (int) $limite
        )->result_array();
    }

    // ==================================================================
    // Helpers
    // ==================================================================

    private function _v($a, $k)
    {
        return isset($a[$k]) ? $a[$k] : NULL;
    }

    // ------------------------------------------------------------------

    private function _vazio($v)
    {
        return $v === NULL || $v === '' || $v === FALSE
            || (is_string($v) && trim($v) === '');
    }

    // ------------------------------------------------------------------

    private function _int($v)
    {
        if ($this->_vazio($v))
        {
            return NULL;
        }

        return (int) $v;
    }

    // ------------------------------------------------------------------

    private function _dec($v)
    {
        if ($this->_vazio($v))
        {
            return NULL;
        }

        if (is_string($v))
        {
            $v = trim($v);
            $v = str_replace(array('R$', ' ', "\xc2\xa0"), '', $v);

            if (strpos($v, ',') !== FALSE && strpos($v, '.') !== FALSE)
            {
                $v = str_replace('.', '', $v);
            }

            $v = str_replace(',', '.', $v);
        }

        return round((float) $v, 4);
    }

    // ------------------------------------------------------------------

    private function _data($v)
    {
        if ($this->_vazio($v))
        {
            return NULL;
        }

        $v = trim((string) $v);

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $v, $m))
        {
            return ((int) $m[3] < 1901) ? NULL : $m[3] . '-' . $m[2] . '-' . $m[1];
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $v, $m))
        {
            return ((int) $m[1] < 1901) ? NULL : $m[1] . '-' . $m[2] . '-' . $m[3];
        }

        $ts = strtotime($v);

        return $ts ? date('Y-m-d', $ts) : NULL;
    }

    // ------------------------------------------------------------------

    private function _hora($v)
    {
        if ($this->_vazio($v))
        {
            return NULL;
        }

        if (preg_match('/(\d{1,2}):(\d{2})(?::(\d{2}))?/', (string) $v, $m))
        {
            return str_pad($m[1], 2, '0', STR_PAD_LEFT) . ':' . $m[2] . ':' . (isset($m[3]) && $m[3] !== '' ? $m[3] : '00');
        }

        return NULL;
    }

    // ------------------------------------------------------------------

    private function _texto($v, $limite = NULL)
    {
        if ($v === NULL)
        {
            return NULL;
        }

        $v = trim((string) $v);

        if ($v === '')
        {
            return NULL;
        }

        if ( ! mb_check_encoding($v, 'UTF-8'))
        {
            $v = @iconv('UTF-8', 'UTF-8//IGNORE', $v);
        }

        $v = preg_replace('/[\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}\x{007F}-\x{009F}]/u', ' ', $v);
        $v = trim(preg_replace('/[ ]{2,}/', ' ', $v));

        if ($v === '')
        {
            return NULL;
        }

        return $limite === NULL ? $v : mb_substr($v, 0, $limite, 'UTF-8');
    }

    // ------------------------------------------------------------------

    private function _bool($v, $padrao = FALSE)
    {
        if ($v === NULL || $v === '')
        {
            return $padrao;
        }

        if (is_bool($v))
        {
            return $v;
        }

        return in_array(mb_strtolower(trim((string) $v), 'UTF-8'), array('1', 's', 'sim', 'true', 'on', 'y', 'yes'), TRUE);
    }

    // ------------------------------------------------------------------

    private function _desconto($bruto, $percentual, $valor)
    {
        if ($percentual !== NULL && $percentual > 0)
        {
            return round($bruto * $percentual / 100, 2);
        }

        if ($valor !== NULL && $valor > 0)
        {
            return round($valor, 2);
        }

        return 0.0;
    }

    // ------------------------------------------------------------------

    /**
     * Confere se todos os produtos informados existem na tabela de produtos.
     * Evita itens "fantasma" (ids invalidos) que depois somem nas listagens.
     */
    private function _produtos_existem($itens)
    {
        if (empty($itens))
        {
            return TRUE;
        }

        $ids = array();

        foreach ($itens as $item)
        {
            $ids[] = (int) $item['produto_id'];
        }

        $ids = array_values(array_unique($ids));

        $row = $this->db->query(
            "SELECT count(*) AS total FROM " . T_PRODUTOS . "
              WHERE id IN (" . implode(',', $ids) . ")"
        )->row_array();

        return (int) $row['total'] === count($ids);
    }

    // ------------------------------------------------------------------

    private function _normalizar_itens($itens)
    {
        $saida = array();

        if ( ! is_array($itens))
        {
            return $saida;
        }

        foreach ($itens as $item)
        {
            $produto = $this->_int($this->_v($item, 'produto_id'));
            $qtde    = $this->_dec($this->_v($item, 'quantidade'));

            if ($produto === NULL || $produto <= 0 || $qtde === NULL || $qtde <= 0)
            {
                continue;
            }

            $unit = $this->_dec($this->_v($item, 'valor_unitario'));

            if ($unit === NULL)
            {
                $row = $this->db->query(
                    "SELECT valor_compra FROM " . T_PRODUTOS . " WHERE id = ?",
                    array($produto)
                )->row_array();

                $unit = $row ? (float) $row['valor_compra'] : 0;
            }

            $novo   = $this->_dec($this->_v($item, 'novo_valor_unitario'));
            $perc   = $this->_dec($this->_v($item, 'desconto_percentual'));
            $desc   = $this->_dec($this->_v($item, 'desconto_item'));
            $base   = $qtde * $unit;

            if ($novo !== NULL && $novo > 0)
            {
                $total = round($qtde * $novo, 2);
                $desc  = round($base - $total, 2);
            }
            else
            {
                if ($perc !== NULL && $perc > 0)
                {
                    $desc = round($base * $perc / 100, 2);
                }

                if ($desc === NULL)
                {
                    $desc = 0;
                }

                $total = round($base - $desc, 2);
            }

            if ($total < 0)
            {
                $total = 0;
            }

            $saida[] = array(
                'produto_id'            => $produto,
                'quantidade'            => $qtde,
                'valor_unitario'        => round($unit, 4),
                'novo_valor_unitario'   => ($novo !== NULL && $novo > 0) ? round($novo, 4) : NULL,
                'desconto_percentual'   => ($perc !== NULL && $perc > 0) ? $perc : NULL,
                'desconto_item'         => round((float) $desc, 2),
                'valor_total'           => $total,
                'observacao'            => $this->_texto($this->_v($item, 'observacao')),
                'atualiza_preco_compra' => $this->_bool($this->_v($item, 'atualiza_preco_compra')),
            );
        }

        return $saida;
    }

    // ------------------------------------------------------------------

    private function _normalizar_itens_cotacao($itens)
    {
        $saida = array();

        if ( ! is_array($itens))
        {
            return $saida;
        }

        foreach ($itens as $item)
        {
            $produto = $this->_int($this->_v($item, 'produto_id'));
            $qtde    = $this->_dec($this->_v($item, 'quantidade'));

            if ($produto === NULL || $produto <= 0 || $qtde === NULL || $qtde <= 0)
            {
                continue;
            }

            $f1 = $this->_int($this->_v($item, 'fornecedor_1'));
            $f2 = $this->_int($this->_v($item, 'fornecedor_2'));
            $f3 = $this->_int($this->_v($item, 'fornecedor_3'));
            $p1 = $this->_dec($this->_v($item, 'preco_1'));
            $p2 = $this->_dec($this->_v($item, 'preco_2'));
            $p3 = $this->_dec($this->_v($item, 'preco_3'));
            $ef = $this->_int($this->_v($item, 'fornecedor_escolhido'));

            if ($ef !== NULL && ! in_array($ef, array($f1, $f2, $f3), TRUE))
            {
                $ef = NULL;
            }

            $saida[] = array(
                'produto_id'         => $produto,
                'quantidade'         => $qtde,
                'fornecedor_1'       => $f1,
                'preco_1'            => $p1,
                'fornecedor_2'       => $f2,
                'preco_2'            => $p2,
                'fornecedor_3'       => $f3,
                'preco_3'            => $p3,
                'fornecedor_escolhido' => $ef,
                'numero_pedido'      => $this->_int($this->_v($item, 'numero_pedido')),
            );
        }

        return $saida;
    }

    // ------------------------------------------------------------------

    private function _normalizar_itens_solicitacao($itens)
    {
        $saida = array();

        if ( ! is_array($itens))
        {
            return $saida;
        }

        foreach ($itens as $item)
        {
            $produto = $this->_int($this->_v($item, 'produto_id'));
            $qtde    = $this->_dec($this->_v($item, 'quantidade'));

            if ($produto === NULL || $produto <= 0 || $qtde === NULL || $qtde <= 0)
            {
                continue;
            }

            $saida[] = array(
                'produto_id'      => $produto,
                'quantidade'      => $qtde,
                'repor_estoque'   => $this->_bool($this->_v($item, 'repor_estoque')),
                'data_utilizacao' => $this->_data($this->_v($item, 'data_utilizacao')),
                'periodo'         => $this->_texto($this->_v($item, 'periodo'), 20),
                'selecionado'     => $this->_bool($this->_v($item, 'selecionado')),
            );
        }

        return $saida;
    }

    // ------------------------------------------------------------------

    private function _preco_item($item)
    {
        $novo = isset($item['novo_valor_unitario']) ? $item['novo_valor_unitario'] : NULL;

        if ($novo !== NULL && (float) $novo > 0)
        {
            return round((float) $novo, 4);
        }

        return round((float) $item['valor_unitario'], 4);
    }

    // ------------------------------------------------------------------

    private function _normalizar_parcelas($lista, $dados, $total)
    {
        $total = round((float) $total, 2);

        if ($total <= 0)
        {
            return array('erro' => 'O pedido nao possui valor para gerar parcelas.');
        }

        $base = $this->_data($this->_v($dados, 'data_vencimento'))
             ?: $this->_data($this->_v($dados, 'data_compra'))
             ?: date('Y-m-d');

        $limpa = array();

        if (is_array($lista))
        {
            foreach ($lista as $p)
            {
                $dias = $this->_int($this->_v($p, 'dias'));
                $venc = $this->_data($this->_v($p, 'vencimento'));

                if ($dias === NULL && $venc === NULL)
                {
                    continue;
                }

                $limpa[] = array(
                    'dias'       => $dias === NULL ? 0 : $dias,
                    'vencimento' => $venc,
                );
            }
        }

        if (empty($limpa))
        {
            $limpa[] = array('dias' => 0, 'vencimento' => $base);
        }

        if (count($limpa) > 12)
        {
            return array('erro' => 'O pedido permite no maximo 12 parcelas.');
        }

        $anterior_dias = -1;
        $anterior_venc = NULL;

        foreach ($limpa as $i => $p)
        {
            if ($p['dias'] < 0)
            {
                return array('erro' => 'O prazo em dias da parcela ' . ($i + 1) . ' e invalido.');
            }

            if ($p['vencimento'] === NULL)
            {
                $p['vencimento'] = date('Y-m-d', strtotime($base . ' +' . $p['dias'] . ' days'));
            }

            if ($p['dias'] < $anterior_dias)
            {
                return array('erro' => 'O prazo em dias da parcela ' . ($i + 1) . ' nao pode ser menor que o da parcela anterior.');
            }

            if ($anterior_venc !== NULL && $p['vencimento'] < $anterior_venc)
            {
                return array('erro' => 'O vencimento da parcela ' . ($i + 1) . ' nao pode ser menor que o da parcela anterior.');
            }

            $anterior_dias = $p['dias'];
            $anterior_venc = $p['vencimento'];

            $limpa[$i] = $p;
        }

        // Rateio (mesma regra do VALPARC do Access).
        $n    = count($limpa);
        $base_valor = round($total / $n, 2);
        $soma = round($base_valor * $n, 2);
        $valores = array_fill(0, $n, $base_valor);

        if ($soma < $total)
        {
            $valores[0] = round($valores[0] + ($total - $soma), 2);
        }
        elseif ($soma > $total)
        {
            $valores[$n - 1] = round($valores[$n - 1] - ($soma - $total), 2);
        }

        if ($valores[$n - 1] <= 0)
        {
            return array('erro' => 'O valor das parcelas ficou invalido. Verifique o total do pedido.');
        }

        $saida = array();

        foreach ($limpa as $i => $p)
        {
            $saida[] = array(
                'numero_par' => $i + 1,
                'dias'       => $p['dias'],
                'vencimento' => $p['vencimento'],
                'valor'      => $valores[$i],
            );
        }

        return $saida;
    }

    // ------------------------------------------------------------------

    private function _recalcular_totais($numero)
    {
        $row = $this->db->query(
            "SELECT COALESCE(sum(valor_total), 0) AS bruto
               FROM " . T_COMPRAS_ITENS . " WHERE pedido_numero = ?",
            array((int) $numero)
        )->row_array();

        $p = $this->db->query(
            "SELECT desconto, desconto_percentual FROM " . T_COMPRAS_PEDIDOS . " WHERE numero = ?",
            array((int) $numero)
        )->row_array();

        $bruto = round((float) $row['bruto'], 2);
        $desc  = $p ? $this->_desconto($bruto, $p['desconto_percentual'], $p['desconto']) : 0;

        $this->db->query(
            "UPDATE " . T_COMPRAS_PEDIDOS . "
                SET total_bruto = ?, desconto = ?, total = ?, updated_at = now()
              WHERE numero = ?",
            array($bruto, $desc, max(0, round($bruto - $desc, 2)), (int) $numero)
        );

        return $bruto;
    }

    // ------------------------------------------------------------------

    private function _atualizar_produto($item, $numero, $data_compra, $num_nf)
    {
        $unit = round((float) $item['valor_unitario'], 4);
        $novo = isset($item['novo_valor_unitario']) ? $item['novo_valor_unitario'] : NULL;

        $custo = $this->_preco_item($item);

        $percentual = isset($item['percentual_lucro']) ? $item['percentual_lucro'] : NULL;

        if (($percentual === NULL || (float) $percentual <= 0)
            && (float) $item['preco_compra'] > 0 && (float) $item['valor_venda'] > 0)
        {
            $percentual = round((((float) $item['valor_venda'] / (float) $item['preco_compra']) - 1) * 100, 4);
        }

        $venda = NULL;

        if ($percentual !== NULL && (float) $percentual > 0)
        {
            $venda = round($unit * (1 + ((float) $percentual / 100)), 2);
        }

        $this->db->query(
            "UPDATE " . T_PRODUTOS . "
                SET valor_compra = ?, valor_compra_desconto = ?,
                    ultimo_pedido_compra = ?, data_ultima_entrada = ?,
                    ultima_nf_entrada = COALESCE(?, ultima_nf_entrada),
                    percentual_lucro = COALESCE(percentual_lucro, ?),
                    valor_venda = COALESCE(?, valor_venda),
                    updated_at = now()
              WHERE id = ?",
            array(
                $custo,
                $custo,
                (int) $numero,
                $data_compra,
                $num_nf,
                $percentual,
                $venda,
                (int) $item['produto_id'],
            )
        );
    }

    // ------------------------------------------------------------------

    private function _historico_conta($parcela, $pedido, $num_nf, $data_nf, $todas)
    {
        $texto = $parcela['numero_par'] . 'a parcela no valor de R$ '
            . number_format((float) $parcela['valor'], 2, ',', '.')
            . ' da conta a pagar referente ao pedido de compra numero ' . $pedido['numero']
            . ' datado de ' . data_br($pedido['data'])
            . ' no valor total de R$ ' . number_format((float) $pedido['total'], 2, ',', '.');

        if ($num_nf)
        {
            $texto .= ' conforme nota fiscal numero ' . $num_nf;

            if ($data_nf)
            {
                $texto .= ' datada de ' . data_br($data_nf);
            }
        }

        return $texto . '.';
    }

    // ------------------------------------------------------------------

    private function _json($valor)
    {
        if (is_array($valor))
        {
            $saida = array();

            foreach ($valor as $k => $v)
            {
                $saida[$k] = $this->_json($v);
            }

            return $saida;
        }

        if (is_string($valor))
        {
            $valor = preg_replace('/[\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}\x{007F}-\x{009F}]/u', ' ', $valor);
            return trim($valor);
        }

        return $valor;
    }

    // ------------------------------------------------------------------

    private function _log($pedido, $solicitacao, $acao, $usuario, $detalhe = array())
    {
        $detalhe = $this->_json($detalhe);

        $this->db->query(
            "INSERT INTO " . T_COMPRAS_LOG . "
                (pedido_numero, solicitacao_numero, acao, usuario, data_mov, detalhe)
             VALUES (?, ?, ?, ?, now(), ?::jsonb)",
            array(
                $pedido ? (int) $pedido : NULL,
                $solicitacao ? (int) $solicitacao : NULL,
                $acao,
                $usuario['login'],
                json_encode($detalhe, JSON_UNESCAPED_UNICODE),
            )
        );
    }
}
