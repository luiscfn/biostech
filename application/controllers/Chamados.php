<?php
/**
 * Chamados - Chamados e Ordens de Servico.
 *
 * Telas: pesquisa, ficha com abas, status, encerramento, reabertura,
 * geracao cruzada Chamado <-> OS, impressao e autocomplete.
 */
class Chamados extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('chamado_model');
        $this->load->model('dominio_model');
        $this->load->model('cliente_model');
        $this->load->library('form_validation');

        $this->titulo     = 'Chamados / OS';
        $this->menu_ativo = 'chamados';
    }

    // ==================================================================
    // Pesquisa
    // ==================================================================

    public function index()
    {
        $filtros = array(
            'busca'      => trim((string) $this->input->get('busca')),
            'categoria'  => (string) $this->input->get('categoria'),
            'status'     => (string) $this->input->get('status'),
            'situacao'   => (string) $this->input->get('situacao'),
            'de'         => (string) $this->input->get('de'),
            'ate'        => (string) $this->input->get('ate'),
            'encerrado'  => $this->input->get('encerrado'),
        );

        $pagina = (int) $this->input->get('pagina');
        $total  = $this->chamado_model->total($filtros);
        $pag    = $this->paginar($total, 25, $pagina);

        $dados = array(
            'registros'  => $this->chamado_model->listar($filtros, $pag['por_pagina'], $pag['offset']),
            'filtros'    => $filtros,
            'pag'        => $pag,
            'status'     => $this->dominio_model->opcoes('status_os'),
            'situacoes'  => $this->dominio_model->opcoes('situacao_os'),
        );

        $this->render('chamados/index', $dados);
    }

    // ==================================================================
    // Ficha
    // ==================================================================

    public function ver($numero)
    {
        $registro = $this->chamado_model->por_numero($numero);

        if (empty($registro))
        {
            show_404();
        }

        $aba = (string) $this->input->get('aba');
        $abas = array('dados', 'equipamento', 'atendimento', 'intervencoes',
                      'pecas', 'servicos', 'contatos', 'financeiro', 'historico');

        if ( ! in_array($aba, $abas, TRUE))
        {
            $aba = 'dados';
        }

        $dados = $this->dados_ficha($registro);
        $dados['aba'] = $aba;

        $this->render('chamados/ver', $dados);
    }

    // ------------------------------------------------------------------

    /**
     * Dados comuns da ficha (todas as abas carregadas de uma vez).
     */
    private function dados_ficha($registro)
    {
        $numero = $registro['numero'];

        return array(
            'registro'      => $registro,
            'equipamento'   => $this->chamado_model->equipamento($numero),
            'atendimento'   => $this->chamado_model->atendimento($numero),
            'intervencoes'  => $this->chamado_model->intervencoes($numero),
            'pecas'         => $this->chamado_model->pecas($numero),
            'servicos'      => $this->chamado_model->servicos($numero),
            'contatos'      => $this->chamado_model->contatos($numero),
            'plano'         => $this->chamado_model->plano($numero),
            'parcelas'      => $this->chamado_model->parcelas($numero),
            'contas'        => $this->chamado_model->contas_receber($numero),
            'eventos'       => $this->chamado_model->eventos($numero),
            'log'           => $this->chamado_model->log($numero),
            'tecnicos'      => $this->tecnicos(),
            'dominios'      => $this->mosaico_dominios(),
            'status_opcoes' => $this->chamado_model->status_mudanca(),
            'situacoes'     => $this->dominio_model->opcoes('situacao_os'),
            'total_previsto'=> $this->chamado_model->total_encerramento($numero),
        );
    }

    // ==================================================================
    // Inclusao / alteracao
    // ==================================================================

    public function novo()
    {
        if ($this->eh_post())
        {
            $dados = $this->_post_chamado();

            $this->form_validation->set_rules('categoria', 'Categoria', 'trim|required|in_list[Chamado,Ordem Serviço]');
            $this->form_validation->set_rules('cliente_id', 'Cliente', 'trim|required|integer');
            $this->form_validation->set_rules('data_abertura', 'Data de Abertura', 'trim|required');

            if ($this->form_validation->run())
            {
                $numero = $this->chamado_model->criar($dados, $this->usuario);

                if ($numero)
                {
                    $this->flash('Registro ' . $numero . ' criado com sucesso.');
                    redirect('chamados/ver/' . $numero);
                }

                $this->flash('Nao foi possivel criar o registro. Verifique os dados.', 'danger');
            }
        }

        $dados = array(
            'registro'  => array(),
            'dominios'  => $this->mosaico_dominios(),
            'tecnicos'  => $this->tecnicos(),
            'categorias'=> array('Chamado', 'Ordem Serviço'),
        );

        $this->render('chamados/form', $dados);
    }

    // ------------------------------------------------------------------

    public function editar($numero)
    {
        $registro = $this->chamado_model->por_numero($numero);

        if (empty($registro))
        {
            show_404();
        }

        if ($this->eh_post())
        {
            $dados = $this->_post_chamado();

            $this->form_validation->set_rules('categoria', 'Categoria', 'trim|required|in_list[Chamado,Ordem Serviço]');
            $this->form_validation->set_rules('cliente_id', 'Cliente', 'trim|required|integer');
            $this->form_validation->set_rules('data_abertura', 'Data de Abertura', 'trim|required');

            if ($this->form_validation->run())
            {
                $ok = $this->chamado_model->alterar($numero, $dados, $this->usuario);

                if ($ok)
                {
                    // O formulario tambem edita equipamento e atendimento.
                    $this->chamado_model->salvar_equipamento($numero, array(
                        'equipamento_serie'    => $this->input->post('equipamento_serie'),
                        'tipo_garantia_codigo' => $this->chamado_model->inteiro_ou_nulo($this->input->post('tipo_garantia_codigo')),
                        'patrimonio'           => $this->input->post('patrimonio'),
                        'nota_fiscal_chegada'  => $this->input->post('nota_fiscal_chegada'),
                        'solicitante'          => $this->input->post('solicitante'),
                        'contato'              => $this->input->post('contato'),
                        'local_atendimento'    => $this->input->post('local_atendimento'),
                    ), $this->usuario);

                    $this->chamado_model->salvar_atendimento($numero, array(
                        'prioridade_codigo' => $this->chamado_model->inteiro_ou_nulo($this->input->post('prioridade_codigo')),
                        'segmento_codigo'   => $this->chamado_model->inteiro_ou_nulo($this->input->post('segmento_codigo')),
                        'solicitante'       => $this->input->post('solicitante'),
                        'contato'           => $this->input->post('contato'),
                        'local_atendimento' => $this->input->post('local_atendimento'),
                    ), $this->usuario);

                    $this->flash('Dados atualizados.');
                    redirect('chamados/ver/' . $numero);
                }

                $this->flash('Registro encerrado nao pode ser alterado.', 'danger');
            }
        }

        // Completa a ficha com equipamento e atendimento para o formulario.
        $registro = array_merge(
            $registro,
            $this->chamado_model->equipamento($numero),
            $this->chamado_model->atendimento($numero)
        );

        $this->render('chamados/form', array(
            'registro'  => $registro,
            'dominios'  => $this->mosaico_dominios(),
            'tecnicos'  => $this->tecnicos(),
            'categorias'=> array('Chamado', 'Ordem Serviço'),
        ));
    }

    // ==================================================================
    // Aba Equipamento / Atendimento
    // ==================================================================

    public function equipamento($numero)
    {
        $this->_exigir_registro($numero);

        if ( ! $this->eh_post())
        {
            redirect('chamados/ver/' . $numero . '?aba=equipamento');
        }

        $dados = array();

        foreach (array(
            'equipamento_serie', 'voltagem', 'tipo_garantia_codigo', 'autorizada',
            'acessorios', 'lista_equipamento', 'patrimonio', 'solicitante',
            'local_atendimento', 'voltagem_saida', 'nota_fiscal_chegada', 'contato',
            'rede_fn', 'rede_ft', 'rede_nt', 'estab_marca', 'estab_potencia',
            'estab_kva', 'def_outros',
        ) as $campo)
        {
            $dados[$campo] = $this->input->post($campo);
        }

        foreach (array(
            'recolhido', 'estabilizador', 'def_virus', 'def_software', 'def_disquete',
            'def_cd_dvd', 'def_tensao_imp', 'def_cabos_desc', 'def_nao_canc_chamado',
            'def_abert_equip',
        ) as $campo)
        {
            $dados[$campo] = $this->input->post($campo) ? TRUE : FALSE;
        }

        $dados['equipamento_serie'] = $this->chamado_model->nullify($dados['equipamento_serie']);
        $dados['tipo_garantia_codigo'] = $this->chamado_model->inteiro_ou_nulo($dados['tipo_garantia_codigo']);

        $ok = $this->chamado_model->salvar_equipamento($numero, $dados, $this->usuario);

        $this->flash($ok ? 'Equipamento atualizado.' : 'Nao foi possivel salvar o equipamento.', $ok ? 'success' : 'danger');
        redirect('chamados/ver/' . $numero . '?aba=equipamento');
    }

    // ------------------------------------------------------------------

    public function atendimento($numero)
    {
        $this->_exigir_registro($numero);

        if ( ! $this->eh_post())
        {
            redirect('chamados/ver/' . $numero . '?aba=atendimento');
        }

        $dados = array();

        foreach (array(
            'previsao_data', 'previsao_hora', 'atendimento_data', 'atendimento_hora',
            'atendimento_hora_fim', 'solucao_data', 'solucao_hora', 'devolucao_data',
            'devolucao_hora', 'tecnico_atendimento_id', 'tecnico_abertura_id',
            'tecnico_conclusao_id', 'responsavel_encerramento_id',
            'responsavel_entrega', 'responsavel_recebimento', 'quem_devolveu',
            'quem_recebeu', 'prioridade_codigo', 'segmento_codigo',
            'valor_recolhimento', 'valor_entrega', 'autorizada',
            'contato', 'fone_contato', 'solicitante', 'fone_solicitante',
            'local_atendimento',
        ) as $campo)
        {
            $dados[$campo] = $this->input->post($campo);
        }

        foreach (array('pago_recolhimento', 'pago_entrega', 'garantia', 'recolhido') as $campo)
        {
            $dados[$campo] = $this->input->post($campo) ? TRUE : FALSE;
        }

        foreach (array('tecnico_atendimento_id', 'tecnico_abertura_id', 'tecnico_conclusao_id',
                       'responsavel_encerramento_id', 'responsavel_recebimento', 'quem_devolveu',
                       'prioridade_codigo', 'segmento_codigo', 'autorizada') as $campo)
        {
            $dados[$campo] = $this->chamado_model->inteiro_ou_nulo($dados[$campo]);
        }

        $ok = $this->chamado_model->salvar_atendimento($numero, $dados, $this->usuario);

        $this->flash($ok ? 'Atendimento atualizado.' : 'Nao foi possivel salvar o atendimento.', $ok ? 'success' : 'danger');
        redirect('chamados/ver/' . $numero . '?aba=atendimento');
    }

    // ==================================================================
    // Status
    // ==================================================================

    public function status($numero)
    {
        $registro = $this->_exigir_registro($numero);

        if ($this->eh_post())
        {
            $r = $this->chamado_model->mudar_status(
                $numero,
                $this->input->post('status_codigo'),
                $this->input->post('situacao_codigo'),
                array(
                    'tecnico_atendimento_id' => $this->input->post('tecnico_atendimento_id'),
                    'atendimento_hora_fim'   => $this->input->post('atendimento_hora_fim'),
                ),
                $this->usuario
            );

            $this->flash($r['ok'] ? 'Status alterado.' : $r['erro'], $r['ok'] ? 'success' : 'danger');

            if ($r['ok'])
            {
                redirect('chamados/ver/' . $numero);
            }
        }

        $this->render('chamados/status', array(
            'registro'      => $registro,
            'status_opcoes' => $this->chamado_model->status_mudanca(),
            'situacoes'     => $this->dominio_model->opcoes('situacao_os'),
            'tecnicos'      => $this->tecnicos(),
            'atendimento'   => $this->chamado_model->atendimento($numero),
            'dominios'      => $this->mosaico_dominios(),
        ));
    }

    // ==================================================================
    // Encerramento / reabertura
    // ==================================================================

    public function encerrar($numero)
    {
        $registro = $this->_exigir_registro($numero);

        if ($this->eh_post())
        {
            $r = $this->chamado_model->encerrar($numero, $this->input->post(), $this->usuario);

            if ($r['ok'])
            {
                $msg = 'Encerramento efetuado. Total ' . moeda_br($r['total']) . '.';

                if ( ! empty($r['foi_cr']))
                {
                    $msg .= ' Enviado para o Contas a Receber.';
                }

                if ( ! empty($r['baixa']['ignoradas']))
                {
                    $msg .= ' ' . $r['baixa']['ignoradas'] . ' peca(s) sem saldo nao baixada(s).';
                }

                $this->flash($msg);
                redirect('chamados/ver/' . $numero);
            }

            $this->flash($r['erro'], 'danger');
        }

        $this->render('chamados/encerrar', array(
            'registro'    => $registro,
            'atendimento' => $this->chamado_model->atendimento($numero),
            'pecas'       => $this->chamado_model->pecas($numero),
            'servicos'    => $this->chamado_model->servicos($numero),
            'parcelas'    => $this->chamado_model->parcelas($numero),
            'plano'       => $this->chamado_model->plano($numero),
            'total'       => $this->chamado_model->total_encerramento($numero),
            'dominios'    => $this->mosaico_dominios(),
        ));
    }

    // ------------------------------------------------------------------

    public function reabrir($numero)
    {
        $this->_exigir_registro($numero);

        if ( ! $this->eh_post())
        {
            redirect('chamados/ver/' . $numero);
        }

        $r = $this->chamado_model->reabrir($numero, $this->input->post('motivo'), $this->usuario);

        $this->flash($r['ok'] ? 'Registro reaberto.' : $r['erro'], $r['ok'] ? 'success' : 'danger');
        redirect('chamados/ver/' . $numero);
    }

    // ------------------------------------------------------------------

    public function cancelar_encerramento($numero)
    {
        $this->_exigir_registro($numero);

        if ( ! $this->eh_post())
        {
            redirect('chamados/ver/' . $numero);
        }

        $r = $this->chamado_model->cancelar_encerramento($numero, $this->usuario);

        $this->flash($r['ok'] ? 'Encerramento cancelado. Contas a Receber canceladas.' : $r['erro'], $r['ok'] ? 'success' : 'danger');
        redirect('chamados/ver/' . $numero);
    }

    // ==================================================================
    // Geracao cruzada
    // ==================================================================

    public function gerar($numero)
    {
        $this->_exigir_registro($numero);

        if ( ! $this->eh_post())
        {
            redirect('chamados/ver/' . $numero);
        }

        $r = $this->chamado_model->gerar_par($numero, $this->usuario);

        if ($r['ok'])
        {
            $this->flash($r['categoria'] . ' ' . $r['numero'] . ' gerado(a).');
            redirect('chamados/ver/' . $r['numero']);
        }

        $this->flash($r['erro'], 'danger');
        redirect('chamados/ver/' . $numero);
    }

    // ==================================================================
    // Intervencoes
    // ==================================================================

    public function intervencoes($numero)
    {
        $this->_exigir_registro($numero);

        if ( ! $this->eh_post())
        {
            redirect('chamados/ver/' . $numero . '?aba=intervencoes');
        }

        $r = $this->chamado_model->adicionar_intervencao($numero, array(
            'data_movimento' => $this->input->post('data_movimento'),
            'hora_movimento' => $this->input->post('hora_movimento'),
            'hora_inicio'    => $this->input->post('hora_inicio'),
            'hora_fim'       => $this->input->post('hora_fim'),
            'descricao'      => $this->input->post('descricao'),
            'resolveu'       => $this->input->post('resolveu'),
        ), $this->usuario);

        $this->flash($r['ok'] ? 'Intervencao registrada.' : $r['erro'], $r['ok'] ? 'success' : 'danger');
        redirect('chamados/ver/' . $numero . '?aba=intervencoes');
    }

    // ==================================================================
    // Pecas
    // ==================================================================

    public function pecas($numero)
    {
        $this->_exigir_registro($numero);

        if ( ! $this->eh_post())
        {
            redirect('chamados/ver/' . $numero . '?aba=pecas');
        }

        $r = $this->chamado_model->adicionar_peca($numero, array(
            'produto_id'     => $this->input->post('produto_id'),
            'quantidade'     => $this->input->post('quantidade'),
            'valor_unitario' => $this->input->post('valor_unitario'),
        ), $this->usuario);

        $this->flash($r['ok'] ? 'Peca incluida.' : $r['erro'], $r['ok'] ? 'success' : 'danger');
        redirect('chamados/ver/' . $numero . '?aba=pecas');
    }

    // ------------------------------------------------------------------

    public function excluir_peca($numero, $id)
    {
        $this->_exigir_registro($numero);

        if ( ! $this->eh_post())
        {
            redirect('chamados/ver/' . $numero . '?aba=pecas');
        }

        $r = $this->chamado_model->excluir_peca($numero, (int) $id, $this->usuario);

        $this->flash($r['ok'] ? 'Peca excluida.' : $r['erro'], $r['ok'] ? 'success' : 'danger');
        redirect('chamados/ver/' . $numero . '?aba=pecas');
    }

    // ------------------------------------------------------------------

    public function baixar_estoque($numero)
    {
        $this->_exigir_registro($numero);

        if ( ! $this->eh_post())
        {
            redirect('chamados/ver/' . $numero . '?aba=pecas');
        }

        $r = $this->chamado_model->baixar_estoque($numero, $this->usuario, TRUE);

        $msg = $r['baixadas'] . ' peca(s) baixada(s) do estoque.';

        if ( ! empty($r['ignoradas']))
        {
            $msg .= ' ' . $r['ignoradas'] . ' ignorada(s): ' . implode(' | ', $r['avisos']);
        }

        $this->flash($msg, empty($r['ignoradas']) ? 'success' : 'warning');
        redirect('chamados/ver/' . $numero . '?aba=pecas');
    }

    // ==================================================================
    // Servicos
    // ==================================================================

    public function servicos($numero)
    {
        $this->_exigir_registro($numero);

        if ( ! $this->eh_post())
        {
            redirect('chamados/ver/' . $numero . '?aba=servicos');
        }

        $r = $this->chamado_model->adicionar_servico($numero, array(
            'tipo'           => $this->input->post('tipo'),
            'descricao'      => $this->input->post('descricao'),
            'quantidade'     => $this->input->post('quantidade'),
            'valor_unitario' => $this->input->post('valor_unitario'),
        ), $this->usuario);

        $this->flash($r['ok'] ? 'Servico incluido.' : $r['erro'], $r['ok'] ? 'success' : 'danger');
        redirect('chamados/ver/' . $numero . '?aba=servicos');
    }

    // ------------------------------------------------------------------

    public function concluir_servico($numero, $id)
    {
        $this->_exigir_registro($numero);

        if ( ! $this->eh_post())
        {
            redirect('chamados/ver/' . $numero . '?aba=servicos');
        }

        $r = $this->chamado_model->concluir_servico($numero, (int) $id, $this->usuario);

        $this->flash($r['ok'] ? 'Servico concluido.' : $r['erro'], $r['ok'] ? 'success' : 'danger');
        redirect('chamados/ver/' . $numero . '?aba=servicos');
    }

    // ==================================================================
    // Contatos
    // ==================================================================

    public function contatos($numero)
    {
        $this->_exigir_registro($numero);

        if ( ! $this->eh_post())
        {
            redirect('chamados/ver/' . $numero . '?aba=contatos');
        }

        $r = $this->chamado_model->adicionar_contato($numero, array(
            'data_movimento'       => $this->input->post('data_movimento'),
            'hora_movimento'       => $this->input->post('hora_movimento'),
            'descricao'            => $this->input->post('descricao'),
            'contato'              => $this->input->post('contato'),
            'proximo_contato_data' => $this->input->post('proximo_contato_data'),
            'proximo_contato_hora' => $this->input->post('proximo_contato_hora'),
            'concluido'            => $this->input->post('concluido'),
        ), $this->usuario);

        $this->flash($r['ok'] ? 'Contato registrado.' : $r['erro'], $r['ok'] ? 'success' : 'danger');
        redirect('chamados/ver/' . $numero . '?aba=contatos');
    }

    // ==================================================================
    // Financeiro
    // ==================================================================

    public function financeiro($numero)
    {
        $this->_exigir_registro($numero);

        if ( ! $this->eh_post())
        {
            redirect('chamados/ver/' . $numero . '?aba=financeiro');
        }

        $r = $this->chamado_model->emite_parcelas($numero, array(
            'valor_total'                => $this->input->post('valor_total'),
            'quantidade_parcelas'        => $this->input->post('quantidade_parcelas'),
            'data_emissao'               => $this->input->post('data_emissao'),
            'primeiro_vencimento'        => $this->input->post('primeiro_vencimento'),
            'intervalo_dias'             => $this->input->post('intervalo_dias'),
            'condicao_pagamento_codigo'  => $this->input->post('condicao_pagamento_codigo'),
            'forma_recebimento_codigo'   => $this->input->post('forma_recebimento_codigo'),
            'documento_codigo'           => $this->input->post('documento_codigo'),
            'cliente_nota_id'            => $this->input->post('cliente_nota_id'),
        ), $this->usuario);

        $this->flash($r['ok'] ? 'Plano de parcelas atualizado.' : $r['erro'], $r['ok'] ? 'success' : 'danger');
        redirect('chamados/ver/' . $numero . '?aba=financeiro');
    }

    // ==================================================================
    // Impressao
    // ==================================================================

    public function imprimir($numero)
    {
        $registro = $this->chamado_model->por_numero($numero);

        if (empty($registro))
        {
            show_404();
        }

        $dados = $this->dados_ficha($registro);
        $dados['titulo'] = 'OS ' . $numero;

        $this->load->view('chamados/imprimir', $dados);
    }

    // ==================================================================
    // Autocomplete (JSON)
    // ==================================================================

    public function buscar_clientes()
    {
        $termo = trim((string) $this->input->get('termo'));
        $lista = $termo === '' ? array() : $this->chamado_model->buscar_clientes($termo);

        $this->_json($lista);
    }

    // ------------------------------------------------------------------

    public function buscar_produtos()
    {
        $termo = trim((string) $this->input->get('termo'));
        $lista = $termo === '' ? array() : $this->chamado_model->buscar_produtos($termo);

        $this->_json($lista);
    }

    // ==================================================================
    // Helpers
    // ==================================================================

    private function _post_chamado()
    {
        return array(
            'categoria'            => $this->input->post('categoria'),
            'cliente_id'           => (int) $this->input->post('cliente_id'),
            'numero_par'           => $this->input->post('numero_par'),
            'data_abertura'        => $this->input->post('data_abertura'),
            'hora_abertura'        => $this->input->post('hora_abertura'),
            'equipamento_serie'    => $this->input->post('equipamento_serie'),
            'tipo_garantia_codigo' => $this->input->post('tipo_garantia_codigo'),
            'patrimonio'           => $this->input->post('patrimonio'),
            'solicitante'          => $this->input->post('solicitante'),
            'local_atendimento'    => $this->input->post('local_atendimento'),
            'contato'              => $this->input->post('contato'),
            'nota_fiscal_chegada'  => $this->input->post('nota_fiscal_chegada'),
            'prioridade_codigo'    => $this->input->post('prioridade_codigo'),
            'segmento_codigo'      => $this->input->post('segmento_codigo'),
            'defeito_relatado'     => $this->input->post('defeito_relatado'),
            'observacao'           => $this->input->post('observacao'),
        );
    }

    // ------------------------------------------------------------------

    private function _exigir_registro($numero)
    {
        $registro = $this->chamado_model->por_numero($numero);

        if (empty($registro))
        {
            show_404();
        }

        return $registro;
    }

    // ------------------------------------------------------------------

    private function tecnicos()
    {
        $sql = "SELECT DISTINCT funcionario_id AS id, nome
                  FROM " . T_USUARIOS . "
                 WHERE ativo AND funcionario_id IS NOT NULL
                 ORDER BY nome";

        return $this->db->query($sql)->result_array();
    }

    // ------------------------------------------------------------------

    private function mosaico_dominios()
    {
        return array(
            'status_os'          => $this->dominio_model->mapa('status_os'),
            'situacao_os'        => $this->dominio_model->mapa('situacao_os'),
            'tipo_garantia'      => $this->dominio_model->mapa('tipo_garantia'),
            'prioridade'         => $this->dominio_model->mapa('prioridade'),
            'segmento'           => $this->dominio_model->mapa('segmento'),
            'forma_recebimento'  => $this->dominio_model->mapa('forma_recebimento'),
            'condicao_pagamento' => $this->dominio_model->mapa('condicao_pagamento'),
            'documento'          => $this->dominio_model->mapa('documento'),
            'tipo_pagamento'     => $this->dominio_model->mapa('tipo_pagamento'),
        );
    }

    // ------------------------------------------------------------------

    private function _json($dados)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($dados));
    }
}
