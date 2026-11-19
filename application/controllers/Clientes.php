<?php
class Clientes extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cliente_model');
        $this->load->library('auditoria');
        $this->load->library('form_validation');

        $this->titulo     = 'Clientes';
        $this->menu_ativo = 'clientes';
    }

    // ------------------------------------------------------------------

    public function index()
    {
        $busca  = trim((string) $this->input->get('busca'));
        $pagina = (int) $this->input->get('pagina');
        $ordem  = (string) $this->input->get('ordem');

        $ordens = array(
            'nome'    => 'nome',
            'cidade'  => 'cidade, nome',
            'recente' => 'id DESC',
        );

        if ( ! isset($ordens[$ordem]))
        {
            $ordem = 'nome';
        }

        $total = $this->cliente_model->total($busca);
        $pag   = $this->paginar($total, 8, $pagina);

        $dados = array(
            'registros' => $this->cliente_model->listar($busca, $pag['por_pagina'], $pag['offset'], $ordens[$ordem]),
            'busca'     => $busca,
            'ordem'     => $ordem,
            'pag'       => $pag,
        );

        $this->render('clientes/index', $dados);
    }

    // ------------------------------------------------------------------

    public function ver($id)
    {
        $registro = $this->cliente_model->por_id((int) $id);

        if (empty($registro))
        {
            show_404();
        }

        $this->load->model('cliente_especial_model');

        $this->render('clientes/ver', array(
            'registro'     => $registro,
            'especial_qtd' => $this->cliente_especial_model->quantidade((int) $id),
        ));
    }

    // ------------------------------------------------------------------
    // Cliente especial (Access: PRODUTO_CLIENTES_ESPECIAIS_01)
    // ------------------------------------------------------------------

    public function especiais($id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $registro = $this->cliente_model->por_id((int) $id);

        if (empty($registro))
        {
            show_404();
        }

        $this->load->model('cliente_especial_model');

        $busca  = trim((string) $this->input->get('busca'));
        $pagina = (int) $this->input->get('pagina');

        $total = $this->cliente_especial_model->total((int) $id, $busca);
        $pag   = $this->paginar($total, 20, $pagina);

        $this->titulo = 'Precos especiais - ' . $registro['nome'];

        $this->render('clientes/especiais', array(
            'registro'   => $registro,
            'registros'  => $this->cliente_especial_model->listar(
                (int) $id, $busca, $pag['por_pagina'], $pag['offset']
            ),
            'busca'      => $busca,
            'pag'        => $pag,
            'quantidade' => $this->cliente_especial_model->quantidade((int) $id),
        ));
    }

    // ------------------------------------------------------------------

    /**
     * Insere/exclui o cliente da tabela de precos especiais.
     */
    public function especial_alternar($id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $registro = $this->cliente_model->por_id((int) $id);

        if (empty($registro))
        {
            show_404();
        }

        if ( ! $this->eh_post())
        {
            redirect('clientes/ver/' . (int) $id);
        }

        $this->load->model('cliente_especial_model');

        if ((string) $this->input->post('acao') === 'excluir')
        {
            $removidos = $this->cliente_especial_model->excluir_todos((int) $id);

            $this->auditoria->registrar('excluir', 'cliente_especial', (int) $id, array('registros' => $removidos));
            $this->flash('Cliente excluido dos precos especiais (' . $removidos . ' produto(s)).');
        }
        else
        {
            $perc = $this->_decimal($this->input->post('perc_lucro'));

            if ($perc === NULL)
            {
                $perc = 15;
            }

            $incluidos = $this->cliente_especial_model->incluir_todos((int) $id, $perc);

            $this->auditoria->registrar('criar', 'cliente_especial', (int) $id, array(
                'produtos'   => $incluidos,
                'perc_lucro' => $perc,
            ));

            if ($incluidos > 0)
            {
                $this->flash('Cliente inserido como cliente especial (' . $incluidos . ' produto(s)).');
            }
            else
            {
                $this->flash('O cliente ja possui precos especiais cadastrados.', 'info');
            }
        }

        redirect('clientes/ver/' . (int) $id);
    }

    // ------------------------------------------------------------------

    /**
     * Inclui/atualiza o preco especial de um produto.
     */
    public function especial_salvar($id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $registro = $this->cliente_model->por_id((int) $id);

        if (empty($registro))
        {
            show_404();
        }

        if ( ! $this->eh_post())
        {
            redirect('clientes/especiais/' . (int) $id);
        }

        $produto_id = (int) $this->input->post('produto_id');

        if ($produto_id <= 0)
        {
            $this->flash('Selecione um produto.', 'erro');
            redirect('clientes/especiais/' . (int) $id);
        }

        $this->load->model('produto_model');
        $produto = $this->produto_model->por_id($produto_id);

        if (empty($produto))
        {
            $this->flash('Produto nao encontrado.', 'erro');
            redirect('clientes/especiais/' . (int) $id);
        }

        $this->load->model('cliente_especial_model');

        $perc  = $this->_decimal($this->input->post('perc_lucro'));
        $valor = $this->_decimal($this->input->post('valor_venda'));

        if ($valor === NULL && $perc !== NULL)
        {
            $valor = round((float) $produto['valor_compra'] * (1 + ($perc / 100)), 2);
        }

        $this->cliente_especial_model->salvar((int) $id, $produto_id, $perc, $valor);

        $this->auditoria->registrar('alterar', 'cliente_especial', (int) $id, array(
            'produto_id' => $produto_id,
            'perc_lucro' => $perc,
            'valor_venda'=> $valor,
        ));

        $this->flash('Preco especial de ' . $produto['descricao'] . ' salvo.');

        redirect('clientes/especiais/' . (int) $id);
    }

    // ------------------------------------------------------------------

    public function especial_excluir($id, $produto_id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $registro = $this->cliente_model->por_id((int) $id);

        if (empty($registro))
        {
            show_404();
        }

        if ( ! $this->eh_post())
        {
            redirect('clientes/especiais/' . (int) $id);
        }

        $this->load->model('cliente_especial_model');
        $this->cliente_especial_model->excluir_produto((int) $id, (int) $produto_id);

        $this->auditoria->registrar('excluir', 'cliente_especial', (int) $id, array('produto_id' => (int) $produto_id));
        $this->flash('Produto removido dos precos especiais.');

        redirect('clientes/especiais/' . (int) $id);
    }

    // ------------------------------------------------------------------
    // Consultas do cliente (Access F_MODULO_CLIENTES: C, D e E)
    // ------------------------------------------------------------------

    public function ordens($id = NULL)
    {
        $this->menu_ativo = 'consulta_os';

        $consulta   = ($id === NULL);
        $cliente_id = $consulta ? (int) $this->input->get('cliente') : (int) $id;

        $registro = $cliente_id > 0
            ? $this->cliente_model->por_id($cliente_id)
            : NULL;

        if ($cliente_id > 0 && empty($registro))
        {
            show_404();
        }

        $this->load->model('chamado_model');

        $busca  = trim((string) $this->input->get('busca'));
        $pagina = (int) $this->input->get('pagina');

        if (empty($registro))
        {
            $this->titulo = 'Ordens de servico do cliente';

            $this->render('clientes/ordens', array(
                'consulta'  => TRUE,
                'registro'  => NULL,
                'registros' => array(),
                'busca'     => $busca,
                'pag'       => $this->paginar(0, 10, 1),
            ));

            return;
        }

        $filtros = array(
            'cliente_id' => $cliente_id,
            'busca'      => $busca,
        );

        $total = $this->chamado_model->total($filtros);
        $pag   = $this->paginar($total, 10, $pagina);

        $this->titulo = 'Ordens de servico - ' . $registro['nome'];

        $this->render('clientes/ordens', array(
            'consulta'  => $consulta,
            'registro'  => $registro,
            'registros' => $this->chamado_model->listar($filtros, $pag['por_pagina'], $pag['offset']),
            'busca'     => $busca,
            'pag'       => $pag,
        ));
    }

    // ------------------------------------------------------------------

    public function orcamentos($id = NULL)
    {
        $this->menu_ativo = 'consulta_orcamentos';

        $consulta   = ($id === NULL);
        $cliente_id = $consulta ? (int) $this->input->get('cliente') : (int) $id;

        $registro = $cliente_id > 0
            ? $this->cliente_model->por_id($cliente_id)
            : NULL;

        if ($cliente_id > 0 && empty($registro))
        {
            show_404();
        }

        $this->load->model('orcamentos_model');

        $busca  = trim((string) $this->input->get('busca'));
        $pagina = (int) $this->input->get('pagina');

        if (empty($registro))
        {
            $this->titulo = 'Orcamentos do cliente';

            $this->render('clientes/orcamentos', array(
                'consulta'   => TRUE,
                'registro'   => NULL,
                'registros'  => array(),
                'situacoes'  => $this->orcamentos_model->situacoes(),
                'resultados' => $this->orcamentos_model->resultados(),
                'busca'      => $busca,
                'pag'        => $this->paginar(0, 10, 1),
            ));

            return;
        }

        $filtros = array(
            'cliente' => $cliente_id,
            'busca'   => $busca,
        );

        $total = $this->orcamentos_model->total($filtros);
        $pag   = $this->paginar($total, 10, $pagina);

        $this->titulo = 'Orcamentos - ' . $registro['nome'];

        $this->render('clientes/orcamentos', array(
            'consulta'   => $consulta,
            'registro'   => $registro,
            'registros'  => $this->orcamentos_model->listar($filtros, $pag['por_pagina'], $pag['offset']),
            'situacoes'  => $this->orcamentos_model->situacoes(),
            'resultados' => $this->orcamentos_model->resultados(),
            'busca'      => $busca,
            'pag'        => $pag,
        ));
    }

    // ------------------------------------------------------------------

    public function notas($id = NULL)
    {
        $this->menu_ativo = 'consulta_notas';

        $consulta   = ($id === NULL);
        $cliente_id = $consulta ? (int) $this->input->get('cliente') : (int) $id;

        $registro = $cliente_id > 0
            ? $this->cliente_model->por_id($cliente_id)
            : NULL;

        if ($cliente_id > 0 && empty($registro))
        {
            show_404();
        }

        $this->load->model('financeiro_model');

        $busca  = trim((string) $this->input->get('busca'));
        $pagina = (int) $this->input->get('pagina');

        if (empty($registro))
        {
            $this->titulo = 'Notas fiscais do cliente';

            $this->render('clientes/notas', array(
                'consulta'  => TRUE,
                'registro'  => NULL,
                'registros' => array(),
                'busca'     => $busca,
                'pag'       => $this->paginar(0, 10, 1),
            ));

            return;
        }

        $total = $this->financeiro_model->total_notas_fiscais_cliente($cliente_id, $busca);
        $pag   = $this->paginar($total, 10, $pagina);

        $this->titulo = 'Notas fiscais - ' . $registro['nome'];

        $this->render('clientes/notas', array(
            'consulta'  => $consulta,
            'registro'  => $registro,
            'registros' => $this->financeiro_model->notas_fiscais_cliente(
                $cliente_id, $busca, $pag['por_pagina'], $pag['offset']
            ),
            'busca'     => $busca,
            'pag'       => $pag,
        ));
    }

    // ------------------------------------------------------------------

    public function nota($id)
    {
        $this->menu_ativo = 'consulta_notas';

        $registro = $this->cliente_model->por_id((int) $id);

        if (empty($registro))
        {
            show_404();
        }

        $this->load->model('financeiro_model');

        $numero   = trim((string) $this->input->get('numero'));
        $parcelas = $numero === ''
            ? array()
            : $this->financeiro_model->parcelas_nota_cliente((int) $id, $numero);

        if (empty($parcelas))
        {
            show_404();
        }

        $this->titulo = 'Nota fiscal ' . $numero . ' - ' . $registro['nome'];

        $this->render('clientes/nota', array(
            'registro' => $registro,
            'numero'   => $numero,
            'parcelas' => $parcelas,
        ));
    }

    // ------------------------------------------------------------------

    public function novo()
    {
        if ($this->input->server('REQUEST_METHOD') === 'POST')
        {
            $this->_validar();

            if ($this->form_validation->run() === TRUE)
            {
                $dados = $this->_dados_post();
                $id    = $this->cliente_model->criar($dados);

                $this->auditoria->registrar('criar', 'cliente', $id, array('nome' => $dados['nome']));
                $this->flash('Cliente cadastrado.');

                redirect('clientes/ver/' . $id);
            }
        }

        $this->render('clientes/form', array('registro' => NULL));
    }

    // ------------------------------------------------------------------

    public function editar($id)
    {
        $registro = $this->cliente_model->por_id((int) $id);

        if (empty($registro))
        {
            show_404();
        }

        if ($this->input->server('REQUEST_METHOD') === 'POST')
        {
            $this->_validar();

            if ($this->form_validation->run() === TRUE)
            {
                $dados = $this->_dados_post();
                $this->cliente_model->alterar((int) $id, $dados);

                $this->auditoria->registrar('alterar', 'cliente', (int) $id, array('nome' => $dados['nome']));
                $this->flash('Cliente atualizado.');

                redirect('clientes/ver/' . (int) $id);
            }
        }

        $this->render('clientes/form', array('registro' => $registro));
    }

    // ------------------------------------------------------------------

    public function buscar_clientes()
    {
        $termo = trim((string) $this->input->get('termo'));
        $lista = $termo === '' ? array() : $this->cliente_model->buscar($termo, 25);

        $this->_json($lista);
    }

    // ------------------------------------------------------------------

    private function _validar()
    {
        $this->form_validation->set_rules('nome', 'Nome', 'trim|required|min_length[2]|max_length[120]');
        $this->form_validation->set_rules('email', 'E-mail', 'trim|valid_email|max_length[120]');
        $this->form_validation->set_rules('tipo', 'Tipo', 'trim|integer');
        $this->form_validation->set_error_delimiters('<div class="campo-erro">', '</div>');
    }

    // ------------------------------------------------------------------

    private function _dados_post()
    {
        $campos = array(
            'fantasia', 'cpf_cnpj', 'ie', 'rg', 'endereco', 'numero', 'complemento',
            'bairro', 'cidade', 'uf', 'cep', 'telefone', 'celular', 'fax', 'contato',
            'email', 'observacao',
        );

        $dados = array('nome' => trim($this->input->post('nome')));

        foreach ($campos as $campo)
        {
            $valor = trim((string) $this->input->post($campo));
            $dados[$campo] = ($valor === '') ? NULL : $valor;
        }

        $tipo = trim((string) $this->input->post('tipo'));
        $dados['tipo']  = ($tipo === '') ? NULL : (int) $tipo;
        $dados['ativo'] = $this->input->post('ativo') ? TRUE : FALSE;

        if ( ! empty($dados['uf']))
        {
            $dados['uf'] = strtoupper($dados['uf']);
        }

        return $dados;
    }

    // ------------------------------------------------------------------

    private function _decimal($valor)
    {
        if ($valor === FALSE || $valor === NULL)
        {
            return NULL;
        }

        $valor = trim((string) $valor);

        if ($valor === '')
        {
            return NULL;
        }

        $valor = str_replace(array('R$', ' ', "\xc2\xa0"), '', $valor);

        if (strpos($valor, ',') !== FALSE && strpos($valor, '.') !== FALSE)
        {
            $valor = str_replace('.', '', $valor);
        }

        $valor = str_replace(',', '.', $valor);

        return is_numeric($valor) ? (float) $valor : NULL;
    }

    // ------------------------------------------------------------------

    private function _json($dados)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($dados));
    }
}
