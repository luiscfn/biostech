<?php
/**
 * Fornecedores - CRUD migrado da tela FORNECEDOR (Access).
 *
 * Alem dos dados cadastrais, a tela reune os prazos oferecidos pelo fornecedor
 * (FORNECEDOR_PRAZOS) e os produtos fornecidos (PROD_FORNEC_01). O botao
 * "Copiar para cliente" cria (ou atualiza) o cliente correspondente usando os
 * mesmos dados, mantendo o vinculo entre as duas bases.
 */
class Fornecedores extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('fornecedor_model');
        $this->load->model('cliente_model');
        $this->load->library('auditoria');
        $this->load->library('form_validation');

        $this->titulo     = 'Fornecedores';
        $this->menu_ativo = 'fornecedores';
    }

    // ------------------------------------------------------------------

    public function index()
    {
        $busca  = trim((string) $this->input->get('busca'));
        $pagina = (int) $this->input->get('pagina');
        $ordem  = (string) $this->input->get('ordem');

        $ordens = array(
            'nome'    => 'f.razao_social',
            'cidade'  => 'f.cidade, f.razao_social',
            'recente' => 'f.codigo DESC',
        );

        if ( ! isset($ordens[$ordem]))
        {
            $ordem = 'nome';
        }

        $total = $this->fornecedor_model->total($busca);
        $pag   = $this->paginar($total, 8, $pagina);

        $this->render('fornecedores/index', array(
            'registros' => $this->fornecedor_model->listar($busca, $pag['por_pagina'], $pag['offset'], $ordens[$ordem]),
            'busca'     => $busca,
            'ordem'     => $ordem,
            'pag'       => $pag,
        ));
    }

    // ------------------------------------------------------------------

    public function novo()
    {
        $erro_codigo = NULL;

        if ($this->eh_post())
        {
            $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
            $this->_validar();

            if ($this->form_validation->run() === TRUE)
            {
                $codigo = (int) $this->input->post('codigo');

                if ($codigo <= 0)
                {
                    $codigo = $this->fornecedor_model->proximo_codigo();
                }

                if ($this->fornecedor_model->codigo_em_uso($codigo))
                {
                    $erro_codigo = 'O codigo ' . $codigo . ' ja esta em uso por outro fornecedor.';
                }
                else
                {
                    $dados = $this->_dados_post();
                    $this->fornecedor_model->criar($codigo, $dados);

                    $this->auditoria->registrar('criar', 'fornecedor', $codigo, array(
                        'razao_social' => $dados['razao_social'],
                    ));

                    $this->flash('Fornecedor cadastrado.');
                    redirect('fornecedores/editar/' . $codigo);
                }
            }
        }

        $this->render('fornecedores/form', array(
            'registro'       => NULL,
            'proximo_codigo' => $this->fornecedor_model->proximo_codigo(),
            'prazos'         => array(),
            'prazo_edicao'   => array(),
            'produtos'       => array(),
            'cliente'        => array(),
            'erro_codigo'    => $erro_codigo,
            'editando'       => FALSE,
        ));
    }

    // ------------------------------------------------------------------

    public function editar($codigo)
    {
        $codigo   = (int) $codigo;
        $registro = $this->fornecedor_model->por_codigo($codigo);

        if (empty($registro))
        {
            show_404();
        }

        if ($this->eh_post())
        {
            $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
            $this->_validar();

            if ($this->form_validation->run() === TRUE)
            {
                $dados = $this->_dados_post();
                $this->fornecedor_model->alterar($codigo, $dados);

                $this->auditoria->registrar('alterar', 'fornecedor', $codigo, array(
                    'razao_social' => $dados['razao_social'],
                ));

                $this->flash('Fornecedor atualizado.');
                redirect('fornecedores/editar/' . $codigo);
            }
        }

        $prazo_id = (int) $this->input->get('prazo');

        $this->render('fornecedores/form', array(
            'registro'     => $registro,
            'prazos'       => $this->fornecedor_model->prazos($codigo),
            'prazo_edicao' => $prazo_id > 0 ? $this->fornecedor_model->prazo($codigo, $prazo_id) : array(),
            'produtos'     => $this->fornecedor_model->produtos($codigo),
            'cliente'      => $this->fornecedor_model->cliente_vinculado($codigo),
            'erro_codigo'  => NULL,
            'editando'     => TRUE,
        ));
    }

    // ------------------------------------------------------------------

    public function excluir($codigo)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $codigo   = (int) $codigo;
        $registro = $this->fornecedor_model->por_codigo($codigo);

        if (empty($registro))
        {
            show_404();
        }

        if ( ! $this->eh_post())
        {
            redirect('fornecedores/editar/' . $codigo);
        }

        $uso = $this->fornecedor_model->uso($codigo);
        $total_uso = array_sum(array_map('intval', $uso));

        if ($total_uso > 0)
        {
            $partes = array();
            foreach (array(
                'pedidos'         => 'pedido(s) de compra',
                'cotacoes'        => 'cotacao(oes)',
                'contas'          => 'conta(s) a pagar',
                'dimensionamentos' => 'cotacao(oes) de dimensionamento',
            ) as $chave => $rotulo)
            {
                if ((int) $uso[$chave] > 0)
                {
                    $partes[] = numero_br($uso[$chave]) . ' ' . $rotulo;
                }
            }

            $mensagem = 'Nao e possivel excluir o fornecedor: existem ' . implode(', ', $partes) . ' vinculados.';

            $this->flash($mensagem, 'danger');

            redirect('fornecedores/editar/' . $codigo);
        }

        $cliente = $this->fornecedor_model->cliente_vinculado($codigo);
        $ok      = $this->fornecedor_model->excluir_fornecedor($codigo);

        if ( ! $ok)
        {
            $this->flash('Nao foi possivel excluir o fornecedor.', 'danger');
            redirect('fornecedores/editar/' . $codigo);
        }

        $this->auditoria->registrar('excluir', 'fornecedor', $codigo, array(
            'razao_social' => $registro['razao_social'],
        ));

        $mensagem = 'Fornecedor excluido.';
        if ( ! empty($cliente))
        {
            $mensagem .= ' O cliente vinculado (' . $cliente['nome'] . ') foi mantido, sem o vinculo.';
        }

        $this->flash($mensagem);
        redirect('fornecedores');
    }

    // ------------------------------------------------------------------
    // Copiar fornecedor para cliente
    // ------------------------------------------------------------------

    public function copiar_cliente($codigo)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $codigo     = (int) $codigo;
        $fornecedor = $this->fornecedor_model->por_codigo($codigo);

        if (empty($fornecedor))
        {
            show_404();
        }

        if ( ! $this->eh_post())
        {
            redirect('fornecedores/editar/' . $codigo);
        }

        $dados = $this->_dados_cliente($fornecedor);
        $acao  = 'criar';

        $cliente = $this->cliente_model->por_fornecedor($codigo);

        if ( ! empty($cliente))
        {
            $this->cliente_model->alterar((int) $cliente['id'], $dados);
            $id      = (int) $cliente['id'];
            $acao    = 'alterar';
            $mensagem = 'Cliente ' . $dados['nome'] . ' atualizado com os dados do fornecedor.';
        }
        else
        {
            $existente = $this->cliente_model->por_documento($fornecedor['cnpj']);

            if ( ! empty($existente))
            {
                $this->cliente_model->definir_fornecedor((int) $existente['id'], $codigo);
                $id       = (int) $existente['id'];
                $acao     = 'alterar';
                $mensagem = 'O cliente ' . $existente['nome'] . ' ja existia com este documento e foi vinculado ao fornecedor.';
            }
            else
            {
                $id       = $this->cliente_model->criar($dados);
                $mensagem = 'Cliente ' . $dados['nome'] . ' criado a partir do fornecedor.';
            }
        }

        $this->auditoria->registrar($acao, 'cliente', $id, array(
            'fornecedor_codigo' => $codigo,
        ));

        $this->flash($mensagem);
        redirect('clientes/ver/' . $id);
    }

    // ------------------------------------------------------------------
    // Prazos oferecidos pelo fornecedor
    // ------------------------------------------------------------------

    public function prazo_salvar()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('fornecedores');
        }

        $codigo = (int) $this->input->post('fornecedor_codigo');
        $prazo  = $this->fornecedor_model->salvar_prazo($codigo, array(
            'prazo_codigo'    => $this->input->post('prazo_codigo'),
            'forma_pagamento' => $this->input->post('forma_pagamento'),
            'prazo'           => $this->input->post('prazo'),
        ));

        $this->auditoria->registrar('alterar', 'fornecedor_prazo', $prazo, array(
            'fornecedor_codigo' => $codigo,
        ));

        $this->flash('Prazo salvo.');
        redirect('fornecedores/editar/' . $codigo);
    }

    // ------------------------------------------------------------------

    public function prazo_excluir()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('fornecedores');
        }

        $codigo = (int) $this->input->post('fornecedor_codigo');
        $prazo  = (int) $this->input->post('prazo_codigo');

        $this->fornecedor_model->excluir_prazo($codigo, $prazo);
        $this->auditoria->registrar('excluir', 'fornecedor_prazo', $prazo, array(
            'fornecedor_codigo' => $codigo,
        ));

        $this->flash('Prazo removido.');
        redirect('fornecedores/editar/' . $codigo);
    }

    // ------------------------------------------------------------------
    // Produtos fornecidos
    // ------------------------------------------------------------------

    public function produto_vincular()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('fornecedores');
        }

        $codigo    = (int) $this->input->post('fornecedor_codigo');
        $resultado = $this->fornecedor_model->vincular_produto($codigo, array(
            'produto_id'        => $this->input->post('produto_id'),
            'codigo_fornecedor' => $this->input->post('codigo_fornecedor'),
            'nome_fornecedor'   => $this->input->post('nome_fornecedor'),
            'preco_venda'       => $this->input->post('preco_venda'),
        ));

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('criar', 'produto_fornecedor', $codigo, array(
                'produto_id' => (int) $this->input->post('produto_id'),
            ));
            $this->flash('Produto vinculado ao fornecedor.');
        }

        redirect('fornecedores/editar/' . $codigo);
    }

    // ------------------------------------------------------------------

    public function produto_desvincular()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('fornecedores');
        }

        $codigo     = (int) $this->input->post('fornecedor_codigo');
        $produto_id = (int) $this->input->post('produto_id');

        $this->fornecedor_model->desvincular_produto($codigo, $produto_id);
        $this->auditoria->registrar('excluir', 'produto_fornecedor', $codigo, array(
            'produto_id' => $produto_id,
        ));

        $this->flash('Produto removido do fornecedor.');
        redirect('fornecedores/editar/' . $codigo);
    }

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------

    private function _validar()
    {
        $this->form_validation->set_rules('codigo', 'Codigo', 'trim|integer');
        $this->form_validation->set_rules('razao_social', 'Nome / Razao social', 'trim|required|min_length[2]|max_length[100]');
        $this->form_validation->set_rules('cnpj', 'CNPJ', 'trim|max_length[20]');
        $this->form_validation->set_rules('inscricao_estadual', 'Inscricao estadual', 'trim|max_length[20]');
        $this->form_validation->set_rules('email', 'E-mail', 'trim|valid_email|max_length[50]');
        $this->form_validation->set_rules('uf', 'UF', 'trim|max_length[2]');
        $this->form_validation->set_error_delimiters('<div class="campo-erro">', '</div>');
    }

    // ------------------------------------------------------------------

    private function _dados_post()
    {
        $campos = array(
            'razao_social', 'nome_fantasia', 'inscricao_estadual', 'cnpj', 'endereco',
            'numero', 'complemento', 'bairro', 'cidade', 'cep', 'uf', 'fone', 'fax',
            'email', 'contato', 'ponto_referencia',
        );

        $dados = array();

        foreach ($campos as $campo)
        {
            $valor = trim((string) $this->input->post($campo));
            $dados[$campo] = ($valor === '') ? NULL : $valor;
        }

        $dados['razao_social'] = trim((string) $this->input->post('razao_social'));
        $dados['ativo']        = $this->input->post('ativo') ? TRUE : FALSE;

        if ( ! empty($dados['uf']))
        {
            $dados['uf'] = strtoupper($dados['uf']);
        }

        return $dados;
    }

    // ------------------------------------------------------------------

    /**
     * Monta os dados do cliente a partir do fornecedor.
     */
    private function _dados_cliente($fornecedor)
    {
        $documento = preg_replace('/\D+/', '', (string) $fornecedor['cnpj']);

        if (strlen($documento) >= 14)
        {
            $tipo = 1; // juridica
        }
        elseif (strlen($documento) === 11)
        {
            $tipo = 2; // fisica
        }
        else
        {
            $tipo = NULL;
        }

        return array(
            'nome'              => $fornecedor['razao_social'],
            'fantasia'          => $fornecedor['nome_fantasia'],
            'tipo'              => $tipo,
            'cpf_cnpj'          => $fornecedor['cnpj'],
            'ie'                => $fornecedor['inscricao_estadual'],
            'rg'                => NULL,
            'endereco'          => $fornecedor['endereco'],
            'numero'            => $fornecedor['numero'],
            'complemento'       => $fornecedor['complemento'],
            'bairro'            => $fornecedor['bairro'],
            'cidade'            => $fornecedor['cidade'],
            'uf'                => $fornecedor['uf'],
            'cep'               => $fornecedor['cep'],
            'telefone'          => $fornecedor['fone'],
            'celular'           => NULL,
            'fax'               => $fornecedor['fax'],
            'contato'           => $fornecedor['contato'],
            'email'             => $fornecedor['email'],
            'observacao'        => $fornecedor['ponto_referencia'],
            'fornecedor_codigo' => (int) $fornecedor['codigo'],
            'ativo'             => bd_bool($fornecedor['ativo']),
        );
    }
}
