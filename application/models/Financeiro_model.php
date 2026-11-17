<?php
/**
 * Financeiro_model - contas a pagar/receber, baixas, movimentos bancarios,
 * caixa, cheques, balanco, cadastros de apoio e relatorios.
 *
 * Regras principais (ver docs/10-regras-financeiro.md):
 *  - titulos de contas a pagar usam a numeracao CP; contas a receber usam CR;
 *  - baixa de titulo grava a baixa, atualiza a situacao e pode gerar movimento
 *    bancario;
 *  - estorno de baixa remove a baixa e reabre o titulo;
 *  - titulo cancelado nao pode ser baixado;
 *  - caixa: saldo de abertura + entradas - saidas = fechamento; quando o dia
 *    tem a linha "saldo anterior" dentro das entradas, o fechamento e
 *    entradas - saidas (o legado ja inclui a abertura nas entradas);
 *  - movimento bancario recalcula o saldo da conta (C soma, D subtrai).
 */
class Financeiro_model extends MY_Model
{
    /** cadastros simples: tipo => tabela, chave e colunas editaveis */
    private $cadastros = array(
        'bancos' => array(
            'tabela' => T_FIN_BANCOS, 'pk' => 'codigo',
            'colunas' => array('codigo', 'nome'),
        ),
        'agencias' => array(
            'tabela' => T_FIN_AGENCIAS, 'pk' => 'id',
            'colunas' => array('banco_codigo', 'agencia', 'nome', 'endereco', 'bairro',
                               'cep', 'cidade', 'uf', 'telefone1', 'telefone2', 'fax', 'contato'),
        ),
        'contas' => array(
            'tabela' => T_FIN_CONTAS, 'pk' => 'id',
            'colunas' => array('banco_codigo', 'agencia', 'conta', 'titular',
                               'valor_limite', 'valor_real'),
        ),
        'cartoes' => array(
            'tabela' => T_FIN_CARTOES, 'pk' => 'codigo',
            'colunas' => array('codigo', 'nome'),
        ),
        'centros' => array(
            'tabela' => T_FIN_CENTROS_CUSTO, 'pk' => 'codigo',
            'colunas' => array('codigo', 'nome'),
        ),
        'naturezas' => array(
            'tabela' => T_FIN_NATUREZAS, 'pk' => 'codigo',
            'colunas' => array('codigo', 'nome'),
        ),
        'status' => array(
            'tabela' => T_FIN_STATUS_PAGAMENTO, 'pk' => 'codigo',
            'colunas' => array('codigo', 'nome'),
        ),
        'periodicidades' => array(
            'tabela' => T_FIN_PERIODICIDADES, 'pk' => 'codigo',
            'colunas' => array('codigo', 'nome'),
        ),
        'despesas' => array(
            'tabela' => T_FIN_DESPESAS_FIXAS, 'pk' => 'codigo',
            'colunas' => array('codigo', 'nome', 'natureza_codigo', 'periodicidade',
                               'centro_custo', 'fornecedor_codigo', 'valor',
                               'dia_vencimento', 'observacao', 'ativo'),
        ),
    );

    public function __construct()
    {
        parent::__construct();
        $this->load->library('numeracao');
    }

    // ==================================================================
    // Dominios e cadastros
    // ==================================================================

    public function dominios($tipo)
    {
        return $this->db->query(
            "SELECT codigo, nome FROM " . T_DOMINIOS . "
              WHERE tipo = ? AND ativo
              ORDER BY ordem, codigo",
            array($tipo)
        )->result_array();
    }

    public function bancos()
    {
        return $this->db->query(
            "SELECT * FROM " . T_FIN_BANCOS . " WHERE ativo ORDER BY nome"
        )->result_array();
    }

    public function agencias($banco = NULL)
    {
        $sql = "SELECT a.*, b.nome AS banco_nome
                  FROM " . T_FIN_AGENCIAS . " a
                  LEFT JOIN " . T_FIN_BANCOS . " b ON b.codigo = a.banco_codigo
                 WHERE a.ativo";
        $params = array();

        if ($banco !== NULL && $banco !== '')
        {
            $sql .= " AND a.banco_codigo = ?";
            $params[] = $banco;
        }

        return $this->db->query($sql . " ORDER BY a.banco_codigo, a.agencia", $params)->result_array();
    }

    public function contas_bancarias($banco = NULL)
    {
        $sql = "SELECT c.*, b.nome AS banco_nome
                  FROM " . T_FIN_CONTAS . " c
                  LEFT JOIN " . T_FIN_BANCOS . " b ON b.codigo = c.banco_codigo
                 WHERE c.ativo";
        $params = array();

        if ($banco !== NULL && $banco !== '')
        {
            $sql .= " AND c.banco_codigo = ?";
            $params[] = $banco;
        }

        return $this->db->query($sql . " ORDER BY c.banco_codigo, c.conta", $params)->result_array();
    }

    public function cartoes()
    {
        return $this->db->query("SELECT * FROM " . T_FIN_CARTOES . " WHERE ativo ORDER BY nome")->result_array();
    }

    public function centros_custo()
    {
        return $this->db->query("SELECT * FROM " . T_FIN_CENTROS_CUSTO . " WHERE ativo ORDER BY nome")->result_array();
    }

    public function setores()
    {
        return $this->db->query(
            "SELECT id, nome FROM " . T_SETORES . " WHERE ativo ORDER BY nome"
        )->result_array();
    }

    public function naturezas()
    {
        return $this->db->query("SELECT * FROM " . T_FIN_NATUREZAS . " WHERE ativo ORDER BY nome")->result_array();
    }

    public function status_pagamento()
    {
        return $this->db->query("SELECT * FROM " . T_FIN_STATUS_PAGAMENTO . " WHERE ativo ORDER BY nome")->result_array();
    }

    public function periodicidades()
    {
        return $this->db->query("SELECT * FROM " . T_FIN_PERIODICIDADES . " WHERE ativo ORDER BY nome")->result_array();
    }

    public function despesas_fixas()
    {
        return $this->db->query(
            "SELECT d.*, c.nome AS centro_nome, f.razao_social AS fornecedor_nome
               FROM " . T_FIN_DESPESAS_FIXAS . " d
               LEFT JOIN " . T_FIN_CENTROS_CUSTO . " c ON c.codigo = d.centro_custo
               LEFT JOIN " . T_FORNECEDORES . " f ON f.codigo = d.fornecedor_codigo
              WHERE d.ativo
              ORDER BY d.nome"
        )->result_array();
    }

    public function buscar_fornecedores($termo, $limite = 20)
    {
        $termo = '%' . str_replace(array('%', '_'), array('\%', '\_'), trim((string) $termo)) . '%';

        return $this->db->query(
            "SELECT codigo, razao_social AS nome, cnpj
               FROM " . T_FORNECEDORES . "
              WHERE ativo AND (razao_social ILIKE ? OR nome_fantasia ILIKE ? OR cnpj ILIKE ?)
              ORDER BY razao_social
              LIMIT " . (int) $limite,
            array($termo, $termo, $termo)
        )->result_array();
    }

    public function buscar_clientes($termo, $limite = 20)
    {
        $termo = '%' . str_replace(array('%', '_'), array('\%', '\_'), trim((string) $termo)) . '%';

        return $this->db->query(
            "SELECT id AS codigo, nome, cpf_cnpj AS cnpj
               FROM " . T_CLIENTES . "
              WHERE nome ILIKE ? OR fantasia ILIKE ? OR cpf_cnpj ILIKE ?
              ORDER BY nome
              LIMIT " . (int) $limite,
            array($termo, $termo, $termo)
        )->result_array();
    }

    // ------------------------------------------------------------------

    public function cadastro_todos($tipo)
    {
        $cfg = $this->_cadastro($tipo);

        if ($cfg === NULL)
        {
            return array();
        }

        $ordem = $cfg['pk'] === 'codigo' ? 'codigo' : $cfg['pk'];

        if ($tipo === 'agencias')
        {
            return $this->agencias();
        }

        if ($tipo === 'contas')
        {
            return $this->contas_bancarias();
        }

        return $this->db->query(
            "SELECT * FROM " . $cfg['tabela'] . " ORDER BY " . $ordem
        )->result_array();
    }

    public function cadastro_contar($tipo)
    {
        $cfg = $this->_cadastro($tipo);

        if ($cfg === NULL)
        {
            return 0;
        }

        $row = $this->db->query(
            "SELECT count(*) AS n FROM " . $cfg['tabela']
        )->row_array();

        return (int) $row['n'];
    }

    public function cadastro_por_id($tipo, $id)
    {
        $cfg = $this->_cadastro($tipo);

        if ($cfg === NULL)
        {
            return NULL;
        }

        return $this->db->query(
            "SELECT * FROM " . $cfg['tabela'] . " WHERE " . $cfg['pk'] . " = ?",
            array($id)
        )->row_array();
    }

    public function salvar_cadastro($tipo, $dados, $id = NULL)
    {
        $cfg = $this->_cadastro($tipo);

        if ($cfg === NULL)
        {
            return $this->_erro('Cadastro invalido.');
        }

        $registro = array();

        foreach ($cfg['colunas'] as $coluna)
        {
            if (array_key_exists($coluna, $dados))
            {
                $registro[$coluna] = $this->_vazio($dados[$coluna]) ? NULL : $dados[$coluna];
            }
        }

        // Conversao de valores monetarios e booleanos dos cadastros.
        if ($tipo === 'contas')
        {
            foreach (array('valor_limite', 'valor_real') as $campo)
            {
                if (array_key_exists($campo, $registro))
                {
                    $registro[$campo] = $this->_dec($registro[$campo]);
                }
            }
        }

        if ($tipo === 'despesas')
        {
            foreach (array('natureza_codigo', 'periodicidade', 'centro_custo',
                           'fornecedor_codigo', 'dia_vencimento') as $campo)
            {
                if (array_key_exists($campo, $registro))
                {
                    $registro[$campo] = $this->_int($registro[$campo]);
                }
            }

            $valor = $this->_dec($this->_v($registro, 'valor'));
            $registro['valor'] = $valor === NULL ? 0 : $valor;
            $registro['ativo'] = ! $this->_vazio($this->_v($dados, 'ativo'));
        }

        if ($cfg['pk'] === 'codigo')
        {
            if ($id === NULL)
            {
                // O codigo e textual (ex.: bancos "237", "dm", "DMINS").
                $chave = trim((string) $this->_v($registro, 'codigo'));

                if ($chave === '')
                {
                    return $this->_erro('Informe o codigo.');
                }
            }
            else
            {
                $chave = (string) $id;
                unset($registro['codigo']);
            }

            if ($this->_vazio($this->_v($registro, 'nome')))
            {
                return $this->_erro('Informe o nome.');
            }

            $existe = $this->db->query(
                "SELECT 1 FROM " . $cfg['tabela'] . " WHERE " . $cfg['pk'] . "::text = ?",
                array($chave)
            )->row_array();

            if (empty($existe))
            {
                $registro['codigo'] = $chave;
                $this->_inserir($cfg['tabela'], $registro);
            }
            else
            {
                $this->_atualizar($cfg['tabela'], $chave, $registro, $cfg['pk']);
            }

            return $this->_ok();
        }

        // Chaves seriais (agencias/contas/despesas).
        if ($cfg['pk'] === 'id' && $tipo !== 'despesas')
        {
            if ($id === NULL)
            {
                $this->_inserir($cfg['tabela'], $registro);
            }
            else
            {
                $this->_atualizar($cfg['tabela'], $id, $registro, 'id');
            }

            return $this->_ok();
        }

        // despesas fixas usam codigo informado pelo usuario.
        $chave = $this->_int($this->_v($registro, 'codigo'));

        if ($chave === NULL)
        {
            return $this->_erro('Informe o codigo.');
        }

        unset($registro['codigo']);

        $existe = $this->db->query(
            "SELECT 1 FROM " . $cfg['tabela'] . " WHERE codigo = ?",
            array($chave)
        )->row_array();

        if (empty($existe))
        {
            $registro['codigo'] = $chave;
            $this->_inserir($cfg['tabela'], $registro);
        }
        else
        {
            $this->_atualizar($cfg['tabela'], $chave, $registro, 'codigo');
        }

        return $this->_ok();
    }

    public function excluir_cadastro($tipo, $id)
    {
        $cfg = $this->_cadastro($tipo);

        if ($cfg === NULL)
        {
            return $this->_erro('Cadastro invalido.');
        }

        $uso = $this->_cadastro_em_uso($tipo, $id);

        if ($uso !== NULL)
        {
            return $this->_erro('Nao e possivel excluir: ' . $uso . '.');
        }

        $this->db->query(
            "DELETE FROM " . $cfg['tabela'] . " WHERE " . $cfg['pk'] . " = ?",
            array($id)
        );

        return $this->_ok();
    }

    private function _cadastro($tipo)
    {
        return isset($this->cadastros[$tipo]) ? $this->cadastros[$tipo] : NULL;
    }

    private function _cadastro_em_uso($tipo, $id)
    {
        $checagens = array(
            'bancos' => array(
                array(T_FIN_AGENCIAS, 'banco_codigo', 'agencia cadastrada'),
                array(T_FIN_CONTAS, 'banco_codigo', 'conta bancaria cadastrada'),
                array(T_FIN_MOVIMENTOS, 'banco_codigo', 'movimento bancario'),
                array(T_FIN_CHEQUES, 'banco_codigo', 'cheque'),
            ),
            'contas' => array(
                array(T_FIN_MOVIMENTOS, 'conta', 'movimento bancario'),
                array(T_FIN_CHEQUES, 'conta', 'cheque'),
            ),
            'centros' => array(
                array(T_CONTAS_PAGAR, 'centro_custo', 'conta a pagar'),
                array(T_CONTAS_RECEBER, 'codigo_centro_custo', 'conta a receber'),
                array(T_FIN_DESPESAS_FIXAS, 'centro_custo', 'despesa fixa'),
            ),
            'cartoes' => array(),
            'naturezas' => array(
                array(T_FIN_DESPESAS_FIXAS, 'natureza_codigo', 'despesa fixa'),
            ),
            'periodicidades' => array(
                array(T_FIN_DESPESAS_FIXAS, 'periodicidade', 'despesa fixa'),
            ),
        );

        if ( ! isset($checagens[$tipo]))
        {
            return NULL;
        }

        foreach ($checagens[$tipo] as $c)
        {
            list($tabela, $coluna, $rotulo) = $c;
            $row = $this->db->query(
                "SELECT 1 FROM " . $tabela . " WHERE " . $coluna . " = ? LIMIT 1",
                array($id)
            )->row_array();

            if ( ! empty($row))
            {
                return $rotulo;
            }
        }

        return NULL;
    }

    // ==================================================================
    // Contas a pagar
    // ==================================================================

    public function contas_pagar($filtros = array(), $pagina = 1, $por_pagina = 30)
    {
        $params = array();
        $where = $this->_where_cp($filtros, $params);
        $offset = (max(1, (int) $pagina) - 1) * (int) $por_pagina;

        return $this->db->query(
            "SELECT p.*, f.razao_social AS fornecedor_nome, c.nome AS centro_custo_nome,
                    (SELECT count(*) FROM " . T_CONTAS_PAGAR_BAIXAS . " b
                      WHERE b.conta_pagar_id = p.id) AS qtd_baixas
               FROM " . T_CONTAS_PAGAR . " p
               LEFT JOIN " . T_FORNECEDORES . " f ON f.codigo = p.fornecedor_codigo
               LEFT JOIN " . T_FIN_CENTROS_CUSTO . " c ON c.codigo = p.centro_custo
              WHERE " . $where . "
              ORDER BY p.vencimento NULLS LAST, p.numero, p.parcela
              LIMIT " . (int) $por_pagina . " OFFSET " . (int) $offset,
            $params
        )->result_array();
    }

    public function contar_contas_pagar($filtros = array())
    {
        $params = array();
        $where = $this->_where_cp($filtros, $params);

        $row = $this->db->query(
            "SELECT count(*) AS total, COALESCE(sum(p.valor), 0) AS valor
               FROM " . T_CONTAS_PAGAR . " p
               LEFT JOIN " . T_FORNECEDORES . " f ON f.codigo = p.fornecedor_codigo
               LEFT JOIN " . T_FIN_CENTROS_CUSTO . " c ON c.codigo = p.centro_custo
              WHERE " . $where,
            $params
        )->row_array();

        return array('total' => (int) $row['total'], 'valor' => (float) $row['valor']);
    }

    public function conta_pagar($id)
    {
        return $this->db->query(
            "SELECT p.*, f.razao_social AS fornecedor_nome, f.cnpj AS fornecedor_cnpj,
                    f.fone AS fornecedor_fone, c.nome AS centro_custo_nome
               FROM " . T_CONTAS_PAGAR . " p
               LEFT JOIN " . T_FORNECEDORES . " f ON f.codigo = p.fornecedor_codigo
               LEFT JOIN " . T_FIN_CENTROS_CUSTO . " c ON c.codigo = p.centro_custo
              WHERE p.id = ?",
            array($id)
        )->row_array();
    }

    public function salvar_conta_pagar($dados, $usuario, $id = NULL, $transacao = TRUE)
    {
        if ($transacao)
        {
            $this->db->trans_begin();
        }

        try
        {
            if ($id !== NULL)
            {
                $antes = $this->conta_pagar($id);

                if (empty($antes))
                {
                    return $this->_erro('Titulo nao encontrado.', $transacao);
                }

                $registro = $this->_campos_cp($dados);
                $this->_atualizar(T_CONTAS_PAGAR, $id, $registro, 'id');

                $this->_log_alteracoes($antes, $registro, $usuario);

                if ($transacao) { $this->db->trans_commit(); }
                return $this->_ok('Titulo atualizado.', array('id' => $id, 'numero' => $antes['numero']));
            }

            $valor = $this->_dec($this->_v($dados, 'valor'));

            if ($valor === NULL || $valor < 0)
            {
                return $this->_erro('Informe o valor do titulo.', $transacao);
            }

            $parcelas = (int) $this->_v($dados, 'parcelas');
            $parcelas = max(1, min(12, $parcelas ? $parcelas : 1));
            $intervalo = (int) $this->_v($dados, 'intervalo_dias');
            $intervalo = $intervalo > 0 ? $intervalo : 30;

            $vencimento = $this->_data($this->_v($dados, 'vencimento'));
            $vencimento = $vencimento !== NULL ? $vencimento : date('Y-m-d');

            $numero = (int) $this->numeracao->proximo('CP');
            $base = floor(($valor * 100) / $parcelas) / 100;

            for ($i = 1; $i <= $parcelas; $i++)
            {
                $reg = $this->_campos_cp($dados);
                $reg['numero'] = $numero;
                $reg['parcela'] = $i;
                $reg['total_parcelas'] = $parcelas;
                $reg['valor'] = ($i === $parcelas)
                    ? round($valor - ($base * ($parcelas - 1)), 2)
                    : $base;
                $reg['vencimento'] = date('Y-m-d', strtotime($vencimento . ' +' . (($i - 1) * $intervalo) . ' days'));
                $reg['lancamento'] = $reg['lancamento'] !== NULL ? $reg['lancamento'] : date('Y-m-d');
                $reg['origem'] = 'manual';
                $reg['origem_id'] = (string) $numero;
                $reg['usuario_cadastro'] = $usuario['login'];
                $reg['data_cadastro'] = date('Y-m-d H:i:s');
                $reg['situacao'] = 1;

                $this->_inserir(T_CONTAS_PAGAR, $reg);
            }

            $this->_log_cp($numero, 1, 'I', array(
                'criacao' => array(
                    'valor' => $valor,
                    'parcelas' => $parcelas,
                    'vencimento' => $vencimento,
                    'usuario' => $usuario['login'],
                ),
            ), $usuario);

            if ($transacao) { $this->db->trans_commit(); }

            return $this->_ok('Titulo ' . $numero . ' criado.', array('numero' => $numero));
        }
        catch (Exception $e)
        {
            if ($transacao) { $this->db->trans_rollback(); }
            return $this->_erro('Erro ao salvar titulo: ' . $e->getMessage(), FALSE);
        }
    }

    public function baixar_conta_pagar($id, $dados, $usuario, $transacao = TRUE)
    {
        if ($transacao)
        {
            $this->db->trans_begin();
        }

        try
        {
            $conta = $this->conta_pagar($id);

            if (empty($conta))
            {
                return $this->_erro('Titulo nao encontrado.', $transacao);
            }

            if ((int) $conta['situacao'] === 2)
            {
                return $this->_erro('Titulo ja esta baixado.', $transacao);
            }

            if ((int) $conta['situacao'] === 3 || $this->_bool($this->_v($conta, 'cancelado')))
            {
                return $this->_erro('Titulo cancelado nao pode ser baixado.', $transacao);
            }

            $multa = max(0, (float) $this->_dec($this->_v($dados, 'multa')));
            $juros = max(0, (float) $this->_dec($this->_v($dados, 'juros')));
            $desconto = max(0, (float) $this->_dec($this->_v($dados, 'desconto')));
            $data_pagamento = $this->_data($this->_v($dados, 'data_pagamento'));
            $data_pagamento = $data_pagamento !== NULL ? $data_pagamento : date('Y-m-d');

            $valor_pago = $this->_dec($this->_v($dados, 'valor_pago'));

            if ($valor_pago === NULL)
            {
                $valor_pago = (float) $conta['valor'] + $multa + $juros - $desconto;
            }

            $valor_pago = max(0, $valor_pago);

            $this->db->query(
                "INSERT INTO " . T_CONTAS_PAGAR_BAIXAS . "
                    (conta_pagar_id, numero, parcela, data_pagamento, multa, juros,
                     desconto, valor_atualizado, valor_pago, saldo_caixa, saldo_conta,
                     conta_codigo, arquivada)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, false)
                 ON CONFLICT (numero, parcela) DO UPDATE SET
                    conta_pagar_id = EXCLUDED.conta_pagar_id,
                    data_pagamento = EXCLUDED.data_pagamento,
                    multa = EXCLUDED.multa,
                    juros = EXCLUDED.juros,
                    desconto = EXCLUDED.desconto,
                    valor_atualizado = EXCLUDED.valor_atualizado,
                    valor_pago = EXCLUDED.valor_pago,
                    saldo_caixa = EXCLUDED.saldo_caixa,
                    saldo_conta = EXCLUDED.saldo_conta,
                    conta_codigo = EXCLUDED.conta_codigo",
                array(
                    (int) $conta['id'],
                    (int) $conta['numero'],
                    (int) $conta['parcela'],
                    $data_pagamento,
                    $multa,
                    $juros,
                    $desconto,
                    (float) $conta['valor'] + $multa + $juros,
                    $valor_pago,
                    $this->_dec($this->_v($dados, 'saldo_caixa')),
                    $this->_dec($this->_v($dados, 'saldo_conta')),
                    $this->_int($this->_v($dados, 'conta_codigo')),
                )
            );

            $this->db->query(
                "UPDATE " . T_CONTAS_PAGAR . "
                    SET situacao = 2, valor_baixado = ?, data_baixa = ?, usuario_baixa = ?,
                        updated_at = now()
                  WHERE id = ?",
                array($valor_pago, $data_pagamento, $usuario['login'], (int) $conta['id'])
            );

            $this->_log_cp((int) $conta['numero'], (int) $conta['parcela'], 'A', array(
                'baixa' => array(
                    'data_pagamento' => $data_pagamento,
                    'valor_pago' => $valor_pago,
                    'multa' => $multa,
                    'juros' => $juros,
                    'desconto' => $desconto,
                    'usuario' => $usuario['login'],
                ),
            ), $usuario);

            if ( ! empty($dados['gerar_movimento']) && ! $this->_vazio($this->_v($conta, 'banco'))
                && ! $this->_vazio($this->_v($conta, 'conta')))
            {
                $r = $this->salvar_movimento_bancario(array(
                    'data' => $data_pagamento,
                    'banco_codigo' => $conta['banco'],
                    'agencia' => $conta['agencia'],
                    'conta' => $conta['conta'],
                    'tipo' => 'D',
                    'valor' => $valor_pago,
                    'historico' => 'Baixa da conta a pagar ' . $conta['numero'] . '/' . $conta['parcela'],
                    'conta_pagar_numero' => (int) $conta['numero'],
                    'conta_pagar_parcela' => (int) $conta['parcela'],
                ), $usuario, NULL, FALSE);

                if ( ! $r['ok'])
                {
                    if ($transacao) { $this->db->trans_rollback(); }
                    return $r;
                }
            }

            if ($transacao) { $this->db->trans_commit(); }

            return $this->_ok('Titulo baixado.');
        }
        catch (Exception $e)
        {
            if ($transacao) { $this->db->trans_rollback(); }
            return $this->_erro('Erro na baixa: ' . $e->getMessage(), FALSE);
        }
    }

    public function estornar_baixa_pagar($id, $usuario, $transacao = TRUE)
    {
        if ($transacao)
        {
            $this->db->trans_begin();
        }

        $conta = $this->conta_pagar($id);

        if (empty($conta))
        {
            return $this->_erro('Titulo nao encontrado.', $transacao);
        }

        $baixas = (int) $this->db->query(
            "SELECT count(*) AS n FROM " . T_CONTAS_PAGAR_BAIXAS . " WHERE conta_pagar_id = ?",
            array($id)
        )->row()->n;

        if ($baixas === 0)
        {
            return $this->_erro('Titulo nao possui baixa para estornar.', $transacao);
        }

        $this->db->query(
            "DELETE FROM " . T_CONTAS_PAGAR_BAIXAS . " WHERE conta_pagar_id = ?",
            array($id)
        );

        $this->db->query(
            "UPDATE " . T_CONTAS_PAGAR . "
                SET situacao = CASE WHEN cancelado THEN 3 ELSE 1 END,
                    valor_baixado = NULL, data_baixa = NULL, usuario_baixa = NULL,
                    updated_at = now()
              WHERE id = ?",
            array($id)
        );

        $this->_log_cp((int) $conta['numero'], (int) $conta['parcela'], 'A', array(
            'estorno_baixa' => array('usuario' => $usuario['login']),
        ), $usuario);

        if ($transacao) { $this->db->trans_commit(); }

        return $this->_ok('Baixa estornada.');
    }

    public function cancelar_conta_pagar($id, $motivo, $usuario, $transacao = TRUE)
    {
        if ($transacao)
        {
            $this->db->trans_begin();
        }

        $conta = $this->conta_pagar($id);

        if (empty($conta))
        {
            return $this->_erro('Titulo nao encontrado.', $transacao);
        }

        if ($this->_bool($this->_v($conta, 'cancelado')))
        {
            if ($transacao) { $this->db->trans_commit(); }
            return $this->_ok('Titulo ja estava cancelado.');
        }

        $this->db->query(
            "UPDATE " . T_CONTAS_PAGAR . "
                SET situacao = 3, cancelado = true, usuario_cancelamento = ?,
                    data_cancelamento = now(), updated_at = now()
              WHERE id = ?",
            array($usuario['login'], $id)
        );

        $this->_log_cp((int) $conta['numero'], (int) $conta['parcela'], 'C', array(
            'cancelamento' => array('motivo' => $motivo, 'usuario' => $usuario['login']),
        ), $usuario);

        if ($transacao) { $this->db->trans_commit(); }

        return $this->_ok('Titulo cancelado.');
    }

    public function excluir_conta_pagar($id, $transacao = TRUE)
    {
        if ($transacao)
        {
            $this->db->trans_begin();
        }

        $conta = $this->conta_pagar($id);

        if (empty($conta))
        {
            return $this->_erro('Titulo nao encontrado.', $transacao);
        }

        $baixas = (int) $this->db->query(
            "SELECT count(*) AS n FROM " . T_CONTAS_PAGAR_BAIXAS . " WHERE conta_pagar_id = ?",
            array($id)
        )->row()->n;

        if ($baixas > 0)
        {
            return $this->_erro('Estorne a baixa antes de excluir o titulo.', $transacao);
        }

        $this->db->query(
            "DELETE FROM " . T_CONTAS_PAGAR_LOG . " WHERE conta_pagar_numero = ? AND parcela = ?",
            array((int) $conta['numero'], (int) $conta['parcela'])
        );

        $this->db->query("DELETE FROM " . T_CONTAS_PAGAR . " WHERE id = ?", array($id));

        if ($transacao) { $this->db->trans_commit(); }

        return $this->_ok('Titulo excluido.');
    }

    public function baixas_conta_pagar($id)
    {
        return $this->db->query(
            "SELECT b.*,
                    COALESCE(
                        NULLIF(c.titular, ''),
                        TRIM(COALESCE(c.banco_codigo, '') || ' ' ||
                             COALESCE(c.agencia, '') || ' ' ||
                             COALESCE(c.conta, ''))
                    ) AS conta_nome
               FROM " . T_CONTAS_PAGAR_BAIXAS . " b
               LEFT JOIN " . T_FIN_CONTAS . " c ON c.id = b.conta_codigo
              WHERE b.conta_pagar_id = ?
              ORDER BY b.data_pagamento, b.id",
            array($id)
        )->result_array();
    }

    public function log_conta_pagar($numero, $parcela = NULL)
    {
        $sql = "SELECT * FROM " . T_CONTAS_PAGAR_LOG . " WHERE conta_pagar_numero = ?";
        $params = array($numero);

        if ($parcela !== NULL)
        {
            $sql .= " AND parcela = ?";
            $params[] = $parcela;
        }

        return $this->db->query($sql . " ORDER BY data DESC NULLS LAST, id DESC LIMIT 200", $params)->result_array();
    }

    private function _where_cp($f, &$params)
    {
        $where = array('1=1');

        if ( ! empty($f['busca']))
        {
            $t = '%' . trim($f['busca']) . '%';
            $where[] = "(p.numero::text ILIKE ? OR p.documento ILIKE ? OR p.historico ILIKE ? OR f.razao_social ILIKE ?)";
            $params[] = $t;
            $params[] = $t;
            $params[] = $t;
            $params[] = $t;
        }

        if ( ! empty($f['fornecedor']))
        {
            $where[] = "p.fornecedor_codigo = ?";
            $params[] = (int) $f['fornecedor'];
        }

        if ( ! empty($f['fornecedor_nome']))
        {
            $where[] = "f.razao_social ILIKE ?";
            $params[] = '%' . trim($f['fornecedor_nome']) . '%';
        }

        if (isset($f['situacao']) && $f['situacao'] !== '' && $f['situacao'] !== NULL)
        {
            if (is_numeric($f['situacao']))
            {
                $where[] = "p.situacao = ?";
                $params[] = (int) $f['situacao'];
            }
            elseif ($f['situacao'] === 'vencido')
            {
                $where[] = "p.situacao = 1 AND p.vencimento < CURRENT_DATE";
            }
            elseif ($f['situacao'] === 'aberta')
            {
                $where[] = "p.situacao = 1";
            }
            elseif ($f['situacao'] === 'baixada')
            {
                $where[] = "p.situacao = 2";
            }
            elseif ($f['situacao'] === 'cancelada')
            {
                $where[] = "p.situacao = 3";
            }
        }

        if ( ! empty($f['centro_custo']))
        {
            $where[] = "p.centro_custo = ?";
            $params[] = (int) $f['centro_custo'];
        }

        if ( ! empty($f['venc_de']))
        {
            $where[] = "p.vencimento >= ?";
            $params[] = $f['venc_de'];
        }

        if ( ! empty($f['venc_ate']))
        {
            $where[] = "p.vencimento <= ?";
            $params[] = $f['venc_ate'];
        }

        if ( ! empty($f['origem']))
        {
            $where[] = "p.origem = ?";
            $params[] = $f['origem'];
        }

        return implode(' AND ', $where);
    }

    private function _campos_cp($dados)
    {
        return array(
            'fornecedor_codigo' => $this->_int($this->_v($dados, 'fornecedor_codigo')),
            'portador_setor' => $this->_int($this->_v($dados, 'portador_setor')),
            'emissao' => $this->_data($this->_v($dados, 'emissao')),
            'lancamento' => $this->_data($this->_v($dados, 'lancamento')),
            'vencimento' => $this->_data($this->_v($dados, 'vencimento')),
            'valor' => $this->_dec($this->_v($dados, 'valor')),
            'forma_recebimento' => $this->_int($this->_v($dados, 'forma_recebimento')),
            'documento_recebimento' => $this->_int($this->_v($dados, 'documento_recebimento')),
            'centro_custo' => $this->_int($this->_v($dados, 'centro_custo')),
            'documento' => $this->_texto($this->_v($dados, 'documento'), 30),
            'historico' => $this->_texto($this->_v($dados, 'historico')),
            'banco' => $this->_texto($this->_v($dados, 'banco'), 20),
            'agencia' => $this->_texto($this->_v($dados, 'agencia'), 25),
            'conta' => $this->_texto($this->_v($dados, 'conta'), 25),
            'portador' => $this->_int($this->_v($dados, 'portador')),
            'ret_caixa' => empty($dados['ret_caixa']) ? 'false' : 'true',
            'data_caixa' => $this->_data($this->_v($dados, 'data_caixa')),
        );
    }

    private function _log_alteracoes($antes, $depois, $usuario)
    {
        $mudancas = array();
        $rotulos = array(
            'fornecedor_codigo' => 'fornecedor', 'emissao' => 'emissao',
            'lancamento' => 'lancamento', 'vencimento' => 'vencimento',
            'valor' => 'valor', 'documento' => 'documento', 'historico' => 'historico',
            'centro_custo' => 'centro_custo', 'forma_recebimento' => 'forma_recebimento',
            'documento_recebimento' => 'documento_recebimento', 'banco' => 'banco',
            'agencia' => 'agencia', 'conta' => 'conta',
        );

        foreach ($depois as $campo => $novo)
        {
            $velho = $this->_v($antes, $campo);

            if ((string) $velho !== (string) $novo)
            {
                $mudancas[$rotulos[$campo] ? $rotulos[$campo] : $campo] = array($velho, $novo);
            }
        }

        if ($mudancas)
        {
            $this->_log_cp((int) $antes['numero'], (int) $antes['parcela'], 'A', $mudancas, $usuario);
        }
    }

    private function _log_cp($numero, $parcela, $tipo, $dados, $usuario)
    {
        $json = json_encode($dados, JSON_UNESCAPED_UNICODE);

        $this->db->query(
            "INSERT INTO " . T_CONTAS_PAGAR_LOG . "
                (conta_pagar_numero, parcela, data, usuario, tipo_mov, dados)
             VALUES (?, ?, now(), ?, ?, ?::jsonb)",
            array($numero, $parcela, $usuario['login'], $tipo, $json === FALSE ? NULL : $json)
        );
    }

    // ==================================================================
    // Contas a receber
    // ==================================================================

    public function contas_receber($filtros = array(), $pagina = 1, $por_pagina = 30)
    {
        $params = array();
        $where = $this->_where_cr($filtros, $params);
        $offset = (max(1, (int) $pagina) - 1) * (int) $por_pagina;

        return $this->db->query(
            "SELECT c.*, cl.nome AS cliente_nome, cc.nome AS centro_custo_nome,
                    (SELECT count(*) FROM " . T_CONTAS_RECEBER_BAIXAS . " b
                      WHERE b.conta_codigo = c.codigo) AS qtd_baixas,
                    CASE WHEN c.cancelado THEN 'cancelada'
                         WHEN EXISTS (SELECT 1 FROM " . T_CONTAS_RECEBER_BAIXAS . " b
                                       WHERE b.conta_codigo = c.codigo) THEN 'baixada'
                         ELSE 'aberta' END AS situacao
               FROM " . T_CONTAS_RECEBER . " c
               LEFT JOIN " . T_CLIENTES . " cl ON cl.id = c.cliente_id
               LEFT JOIN " . T_FIN_CENTROS_CUSTO . " cc ON cc.codigo = c.codigo_centro_custo
              WHERE " . $where . "
              ORDER BY c.data_vencimento NULLS LAST, c.codigo, c.parcela
              LIMIT " . (int) $por_pagina . " OFFSET " . (int) $offset,
            $params
        )->result_array();
    }

    public function contar_contas_receber($filtros = array())
    {
        $params = array();
        $where = $this->_where_cr($filtros, $params);

        $row = $this->db->query(
            "SELECT count(*) AS total, COALESCE(sum(c.valor), 0) AS valor
               FROM " . T_CONTAS_RECEBER . " c
               LEFT JOIN " . T_CLIENTES . " cl ON cl.id = c.cliente_id
              WHERE " . $where,
            $params
        )->row_array();

        return array('total' => (int) $row['total'], 'valor' => (float) $row['valor']);
    }

    public function conta_receber($codigo)
    {
        return $this->db->query(
            "SELECT c.*, cl.nome AS cliente_nome, cl.cpf_cnpj AS cliente_cnpj,
                    cl.telefone AS cliente_fone, cc.nome AS centro_custo_nome
               FROM " . T_CONTAS_RECEBER . " c
               LEFT JOIN " . T_CLIENTES . " cl ON cl.id = c.cliente_id
               LEFT JOIN " . T_FIN_CENTROS_CUSTO . " cc ON cc.codigo = c.codigo_centro_custo
              WHERE c.codigo = ?",
            array($codigo)
        )->row_array();
    }

    public function salvar_conta_receber($dados, $usuario, $codigo = NULL, $transacao = TRUE)
    {
        if ($transacao)
        {
            $this->db->trans_begin();
        }

        try
        {
            if ($codigo !== NULL)
            {
                $antes = $this->conta_receber($codigo);

                if (empty($antes))
                {
                    return $this->_erro('Titulo nao encontrado.', $transacao);
                }

                $registro = $this->_campos_cr($dados);
                $this->_atualizar(T_CONTAS_RECEBER, $codigo, $registro, 'codigo');

                if ($transacao) { $this->db->trans_commit(); }
                return $this->_ok('Titulo atualizado.', array('codigo' => $codigo));
            }

            $valor = $this->_dec($this->_v($dados, 'valor'));

            if ($valor === NULL || $valor < 0)
            {
                return $this->_erro('Informe o valor do titulo.', $transacao);
            }

            $parcelas = (int) $this->_v($dados, 'parcelas');
            $parcelas = max(1, min(12, $parcelas ? $parcelas : 1));
            $intervalo = (int) $this->_v($dados, 'intervalo_dias');
            $intervalo = $intervalo > 0 ? $intervalo : 30;

            $vencimento = $this->_data($this->_v($dados, 'data_vencimento'));
            $vencimento = $vencimento !== NULL ? $vencimento : date('Y-m-d');

            $base = floor(($valor * 100) / $parcelas) / 100;
            $codigos = array();

            for ($i = 1; $i <= $parcelas; $i++)
            {
                // No legado cada parcela de conta a receber e um titulo com
                // codigo proprio (a PK e o codigo), e nao um codigo repetido.
                $codigo = (int) $this->numeracao->proximo('CR');
                $codigos[] = $codigo;

                $reg = $this->_campos_cr($dados);
                $reg['codigo'] = $codigo;
                $reg['parcela'] = $i;
                $reg['num_parcelas'] = $parcelas;
                $reg['posicao'] = $i;
                $reg['valor'] = ($i === $parcelas)
                    ? round($valor - ($base * ($parcelas - 1)), 2)
                    : $base;
                $reg['data_vencimento'] = date('Y-m-d', strtotime($vencimento . ' +' . (($i - 1) * $intervalo) . ' days'));
                $reg['data_emissao'] = $reg['data_emissao'] !== NULL ? $reg['data_emissao'] : date('Y-m-d');
                $reg['usuario_cadastro'] = $usuario['login'];
                $reg['data_cadastro'] = date('Y-m-d H:i:s');

                $this->_inserir(T_CONTAS_RECEBER, $reg);
            }

            if ($transacao) { $this->db->trans_commit(); }

            return $this->_ok(
                count($codigos) > 1
                    ? count($codigos) . ' titulos criados.'
                    : 'Titulo ' . $codigos[0] . ' criado.',
                array('codigo' => $codigos[0], 'codigos' => $codigos)
            );
        }
        catch (Exception $e)
        {
            if ($transacao) { $this->db->trans_rollback(); }
            return $this->_erro('Erro ao salvar titulo: ' . $e->getMessage(), FALSE);
        }
    }

    public function baixar_conta_receber($codigo, $dados, $usuario, $transacao = TRUE)
    {
        if ($transacao)
        {
            $this->db->trans_begin();
        }

        try
        {
            $conta = $this->conta_receber($codigo);

            if (empty($conta))
            {
                return $this->_erro('Titulo nao encontrado.', $transacao);
            }

            if ($this->_bool($this->_v($conta, 'cancelado')))
            {
                return $this->_erro('Titulo cancelado nao pode ser baixado.', $transacao);
            }

            $existe = $this->db->query(
                "SELECT 1 FROM " . T_CONTAS_RECEBER_BAIXAS . "
                  WHERE conta_codigo = ? AND parcela = ?",
                array($codigo, (int) $conta['parcela'])
            )->row_array();

            if ( ! empty($existe))
            {
                return $this->_erro('Titulo ja esta baixado.', $transacao);
            }

            $multa = max(0, (float) $this->_dec($this->_v($dados, 'multa')));
            $juros = max(0, (float) $this->_dec($this->_v($dados, 'juros')));
            $desconto = max(0, (float) $this->_dec($this->_v($dados, 'desconto')));
            $data_pagamento = $this->_data($this->_v($dados, 'data_pagamento'));
            $data_pagamento = $data_pagamento !== NULL ? $data_pagamento : date('Y-m-d');

            $valor_recebido = $this->_dec($this->_v($dados, 'valor_recebido'));

            if ($valor_recebido === NULL)
            {
                $valor_recebido = (float) $conta['valor'] + $multa + $juros - $desconto;
            }

            $valor_recebido = max(0, $valor_recebido);

            $this->db->query(
                "INSERT INTO " . T_CONTAS_RECEBER_BAIXAS . "
                    (conta_codigo, parcela, data_pagamento, multa, juros, desconto,
                     valor_atualizado, valor_recebido, arquivada)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, false)",
                array(
                    (int) $codigo,
                    (int) $conta['parcela'],
                    $data_pagamento,
                    $multa,
                    $juros,
                    $desconto,
                    (float) $conta['valor'] + $multa + $juros,
                    $valor_recebido,
                )
            );

            $this->db->query(
                "UPDATE " . T_CONTAS_RECEBER . "
                    SET usuario_baixa = ?, data_baixa = ?, usuario_alteracao = ?,
                        data_alteracao = now()
                  WHERE codigo = ?",
                array($usuario['login'], $data_pagamento, $usuario['login'], $codigo)
            );

            if ( ! empty($dados['gerar_movimento']) && ! $this->_vazio($this->_v($conta, 'banco'))
                && ! $this->_vazio($this->_v($conta, 'conta')))
            {
                $r = $this->salvar_movimento_bancario(array(
                    'data' => $data_pagamento,
                    'banco_codigo' => $conta['banco'],
                    'agencia' => $conta['agencia'],
                    'conta' => $conta['conta'],
                    'tipo' => 'C',
                    'valor' => $valor_recebido,
                    'historico' => 'Baixa da conta a receber ' . $codigo . '/' . $conta['parcela'],
                    'conta_receber_codigo' => (int) $codigo,
                    'conta_receber_parcela' => (int) $conta['parcela'],
                ), $usuario, NULL, FALSE);

                if ( ! $r['ok'])
                {
                    if ($transacao) { $this->db->trans_rollback(); }
                    return $r;
                }
            }

            if ($transacao) { $this->db->trans_commit(); }

            return $this->_ok('Titulo recebido.');
        }
        catch (Exception $e)
        {
            if ($transacao) { $this->db->trans_rollback(); }
            return $this->_erro('Erro na baixa: ' . $e->getMessage(), FALSE);
        }
    }

    public function estornar_baixa_receber($codigo, $usuario, $transacao = TRUE)
    {
        if ($transacao)
        {
            $this->db->trans_begin();
        }

        $conta = $this->conta_receber($codigo);

        if (empty($conta))
        {
            return $this->_erro('Titulo nao encontrado.', $transacao);
        }

        $n = (int) $this->db->query(
            "SELECT count(*) AS n FROM " . T_CONTAS_RECEBER_BAIXAS . " WHERE conta_codigo = ?",
            array($codigo)
        )->row()->n;

        if ($n === 0)
        {
            return $this->_erro('Titulo nao possui baixa para estornar.', $transacao);
        }

        $this->db->query(
            "DELETE FROM " . T_CONTAS_RECEBER_BAIXAS . " WHERE conta_codigo = ?",
            array($codigo)
        );

        $this->db->query(
            "UPDATE " . T_CONTAS_RECEBER . "
                SET usuario_baixa = NULL, data_baixa = NULL, usuario_alteracao = ?,
                    data_alteracao = now()
              WHERE codigo = ?",
            array($usuario['login'], $codigo)
        );

        if ($transacao) { $this->db->trans_commit(); }

        return $this->_ok('Baixa estornada.');
    }

    public function cancelar_conta_receber($codigo, $motivo, $usuario, $transacao = TRUE)
    {
        if ($transacao)
        {
            $this->db->trans_begin();
        }

        $conta = $this->conta_receber($codigo);

        if (empty($conta))
        {
            return $this->_erro('Titulo nao encontrado.', $transacao);
        }

        $this->db->query(
            "UPDATE " . T_CONTAS_RECEBER . "
                SET cancelado = true, usuario_cancelamento = ?, data_cancelamento = now(),
                    usuario_alteracao = ?, data_alteracao = now()
              WHERE codigo = ?",
            array($usuario['login'], $usuario['login'], $codigo)
        );

        if ($transacao) { $this->db->trans_commit(); }

        return $this->_ok('Titulo cancelado.');
    }

    public function excluir_conta_receber($codigo, $transacao = TRUE)
    {
        if ($transacao)
        {
            $this->db->trans_begin();
        }

        $conta = $this->conta_receber($codigo);

        if (empty($conta))
        {
            return $this->_erro('Titulo nao encontrado.', $transacao);
        }

        $n = (int) $this->db->query(
            "SELECT count(*) AS n FROM " . T_CONTAS_RECEBER_BAIXAS . " WHERE conta_codigo = ?",
            array($codigo)
        )->row()->n;

        if ($n > 0)
        {
            return $this->_erro('Estorne a baixa antes de excluir o titulo.', $transacao);
        }

        $this->db->query("DELETE FROM " . T_CONTAS_RECEBER . " WHERE codigo = ?", array($codigo));

        if ($transacao) { $this->db->trans_commit(); }

        return $this->_ok('Titulo excluido.');
    }

    public function baixas_conta_receber($codigo)
    {
        return $this->db->query(
            "SELECT * FROM " . T_CONTAS_RECEBER_BAIXAS . "
              WHERE conta_codigo = ?
              ORDER BY data_pagamento, id",
            array($codigo)
        )->result_array();
    }

    private function _where_cr($f, &$params)
    {
        $where = array('1=1');

        if ( ! empty($f['busca']))
        {
            $t = '%' . trim($f['busca']) . '%';
            $where[] = "(c.codigo::text ILIKE ? OR c.numero_documento ILIKE ? OR c.historico ILIKE ? OR cl.nome ILIKE ?)";
            $params[] = $t;
            $params[] = $t;
            $params[] = $t;
            $params[] = $t;
        }

        if ( ! empty($f['cliente']))
        {
            $where[] = "c.cliente_id = ?";
            $params[] = (int) $f['cliente'];
        }

        if ( ! empty($f['situacao']))
        {
            if ($f['situacao'] === 'aberta')
            {
                $where[] = "NOT c.cancelado AND NOT EXISTS (SELECT 1 FROM " . T_CONTAS_RECEBER_BAIXAS . " b WHERE b.conta_codigo = c.codigo)";
            }
            elseif ($f['situacao'] === 'baixada')
            {
                $where[] = "EXISTS (SELECT 1 FROM " . T_CONTAS_RECEBER_BAIXAS . " b WHERE b.conta_codigo = c.codigo)";
            }
            elseif ($f['situacao'] === 'cancelada')
            {
                $where[] = "c.cancelado";
            }
        }

        if ( ! empty($f['centro_custo']))
        {
            $where[] = "c.codigo_centro_custo = ?";
            $params[] = (int) $f['centro_custo'];
        }

        if ( ! empty($f['venc_de']))
        {
            $where[] = "c.data_vencimento >= ?";
            $params[] = $f['venc_de'];
        }

        if ( ! empty($f['venc_ate']))
        {
            $where[] = "c.data_vencimento <= ?";
            $params[] = $f['venc_ate'];
        }

        if ( ! empty($f['arquivada']))
        {
            $where[] = $f['arquivada'] === 'S' ? "c.arquivada" : "NOT c.arquivada";
        }

        return implode(' AND ', $where);
    }

    private function _campos_cr($dados)
    {
        $valor = $this->_dec($this->_v($dados, 'valor'));
        $desconto = (float) $this->_dec($this->_v($dados, 'desconto_especial'));
        $vdesp = (float) $this->_dec($this->_v($dados, 'vdesp'));

        return array(
            'data_emissao' => $this->_data($this->_v($dados, 'data_emissao')),
            'chamado_numero' => $this->_texto($this->_v($dados, 'chamado_numero'), 16),
            'numero_documento' => $this->_texto($this->_v($dados, 'numero_documento'), 20),
            'tipo' => $this->_int($this->_v($dados, 'tipo')),
            'forma_recebimento_codigo' => $this->_int($this->_v($dados, 'forma_recebimento_codigo')),
            'documento_codigo' => $this->_int($this->_v($dados, 'documento_codigo')),
            'dias' => $this->_int($this->_v($dados, 'dias')),
            'data_vencimento' => $this->_data($this->_v($dados, 'data_vencimento')),
            'valor' => $valor,
            'desconto_especial' => $desconto,
            'vdesp' => $vdesp,
            'aliq_issqn' => $this->_dec($this->_v($dados, 'aliq_issqn')),
            'viss' => (float) $this->_dec($this->_v($dados, 'viss')),
            'vliq' => $this->_dec($this->_v($dados, 'vliq')) !== NULL
                ? $this->_dec($this->_v($dados, 'vliq'))
                : max(0, (float) $valor - $desconto + $vdesp),
            'banco' => $this->_texto($this->_v($dados, 'banco'), 50),
            'agencia' => $this->_texto($this->_v($dados, 'agencia'), 25),
            'conta' => $this->_texto($this->_v($dados, 'conta'), 20),
            'cliente_id' => $this->_int($this->_v($dados, 'cliente_id')),
            'portador_codigo' => $this->_int($this->_v($dados, 'portador_codigo')),
            'num_parcelas' => $this->_int($this->_v($dados, 'num_parcelas')) ?: 1,
            'posicao' => $this->_int($this->_v($dados, 'posicao')) ?: 1,
            'historico' => $this->_texto($this->_v($dados, 'historico')),
            'codigo_centro_custo' => $this->_int($this->_v($dados, 'codigo_centro_custo')),
            'ret_caixa' => empty($dados['ret_caixa']) ? 'false' : 'true',
            'numero_nota_fiscal' => $this->_texto($this->_v($dados, 'numero_nota_fiscal'), 30),
        );
    }

    // ==================================================================
    // Movimentos bancarios
    // ==================================================================

    public function movimentos_bancarios($filtros = array(), $pagina = 1, $por_pagina = 30)
    {
        $params = array();
        $where = $this->_where_mov($filtros, $params);
        $offset = (max(1, (int) $pagina) - 1) * (int) $por_pagina;

        return $this->db->query(
            "SELECT * FROM " . T_FIN_MOVIMENTOS . "
              WHERE " . $where . "
              ORDER BY data DESC, numero DESC
              LIMIT " . (int) $por_pagina . " OFFSET " . (int) $offset,
            $params
        )->result_array();
    }

    public function contar_movimentos_bancarios($filtros = array())
    {
        $params = array();
        $where = $this->_where_mov($filtros, $params);

        $row = $this->db->query(
            "SELECT count(*) AS total,
                    COALESCE(sum(CASE WHEN tipo = 'C' THEN valor ELSE -valor END), 0) AS liquido
               FROM " . T_FIN_MOVIMENTOS . " WHERE " . $where,
            $params
        )->row_array();

        return array('total' => (int) $row['total'], 'liquido' => (float) $row['liquido']);
    }

    public function movimento_bancario($id)
    {
        return $this->db->query(
            "SELECT * FROM " . T_FIN_MOVIMENTOS . " WHERE id = ?",
            array($id)
        )->row_array();
    }

    public function salvar_movimento_bancario($dados, $usuario, $id = NULL, $transacao = TRUE)
    {
        if ($transacao)
        {
            $this->db->trans_begin();
        }

        try
        {
            $data = $this->_data($this->_v($dados, 'data'));
            $data = $data !== NULL ? $data : date('Y-m-d');
            $tipo = mb_strtoupper((string) $this->_texto($this->_v($dados, 'tipo')));
            $tipo = $tipo === 'C' ? 'C' : 'D';
            $valor = (float) $this->_dec($this->_v($dados, 'valor'));

            if ($valor <= 0)
            {
                return $this->_erro('Informe um valor maior que zero.', $transacao);
            }

            $banco = $this->_texto($this->_v($dados, 'banco_codigo'), 20);
            $conta = $this->_texto($this->_v($dados, 'conta'), 25);

            if ($id === NULL)
            {
                $numero = $this->_int($this->_v($dados, 'numero'));

                if ($numero === NULL)
                {
                    $numero = (int) $this->numeracao->proximo('MB');
                }

                $saldo_anterior = $this->saldo_conta($banco, $conta, $data);

                $registro = array(
                    'numero' => $numero,
                    'data' => $data,
                    'banco_codigo' => $banco,
                    'agencia' => $this->_texto($this->_v($dados, 'agencia'), 25),
                    'conta' => $conta,
                    'tipo' => $tipo,
                    'valor' => $valor,
                    'historico' => $this->_texto($this->_v($dados, 'historico')),
                    'conta_pagar_numero' => $this->_int($this->_v($dados, 'conta_pagar_numero')),
                    'conta_pagar_parcela' => $this->_int($this->_v($dados, 'conta_pagar_parcela')),
                    'conta_receber_codigo' => $this->_int($this->_v($dados, 'conta_receber_codigo')),
                    'conta_receber_parcela' => $this->_int($this->_v($dados, 'conta_receber_parcela')),
                    'saldo_anterior' => $saldo_anterior,
                    'saldo_atual' => $saldo_anterior + ($tipo === 'C' ? $valor : -$valor),
                    'usuario_cadastro' => $usuario['login'],
                    'data_cadastro' => date('Y-m-d H:i:s'),
                );

                $this->_inserir(T_FIN_MOVIMENTOS, $registro);
            }
            else
            {
                $antes = $this->movimento_bancario($id);

                if (empty($antes))
                {
                    return $this->_erro('Movimento nao encontrado.', $transacao);
                }

                $saldo_anterior = $antes['saldo_anterior'] !== NULL
                    ? (float) $antes['saldo_anterior']
                    : $this->saldo_conta($banco, $conta, $data);

                $registro = array(
                    'data' => $data,
                    'banco_codigo' => $banco,
                    'agencia' => $this->_texto($this->_v($dados, 'agencia'), 25),
                    'conta' => $conta,
                    'tipo' => $tipo,
                    'valor' => $valor,
                    'historico' => $this->_texto($this->_v($dados, 'historico')),
                    'conta_pagar_numero' => $this->_int($this->_v($dados, 'conta_pagar_numero')),
                    'conta_pagar_parcela' => $this->_int($this->_v($dados, 'conta_pagar_parcela')),
                    'conta_receber_codigo' => $this->_int($this->_v($dados, 'conta_receber_codigo')),
                    'conta_receber_parcela' => $this->_int($this->_v($dados, 'conta_receber_parcela')),
                    'saldo_anterior' => $saldo_anterior,
                    'saldo_atual' => $saldo_anterior + ($tipo === 'C' ? $valor : -$valor),
                    'usuario_alteracao' => $usuario['login'],
                    'data_alteracao' => date('Y-m-d H:i:s'),
                );

                $this->_atualizar(T_FIN_MOVIMENTOS, $id, $registro, 'id');
            }

            if ($transacao) { $this->db->trans_commit(); }

            return $this->_ok('Movimento gravado.');
        }
        catch (Exception $e)
        {
            if ($transacao) { $this->db->trans_rollback(); }
            return $this->_erro('Erro no movimento: ' . $e->getMessage(), FALSE);
        }
    }

    public function excluir_movimento_bancario($id)
    {
        $this->db->query("DELETE FROM " . T_FIN_MOVIMENTOS . " WHERE id = ?", array($id));
        return $this->_ok('Movimento excluido.');
    }

    public function saldo_conta($banco, $conta, $ate = NULL)
    {
        $sql = "SELECT saldo_atual FROM " . T_FIN_MOVIMENTOS . "
                 WHERE banco_codigo IS NOT DISTINCT FROM ? AND conta IS NOT DISTINCT FROM ?";
        $params = array($banco, $conta);

        if ( ! $this->_vazio($ate))
        {
            $sql .= " AND data <= ?";
            $params[] = $ate;
        }

        $row = $this->db->query($sql . " ORDER BY data DESC, numero DESC LIMIT 1", $params)->row_array();

        if ( ! empty($row) && $row['saldo_atual'] !== NULL)
        {
            return (float) $row['saldo_atual'];
        }

        $sql = "SELECT COALESCE(sum(CASE WHEN tipo = 'C' THEN valor ELSE -valor END), 0) AS saldo
                  FROM " . T_FIN_MOVIMENTOS . "
                 WHERE banco_codigo IS NOT DISTINCT FROM ? AND conta IS NOT DISTINCT FROM ?";
        $params = array($banco, $conta);

        if ( ! $this->_vazio($ate))
        {
            $sql .= " AND data <= ?";
            $params[] = $ate;
        }

        $row = $this->db->query($sql, $params)->row_array();

        return (float) $row['saldo'];
    }

    private function _where_mov($f, &$params)
    {
        $where = array('1=1');

        if ( ! empty($f['busca']))
        {
            $t = '%' . trim($f['busca']) . '%';
            $where[] = "(numero::text ILIKE ? OR historico ILIKE ?)";
            $params[] = $t;
            $params[] = $t;
        }

        if ( ! empty($f['banco']))
        {
            $where[] = "banco_codigo = ?";
            $params[] = $f['banco'];
        }

        if ( ! empty($f['conta']))
        {
            $where[] = "conta = ?";
            $params[] = $f['conta'];
        }

        if ( ! empty($f['tipo']))
        {
            $where[] = "tipo = ?";
            $params[] = strtoupper($f['tipo']);
        }

        if ( ! empty($f['data_de']))
        {
            $where[] = "data >= ?";
            $params[] = $f['data_de'];
        }

        if ( ! empty($f['data_ate']))
        {
            $where[] = "data <= ?";
            $params[] = $f['data_ate'];
        }

        if ( ! empty($f['vinculo']))
        {
            if ($f['vinculo'] === 'cp')
            {
                $where[] = "conta_pagar_numero IS NOT NULL";
            }
            elseif ($f['vinculo'] === 'cr')
            {
                $where[] = "conta_receber_codigo IS NOT NULL";
            }
            elseif ($f['vinculo'] === 'sem')
            {
                $where[] = "conta_pagar_numero IS NULL AND conta_receber_codigo IS NULL";
            }
        }

        return implode(' AND ', $where);
    }

    // ==================================================================
    // Caixa
    // ==================================================================

    public function caixa_dias($filtros = array(), $pagina = 1, $por_pagina = 30)
    {
        $params = array();
        $where = $this->_where_caixa($filtros, $params);
        $offset = (max(1, (int) $pagina) - 1) * (int) $por_pagina;

        return $this->db->query(
            "SELECT * FROM " . T_FIN_CAIXA_DIARIO . "
              WHERE " . $where . "
              ORDER BY data DESC
              LIMIT " . (int) $por_pagina . " OFFSET " . (int) $offset,
            $params
        )->result_array();
    }

    public function contar_caixa_dias($filtros = array())
    {
        $params = array();
        $where = $this->_where_caixa($filtros, $params);

        $row = $this->db->query(
            "SELECT count(*) AS total FROM " . T_FIN_CAIXA_DIARIO . " WHERE " . $where,
            $params
        )->row_array();

        return (int) $row['total'];
    }

    public function caixa_dia($data)
    {
        return $this->db->query(
            "SELECT * FROM " . T_FIN_CAIXA_DIARIO . " WHERE data = ?",
            array($data)
        )->row_array();
    }

    public function caixa_movimentos($data)
    {
        return $this->db->query(
            "SELECT * FROM " . T_FIN_CAIXA_MOVIMENTOS . "
              WHERE data = ? ORDER BY numero",
            array($data)
        )->result_array();
    }

    public function caixa_movimento($id)
    {
        return $this->db->query(
            "SELECT * FROM " . T_FIN_CAIXA_MOVIMENTOS . " WHERE id = ?",
            array($id)
        )->row_array();
    }

    public function salvar_caixa_movimento($dados, $usuario, $id = NULL, $transacao = TRUE)
    {
        if ($transacao)
        {
            $this->db->trans_begin();
        }

        try
        {
            $data = $this->_data($this->_v($dados, 'data'));
            $data = $data !== NULL ? $data : date('Y-m-d');

            $entrada = (float) $this->_dec($this->_v($dados, 'valor_entrada'));
            $saida = (float) $this->_dec($this->_v($dados, 'valor_saida'));
            $entrada = max(0, $entrada);
            $saida = max(0, $saida);

            if ($entrada <= 0 && $saida <= 0)
            {
                return $this->_erro('Informe o valor de entrada ou de saida.', $transacao);
            }

            $registro = array(
                'data' => $data,
                'setor' => $this->_int($this->_v($dados, 'setor')),
                'documento' => $this->_texto($this->_v($dados, 'documento'), 30),
                'historico' => $this->_texto($this->_v($dados, 'historico')),
                'valor_entrada' => $entrada,
                'valor_saida' => $saida,
                'chamado_numero' => $this->_texto($this->_v($dados, 'chamado_numero'), 16),
                'forma_recebimento' => $this->_int($this->_v($dados, 'forma_recebimento')),
                'documento_recebimento' => $this->_int($this->_v($dados, 'documento_recebimento')),
            );

            if ($id === NULL)
            {
                $row = $this->db->query(
                    "SELECT COALESCE(MAX(numero), 0) + 1 AS proximo
                       FROM " . T_FIN_CAIXA_MOVIMENTOS . " WHERE data = ?",
                    array($data)
                )->row_array();

                $registro['numero'] = (int) $row['proximo'];
                $registro['usuario_cadastro'] = $usuario['login'];
                $registro['data_cadastro'] = date('Y-m-d H:i:s');

                $this->_inserir(T_FIN_CAIXA_MOVIMENTOS, $registro);
            }
            else
            {
                $registro['usuario_alteracao'] = $usuario['login'];
                $registro['data_alteracao'] = date('Y-m-d H:i:s');

                $this->_atualizar(T_FIN_CAIXA_MOVIMENTOS, $id, $registro, 'id');
            }

            $this->recalcular_caixa($data);

            if ($transacao) { $this->db->trans_commit(); }

            return $this->_ok('Movimento de caixa gravado.');
        }
        catch (Exception $e)
        {
            if ($transacao) { $this->db->trans_rollback(); }
            return $this->_erro('Erro no caixa: ' . $e->getMessage(), FALSE);
        }
    }

    public function excluir_caixa_movimento($id)
    {
        $mov = $this->db->query(
            "SELECT * FROM " . T_FIN_CAIXA_MOVIMENTOS . " WHERE id = ?",
            array($id)
        )->row_array();

        if (empty($mov))
        {
            return $this->_erro('Movimento nao encontrado.');
        }

        $this->db->query("DELETE FROM " . T_FIN_CAIXA_MOVIMENTOS . " WHERE id = ?", array($id));
        $this->recalcular_caixa($mov['data']);

        return $this->_ok('Movimento excluido.');
    }

    public function fechar_caixa($data, $usuario)
    {
        $this->recalcular_caixa($data);

        $this->db->query(
            "UPDATE " . T_FIN_CAIXA_DIARIO . "
                SET fechado = true, updated_at = now()
              WHERE data = ?",
            array($data)
        );

        $dia = $this->caixa_dia($data);

        return $this->_ok('Caixa fechado.', $dia);
    }

    public function reabrir_caixa($data)
    {
        $this->db->query(
            "UPDATE " . T_FIN_CAIXA_DIARIO . "
                SET fechado = false, updated_at = now()
              WHERE data = ?",
            array($data)
        );

        return $this->_ok('Caixa reaberto.');
    }

    public function recalcular_caixa($data)
    {
        $dia = $this->caixa_dia($data);

        if ( ! empty($dia))
        {
            $abertura = (float) $dia['saldo_anterior'];
        }
        else
        {
            $anterior = $this->db->query(
                "SELECT saldo_atual FROM " . T_FIN_CAIXA_DIARIO . "
                  WHERE data < ? ORDER BY data DESC LIMIT 1",
                array($data)
            )->row_array();

            $abertura = empty($anterior) ? 0.0 : (float) $anterior['saldo_atual'];
        }

        $tot = $this->db->query(
            "SELECT COALESCE(sum(valor_entrada), 0) AS entradas,
                    COALESCE(sum(valor_saida), 0) AS saidas,
                    COALESCE(bool_or(historico ILIKE '%SALDO ANTERIOR%'), false) AS tem_abertura
               FROM " . T_FIN_CAIXA_MOVIMENTOS . " WHERE data = ?",
            array($data)
        )->row_array();

        $entradas = (float) $tot['entradas'];
        $saidas = (float) $tot['saidas'];
        $tem_abertura = ($tot['tem_abertura'] === TRUE || $tot['tem_abertura'] === 't');

        $fechamento = $tem_abertura
            ? $entradas - $saidas
            : $abertura + $entradas - $saidas;

        if (empty($dia))
        {
            $this->db->query(
                "INSERT INTO " . T_FIN_CAIXA_DIARIO . "
                    (data, saldo_anterior, saldo_atual, total_entradas, total_saidas, fechado)
                 VALUES (?, ?, ?, ?, ?, false)",
                array($data, $abertura, $fechamento, $entradas, $saidas)
            );
        }
        else
        {
            $this->db->query(
                "UPDATE " . T_FIN_CAIXA_DIARIO . "
                    SET saldo_anterior = ?, saldo_atual = ?, total_entradas = ?,
                        total_saidas = ?, updated_at = now()
                  WHERE data = ?",
                array($abertura, $fechamento, $entradas, $saidas, $data)
            );
        }
    }

    public function caixa_saldo_atual()
    {
        $row = $this->db->query(
            "SELECT saldo_atual FROM " . T_FIN_CAIXA_DIARIO . " ORDER BY data DESC LIMIT 1"
        )->row_array();

        return empty($row) ? 0.0 : (float) $row['saldo_atual'];
    }

    private function _where_caixa($f, &$params)
    {
        $where = array('1=1');

        if ( ! empty($f['data_de']))
        {
            $where[] = "data >= ?";
            $params[] = $f['data_de'];
        }

        if ( ! empty($f['data_ate']))
        {
            $where[] = "data <= ?";
            $params[] = $f['data_ate'];
        }

        if (isset($f['fechado']) && $f['fechado'] !== '' && $f['fechado'] !== NULL)
        {
            $where[] = $f['fechado'] === 'S' ? "fechado" : "NOT COALESCE(fechado, false)";
        }

        return implode(' AND ', $where);
    }

    // ==================================================================
    // Cheques
    // ==================================================================

    public function cheques($filtros = array(), $pagina = 1, $por_pagina = 30)
    {
        $params = array();
        $where = $this->_where_cheque($filtros, $params);
        $offset = (max(1, (int) $pagina) - 1) * (int) $por_pagina;

        return $this->db->query(
            "SELECT * FROM " . T_FIN_CHEQUES . " WHERE " . $where . "
              ORDER BY data_emissao DESC NULLS LAST, id DESC
              LIMIT " . (int) $por_pagina . " OFFSET " . (int) $offset,
            $params
        )->result_array();
    }

    public function contar_cheques($filtros = array())
    {
        $params = array();
        $where = $this->_where_cheque($filtros, $params);

        $row = $this->db->query(
            "SELECT count(*) AS total, COALESCE(sum(valor), 0) AS valor
               FROM " . T_FIN_CHEQUES . " WHERE " . $where,
            $params
        )->row_array();

        return array('total' => (int) $row['total'], 'valor' => (float) $row['valor']);
    }

    public function cheque($id)
    {
        return $this->db->query("SELECT * FROM " . T_FIN_CHEQUES . " WHERE id = ?", array($id))->row_array();
    }

    public function salvar_cheque($dados, $id = NULL)
    {
        $registro = array(
            'numero' => $this->_texto($this->_v($dados, 'numero'), 20),
            'banco_codigo' => $this->_texto($this->_v($dados, 'banco_codigo'), 20),
            'agencia' => $this->_texto($this->_v($dados, 'agencia'), 25),
            'conta' => $this->_texto($this->_v($dados, 'conta'), 25),
            'valor' => (float) $this->_dec($this->_v($dados, 'valor')),
            'favorecido' => $this->_texto($this->_v($dados, 'favorecido'), 120),
            'data_emissao' => $this->_data($this->_v($dados, 'data_emissao')),
            'data_vencimento' => $this->_data($this->_v($dados, 'data_vencimento')),
            'data_compensacao' => $this->_data($this->_v($dados, 'data_compensacao')),
            'observacao' => $this->_texto($this->_v($dados, 'observacao')),
            'situacao' => $this->_int($this->_v($dados, 'situacao')) ?: 1,
        );

        if ($id === NULL)
        {
            $this->_inserir(T_FIN_CHEQUES, $registro);
        }
        else
        {
            $this->_atualizar(T_FIN_CHEQUES, $id, $registro, 'id');
        }

        return $this->_ok('Cheque gravado.');
    }

    public function excluir_cheque($id)
    {
        $this->db->query("DELETE FROM " . T_FIN_CHEQUES . " WHERE id = ?", array($id));
        return $this->_ok('Cheque excluido.');
    }

    public function compensar_cheque($id, $data)
    {
        $this->db->query(
            "UPDATE " . T_FIN_CHEQUES . "
                SET situacao = 2, data_compensacao = ?, updated_at = now()
              WHERE id = ?",
            array($data, $id)
        );

        return $this->_ok('Cheque compensado.');
    }

    private function _where_cheque($f, &$params)
    {
        $where = array('1=1');

        if ( ! empty($f['busca']))
        {
            $t = '%' . trim($f['busca']) . '%';
            $where[] = "(numero ILIKE ? OR favorecido ILIKE ?)";
            $params[] = $t;
            $params[] = $t;
        }

        if ( ! empty($f['situacao']))
        {
            $where[] = "situacao = ?";
            $params[] = (int) $f['situacao'];
        }

        if ( ! empty($f['banco']))
        {
            $where[] = "banco_codigo = ?";
            $params[] = $f['banco'];
        }

        return implode(' AND ', $where);
    }

    // ==================================================================
    // Balanco
    // ==================================================================

    public function balanco($filtros = array(), $pagina = 1, $por_pagina = 30)
    {
        $params = array();
        $where = array('1=1');

        if ( ! empty($filtros['de']))
        {
            $where[] = "data_inicio >= ?";
            $params[] = $filtros['de'];
        }

        if ( ! empty($filtros['ate']))
        {
            $where[] = "data_fim <= ?";
            $params[] = $filtros['ate'];
        }

        $offset = (max(1, (int) $pagina) - 1) * (int) $por_pagina;

        return $this->db->query(
            "SELECT * FROM " . T_FIN_BALANCO . " WHERE " . implode(' AND ', $where) . "
              ORDER BY data_inicio DESC, id DESC
              LIMIT " . (int) $por_pagina . " OFFSET " . (int) $offset,
            $params
        )->result_array();
    }

    public function contar_balanco($filtros = array())
    {
        $params = array();
        $where = array('1=1');

        if ( ! empty($filtros['de']))
        {
            $where[] = "data_inicio >= ?";
            $params[] = $filtros['de'];
        }

        if ( ! empty($filtros['ate']))
        {
            $where[] = "data_fim <= ?";
            $params[] = $filtros['ate'];
        }

        $row = $this->db->query(
            "SELECT count(*) AS total FROM " . T_FIN_BALANCO . " WHERE " . implode(' AND ', $where),
            $params
        )->row_array();

        return (int) $row['total'];
    }

    public function gerar_balanco($inicio, $fim)
    {
        if ($this->_vazio($inicio) || $this->_vazio($fim))
        {
            return $this->_erro('Informe o periodo.');
        }

        // Contas a receber faturadas no periodo.
        $cr = $this->db->query(
            "SELECT COALESCE(sum(valor), 0) AS bruto,
                    COALESCE(sum(viss), 0) AS iss,
                    COALESCE(sum(desconto_especial), 0) AS desconto
               FROM " . T_CONTAS_RECEBER . "
              WHERE NOT cancelado AND data_emissao BETWEEN ? AND ?",
            array($inicio, $fim)
        )->row_array();

        // Contas a receber ainda nao faturadas.
        $crf = $this->db->query(
            "SELECT COALESCE(sum(valor), 0) AS bruto,
                    COALESCE(sum(viss), 0) AS iss
               FROM " . T_CONTAS_RECEBER . "
              WHERE NOT cancelado AND NOT faturado
                AND data_vencimento BETWEEN ? AND ?",
            array($inicio, $fim)
        )->row_array();

        // Contas a pagar baixadas no periodo.
        $cp = $this->db->query(
            "SELECT COALESCE(sum(b.valor_pago), 0) AS bruto,
                    COALESCE(sum(b.juros), 0) AS juros,
                    COALESCE(sum(b.multa), 0) AS multa,
                    COALESCE(sum(b.desconto), 0) AS desconto
               FROM " . T_CONTAS_PAGAR_BAIXAS . " b
               JOIN " . T_CONTAS_PAGAR . " p ON p.id = b.conta_pagar_id
              WHERE b.data_pagamento BETWEEN ? AND ? AND NOT p.cancelado",
            array($inicio, $fim)
        )->row_array();

        // Contas a pagar em aberto no periodo.
        $cpf = $this->db->query(
            "SELECT COALESCE(sum(valor), 0) AS bruto
               FROM " . T_CONTAS_PAGAR . "
              WHERE situacao = 1 AND vencimento BETWEEN ? AND ?",
            array($inicio, $fim)
        )->row_array();

        $bruto_faturado = (float) $cr['bruto'];
        $iss_faturado = (float) $cr['iss'];
        $liquido_faturado = $bruto_faturado - $iss_faturado;

        $bruto_pago = (float) $cp['bruto'];
        $juros_pago = (float) $cp['juros'];
        $multa_pago = (float) $cp['multa'];
        $desconto_pago = (float) $cp['desconto'];
        $principal_pago = $bruto_pago - $juros_pago - $multa_pago + $desconto_pago;

        $this->db->query(
            "INSERT INTO " . T_FIN_BALANCO . "
                (data_inicio, data_fim, mes_ano, bruto_faturado, iss_faturado,
                 multa_faturado, juros_faturado, desconto_faturado, liquido_faturado,
                 bruto_faturar, iss_faturar, liquido_faturar, valor_princ_pago,
                 juros_pago, multa_pago, desconto_pago, bruto_pago, valor_princ_pagar,
                 juros_pagar, multa_pagar, bruto_pagar)
             VALUES (?, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?)",
            array(
                $inicio, $fim, date('m/Y', strtotime($inicio)),
                $bruto_faturado, $iss_faturado,
                (float) $cr['desconto'], $liquido_faturado,
                (float) $crf['bruto'], (float) $crf['iss'],
                (float) $crf['bruto'] - (float) $crf['iss'],
                $principal_pago, $juros_pago, $multa_pago, $desconto_pago, $bruto_pago,
                (float) $cpf['bruto'], (float) $cpf['bruto'],
            )
        );

        return $this->_ok('Balanco gerado.');
    }

    public function excluir_balanco($id)
    {
        $this->db->query("DELETE FROM " . T_FIN_BALANCO . " WHERE id = ?", array($id));
        return $this->_ok('Balanco excluido.');
    }

    // ==================================================================
    // Indicadores e relatorios
    // ==================================================================

    public function resumo()
    {
        $cp = $this->db->query(
            "SELECT count(*) FILTER (WHERE situacao = 1) AS abertas,
                    COALESCE(sum(valor) FILTER (WHERE situacao = 1), 0) AS valor_aberto,
                    COALESCE(sum(valor) FILTER (WHERE situacao = 1 AND vencimento < CURRENT_DATE), 0) AS valor_vencido,
                    COALESCE(sum(valor_baixado) FILTER (WHERE situacao = 2 AND data_baixa >= date_trunc('month', CURRENT_DATE)), 0) AS pago_mes
               FROM " . T_CONTAS_PAGAR
        )->row_array();

        $cr = $this->db->query(
            "SELECT count(*) FILTER (WHERE NOT cancelado
                        AND NOT EXISTS (SELECT 1 FROM " . T_CONTAS_RECEBER_BAIXAS . " b
                                         WHERE b.conta_codigo = c.codigo)) AS abertas,
                    COALESCE(sum(valor) FILTER (WHERE NOT cancelado
                        AND NOT EXISTS (SELECT 1 FROM " . T_CONTAS_RECEBER_BAIXAS . " b
                                         WHERE b.conta_codigo = c.codigo)), 0) AS valor_aberto,
                    COALESCE(sum(valor) FILTER (WHERE NOT cancelado AND data_vencimento < CURRENT_DATE
                        AND NOT EXISTS (SELECT 1 FROM " . T_CONTAS_RECEBER_BAIXAS . " b
                                         WHERE b.conta_codigo = c.codigo)), 0) AS valor_vencido
               FROM " . T_CONTAS_RECEBER . " c"
        )->row_array();

        return array(
            'cp_abertas' => (int) $cp['abertas'],
            'cp_valor' => (float) $cp['valor_aberto'],
            'cp_vencido' => (float) $cp['valor_vencido'],
            'cp_pago_mes' => (float) $cp['pago_mes'],
            'cr_abertas' => (int) $cr['abertas'],
            'cr_valor' => (float) $cr['valor_aberto'],
            'cr_vencido' => (float) $cr['valor_vencido'],
            'caixa' => $this->caixa_saldo_atual(),
        );
    }

    public function posicao_bancaria()
    {
        return $this->db->query(
            "SELECT b.nome AS banco_nome, c.banco_codigo, c.agencia, c.conta, c.titular,
                    COALESCE((
                        SELECT m.saldo_atual FROM " . T_FIN_MOVIMENTOS . " m
                         WHERE m.banco_codigo = c.banco_codigo AND m.conta = c.conta
                         ORDER BY m.data DESC, m.numero DESC LIMIT 1
                    ), (
                        SELECT COALESCE(sum(CASE WHEN m.tipo = 'C' THEN m.valor ELSE -m.valor END), 0)
                          FROM " . T_FIN_MOVIMENTOS . " m
                         WHERE m.banco_codigo = c.banco_codigo AND m.conta = c.conta
                    )) AS saldo
               FROM " . T_FIN_CONTAS . " c
               LEFT JOIN " . T_FIN_BANCOS . " b ON b.codigo = c.banco_codigo
              WHERE c.ativo
              ORDER BY b.nome, c.conta"
        )->result_array();
    }

    public function fluxo_caixa($inicio, $fim)
    {
        return $this->db->query(
            "SELECT data, descricao, tipo, valor FROM (
                SELECT b.data_pagamento AS data,
                       'Conta a pagar ' || p.numero || '/' || p.parcela AS descricao,
                       'D' AS tipo, b.valor_pago AS valor
                  FROM " . T_CONTAS_PAGAR_BAIXAS . " b
                  JOIN " . T_CONTAS_PAGAR . " p ON p.id = b.conta_pagar_id
                 WHERE b.data_pagamento BETWEEN ? AND ? AND NOT p.cancelado
                UNION ALL
                SELECT b.data_pagamento,
                       'Conta a receber ' || c.codigo || '/' || c.parcela,
                       'C', b.valor_recebido
                  FROM " . T_CONTAS_RECEBER_BAIXAS . " b
                  JOIN " . T_CONTAS_RECEBER . " c ON c.codigo = b.conta_codigo
                 WHERE b.data_pagamento BETWEEN ? AND ? AND NOT c.cancelado
             ) t
             ORDER BY data, descricao",
            array($inicio, $fim, $inicio, $fim)
        )->result_array();
    }

    public function relatorio_contas_pagar($filtros = array(), $limite = 5000)
    {
        $params = array();
        $where = $this->_where_cp($filtros, $params);

        return $this->db->query(
            "SELECT p.*, f.razao_social AS fornecedor_nome, c.nome AS centro_custo_nome
               FROM " . T_CONTAS_PAGAR . " p
               LEFT JOIN " . T_FORNECEDORES . " f ON f.codigo = p.fornecedor_codigo
               LEFT JOIN " . T_FIN_CENTROS_CUSTO . " c ON c.codigo = p.centro_custo
              WHERE " . $where . "
              ORDER BY p.vencimento NULLS LAST, p.numero, p.parcela
              LIMIT " . (int) $limite,
            $params
        )->result_array();
    }

    public function relatorio_contas_receber($filtros = array(), $limite = 5000)
    {
        $params = array();
        $where = $this->_where_cr($filtros, $params);

        return $this->db->query(
            "SELECT c.*, cl.nome AS cliente_nome, cc.nome AS centro_custo_nome
               FROM " . T_CONTAS_RECEBER . " c
               LEFT JOIN " . T_CLIENTES . " cl ON cl.id = c.cliente_id
               LEFT JOIN " . T_FIN_CENTROS_CUSTO . " cc ON cc.codigo = c.codigo_centro_custo
              WHERE " . $where . "
              ORDER BY c.data_vencimento NULLS LAST, c.codigo, c.parcela
              LIMIT " . (int) $limite,
            $params
        )->result_array();
    }

    // ==================================================================
    // Helpers
    // ==================================================================

    private function _ok($mensagem = '', $extra = array())
    {
        return array_merge(array('ok' => TRUE, 'erro' => NULL, 'mensagem' => $mensagem), $extra);
    }

    private function _erro($mensagem, $transacao = FALSE)
    {
        if ($transacao)
        {
            $this->db->trans_rollback();
        }

        return array('ok' => FALSE, 'erro' => $mensagem, 'mensagem' => $mensagem);
    }

    private function _inserir($tabela, array $dados)
    {
        $colunas = array_keys($dados);
        $marcas = rtrim(str_repeat('?, ', count($colunas)), ', ');

        $this->db->query(
            "INSERT INTO " . $tabela . " (" . implode(', ', $colunas) . ")
             VALUES (" . $marcas . ")",
            $this->_normalizar($dados)
        );
    }

    private function _atualizar($tabela, $id, array $dados, $pk = 'id')
    {
        $sets = array();

        foreach (array_keys($dados) as $coluna)
        {
            $sets[] = $coluna . ' = ?';
        }

        $params = $this->_normalizar($dados);
        $params[] = $id;

        $this->db->query(
            "UPDATE " . $tabela . " SET " . implode(', ', $sets) . ", updated_at = now()
              WHERE " . $pk . " = ?",
            $params
        );
    }

    private function _normalizar(array $dados)
    {
        $params = array();

        foreach ($dados as $valor)
        {
            $params[] = is_bool($valor) ? ($valor ? 'true' : 'false') : $valor;
        }

        return $params;
    }

    private function _v($a, $k)
    {
        return isset($a[$k]) ? $a[$k] : NULL;
    }

    private function _bool($v)
    {
        return is_bool($v)
            ? $v
            : in_array(strtolower((string) $v), array('t', 'true', '1', 's', 'sim'), TRUE);
    }

    private function _vazio($v)
    {
        return $v === NULL || $v === '' || $v === FALSE
            || (is_string($v) && trim($v) === '');
    }

    private function _int($v)
    {
        return $this->_vazio($v) ? NULL : (int) $v;
    }

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

        return is_numeric($v) ? (float) $v : NULL;
    }

    private function _texto($v, $limite = NULL)
    {
        if ($this->_vazio($v))
        {
            return NULL;
        }

        $v = trim((string) $v);

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

    private function _data($v)
    {
        if ($this->_vazio($v))
        {
            return NULL;
        }

        $v = trim((string) $v);

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $v, $m))
        {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1])
                ? $m[1] . '-' . $m[2] . '-' . $m[3] : NULL;
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $v, $m))
        {
            return checkdate((int) $m[2], (int) $m[1], (int) $m[3])
                ? $m[3] . '-' . $m[2] . '-' . $m[1] : NULL;
        }

        return NULL;
    }
}
