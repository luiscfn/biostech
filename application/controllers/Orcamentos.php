<?php
/**
 * Orcamentos - modulo comercial (propostas) e dimensionamento tecnico.
 *
 * Origem Access: F_ORCAMENTO_01 / F_ORCAMENTO_PESQ_01 / F_ORC_DIMEN_01 /
 * F_ORC_DIMEN_05. Regras em docs/09-regras-orcamentos.md.
 *
 * Fluxo comercial:
 *   aberto -> enviado -> pendente -> concluido -> encerrado (situacao)
 *   resultado: aprovado / nao aprovado / em analise / cancelado
 *   aprovado + concluido + datas de envio/aprovacao -> gera chamado.
 *
 * Fluxo dimensionamento:
 *   levantamento tecnico -> itens -> servicos -> gera orcamento comercial.
 */
class Orcamentos extends MY_Controller
{
    /** campos do cabecalho comercial aceitos no POST */
    private static $campos_post = array(
        'data', 'cliente_id', 'chamado_numero', 'equipamento_serie',
        'nota_fiscal_chegada', 'atencao', 'finalidade', 'observacao',
        'condicao_pagamento', 'entrega', 'garantia', 'validade',
        'validade_pecas', 'assinatura', 'situacao', 'resultado',
        'percentual_desconto', 'percentual_imposto', 'percentual_mao_obra',
        'percentual_servico', 'percentual_nota_fiscal',
        'percentual_mat_fixacao', 'percentual_acrescimo_custo',
        'valor_mao_obra', 'valor_imposto', 'valor_desconto',
        'material_fixacao', 'acrescimo_custo', 'valor_maximo_cliente',
        'valor_custos_indiretos', 'desconto_percentual', 'acrescimo_percentual',
    );

    /** campos do dimensionamento aceitos no POST */
    private static $campos_dim_post = array(
        'data', 'chamado_numero', 'cliente_id', 'contato', 'tecnico_id',
        'local_instalacao', 'tipo_contato', 'setor', 'email', 'qtd_tecnicos',
        'qtd_comerciais', 'qtd_nao_comerciais', 'solicitante',
        'qtd_dias', 'que_funciona', 'quer_fazer', 'dificuldades',
        'material_precisa', 'providenciar', 'observacao',
        'altura', 'comprimento_muro', 'lado_direito', 'lado_esquerdo',
        'frente', 'fundo', 'rebocado', 'cerca_arame', 'telhado', 'arvore',
        'cinta_cimento', 'pega_ladrao',
        'percentual_mat_fixacao', 'percentual_mao_obra',
        'percentual_nota_fiscal', 'percentual_acrescimo_custo',
        'segunda', 'segunda_inicio', 'segunda_fim',
        'terca', 'terca_inicio', 'terca_fim',
        'quarta', 'quarta_inicio', 'quarta_fim',
        'quinta', 'quinta_inicio', 'quinta_fim',
        'sexta', 'sexta_inicio', 'sexta_fim',
        'sabado', 'sabado_inicio', 'sabado_fim',
        'domingo', 'domingo_inicio', 'domingo_fim',
    );

    public function __construct()
    {
        parent::__construct();

        $this->load->model('orcamentos_model');
        $this->load->library('auditoria');
        $this->load->library('form_validation');

        $this->titulo     = 'Orcamentos';
        $this->menu_ativo = 'orcamentos';
    }

    // ==================================================================
    // Comercial - lista
    // ==================================================================

    public function index()
    {
        $filtros = array(
            'busca'     => trim((string) $this->input->get('busca')),
            'cliente'   => (int) $this->input->get('cliente'),
            'cliente_nome' => trim((string) $this->input->get('cliente_nome')),
            'situacao'  => trim((string) $this->input->get('situacao')),
            'resultado' => trim((string) $this->input->get('resultado')),
            'data_de'   => trim((string) $this->input->get('data_de')),
            'data_ate'  => trim((string) $this->input->get('data_ate')),
            'com_os'    => trim((string) $this->input->get('com_os')),
            'com_dim'   => trim((string) $this->input->get('com_dim')),
        );

        $pagina = (int) $this->input->get('pagina');
        $total  = $this->orcamentos_model->total($filtros);
        $pag    = $this->paginar($total, 25, $pagina);

        $this->render('orcamentos/index', array(
            'registros'   => $this->orcamentos_model->listar($filtros, $pag['por_pagina'], $pag['offset']),
            'filtros'     => $filtros,
            'pag'         => $pag,
            'stats'       => $this->orcamentos_model->stats(),
            'situacoes'   => $this->orcamentos_model->situacoes(),
            'resultados'  => $this->orcamentos_model->resultados(),
        ));
    }

    // ==================================================================
    // Comercial - CRUD do cabecalho
    // ==================================================================

    public function novo()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ($this->eh_post())
        {
            $dados = $this->_post_orcamento();

            $resultado = $this->orcamentos_model->salvar($dados, $this->usuario);

            if ( ! $resultado['ok'])
            {
                $this->flash($resultado['erro'], 'erro');
                redirect('orcamentos/novo');
            }

            $this->auditoria->registrar('criar', 'orcamento', $resultado['numero'], array(
                'cliente_id' => isset($dados['cliente_id']) ? (int) $dados['cliente_id'] : NULL,
            ));

            $this->flash('Orcamento ' . $resultado['numero'] . ' criado. Inclua os itens na aba Itens.');
            redirect('orcamentos/ver/' . $resultado['numero'] . '?aba=itens');
        }

        $this->titulo = 'Novo orcamento';
        $this->render('orcamentos/form', array(
            'registro'    => array(),
            'finalidades' => $this->orcamentos_model->finalidades(),
        ));
    }

    // ------------------------------------------------------------------

    public function editar($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $numero = (int) $numero;
        $orcamento = $this->orcamentos_model->por_numero($numero);

        if (empty($orcamento))
        {
            show_404();
        }

        if ($this->eh_post())
        {
            $dados = $this->_post_orcamento();
            $dados['numero'] = $numero;

            $resultado = $this->orcamentos_model->salvar($dados, $this->usuario);

            if ( ! $resultado['ok'])
            {
                $this->flash($resultado['erro'], 'erro');
                redirect('orcamentos/editar/' . $numero);
            }

            $this->auditoria->registrar('alterar', 'orcamento', $numero, NULL);

            $this->flash('Orcamento ' . $numero . ' atualizado.');
            redirect('orcamentos/ver/' . $numero);
        }

        $this->titulo = 'Editar orcamento ' . $numero;
        $this->render('orcamentos/form', array(
            'registro'    => $orcamento,
            'finalidades' => $this->orcamentos_model->finalidades(),
        ));
    }

    // ------------------------------------------------------------------

    public function ver($numero)
    {
        $numero = (int) $numero;
        $orcamento = $this->orcamentos_model->por_numero($numero);

        if (empty($orcamento))
        {
            show_404();
        }

        $abas = array('dados', 'itens', 'servicos', 'servicos_valores', 'contatos', 'log');
        $aba  = (string) $this->input->get('aba');

        if ( ! in_array($aba, $abas, TRUE))
        {
            $aba = 'dados';
        }

        $this->titulo = 'Orcamento ' . $numero;

        $this->render('orcamentos/ver', array(
            'registro'       => $orcamento,
            'itens'          => $this->orcamentos_model->itens($numero),
            'servicos'       => $this->orcamentos_model->servicos($numero),
            'servicos_valores' => $this->orcamentos_model->servicos_valores($numero),
            'contatos'       => $this->orcamentos_model->contatos($numero),
            'log'            => $this->orcamentos_model->log($numero),
            'funcionarios'   => $this->orcamentos_model->funcionarios(),
            'aba'            => $aba,
            'abas'           => $abas,
        ));
    }

    // ------------------------------------------------------------------

    public function excluir($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/ver/' . (int) $numero);
        }

        $resultado = $this->orcamentos_model->excluir_orcamento((int) $numero, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
            redirect('orcamentos/ver/' . (int) $numero);
        }

        $this->auditoria->registrar('excluir', 'orcamento', (int) $numero, NULL);
        $this->flash('Orcamento excluido.');
        redirect('orcamentos');
    }

    // ==================================================================
    // Comercial - status / chamado / impressao
    // ==================================================================

    public function status($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $numero = (int) $numero;
        $orcamento = $this->orcamentos_model->por_numero($numero);

        if (empty($orcamento))
        {
            show_404();
        }

        if ($this->eh_post())
        {
            $dados = array(
                'data_envio'          => $this->input->post('data_envio'),
                'data_aprovacao'      => $this->input->post('data_aprovacao'),
                'motivo_cancelamento' => $this->input->post('motivo_cancelamento'),
            );

            $resultado = $this->orcamentos_model->mudar_status(
                $numero,
                $this->input->post('situacao'),
                $this->input->post('resultado'),
                $dados,
                $this->usuario
            );

            if ( ! $resultado['ok'])
            {
                $this->flash($resultado['erro'], 'erro');
                redirect('orcamentos/status/' . $numero);
            }

            $this->auditoria->registrar('alterar', 'orcamento_status', $numero, array(
                'situacao'  => $this->input->post('situacao'),
                'resultado' => $this->input->post('resultado'),
            ));

            $this->flash('Status do orcamento atualizado.');
            redirect('orcamentos/ver/' . $numero);
        }

        $this->titulo = 'Status do orcamento ' . $numero;

        $this->render('orcamentos/status', array(
            'registro'   => $orcamento,
            'situacoes'  => $this->orcamentos_model->situacoes(),
            'resultados' => $this->orcamentos_model->resultados(),
        ));
    }

    // ------------------------------------------------------------------

    public function gerar_chamado($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/ver/' . (int) $numero);
        }

        $resultado = $this->orcamentos_model->gerar_chamado((int) $numero, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
            redirect('orcamentos/ver/' . (int) $numero);
        }

        $this->auditoria->registrar('criar', 'chamado', $resultado['chamado'], array(
            'origem'   => 'orcamento',
            'orcamento' => (int) $numero,
        ));

        $this->flash('Chamado ' . $resultado['chamado'] . ' gerado a partir do orcamento ' . (int) $numero . '.');
        redirect('chamados/ver/' . $resultado['chamado']);
    }

    // ------------------------------------------------------------------

    public function imprimir($numero)
    {
        $numero = (int) $numero;
        $orcamento = $this->orcamentos_model->por_numero($numero);

        if (empty($orcamento))
        {
            show_404();
        }

        $this->load->view('orcamentos/imprimir', array(
            'registro'         => $orcamento,
            'itens'            => $this->orcamentos_model->itens($numero),
            'servicos'         => $this->orcamentos_model->servicos($numero),
            'servicos_valores' => $this->orcamentos_model->servicos_valores($numero),
        ));
    }

    // ==================================================================
    // Comercial - itens
    // ==================================================================

    public function adicionar_item($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/ver/' . (int) $numero . '?aba=itens');
        }

        $resultado = $this->orcamentos_model->adicionar_item((int) $numero, array(
            'produto_id'        => $this->input->post('produto_id'),
            'descricao_produto' => $this->input->post('descricao_produto'),
            'descricao'         => $this->input->post('descricao'),
            'quantidade'        => $this->input->post('quantidade'),
            'valor_unitario'    => $this->input->post('valor_unitario'),
            'valor_total'       => $this->input->post('valor_total'),
        ), $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'orcamento_item', (int) $numero, NULL);
            $this->flash('Item incluido no orcamento.');
        }

        redirect('orcamentos/ver/' . (int) $numero . '?aba=itens');
    }

    // ------------------------------------------------------------------

    public function atualizar_item($numero, $id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/ver/' . (int) $numero . '?aba=itens');
        }

        $resultado = $this->orcamentos_model->atualizar_item((int) $id, array(
            'descricao'      => $this->input->post('descricao'),
            'quantidade'     => $this->input->post('quantidade'),
            'valor_unitario' => $this->input->post('valor_unitario'),
            'valor_total'    => $this->input->post('valor_total'),
        ), $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'orcamento_item', (int) $numero, array('item_id' => (int) $id));
            $this->flash('Item atualizado.');
        }

        redirect('orcamentos/ver/' . (int) $numero . '?aba=itens');
    }

    // ------------------------------------------------------------------

    public function excluir_item($numero, $id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/ver/' . (int) $numero . '?aba=itens');
        }

        $resultado = $this->orcamentos_model->excluir_item((int) $id, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('excluir', 'orcamento_item', (int) $numero, array('item_id' => (int) $id));
            $this->flash('Item excluido.');
        }

        redirect('orcamentos/ver/' . (int) $numero . '?aba=itens');
    }

    // ------------------------------------------------------------------

    public function aplicar_percentual($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/ver/' . (int) $numero . '?aba=itens');
        }

        $tipo = (string) $this->input->post('tipo') === 'acrescimo' ? 'acrescimo' : 'desconto';

        $resultado = $this->orcamentos_model->aplicar_percentual(
            (int) $numero,
            $tipo,
            $this->input->post('percentual'),
            $this->usuario
        );

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'orcamento_item', (int) $numero, array(
                'tipo'       => $tipo,
                'percentual' => $this->input->post('percentual'),
            ));
            $this->flash('Percentual de ' . $tipo . ' aplicado aos itens.');
        }

        redirect('orcamentos/ver/' . (int) $numero . '?aba=itens');
    }

    // ------------------------------------------------------------------

    public function restaurar_valores($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/ver/' . (int) $numero . '?aba=itens');
        }

        $resultado = $this->orcamentos_model->restaurar_valores((int) $numero, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'orcamento_item', (int) $numero, array('restaurado' => TRUE));
            $this->flash('Valores originais restaurados.');
        }

        redirect('orcamentos/ver/' . (int) $numero . '?aba=itens');
    }

    // ==================================================================
    // Comercial - servicos a executar e servicos com valor
    // ==================================================================

    public function adicionar_servico($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/ver/' . (int) $numero . '?aba=servicos');
        }

        $resultado = $this->orcamentos_model->adicionar_servico((int) $numero, array(
            'descricao' => $this->input->post('descricao'),
            'concluido' => $this->input->post('concluido'),
        ), $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'orcamento_servico', (int) $numero, NULL);
            $this->flash('Servico incluido.');
        }

        redirect('orcamentos/ver/' . (int) $numero . '?aba=servicos');
    }

    // ------------------------------------------------------------------

    public function atualizar_servico($numero, $id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/ver/' . (int) $numero . '?aba=servicos');
        }

        $resultado = $this->orcamentos_model->atualizar_servico((int) $id, array(
            'descricao' => $this->input->post('descricao'),
            'concluido' => $this->input->post('concluido'),
        ), $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'orcamento_servico', (int) $numero, array('servico_id' => (int) $id));
            $this->flash('Servico atualizado.');
        }

        redirect('orcamentos/ver/' . (int) $numero . '?aba=servicos');
    }

    // ------------------------------------------------------------------

    public function excluir_servico($numero, $id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/ver/' . (int) $numero . '?aba=servicos');
        }

        $resultado = $this->orcamentos_model->excluir_servico((int) $id, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('excluir', 'orcamento_servico', (int) $numero, array('servico_id' => (int) $id));
            $this->flash('Servico excluido.');
        }

        redirect('orcamentos/ver/' . (int) $numero . '?aba=servicos');
    }

    // ------------------------------------------------------------------

    public function adicionar_servico_valor($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/ver/' . (int) $numero . '?aba=servicos_valores');
        }

        $resultado = $this->orcamentos_model->adicionar_servico_valor((int) $numero, array(
            'descricao'      => $this->input->post('descricao'),
            'quantidade'     => $this->input->post('quantidade'),
            'valor_unitario' => $this->input->post('valor_unitario'),
            'valor_total'    => $this->input->post('valor_total'),
        ), $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'orcamento_servico_valor', (int) $numero, NULL);
            $this->flash('Servico com valor incluido.');
        }

        redirect('orcamentos/ver/' . (int) $numero . '?aba=servicos_valores');
    }

    // ------------------------------------------------------------------

    public function atualizar_servico_valor($numero, $id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/ver/' . (int) $numero . '?aba=servicos_valores');
        }

        $resultado = $this->orcamentos_model->atualizar_servico_valor((int) $id, array(
            'descricao'      => $this->input->post('descricao'),
            'quantidade'     => $this->input->post('quantidade'),
            'valor_unitario' => $this->input->post('valor_unitario'),
            'valor_total'    => $this->input->post('valor_total'),
        ), $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'orcamento_servico_valor', (int) $numero, array('servico_id' => (int) $id));
            $this->flash('Servico com valor atualizado.');
        }

        redirect('orcamentos/ver/' . (int) $numero . '?aba=servicos_valores');
    }

    // ------------------------------------------------------------------

    public function excluir_servico_valor($numero, $id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/ver/' . (int) $numero . '?aba=servicos_valores');
        }

        $resultado = $this->orcamentos_model->excluir_servico_valor((int) $id, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('excluir', 'orcamento_servico_valor', (int) $numero, array('servico_id' => (int) $id));
            $this->flash('Servico com valor excluido.');
        }

        redirect('orcamentos/ver/' . (int) $numero . '?aba=servicos_valores');
    }

    // ==================================================================
    // Comercial - contatos / follow-up
    // ==================================================================

    public function adicionar_contato($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/ver/' . (int) $numero . '?aba=contatos');
        }

        $resultado = $this->orcamentos_model->adicionar_contato((int) $numero, array(
            'data_mov'             => $this->input->post('data_mov'),
            'hora_mov'             => $this->input->post('hora_mov'),
            'assunto'              => $this->input->post('assunto'),
            'funcionario_id'       => $this->input->post('funcionario_id'),
            'contato'              => $this->input->post('contato'),
            'descricao'            => $this->input->post('descricao'),
            'data_proximo_contato' => $this->input->post('data_proximo_contato'),
            'hora_proximo_contato' => $this->input->post('hora_proximo_contato'),
        ), $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'orcamento_contato', (int) $numero, NULL);
            $this->flash('Contato registrado.');
        }

        redirect('orcamentos/ver/' . (int) $numero . '?aba=contatos');
    }

    // ------------------------------------------------------------------

    public function excluir_contato($numero, $id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/ver/' . (int) $numero . '?aba=contatos');
        }

        $resultado = $this->orcamentos_model->excluir_contato((int) $id, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('excluir', 'orcamento_contato', (int) $numero, array('contato_id' => (int) $id));
            $this->flash('Contato excluido.');
        }

        redirect('orcamentos/ver/' . (int) $numero . '?aba=contatos');
    }

    // ==================================================================
    // Dimensionamento - lista e CRUD
    // ==================================================================

    public function dimensionamentos()
    {
        $filtros = array(
            'busca'         => trim((string) $this->input->get('busca')),
            'cliente'       => (int) $this->input->get('cliente'),
            'data_de'       => trim((string) $this->input->get('data_de')),
            'data_ate'      => trim((string) $this->input->get('data_ate')),
            'com_orcamento' => trim((string) $this->input->get('com_orcamento')),
            'com_os'        => trim((string) $this->input->get('com_os')),
        );

        $pagina = (int) $this->input->get('pagina');
        $total  = $this->orcamentos_model->total_dimensionamentos($filtros);
        $pag    = $this->paginar($total, 25, $pagina);

        $this->titulo = 'Dimensionamentos';

        $this->render('orcamentos/dimensionamentos', array(
            'registros' => $this->orcamentos_model->listar_dimensionamentos($filtros, $pag['por_pagina'], $pag['offset']),
            'filtros'   => $filtros,
            'pag'       => $pag,
            'stats'     => $this->orcamentos_model->stats_dimensionamento(),
        ));
    }

    // ------------------------------------------------------------------

    public function dimensionamento_novo()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ($this->eh_post())
        {
            $dados = $this->_post_dimensionamento();

            $resultado = $this->orcamentos_model->salvar_dimensionamento($dados, $this->usuario);

            if ( ! $resultado['ok'])
            {
                $this->flash($resultado['erro'], 'erro');
                redirect('orcamentos/dimensionamento_novo');
            }

            $this->auditoria->registrar('criar', 'dimensionamento', $resultado['numero'], array(
                'cliente_id' => isset($dados['cliente_id']) ? (int) $dados['cliente_id'] : NULL,
            ));

            $this->flash('Dimensionamento ' . $resultado['numero'] . ' criado.');
            redirect('orcamentos/dimensionamento/' . $resultado['numero']);
        }

        $this->titulo = 'Novo dimensionamento';

        $this->render('orcamentos/dimensionamento_form', array(
            'registro'     => array(),
            'funcionarios' => $this->orcamentos_model->funcionarios(),
        ));
    }

    // ------------------------------------------------------------------

    public function dimensionamento_editar($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $numero = (int) $numero;
        $registro = $this->orcamentos_model->dimensionamento($numero);

        if (empty($registro))
        {
            show_404();
        }

        if ($this->eh_post())
        {
            $dados = $this->_post_dimensionamento();
            $dados['numero'] = $numero;

            $resultado = $this->orcamentos_model->salvar_dimensionamento($dados, $this->usuario);

            if ( ! $resultado['ok'])
            {
                $this->flash($resultado['erro'], 'erro');
                redirect('orcamentos/dimensionamento_editar/' . $numero);
            }

            $this->auditoria->registrar('alterar', 'dimensionamento', $numero, NULL);

            $this->flash('Dimensionamento ' . $numero . ' atualizado.');
            redirect('orcamentos/dimensionamento/' . $numero);
        }

        $this->titulo = 'Editar dimensionamento ' . $numero;

        $this->render('orcamentos/dimensionamento_form', array(
            'registro'     => $registro,
            'funcionarios' => $this->orcamentos_model->funcionarios(),
        ));
    }

    // ------------------------------------------------------------------

    public function dimensionamento($numero)
    {
        $numero = (int) $numero;
        $registro = $this->orcamentos_model->dimensionamento($numero);

        if (empty($registro))
        {
            show_404();
        }

        $abas = array('dados', 'itens', 'servicos', 'levantamento', 'cronograma',
                      'materiais', 'cotacoes', 'log');
        $aba = (string) $this->input->get('aba');

        if ( ! in_array($aba, $abas, TRUE))
        {
            $aba = 'dados';
        }

        $this->titulo = 'Dimensionamento ' . $numero;

        $this->render('orcamentos/dimensionamento', array(
            'registro'   => $registro,
            'itens'      => $this->orcamentos_model->itens_dimensionamento($numero),
            'servicos'   => $this->orcamentos_model->servicos_dimensionamento($numero),
            'materiais'  => $this->orcamentos_model->materiais_dimensionamento($numero),
            'cotacoes'   => $this->orcamentos_model->cotacoes_dimensionamento($numero),
            'log'        => $this->orcamentos_model->log_dimensionamento($numero),
            'aba'        => $aba,
            'abas'       => $abas,
        ));
    }

    // ------------------------------------------------------------------

    public function dimensionamento_excluir($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/dimensionamento/' . (int) $numero);
        }

        $resultado = $this->orcamentos_model->excluir_dimensionamento((int) $numero, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
            redirect('orcamentos/dimensionamento/' . (int) $numero);
        }

        $this->auditoria->registrar('excluir', 'dimensionamento', (int) $numero, NULL);
        $this->flash('Dimensionamento excluido.');
        redirect('orcamentos/dimensionamentos');
    }

    // ------------------------------------------------------------------

    public function dimensionamento_gerar_orcamento($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/dimensionamento/' . (int) $numero);
        }

        $resultado = $this->orcamentos_model->gerar_orcamento((int) $numero, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
            redirect('orcamentos/dimensionamento/' . (int) $numero);
        }

        $this->auditoria->registrar('criar', 'orcamento', $resultado['orcamento'], array(
            'origem'          => 'dimensionamento',
            'dimensionamento' => (int) $numero,
        ));

        $this->flash('Orcamento ' . $resultado['orcamento'] . ' gerado a partir do dimensionamento ' . (int) $numero . '.');
        redirect('orcamentos/ver/' . $resultado['orcamento'] . '?aba=itens');
    }

    // ------------------------------------------------------------------

    public function dimensionamento_imprimir($numero)
    {
        $numero = (int) $numero;
        $registro = $this->orcamentos_model->dimensionamento($numero);

        if (empty($registro))
        {
            show_404();
        }

        $this->load->view('orcamentos/dimensionamento_imprimir', array(
            'registro'  => $registro,
            'itens'     => $this->orcamentos_model->itens_dimensionamento($numero),
            'servicos'  => $this->orcamentos_model->servicos_dimensionamento($numero),
            'materiais' => $this->orcamentos_model->materiais_dimensionamento($numero),
        ));
    }

    // ==================================================================
    // Dimensionamento - itens e servicos
    // ==================================================================

    public function dim_adicionar_item($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/dimensionamento/' . (int) $numero . '?aba=itens');
        }

        $resultado = $this->orcamentos_model->adicionar_item_dimensionamento((int) $numero, array(
            'produto_id'        => $this->input->post('produto_id'),
            'descricao_produto' => $this->input->post('descricao_produto'),
            'descricao'         => $this->input->post('descricao'),
            'quantidade'        => $this->input->post('quantidade'),
            'valor_compra'      => $this->input->post('valor_compra'),
            'valor_gasto'       => $this->input->post('valor_gasto'),
            'valor_venda'       => $this->input->post('valor_venda'),
            'grupo'             => $this->input->post('grupo'),
            'subgrupo'          => $this->input->post('subgrupo'),
            'pg'                => $this->input->post('pg'),
            'psg'               => $this->input->post('psg'),
        ), $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'dimensionamento_item', (int) $numero, NULL);
            $this->flash('Item incluido no dimensionamento.');
        }

        redirect('orcamentos/dimensionamento/' . (int) $numero . '?aba=itens');
    }

    // ------------------------------------------------------------------

    public function dim_atualizar_item($numero, $id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/dimensionamento/' . (int) $numero . '?aba=itens');
        }

        $resultado = $this->orcamentos_model->atualizar_item_dimensionamento((int) $id, array(
            'quantidade'   => $this->input->post('quantidade'),
            'valor_compra' => $this->input->post('valor_compra'),
            'valor_venda'  => $this->input->post('valor_venda'),
        ), $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'dimensionamento_item', (int) $numero, array('item_id' => (int) $id));
            $this->flash('Item atualizado.');
        }

        redirect('orcamentos/dimensionamento/' . (int) $numero . '?aba=itens');
    }

    // ------------------------------------------------------------------

    public function dim_excluir_item($numero, $id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/dimensionamento/' . (int) $numero . '?aba=itens');
        }

        $resultado = $this->orcamentos_model->excluir_item_dimensionamento((int) $id, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('excluir', 'dimensionamento_item', (int) $numero, array('item_id' => (int) $id));
            $this->flash('Item excluido.');
        }

        redirect('orcamentos/dimensionamento/' . (int) $numero . '?aba=itens');
    }

    // ------------------------------------------------------------------

    public function dim_adicionar_servico($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/dimensionamento/' . (int) $numero . '?aba=servicos');
        }

        $resultado = $this->orcamentos_model->adicionar_servico_dimensionamento((int) $numero, array(
            'descricao' => $this->input->post('descricao'),
            'concluido' => $this->input->post('concluido'),
        ), $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'dimensionamento_servico', (int) $numero, NULL);
            $this->flash('Servico incluido.');
        }

        redirect('orcamentos/dimensionamento/' . (int) $numero . '?aba=servicos');
    }

    // ------------------------------------------------------------------

    public function dim_atualizar_servico($numero, $id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/dimensionamento/' . (int) $numero . '?aba=servicos');
        }

        $resultado = $this->orcamentos_model->atualizar_servico_dimensionamento((int) $id, array(
            'descricao' => $this->input->post('descricao'),
            'concluido' => $this->input->post('concluido'),
        ), $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'dimensionamento_servico', (int) $numero, array('servico_id' => (int) $id));
            $this->flash('Servico atualizado.');
        }

        redirect('orcamentos/dimensionamento/' . (int) $numero . '?aba=servicos');
    }

    // ------------------------------------------------------------------

    public function dim_excluir_servico($numero, $id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('orcamentos/dimensionamento/' . (int) $numero . '?aba=servicos');
        }

        $resultado = $this->orcamentos_model->excluir_servico_dimensionamento((int) $id, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('excluir', 'dimensionamento_servico', (int) $numero, array('servico_id' => (int) $id));
            $this->flash('Servico excluido.');
        }

        redirect('orcamentos/dimensionamento/' . (int) $numero . '?aba=servicos');
    }

    // ==================================================================
    // Autocomplete (JSON)
    // ==================================================================

    public function buscar_clientes()
    {
        $termo = trim((string) $this->input->get('termo'));
        $lista = $termo === '' ? array() : $this->orcamentos_model->buscar_clientes($termo, 25);

        $this->_json($lista);
    }

    // ------------------------------------------------------------------

    public function buscar_produtos()
    {
        $termo = trim((string) $this->input->get('termo'));
        $lista = $termo === '' ? array() : $this->orcamentos_model->buscar_produtos($termo, 25);

        $this->_json($lista);
    }

    // ==================================================================
    // Helpers
    // ==================================================================

    private function _post_orcamento()
    {
        $dados = array();

        foreach (self::$campos_post as $campo)
        {
            $dados[$campo] = $this->input->post($campo);
        }

        return $dados;
    }

    // ------------------------------------------------------------------

    private function _post_dimensionamento()
    {
        $dados = array();

        foreach (self::$campos_dim_post as $campo)
        {
            $dados[$campo] = $this->input->post($campo);
        }

        return $dados;
    }

    // ------------------------------------------------------------------

    private function _json($dados)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($dados));
    }
}
