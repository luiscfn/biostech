<?php
/**
 * Estoque - saldos, movimentos, entradas, saidas, ajustes, catalogos
 * e fornecedores.
 *
 * Regras principais:
 *  - saldo nunca negativo (D-04);
 *  - correcao de movimento somente por estorno;
 *  - movimento gerado por Chamado/OS so pode ser estornado pelo
 *    cancelamento do encerramento.
 */
class Estoque extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('estoque_model');
        $this->load->library('auditoria');
        $this->load->library('form_validation');

        $this->titulo     = 'Estoque';
        $this->menu_ativo = 'estoque';
    }

    // ==================================================================
    // Saldos
    // ==================================================================

    public function index()
    {
        $filtros = array(
            'busca'    => trim((string) $this->input->get('busca')),
            'grupo'    => trim((string) $this->input->get('grupo')),
            'subgrupo' => trim((string) $this->input->get('subgrupo')),
            'estante'  => trim((string) $this->input->get('estante')),
            'situacao' => trim((string) $this->input->get('situacao')),
        );

        $ordem  = (string) $this->input->get('ordem');
        $pagina = (int) $this->input->get('pagina');

        $total = $this->estoque_model->total_saldos($filtros);
        $pag   = $this->paginar($total, 25, $pagina);

        $dados = array(
            'registros' => $this->estoque_model->listar_saldos($filtros, $pag['por_pagina'], $pag['offset'], $ordem),
            'filtros'   => $filtros,
            'ordem'     => $ordem,
            'pag'       => $pag,
            'stats'     => $this->estoque_model->stats(),
            'grupos'    => $this->estoque_model->grupos(),
            'estantes'  => $this->estoque_model->estantes(),
        );

        $this->render('estoque/index', $dados);
    }

    // ------------------------------------------------------------------

    public function ver($produto_id)
    {
        $produto = $this->estoque_model->por_produto((int) $produto_id);

        if (empty($produto))
        {
            show_404();
        }

        $dados = array(
            'produto'      => $produto,
            'movimentos'   => $this->estoque_model->movimentos_produto((int) $produto_id, 100),
            'fornecedores' => $this->estoque_model->fornecedores_do_produto((int) $produto_id),
            'inventarios'  => $this->estoque_model->inventarios_produto((int) $produto_id, 20),
        );

        $this->render('estoque/ver', $dados);
    }

    // ==================================================================
    // Movimentos
    // ==================================================================

    public function movimentos()
    {
        $filtros = array(
            'busca'     => trim((string) $this->input->get('busca')),
            'tipo'      => trim((string) $this->input->get('tipo')),
            'origem'    => trim((string) $this->input->get('origem')),
            'produto_id'=> (int) $this->input->get('produto_id'),
            'chamado'   => trim((string) $this->input->get('chamado')),
            'data_ini'  => trim((string) $this->input->get('data_ini')),
            'data_fim'  => trim((string) $this->input->get('data_fim')),
        );

        $pagina = (int) $this->input->get('pagina');
        $total  = $this->estoque_model->total_movimentos($filtros);
        $pag    = $this->paginar($total, 40, $pagina);

        $dados = array(
            'registros' => $this->estoque_model->listar_movimentos($filtros, $pag['por_pagina'], $pag['offset']),
            'filtros'   => $filtros,
            'pag'       => $pag,
        );

        $this->render('estoque/movimentos', $dados);
    }

    // ==================================================================
    // Entrada
    // ==================================================================

    public function entrada()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ($this->eh_post())
        {
            $itens = $this->_post_itens(TRUE);

            if (empty($itens))
            {
                $this->flash('Informe pelo menos um produto com quantidade.', 'erro');
                redirect('estoque/entrada');
            }

            $cabecalho = array(
                'fornecedor_id' => (int) $this->input->post('fornecedor_id'),
                'documento'     => $this->input->post('documento'),
                'observacao'    => $this->input->post('observacao'),
                'motivo'        => $this->input->post('motivo'),
            );

            $resultado = $this->estoque_model->entrada($itens, $cabecalho, $this->usuario);

            if ( ! $resultado['ok'])
            {
                $this->flash($resultado['erro'], 'erro');
                redirect('estoque/entrada');
            }

            $this->auditoria->registrar('criar', 'estoque_entrada', $resultado['lote'], array(
                'itens'      => $resultado['itens'],
                'documento'  => $cabecalho['documento'],
                'fornecedor' => $cabecalho['fornecedor_id'] ? $cabecalho['fornecedor_id'] : NULL,
            ));

            $this->flash('Entrada registrada (lote ' . $resultado['lote'] . ', ' . $resultado['itens'] . ' item(ns)).');
            redirect('estoque/movimentos?origem=manual&tipo=entrada');
        }

        $dados = array();

        $this->render('estoque/entrada', $dados);
    }

    // ==================================================================
    // Saida
    // ==================================================================

    public function saida()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ($this->eh_post())
        {
            $itens = $this->_post_itens(TRUE);

            if (empty($itens))
            {
                $this->flash('Informe pelo menos um produto com quantidade.', 'erro');
                redirect('estoque/saida');
            }

            $cabecalho = array(
                'setor_id'      => (int) $this->input->post('setor_id'),
                'funcionario_id'=> (int) $this->input->post('funcionario_id'),
                'documento'     => $this->input->post('documento'),
                'observacao'    => $this->input->post('observacao'),
                'motivo'        => $this->input->post('motivo'),
            );

            $resultado = $this->estoque_model->saida($itens, $cabecalho, $this->usuario);

            if ( ! $resultado['ok'])
            {
                $this->flash($resultado['erro'], 'erro');
                redirect('estoque/saida');
            }

            $this->auditoria->registrar('criar', 'estoque_saida', $resultado['lote'], array(
                'itens'     => $resultado['itens'],
                'documento' => $cabecalho['documento'],
                'setor'     => $cabecalho['setor_id'] ? $cabecalho['setor_id'] : NULL,
            ));

            $this->flash('Saida registrada (lote ' . $resultado['lote'] . ', ' . $resultado['itens'] . ' item(ns)).');
            redirect('estoque/movimentos?origem=manual&tipo=saida');
        }

        $dados = array(
            'setores'     => $this->estoque_model->setores(),
            'funcionarios'=> $this->estoque_model->funcionarios(),
        );

        $this->render('estoque/saida', $dados);
    }

    // ==================================================================
    // Ajuste / inventario
    // ==================================================================

    public function ajuste()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ($this->eh_post())
        {
            $produto_id = (int) $this->input->post('produto_id');
            $contada    = $this->input->post('quantidade_contada');
            $motivo     = $this->input->post('motivo');
            $data       = $this->input->post('data');

            if ($produto_id <= 0)
            {
                $this->flash('Selecione o produto para ajustar.', 'erro');
                redirect('estoque/ajuste');
            }

            $resultado = $this->estoque_model->ajustar($produto_id, $contada, $motivo, $this->usuario, $data);

            if ( ! $resultado['ok'])
            {
                $this->flash($resultado['erro'], 'erro');
                redirect('estoque/ajuste?produto_id=' . $produto_id);
            }

            $this->auditoria->registrar('alterar', 'estoque_inventario', $resultado['inventario_id'], array(
                'produto_id' => $produto_id,
                'diferenca'  => $resultado['diferenca'],
                'saldo'      => $resultado['saldo'],
                'motivo'     => $motivo,
            ));

            if ($resultado['diferenca'] === 0)
            {
                $this->flash('Contagem registrada sem diferenca de saldo.');
            }
            else
            {
                $sinal = $resultado['diferenca'] > 0 ? '+' : '';
                $this->flash('Ajuste registrado: ' . $sinal . $resultado['diferenca'] . ' unidade(s). Novo saldo: ' . $resultado['saldo'] . '.');
            }

            redirect('estoque/ver/' . $produto_id);
        }

        $produto_id = (int) $this->input->get('produto_id');
        $produto    = $produto_id > 0 ? $this->estoque_model->por_produto($produto_id) : NULL;

        $dados = array(
            'produto'    => $produto,
            'inventarios'=> $this->estoque_model->listar_inventarios(30, 0),
        );

        $this->render('estoque/ajuste', $dados);
    }

    // ==================================================================
    // Estorno
    // ==================================================================

    public function estornar($movimento_id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('estoque/movimentos');
        }

        $motivo = trim((string) $this->input->post('motivo'));
        $voltar = (string) $this->input->post('voltar');

        $resultado = $this->estoque_model->estornar((int) $movimento_id, $motivo, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'estoque_movimento', $movimento_id, array(
                'estorno' => $resultado['movimento'],
                'motivo'  => $motivo,
            ));

            $this->flash('Movimento estornado pelo lancamento #' . $resultado['movimento'] . '.');
        }

        if ($voltar !== '' && strpos($voltar, 'estoque/') === 0)
        {
            redirect($voltar);
        }

        redirect('estoque/movimentos');
    }

    // ==================================================================
    // Catalogos
    // ==================================================================

    public function catalogos()
    {
        $tipo = (string) $this->input->get('tipo');

        $tipos = array('grupos', 'subgrupos', 'posicoes', 'estantes', 'prateleiras', 'finalidades');

        if ( ! in_array($tipo, $tipos, TRUE))
        {
            $tipo = 'grupos';
        }

        $grupo = trim((string) $this->input->get('grupo'));

        $dados = array(
            'tipo'        => $tipo,
            'tipos'       => $tipos,
            'grupo'       => $grupo,
            'registros'   => $this->_registros_catalogo($tipo, $grupo),
            'grupos'      => $this->estoque_model->grupos(),
            'estantes'    => $this->estoque_model->estantes(),
        );

        $this->render('estoque/catalogos', $dados);
    }

    // ------------------------------------------------------------------

    public function catalogo_salvar()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('estoque/catalogos');
        }

        $tipo   = (string) $this->input->post('tipo');
        $dados  = $this->_post_catalogo($tipo);
        $resultado = $this->estoque_model->salvar_catalogo($tipo, $dados);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'estoque_catalogo', $tipo, $dados);
            $this->flash('Cadastro salvo com sucesso.');
        }

        redirect('estoque/catalogos?tipo=' . urlencode($this->_tipo_plural($tipo)) . $this->_query_grupo($dados));
    }

    // ------------------------------------------------------------------

    public function catalogo_excluir()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('estoque/catalogos');
        }

        $tipo  = (string) $this->input->post('tipo');
        $chaves = $this->_post_catalogo($tipo);

        $resultado = $this->estoque_model->excluir_catalogo($tipo, $chaves, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('excluir', 'estoque_catalogo', $tipo, $chaves);
            $this->flash('Cadastro excluido.');
        }

        redirect('estoque/catalogos?tipo=' . urlencode($this->_tipo_plural($tipo)) . $this->_query_grupo($chaves));
    }

    // ==================================================================
    // Fornecedores
    // ==================================================================

    public function fornecedores()
    {
        $busca  = trim((string) $this->input->get('busca'));
        $pagina = (int) $this->input->get('pagina');

        $total = $this->estoque_model->total_fornecedores($busca);
        $pag   = $this->paginar($total, 25, $pagina);

        $dados = array(
            'registros' => $this->estoque_model->listar_fornecedores($busca, $pag['por_pagina'], $pag['offset']),
            'busca'     => $busca,
            'pag'       => $pag,
        );

        $this->render('estoque/fornecedores', $dados);
    }

    // ------------------------------------------------------------------

    public function fornecedor($codigo = NULL)
    {
        $codigo = (int) $codigo;
        $registro = $codigo > 0 ? $this->estoque_model->fornecedor($codigo) : NULL;

        if ($codigo > 0 && empty($registro))
        {
            show_404();
        }

        $dados = array(
            'registro' => $registro,
            'produtos' => $registro ? $this->estoque_model->produtos_do_fornecedor($codigo) : array(),
        );

        $this->render('estoque/fornecedor', $dados);
    }

    // ------------------------------------------------------------------

    public function fornecedor_salvar()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('estoque/fornecedores');
        }

        $dados = array(
            'codigo'             => $this->input->post('codigo'),
            'razao_social'       => $this->input->post('razao_social'),
            'nome_fantasia'      => $this->input->post('nome_fantasia'),
            'inscricao_estadual' => $this->input->post('inscricao_estadual'),
            'cnpj'               => $this->input->post('cnpj'),
            'endereco'           => $this->input->post('endereco'),
            'numero'             => $this->input->post('numero'),
            'complemento'        => $this->input->post('complemento'),
            'bairro'             => $this->input->post('bairro'),
            'cidade'             => $this->input->post('cidade'),
            'cep'                => $this->input->post('cep'),
            'uf'                 => $this->input->post('uf'),
            'fone'               => $this->input->post('fone'),
            'fax'                => $this->input->post('fax'),
            'email'              => $this->input->post('email'),
            'contato'            => $this->input->post('contato'),
            'ponto_referencia'   => $this->input->post('ponto_referencia'),
            'ativo'              => $this->input->post('ativo'),
        );

        $resultado = $this->estoque_model->salvar_fornecedor($dados);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
            redirect('estoque/fornecedor/' . (int) $dados['codigo']);
        }

        $this->auditoria->registrar($dados['codigo'] ? 'alterar' : 'criar', 'fornecedor', $resultado['codigo'], array(
            'razao_social' => $dados['razao_social'],
        ));

        $this->flash('Fornecedor salvo com sucesso.');
        redirect('estoque/fornecedor/' . $resultado['codigo']);
    }

    // ------------------------------------------------------------------

    public function fornecedor_vincular()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('estoque/fornecedores');
        }

        $fornecedor = (int) $this->input->post('fornecedor_codigo');
        $resultado  = $this->estoque_model->vincular_produto_fornecedor(
            $fornecedor,
            (int) $this->input->post('produto_id'),
            $this->input->post('codigo_fornecedor'),
            $this->input->post('nome_fornecedor'),
            $this->input->post('preco_venda')
        );

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('criar', 'produto_fornecedor', $fornecedor, array(
                'produto_id' => (int) $this->input->post('produto_id'),
            ));
            $this->flash('Produto vinculado ao fornecedor.');
        }

        redirect('estoque/fornecedor/' . $fornecedor);
    }

    // ------------------------------------------------------------------

    public function fornecedor_desvincular()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('estoque/fornecedores');
        }

        $fornecedor = (int) $this->input->post('fornecedor_codigo');
        $produto_id = (int) $this->input->post('produto_id');

        $this->estoque_model->desvincular_produto_fornecedor($fornecedor, $produto_id);
        $this->auditoria->registrar('excluir', 'produto_fornecedor', $fornecedor, array('produto_id' => $produto_id));

        $this->flash('Vinculo removido.');
        redirect('estoque/fornecedor/' . $fornecedor);
    }

    // ==================================================================
    // Autocomplete (JSON)
    // ==================================================================

    public function buscar_produtos()
    {
        $termo = trim((string) $this->input->get('termo'));
        $lista = $termo === '' ? array() : $this->estoque_model->buscar_produtos($termo, 25);

        $this->_json($lista);
    }

    // ------------------------------------------------------------------

    public function buscar_fornecedores()
    {
        $termo = trim((string) $this->input->get('termo'));
        $lista = $termo === '' ? array() : $this->estoque_model->buscar_fornecedores($termo, 25);

        $this->_json($lista);
    }

    // ==================================================================
    // Helpers
    // ==================================================================

    /**
     * Itens de entrada/saida vindos de arrays paralelos.
     */
    private function _post_itens($com_custo = FALSE)
    {
        $ids    = $this->input->post('produto_id');
        $qtds   = $this->input->post('quantidade');
        $custos = $this->input->post('custo');

        $ids    = is_array($ids) ? $ids : array();
        $qtds   = is_array($qtds) ? $qtds : array();
        $custos = is_array($custos) ? $custos : array();

        $itens = array();

        foreach ($ids as $i => $produto_id)
        {
            $produto_id = (int) $produto_id;
            $qtd        = isset($qtds[$i]) ? (int) $qtds[$i] : 0;

            if ($produto_id <= 0 || $qtd <= 0)
            {
                continue;
            }

            $itens[] = array(
                'produto_id'     => $produto_id,
                'quantidade'     => $qtd,
                'custo_unitario' => $com_custo && isset($custos[$i]) ? $custos[$i] : NULL,
            );
        }

        return $itens;
    }

    // ------------------------------------------------------------------

    private function _post_catalogo($tipo)
    {
        return array(
            'codigo'          => $this->input->post('codigo'),
            'nome'            => $this->input->post('nome'),
            'percentual_venda'=> $this->input->post('percentual_venda'),
            'grupo_codigo'    => $this->input->post('grupo_codigo'),
            'estante_codigo'  => $this->input->post('estante_codigo'),
        );
    }

    // ------------------------------------------------------------------

    private function _registros_catalogo($tipo, $grupo)
    {
        switch ($tipo)
        {
            case 'subgrupos':
                return $this->estoque_model->subgrupos($grupo);
            case 'posicoes':
                return $this->estoque_model->posicoes();
            case 'estantes':
                return $this->estoque_model->estantes();
            case 'prateleiras':
                return $this->estoque_model->prateleiras();
            case 'finalidades':
                return $this->estoque_model->finalidades();
            case 'grupos':
            default:
                return $this->estoque_model->grupos();
        }
    }

    // ------------------------------------------------------------------

    private function _tipo_plural($tipo)
    {
        $mapa = array(
            'grupo'      => 'grupos',
            'subgrupo'   => 'subgrupos',
            'posicao'    => 'posicoes',
            'estante'    => 'estantes',
            'prateleira' => 'prateleiras',
            'finalidade' => 'finalidades',
        );

        return isset($mapa[$tipo]) ? $mapa[$tipo] : 'grupos';
    }

    // ------------------------------------------------------------------

    private function _query_grupo($dados)
    {
        $grupo = isset($dados['grupo_codigo']) ? trim((string) $dados['grupo_codigo']) : '';

        return $grupo !== '' ? '&grupo=' . urlencode($grupo) : '';
    }

    // ------------------------------------------------------------------

    private function _json($dados)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($dados));
    }
}
