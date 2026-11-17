<?php
/**
 * Financeiro - contas a pagar/receber, baixas, movimentos bancarios, caixa,
 * cheques, balanco, cadastros de apoio e relatorios.
 *
 * Regras principais (ver docs/10-regras-financeiro.md):
 *  - titulos de contas a pagar usam numeracao CP; contas a receber usam CR;
 *  - baixa grava a baixa, muda a situacao e pode gerar movimento bancario
 *    e/ou movimento de caixa;
 *  - estorno remove a baixa e reabre o titulo;
 *  - titulo cancelado nao pode ser baixado;
 *  - movimento bancario recalcula o saldo da conta (C soma, D subtrai);
 *  - caixa: abertura + entradas - saidas = fechamento. Quando o dia tem a
 *    linha "saldo anterior" dentro das entradas, o fechamento e
 *    entradas - saidas (a abertura ja esta embutida).
 */
class Financeiro extends MY_Controller
{
    /** cadastros de apoio aceitos */
    private $tipos_cadastro = array(
        'bancos', 'agencias', 'contas', 'cartoes', 'centros',
        'naturezas', 'status', 'periodicidades', 'despesas',
    );

    public function __construct()
    {
        parent::__construct();

        $this->load->model('financeiro_model');
        $this->load->library('auditoria');

        $this->titulo     = 'Financeiro';
        $this->menu_ativo = 'financeiro';
    }

    // ==================================================================
    // Painel
    // ==================================================================

    public function index()
    {
        $resumo = $this->financeiro_model->resumo();

        $this->render('financeiro/index', array(
            'resumo'    => $resumo,
            'posicao'   => $this->financeiro_model->posicao_bancaria(),
            'vencendo'  => $this->financeiro_model->contas_pagar(
                array('situacao' => 'aberta'), 1, 8
            ),
            'recebendo' => $this->financeiro_model->contas_receber(
                array('situacao' => 'aberta'), 1, 8
            ),
            'caixa'     => $this->financeiro_model->caixa_dias(array(), 1, 7),
        ));
    }

    // ==================================================================
    // Contas a pagar
    // ==================================================================

    public function contas_pagar()
    {
        $filtros = array(
            'busca'           => trim((string) $this->input->get('busca')),
            'fornecedor_nome' => trim((string) $this->input->get('fornecedor_nome')),
            'situacao'        => trim((string) $this->input->get('situacao')),
            'centro_custo'    => (int) $this->input->get('centro_custo'),
            'venc_de'         => trim((string) $this->input->get('venc_de')),
            'venc_ate'        => trim((string) $this->input->get('venc_ate')),
            'origem'          => trim((string) $this->input->get('origem')),
        );

        $pagina = (int) $this->input->get('pagina');
        $conta  = $this->financeiro_model->contar_contas_pagar($filtros);
        $pag    = $this->paginar($conta['total'], 30, $pagina);

        $this->titulo = 'Financeiro - Contas a pagar';

        $this->render('financeiro/contas_pagar', array(
            'registros' => $this->financeiro_model->contas_pagar($filtros, $pag['pagina'], $pag['por_pagina']),
            'filtros'   => $filtros,
            'pag'       => $pag,
            'total'     => $conta,
            'resumo'    => $this->financeiro_model->resumo(),
            'centros'   => $this->financeiro_model->centros_custo(),
        ));
    }

    // ------------------------------------------------------------------

    public function conta_pagar_novo()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ($this->eh_post())
        {
            $dados = $this->_post_cp();
            $r = $this->financeiro_model->salvar_conta_pagar($dados, $this->usuario);

            if ( ! $r['ok'])
            {
                $this->flash($r['erro'], 'erro');
                redirect('financeiro/conta_pagar_novo');
            }

            $this->auditoria->registrar('criar', 'conta_pagar', $r['numero'], array(
                'valor' => isset($dados['valor']) ? $dados['valor'] : NULL,
            ));

            $this->flash($r['mensagem']);
            redirect('financeiro/contas_pagar');
        }

        $this->titulo = 'Financeiro - Nova conta a pagar';

        $this->render('financeiro/conta_pagar_form', $this->_dados_form_cp(array()));
    }

    public function conta_pagar_editar($id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $id    = (int) $id;
        $conta = $this->financeiro_model->conta_pagar($id);

        if (empty($conta))
        {
            show_404();
        }

        if ($this->eh_post())
        {
            $dados = $this->_post_cp();
            $r = $this->financeiro_model->salvar_conta_pagar($dados, $this->usuario, $id);

            if ( ! $r['ok'])
            {
                $this->flash($r['erro'], 'erro');
                redirect('financeiro/conta_pagar_editar/' . $id);
            }

            $this->auditoria->registrar('alterar', 'conta_pagar', $id);
            $this->flash($r['mensagem']);
            redirect('financeiro/conta_pagar_ver/' . $id);
        }

        $this->titulo = 'Financeiro - Editar conta a pagar';

        $this->render('financeiro/conta_pagar_form', $this->_dados_form_cp($conta));
    }

    public function conta_pagar_ver($id)
    {
        $id    = (int) $id;
        $conta = $this->financeiro_model->conta_pagar($id);

        if (empty($conta))
        {
            show_404();
        }

        $this->titulo = 'Financeiro - Conta a pagar ' . $conta['numero'] . '/' . $conta['parcela'];

        $this->render('financeiro/conta_pagar_ver', array(
            'registro' => $conta,
            'baixas'   => $this->financeiro_model->baixas_conta_pagar($id),
            'log'      => $this->financeiro_model->log_conta_pagar((int) $conta['numero'], (int) $conta['parcela']),
            'contas'   => $this->financeiro_model->contas_bancarias(),
        ));
    }

    public function conta_pagar_baixar($id)
    {
        $id    = (int) $id;
        $conta = $this->financeiro_model->conta_pagar($id);

        if (empty($conta))
        {
            show_404();
        }

        if ($this->eh_post())
        {
            $dados = array(
                'data_pagamento'  => $this->_post('data_pagamento'),
                'multa'           => $this->_post('multa'),
                'juros'           => $this->_post('juros'),
                'desconto'        => $this->_post('desconto'),
                'valor_pago'      => $this->_post('valor_pago'),
                'saldo_caixa'     => $this->_post('saldo_caixa'),
                'saldo_conta'     => $this->_post('saldo_conta'),
                'conta_codigo'    => $this->_post('conta_codigo'),
                'gerar_movimento' => $this->_post('gerar_movimento'),
                'gerar_caixa'     => $this->_post('gerar_caixa'),
            );

            $r = $this->financeiro_model->baixar_conta_pagar($id, $dados, $this->usuario);

            if ( ! $r['ok'])
            {
                $this->flash($r['erro'], 'erro');
                redirect('financeiro/conta_pagar_baixar/' . $id);
            }

            if ( ! empty($dados['gerar_caixa']) && $r['ok'])
            {
                $this->financeiro_model->salvar_caixa_movimento(array(
                    'data'           => $dados['data_pagamento'],
                    'historico'      => 'BAIXA DE CONTA A PAGAR RELATIVA A ' . $conta['numero'] . '/' . $conta['parcela'],
                    'valor_saida'    => $this->_post('valor_pago'),
                    'documento'      => $conta['documento'],
                    'forma_recebimento' => $conta['forma_recebimento'],
                ), $this->usuario, NULL, FALSE);
            }

            $this->auditoria->registrar('alterar', 'conta_pagar', $id, array('baixa' => TRUE));
            $this->flash($r['mensagem']);
            redirect('financeiro/conta_pagar_ver/' . $id);
        }

        $this->titulo = 'Financeiro - Baixar conta a pagar';

        $this->render('financeiro/conta_pagar_baixar', array(
            'registro' => $conta,
            'contas'   => $this->financeiro_model->contas_bancarias(),
        ));
    }

    public function conta_pagar_estornar($id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $id = (int) $id;
        $r  = $this->financeiro_model->estornar_baixa_pagar($id, $this->usuario);

        $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'erro');
        $this->auditoria->registrar('alterar', 'conta_pagar', $id, array('estorno' => TRUE));

        redirect('financeiro/conta_pagar_ver/' . $id);
    }

    public function conta_pagar_cancelar($id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $id     = (int) $id;
        $motivo = trim((string) $this->_post('motivo'));
        $r      = $this->financeiro_model->cancelar_conta_pagar($id, $motivo, $this->usuario);

        $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'erro');
        $this->auditoria->registrar('alterar', 'conta_pagar', $id, array('cancelamento' => $motivo));

        redirect('financeiro/conta_pagar_ver/' . $id);
    }

    public function conta_pagar_excluir($id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $id = (int) $id;
        $r  = $this->financeiro_model->excluir_conta_pagar($id);

        $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'erro');

        if ($r['ok'])
        {
            $this->auditoria->registrar('excluir', 'conta_pagar', $id);
            redirect('financeiro/contas_pagar');
        }

        redirect('financeiro/conta_pagar_ver/' . $id);
    }

    public function conta_pagar_comprovante($id)
    {
        $id    = (int) $id;
        $conta = $this->financeiro_model->conta_pagar($id);

        if (empty($conta))
        {
            show_404();
        }

        $this->load->view('financeiro/comprovante_pagar', array(
            'registro' => $conta,
            'baixas'   => $this->financeiro_model->baixas_conta_pagar($id),
        ));
    }

    // ==================================================================
    // Contas a receber
    // ==================================================================

    public function contas_receber()
    {
        $filtros = array(
            'busca'        => trim((string) $this->input->get('busca')),
            'situacao'     => trim((string) $this->input->get('situacao')),
            'centro_custo' => (int) $this->input->get('centro_custo'),
            'venc_de'      => trim((string) $this->input->get('venc_de')),
            'venc_ate'     => trim((string) $this->input->get('venc_ate')),
            'arquivada'    => trim((string) $this->input->get('arquivada')),
        );

        $pagina = (int) $this->input->get('pagina');
        $conta  = $this->financeiro_model->contar_contas_receber($filtros);
        $pag    = $this->paginar($conta['total'], 30, $pagina);

        $this->titulo = 'Financeiro - Contas a receber';

        $this->render('financeiro/contas_receber', array(
            'registros' => $this->financeiro_model->contas_receber($filtros, $pag['pagina'], $pag['por_pagina']),
            'filtros'   => $filtros,
            'pag'       => $pag,
            'total'     => $conta,
            'resumo'    => $this->financeiro_model->resumo(),
            'centros'   => $this->financeiro_model->centros_custo(),
        ));
    }

    public function conta_receber_novo()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ($this->eh_post())
        {
            $dados = $this->_post_cr();
            $r = $this->financeiro_model->salvar_conta_receber($dados, $this->usuario);

            if ( ! $r['ok'])
            {
                $this->flash($r['erro'], 'erro');
                redirect('financeiro/conta_receber_novo');
            }

            $this->auditoria->registrar('criar', 'conta_receber', $r['codigo'], array(
                'valor' => isset($dados['valor']) ? $dados['valor'] : NULL,
            ));

            $this->flash($r['mensagem']);
            redirect('financeiro/contas_receber');
        }

        $this->titulo = 'Financeiro - Nova conta a receber';

        $this->render('financeiro/conta_receber_form', $this->_dados_form_cr(array()));
    }

    public function conta_receber_editar($codigo)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $codigo = (int) $codigo;
        $conta  = $this->financeiro_model->conta_receber($codigo);

        if (empty($conta))
        {
            show_404();
        }

        if ($this->eh_post())
        {
            $dados = $this->_post_cr();
            $r = $this->financeiro_model->salvar_conta_receber($dados, $this->usuario, $codigo);

            if ( ! $r['ok'])
            {
                $this->flash($r['erro'], 'erro');
                redirect('financeiro/conta_receber_editar/' . $codigo);
            }

            $this->auditoria->registrar('alterar', 'conta_receber', $codigo);
            $this->flash($r['mensagem']);
            redirect('financeiro/conta_receber_ver/' . $codigo);
        }

        $this->titulo = 'Financeiro - Editar conta a receber';

        $this->render('financeiro/conta_receber_form', $this->_dados_form_cr($conta));
    }

    public function conta_receber_ver($codigo)
    {
        $codigo = (int) $codigo;
        $conta  = $this->financeiro_model->conta_receber($codigo);

        if (empty($conta))
        {
            show_404();
        }

        $this->titulo = 'Financeiro - Conta a receber ' . $conta['codigo'] . '/' . $conta['parcela'];

        $this->render('financeiro/conta_receber_ver', array(
            'registro' => $conta,
            'baixas'   => $this->financeiro_model->baixas_conta_receber($codigo),
            'contas'   => $this->financeiro_model->contas_bancarias(),
        ));
    }

    public function conta_receber_baixar($codigo)
    {
        $codigo = (int) $codigo;
        $conta  = $this->financeiro_model->conta_receber($codigo);

        if (empty($conta))
        {
            show_404();
        }

        if ($this->eh_post())
        {
            $dados = array(
                'data_pagamento'  => $this->_post('data_pagamento'),
                'multa'           => $this->_post('multa'),
                'juros'           => $this->_post('juros'),
                'desconto'        => $this->_post('desconto'),
                'valor_recebido'  => $this->_post('valor_recebido'),
                'gerar_movimento' => $this->_post('gerar_movimento'),
                'gerar_caixa'     => $this->_post('gerar_caixa'),
            );

            $r = $this->financeiro_model->baixar_conta_receber($codigo, $dados, $this->usuario);

            if ( ! $r['ok'])
            {
                $this->flash($r['erro'], 'erro');
                redirect('financeiro/conta_receber_baixar/' . $codigo);
            }

            if ( ! empty($dados['gerar_caixa']) && $r['ok'])
            {
                $this->financeiro_model->salvar_caixa_movimento(array(
                    'data'        => $dados['data_pagamento'],
                    'historico'   => 'RECEBIMENTO DA CONTA A RECEBER ' . $codigo . '/' . $conta['parcela'],
                    'valor_entrada' => $this->_post('valor_recebido'),
                    'documento'   => $conta['numero_documento'],
                    'chamado_numero' => $conta['chamado_numero'],
                    'forma_recebimento' => $conta['forma_recebimento_codigo'],
                    'documento_recebimento' => $conta['documento_codigo'],
                ), $this->usuario, NULL, FALSE);
            }

            $this->auditoria->registrar('alterar', 'conta_receber', $codigo, array('baixa' => TRUE));
            $this->flash($r['mensagem']);
            redirect('financeiro/conta_receber_ver/' . $codigo);
        }

        $this->titulo = 'Financeiro - Receber conta a receber';

        $this->render('financeiro/conta_receber_baixar', array(
            'registro' => $conta,
        ));
    }

    public function conta_receber_estornar($codigo)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $codigo = (int) $codigo;
        $r = $this->financeiro_model->estornar_baixa_receber($codigo, $this->usuario);

        $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'erro');
        $this->auditoria->registrar('alterar', 'conta_receber', $codigo, array('estorno' => TRUE));

        redirect('financeiro/conta_receber_ver/' . $codigo);
    }

    public function conta_receber_cancelar($codigo)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $codigo = (int) $codigo;
        $motivo = trim((string) $this->_post('motivo'));
        $r = $this->financeiro_model->cancelar_conta_receber($codigo, $motivo, $this->usuario);

        $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'erro');
        $this->auditoria->registrar('alterar', 'conta_receber', $codigo, array('cancelamento' => $motivo));

        redirect('financeiro/conta_receber_ver/' . $codigo);
    }

    public function conta_receber_excluir($codigo)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $codigo = (int) $codigo;
        $r = $this->financeiro_model->excluir_conta_receber($codigo);

        $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'erro');

        if ($r['ok'])
        {
            $this->auditoria->registrar('excluir', 'conta_receber', $codigo);
            redirect('financeiro/contas_receber');
        }

        redirect('financeiro/conta_receber_ver/' . $codigo);
    }

    public function conta_receber_comprovante($codigo)
    {
        $codigo = (int) $codigo;
        $conta  = $this->financeiro_model->conta_receber($codigo);

        if (empty($conta))
        {
            show_404();
        }

        $this->load->view('financeiro/comprovante_receber', array(
            'registro' => $conta,
            'baixas'   => $this->financeiro_model->baixas_conta_receber($codigo),
        ));
    }

    // ==================================================================
    // Movimentos bancarios
    // ==================================================================

    public function movimentos()
    {
        $filtros = array(
            'busca'   => trim((string) $this->input->get('busca')),
            'banco'   => trim((string) $this->input->get('banco')),
            'conta'   => trim((string) $this->input->get('conta')),
            'tipo'    => trim((string) $this->input->get('tipo')),
            'data_de' => trim((string) $this->input->get('data_de')),
            'data_ate'=> trim((string) $this->input->get('data_ate')),
            'vinculo' => trim((string) $this->input->get('vinculo')),
        );

        $pagina = (int) $this->input->get('pagina');
        $conta  = $this->financeiro_model->contar_movimentos_bancarios($filtros);
        $pag    = $this->paginar($conta['total'], 40, $pagina);

        $this->titulo = 'Financeiro - Movimentos bancarios';

        $this->render('financeiro/movimentos', array(
            'registros' => $this->financeiro_model->movimentos_bancarios($filtros, $pag['pagina'], $pag['por_pagina']),
            'filtros'   => $filtros,
            'pag'       => $pag,
            'total'     => $conta,
            'bancos'    => $this->financeiro_model->bancos(),
            'contas'    => $this->financeiro_model->contas_bancarias(),
            'posicao'   => $this->financeiro_model->posicao_bancaria(),
        ));
    }

    public function movimento_novo()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ($this->eh_post())
        {
            $dados = $this->_post_movimento();
            $r = $this->financeiro_model->salvar_movimento_bancario($dados, $this->usuario);

            if ( ! $r['ok'])
            {
                $this->flash($r['erro'], 'erro');
                redirect('financeiro/movimento_novo');
            }

            $this->auditoria->registrar('criar', 'fin_movimento', NULL, array('valor' => $dados['valor']));
            $this->flash($r['mensagem']);
            redirect('financeiro/movimentos');
        }

        $this->titulo = 'Financeiro - Novo movimento bancario';

        $this->render('financeiro/movimento_form', array(
            'registro' => array(),
            'bancos'   => $this->financeiro_model->bancos(),
            'contas'   => $this->financeiro_model->contas_bancarias(),
        ));
    }

    public function movimento_editar($id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $id     = (int) $id;
        $reg    = $this->financeiro_model->movimento_bancario($id);

        if (empty($reg))
        {
            show_404();
        }

        if ($this->eh_post())
        {
            $dados = $this->_post_movimento();
            $r = $this->financeiro_model->salvar_movimento_bancario($dados, $this->usuario, $id);

            if ( ! $r['ok'])
            {
                $this->flash($r['erro'], 'erro');
                redirect('financeiro/movimento_editar/' . $id);
            }

            $this->auditoria->registrar('alterar', 'fin_movimento', $id);
            $this->flash($r['mensagem']);
            redirect('financeiro/movimentos');
        }

        $this->titulo = 'Financeiro - Editar movimento bancario';

        $this->render('financeiro/movimento_form', array(
            'registro' => $reg,
            'bancos'   => $this->financeiro_model->bancos(),
            'contas'   => $this->financeiro_model->contas_bancarias(),
        ));
    }

    public function movimento_excluir($id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $id = (int) $id;
        $r  = $this->financeiro_model->excluir_movimento_bancario($id);

        $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'erro');

        if ($r['ok'])
        {
            $this->auditoria->registrar('excluir', 'fin_movimento', $id);
        }

        redirect('financeiro/movimentos');
    }

    // ==================================================================
    // Caixa
    // ==================================================================

    public function caixa()
    {
        $filtros = array(
            'data_de'  => trim((string) $this->input->get('data_de')),
            'data_ate' => trim((string) $this->input->get('data_ate')),
            'fechado'  => trim((string) $this->input->get('fechado')),
        );

        $pagina = (int) $this->input->get('pagina');
        $total  = $this->financeiro_model->contar_caixa_dias($filtros);
        $pag    = $this->paginar($total, 30, $pagina);

        $this->titulo = 'Financeiro - Caixa';

        $this->render('financeiro/caixa', array(
            'registros' => $this->financeiro_model->caixa_dias($filtros, $pag['pagina'], $pag['por_pagina']),
            'filtros'   => $filtros,
            'pag'       => $pag,
            'saldo'     => $this->financeiro_model->caixa_saldo_atual(),
        ));
    }

    public function caixa_dia($data)
    {
        $dia = $this->_data_url($data);

        if ($dia === NULL)
        {
            show_404();
        }

        $registro = $this->financeiro_model->caixa_dia($dia);

        if (empty($registro))
        {
            // Dia sem movimento: mantem a tela para lancar o primeiro movimento.
            $registro = array(
                'data'           => $dia,
                'saldo_anterior' => $this->_saldo_anterior($dia),
                'saldo_atual'    => $this->_saldo_anterior($dia),
                'total_entradas' => 0,
                'total_saidas'   => 0,
                'fechado'        => FALSE,
            );
        }

        $this->titulo = 'Financeiro - Caixa de ' . data_br($dia);

        $editar = (int) $this->input->get('editar');
        $mov_edicao = array();

        if ($editar > 0)
        {
            $mov_edicao = $this->financeiro_model->caixa_movimento($editar);
        }

        $this->render('financeiro/caixa_dia', array(
            'registro'  => $registro,
            'movimentos' => $this->financeiro_model->caixa_movimentos($dia),
            'mov_edicao' => $mov_edicao,
            'setores'   => $this->financeiro_model->setores(),
            'dominios'  => $this->_dominios(),
            'total_entradas' => $this->_soma_caixa($dia, 'valor_entrada'),
            'total_saidas'   => $this->_soma_caixa($dia, 'valor_saida'),
        ));
    }

    public function caixa_movimento_salvar()
    {
        $this->_exigir_post();

        $id     = (int) $this->_post('id');
        $data   = $this->_post('data');
        $dados  = array(
            'data'                  => $data,
            'setor'                 => $this->_post('setor'),
            'documento'             => $this->_post('documento'),
            'historico'             => $this->_post('historico'),
            'valor_entrada'         => $this->_post('valor_entrada'),
            'valor_saida'           => $this->_post('valor_saida'),
            'chamado_numero'        => $this->_post('chamado_numero'),
            'forma_recebimento'     => $this->_post('forma_recebimento'),
            'documento_recebimento' => $this->_post('documento_recebimento'),
        );

        $r = $this->financeiro_model->salvar_caixa_movimento($dados, $this->usuario, $id ? $id : NULL);

        $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'erro');

        redirect('financeiro/caixa_dia/' . $this->_data_url($data));
    }

    public function caixa_movimento_excluir($id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $id  = (int) $id;
        $mov = $this->db->query(
            "SELECT data FROM " . T_FIN_CAIXA_MOVIMENTOS . " WHERE id = ?",
            array($id)
        )->row_array();

        $r = $this->financeiro_model->excluir_caixa_movimento($id);

        $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'erro');

        redirect(empty($mov) ? 'financeiro/caixa' : 'financeiro/caixa_dia/' . $this->_data_url($mov['data']));
    }

    public function caixa_fechar($data)
    {
        $this->_exigir_post();

        $dia = $this->_data_url($data);
        $r   = $this->financeiro_model->fechar_caixa($dia, $this->usuario);

        $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'erro');
        redirect('financeiro/caixa_dia/' . $dia);
    }

    public function caixa_reabrir($data)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $dia = $this->_data_url($data);
        $r   = $this->financeiro_model->reabrir_caixa($dia);

        $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'erro');
        redirect('financeiro/caixa_dia/' . $dia);
    }

    // ==================================================================
    // Cheques
    // ==================================================================

    public function cheques()
    {
        $filtros = array(
            'busca'    => trim((string) $this->input->get('busca')),
            'situacao' => trim((string) $this->input->get('situacao')),
            'banco'    => trim((string) $this->input->get('banco')),
        );

        $pagina = (int) $this->input->get('pagina');
        $conta  = $this->financeiro_model->contar_cheques($filtros);
        $pag    = $this->paginar($conta['total'], 30, $pagina);

        $this->titulo = 'Financeiro - Cheques';

        $this->render('financeiro/cheques', array(
            'registros' => $this->financeiro_model->cheques($filtros, $pag['pagina'], $pag['por_pagina']),
            'filtros'   => $filtros,
            'pag'       => $pag,
            'total'     => $conta,
            'bancos'    => $this->financeiro_model->bancos(),
        ));
    }

    public function cheque_novo()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ($this->eh_post())
        {
            $r = $this->financeiro_model->salvar_cheque($this->_post_cheque());

            $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'erro');
            redirect('financeiro/cheques');
        }

        $this->titulo = 'Financeiro - Novo cheque';

        $this->render('financeiro/cheque_form', array(
            'registro' => array(),
            'bancos'   => $this->financeiro_model->bancos(),
            'contas'   => $this->financeiro_model->contas_bancarias(),
        ));
    }

    public function cheque_editar($id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $id  = (int) $id;
        $reg = $this->financeiro_model->cheque($id);

        if (empty($reg))
        {
            show_404();
        }

        if ($this->eh_post())
        {
            $r = $this->financeiro_model->salvar_cheque($this->_post_cheque(), $id);

            $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'erro');
            redirect('financeiro/cheques');
        }

        $this->titulo = 'Financeiro - Editar cheque';

        $this->render('financeiro/cheque_form', array(
            'registro' => $reg,
            'bancos'   => $this->financeiro_model->bancos(),
            'contas'   => $this->financeiro_model->contas_bancarias(),
        ));
    }

    public function cheque_excluir($id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $id = (int) $id;
        $r  = $this->financeiro_model->excluir_cheque($id);

        $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'erro');
        redirect('financeiro/cheques');
    }

    public function cheque_compensar($id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $id   = (int) $id;
        $data = $this->_data_url($this->_post('data_compensacao'));

        if ($data === NULL)
        {
            $data = date('Y-m-d');
        }

        $r = $this->financeiro_model->compensar_cheque($id, $data);

        $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'erro');
        redirect('financeiro/cheques');
    }

    // ==================================================================
    // Balanco
    // ==================================================================

    public function balanco()
    {
        $filtros = array(
            'de'  => trim((string) $this->input->get('de')),
            'ate' => trim((string) $this->input->get('ate')),
        );

        $pagina = (int) $this->input->get('pagina');
        $total  = $this->financeiro_model->contar_balanco($filtros);
        $pag    = $this->paginar($total, 30, $pagina);

        $this->titulo = 'Financeiro - Balanco';

        $this->render('financeiro/balanco', array(
            'registros' => $this->financeiro_model->balanco($filtros, $pag['pagina'], $pag['por_pagina']),
            'filtros'   => $filtros,
            'pag'       => $pag,
        ));
    }

    public function balanco_gerar()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $inicio = $this->_data_url($this->_post('inicio'));
        $fim    = $this->_data_url($this->_post('fim'));

        if ($inicio === NULL || $fim === NULL)
        {
            $this->flash('Informe o periodo do balanco.', 'erro');
            redirect('financeiro/balanco');
        }

        $r = $this->financeiro_model->gerar_balanco($inicio, $fim);

        $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'erro');
        redirect('financeiro/balanco');
    }

    public function balanco_excluir($id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $id = (int) $id;
        $r  = $this->financeiro_model->excluir_balanco($id);

        $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'erro');
        redirect('financeiro/balanco');
    }

    // ==================================================================
    // Cadastros de apoio
    // ==================================================================

    public function cadastros()
    {
        $contagens = array();

        foreach ($this->tipos_cadastro as $tipo)
        {
            $contagens[$tipo] = $this->financeiro_model->cadastro_contar($tipo);
        }

        $this->titulo = 'Financeiro - Cadastros';

        $this->render('financeiro/cadastros', array(
            'contagens' => $contagens,
        ));
    }

    public function cadastro($tipo, $id = NULL)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $tipo = preg_replace('/[^a-z_]/', '', (string) $tipo);

        if ( ! in_array($tipo, $this->tipos_cadastro, TRUE))
        {
            show_404();
        }

        if ($this->eh_post())
        {
            $dados = $this->_post_cadastro($tipo);
            $erro  = $this->_validar_cadastro($tipo, $dados);

            if ($erro !== NULL)
            {
                $this->flash($erro, 'erro');
                redirect('financeiro/cadastro/' . $tipo . ($id !== NULL ? '/' . rawurlencode($id) : ''));
            }

            $r = $this->financeiro_model->salvar_cadastro($tipo, $dados, $id !== NULL ? $id : NULL);

            if ( ! $r['ok'])
            {
                $this->flash($r['erro'], 'erro');
                redirect('financeiro/cadastro/' . $tipo . ($id !== NULL ? '/' . rawurlencode($id) : ''));
            }

            $this->auditoria->registrar($id !== NULL ? 'alterar' : 'criar', 'fin_' . $tipo, $id);
            $this->flash('Cadastro gravado.');
            redirect('financeiro/cadastro/' . $tipo);
        }

        $registro = $id !== NULL
            ? $this->financeiro_model->cadastro_por_id($tipo, $id)
            : array();

        if ($id !== NULL && empty($registro))
        {
            show_404();
        }

        $this->titulo = 'Financeiro - Cadastro de ' . $this->_rotulo_cadastro($tipo);

        $this->render('financeiro/cadastro_form', array(
            'tipo'      => $tipo,
            'rotulo'    => $this->_rotulo_cadastro($tipo),
            'registro'  => $registro,
            'registros' => $this->financeiro_model->cadastro_todos($tipo),
            'bancos'    => $this->financeiro_model->bancos(),
            'centros'   => $this->financeiro_model->centros_custo(),
            'naturezas' => $this->financeiro_model->naturezas(),
            'periodicidades' => $this->financeiro_model->periodicidades(),
        ));
    }

    public function cadastro_excluir($tipo, $id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $tipo = preg_replace('/[^a-z_]/', '', (string) $tipo);

        if ( ! in_array($tipo, $this->tipos_cadastro, TRUE))
        {
            show_404();
        }

        $r = $this->financeiro_model->excluir_cadastro($tipo, $id);

        $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'erro');
        redirect('financeiro/cadastro/' . $tipo);
    }

    // ==================================================================
    // Relatorios
    // ==================================================================

    public function relatorios()
    {
        $tipo = trim((string) $this->input->get('tipo'));
        $de   = trim((string) $this->input->get('de'));
        $ate  = trim((string) $this->input->get('ate'));
        $sit  = trim((string) $this->input->get('situacao'));

        $tipos = array('cp', 'cr', 'fluxo', 'posicao', 'balanco');

        if ($tipo === '' || ! in_array($tipo, $tipos, TRUE))
        {
            $tipo = 'cp';
        }

        if ($de === '')
        {
            $de = date('Y-m-01');
        }

        if ($ate === '')
        {
            $ate = date('Y-m-t');
        }

        $dados = array(
            'tipo'     => $tipo,
            'de'       => $de,
            'ate'      => $ate,
            'situacao' => $sit,
            'total'    => 0,
            'registros'=> array(),
            'posicao'  => array(),
            'resumo'   => array(),
        );

        if ($tipo === 'cp')
        {
            $f = array('venc_de' => $de, 'venc_ate' => $ate, 'situacao' => $sit);
            $dados['registros'] = $this->financeiro_model->relatorio_contas_pagar($f);
        }
        elseif ($tipo === 'cr')
        {
            $f = array('venc_de' => $de, 'venc_ate' => $ate, 'situacao' => $sit);
            $dados['registros'] = $this->financeiro_model->relatorio_contas_receber($f);
        }
        elseif ($tipo === 'fluxo')
        {
            $dados['registros'] = $this->financeiro_model->fluxo_caixa($de, $ate);
        }
        elseif ($tipo === 'posicao')
        {
            $dados['posicao'] = $this->financeiro_model->posicao_bancaria();
        }
        elseif ($tipo === 'balanco')
        {
            $dados['registros'] = $this->financeiro_model->balanco(array('de' => $de, 'ate' => $ate), 1, 200);
        }

        $soma = 0.0;

        if ($tipo === 'cp' || $tipo === 'cr')
        {
            foreach ($dados['registros'] as $r)
            {
                $soma += (float) $r['valor'];
            }
        }
        elseif ($tipo === 'fluxo')
        {
            foreach ($dados['registros'] as $r)
            {
                $soma += $r['tipo'] === 'C' ? (float) $r['valor'] : -(float) $r['valor'];
            }
        }

        $dados['total'] = $soma;

        $this->titulo = 'Financeiro - Relatorios';

        $this->render('financeiro/relatorios', $dados);
    }

    // ==================================================================
    // Buscas AJAX
    // ==================================================================

    public function buscar_fornecedores()
    {
        $termo = trim((string) $this->input->get('termo'));
        $this->_json($this->financeiro_model->buscar_fornecedores($termo, 20));
    }

    public function buscar_clientes()
    {
        $termo = trim((string) $this->input->get('termo'));
        $this->_json($this->financeiro_model->buscar_clientes($termo, 20));
    }

    // ==================================================================
    // Internos
    // ==================================================================

    private function _post($campo, $default = NULL)
    {
        $v = $this->input->post($campo);

        return $v === FALSE ? $default : $v;
    }

    private function _exigir_post()
    {
        if ( ! $this->eh_post())
        {
            redirect('financeiro');
        }
    }

    private function _json($dados)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($dados, JSON_UNESCAPED_UNICODE));
    }

    private function _data_url($valor)
    {
        $valor = trim((string) $valor);

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $valor, $m))
        {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1])
                ? $m[1] . '-' . $m[2] . '-' . $m[3] : NULL;
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $valor, $m))
        {
            return checkdate((int) $m[2], (int) $m[1], (int) $m[3])
                ? $m[3] . '-' . $m[2] . '-' . $m[1] : NULL;
        }

        return NULL;
    }

    private function _dominios()
    {
        return array(
            'forma_recebimento' => $this->financeiro_model->dominios('forma_recebimento'),
            'documento'         => $this->financeiro_model->dominios('documento'),
            'condicao_pagamento'=> $this->financeiro_model->dominios('condicao_pagamento'),
            'tipo_pagamento'    => $this->financeiro_model->dominios('tipo_pagamento'),
        );
    }

    private function _dados_form_cp($registro)
    {
        return array(
            'registro' => $registro,
            'bancos'   => $this->financeiro_model->bancos(),
            'centros'  => $this->financeiro_model->centros_custo(),
            'contas'   => $this->financeiro_model->contas_bancarias(),
            'dominios' => $this->_dominios(),
        );
    }

    private function _dados_form_cr($registro)
    {
        return array(
            'registro' => $registro,
            'bancos'   => $this->financeiro_model->bancos(),
            'centros'  => $this->financeiro_model->centros_custo(),
            'contas'   => $this->financeiro_model->contas_bancarias(),
            'dominios' => $this->_dominios(),
        );
    }

    private function _post_cp()
    {
        return array(
            'fornecedor_codigo'     => $this->_post('fornecedor_codigo'),
            'portador_setor'        => $this->_post('portador_setor'),
            'emissao'               => $this->_post('emissao'),
            'lancamento'            => $this->_post('lancamento'),
            'vencimento'            => $this->_post('vencimento'),
            'valor'                 => $this->_post('valor'),
            'parcelas'              => $this->_post('parcelas'),
            'intervalo_dias'        => $this->_post('intervalo_dias'),
            'forma_recebimento'     => $this->_post('forma_recebimento'),
            'documento_recebimento' => $this->_post('documento_recebimento'),
            'centro_custo'          => $this->_post('centro_custo'),
            'documento'             => $this->_post('documento'),
            'historico'             => $this->_post('historico'),
            'banco'                 => $this->_post('banco'),
            'agencia'               => $this->_post('agencia'),
            'conta'                 => $this->_post('conta'),
            'portador'              => $this->_post('portador'),
            'ret_caixa'             => $this->_post('ret_caixa'),
            'data_caixa'            => $this->_post('data_caixa'),
        );
    }

    private function _post_cr()
    {
        return array(
            'cliente_id'             => $this->_post('cliente_id'),
            'data_emissao'           => $this->_post('data_emissao'),
            'data_vencimento'        => $this->_post('data_vencimento'),
            'valor'                  => $this->_post('valor'),
            'parcelas'               => $this->_post('parcelas'),
            'intervalo_dias'         => $this->_post('intervalo_dias'),
            'chamado_numero'         => $this->_post('chamado_numero'),
            'numero_documento'       => $this->_post('numero_documento'),
            'tipo'                   => $this->_post('tipo'),
            'forma_recebimento_codigo' => $this->_post('forma_recebimento_codigo'),
            'documento_codigo'       => $this->_post('documento_codigo'),
            'dias'                   => $this->_post('dias'),
            'desconto_especial'      => $this->_post('desconto_especial'),
            'vdesp'                  => $this->_post('vdesp'),
            'aliq_issqn'             => $this->_post('aliq_issqn'),
            'viss'                   => $this->_post('viss'),
            'vliq'                   => $this->_post('vliq'),
            'banco'                  => $this->_post('banco'),
            'agencia'                => $this->_post('agencia'),
            'conta'                  => $this->_post('conta'),
            'portador_codigo'        => $this->_post('portador_codigo'),
            'historico'              => $this->_post('historico'),
            'codigo_centro_custo'    => $this->_post('codigo_centro_custo'),
            'ret_caixa'              => $this->_post('ret_caixa'),
            'numero_nota_fiscal'     => $this->_post('numero_nota_fiscal'),
        );
    }

    private function _post_movimento()
    {
        return array(
            'numero'                => $this->_post('numero'),
            'data'                  => $this->_post('data'),
            'banco_codigo'          => $this->_post('banco_codigo'),
            'agencia'               => $this->_post('agencia'),
            'conta'                 => $this->_post('conta'),
            'tipo'                  => $this->_post('tipo'),
            'valor'                 => $this->_post('valor'),
            'historico'             => $this->_post('historico'),
            'conta_pagar_numero'    => $this->_post('conta_pagar_numero'),
            'conta_pagar_parcela'   => $this->_post('conta_pagar_parcela'),
            'conta_receber_codigo'  => $this->_post('conta_receber_codigo'),
            'conta_receber_parcela' => $this->_post('conta_receber_parcela'),
        );
    }

    private function _post_cheque()
    {
        return array(
            'numero'            => $this->_post('numero'),
            'banco_codigo'      => $this->_post('banco_codigo'),
            'agencia'           => $this->_post('agencia'),
            'conta'             => $this->_post('conta'),
            'valor'             => $this->_post('valor'),
            'favorecido'        => $this->_post('favorecido'),
            'data_emissao'      => $this->_post('data_emissao'),
            'data_vencimento'   => $this->_post('data_vencimento'),
            'data_compensacao'  => $this->_post('data_compensacao'),
            'situacao'          => $this->_post('situacao'),
            'observacao'        => $this->_post('observacao'),
        );
    }

    private function _post_cadastro($tipo)
    {
        $mapa = array(
            'bancos'  => array('codigo', 'nome'),
            'cartoes' => array('codigo', 'nome'),
            'centros' => array('codigo', 'nome'),
            'naturezas' => array('codigo', 'nome'),
            'status'  => array('codigo', 'nome'),
            'periodicidades' => array('codigo', 'nome'),
            'agencias' => array('banco_codigo', 'agencia', 'nome', 'endereco', 'bairro',
                                'cep', 'cidade', 'uf', 'telefone1', 'telefone2', 'fax', 'contato'),
            'contas'  => array('banco_codigo', 'agencia', 'conta', 'titular', 'valor_limite', 'valor_real'),
            'despesas' => array('codigo', 'nome', 'natureza_codigo', 'periodicidade',
                                'centro_custo', 'fornecedor_codigo', 'valor', 'dia_vencimento',
                                'observacao', 'ativo'),
        );

        $dados = array();

        foreach ($mapa[$tipo] as $campo)
        {
            $dados[$campo] = $this->_post($campo);
        }

        return $dados;
    }

    private function _validar_cadastro($tipo, $dados)
    {
        $obrigatorios = array(
            'bancos'         => array('codigo' => 'codigo', 'nome' => 'nome'),
            'cartoes'        => array('codigo' => 'codigo', 'nome' => 'nome'),
            'centros'        => array('codigo' => 'codigo', 'nome' => 'nome'),
            'naturezas'      => array('codigo' => 'codigo', 'nome' => 'nome'),
            'status'         => array('codigo' => 'codigo', 'nome' => 'nome'),
            'periodicidades' => array('codigo' => 'codigo', 'nome' => 'nome'),
            'despesas'       => array('codigo' => 'codigo', 'nome' => 'nome'),
            'agencias'       => array('banco_codigo' => 'banco', 'agencia' => 'agencia'),
            'contas'         => array('banco_codigo' => 'banco', 'agencia' => 'agencia', 'conta' => 'conta'),
        );

        foreach ($obrigatorios[$tipo] as $campo => $rotulo)
        {
            $v = isset($dados[$campo]) ? $dados[$campo] : NULL;

            if ($v === NULL || trim((string) $v) === '')
            {
                return 'Informe ' . $rotulo . '.';
            }
        }

        return NULL;
    }

    private function _rotulo_cadastro($tipo)
    {
        $rotulos = array(
            'bancos' => 'bancos', 'agencias' => 'agencias', 'contas' => 'contas bancarias',
            'cartoes' => 'cartoes', 'centros' => 'centros de custo',
            'naturezas' => 'naturezas de operacao', 'status' => 'status de pagamento',
            'periodicidades' => 'periodicidades', 'despesas' => 'despesas fixas',
        );

        return isset($rotulos[$tipo]) ? $rotulos[$tipo] : $tipo;
    }

    private function _saldo_anterior($data)
    {
        $row = $this->db->query(
            "SELECT saldo_atual FROM " . T_FIN_CAIXA_DIARIO . "
              WHERE data < ? ORDER BY data DESC LIMIT 1",
            array($data)
        )->row_array();

        return empty($row) ? 0.0 : (float) $row['saldo_atual'];
    }

    private function _soma_caixa($data, $coluna)
    {
        $row = $this->db->query(
            "SELECT COALESCE(sum(" . $coluna . "), 0) AS total
               FROM " . T_FIN_CAIXA_MOVIMENTOS . " WHERE data = ?",
            array($data)
        )->row_array();

        return (float) $row['total'];
    }
}
