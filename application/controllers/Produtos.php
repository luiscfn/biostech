<?php
class Produtos extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('produto_model');
        $this->load->model('estoque_model');
        $this->load->model('cliente_especial_model');
        $this->load->library('auditoria');
        $this->load->library('form_validation');

        $this->titulo     = 'Produtos';
        $this->menu_ativo = 'produtos';
    }

    // ------------------------------------------------------------------
    // Listagem
    // ------------------------------------------------------------------

    public function index()
    {
        $busca    = trim((string) $this->input->get('busca'));
        $grupo    = trim((string) $this->input->get('grupo'));
        $subgrupo = trim((string) $this->input->get('subgrupo'));
        $situacao = trim((string) $this->input->get('situacao'));
        $pagina   = (int) $this->input->get('pagina');

        if ( ! in_array($situacao, array('ativos', 'inativos'), TRUE))
        {
            $situacao = '';
        }

        // Os subgrupos dependem do grupo selecionado.
        $grupos  = $this->estoque_model->grupos();
        $codigos = array();

        foreach ($grupos as $g)
        {
            $codigos[] = (string) $g['codigo'];
        }

        if ($grupo !== '' && ! in_array($grupo, $codigos, TRUE))
        {
            $grupo = '';
        }

        $subgrupos = $grupo !== '' ? $this->estoque_model->subgrupos($grupo) : array();
        $codigos_subgrupos = array();

        foreach ($subgrupos as $sg)
        {
            $codigos_subgrupos[] = (string) $sg['codigo'];
        }

        if ($subgrupo !== '' && ! in_array($subgrupo, $codigos_subgrupos, TRUE))
        {
            $subgrupo = '';
        }

        $total = $this->produto_model->total($busca, $grupo, $subgrupo, $situacao);
        $pag   = $this->paginar($total, 7, $pagina);

        $dados = array(
            'registros' => $this->produto_model->listar(
                $busca, $grupo, $subgrupo, $situacao, $pag['por_pagina'], $pag['offset']
            ),
            'busca'     => $busca,
            'grupo'     => $grupo,
            'subgrupo'  => $subgrupo,
            'situacao'  => $situacao,
            'grupos'    => $grupos,
            'subgrupos' => $subgrupos,
            'pag'       => $pag,
        );

        $this->render('produtos/index', $dados);
    }

    // ------------------------------------------------------------------
    // Cadastro (novo / ficha do produto)
    // ------------------------------------------------------------------

    public function novo()
    {
        $registro = NULL;

        if ($this->eh_post())
        {
            $dados = $this->_post_cadastro(NULL);

            if ($this->_validar_cadastro() === TRUE)
            {
                $id = $this->produto_model->salvar_cadastro($dados, NULL);
                $this->auditoria->registrar('criar', 'produto', $id, $dados);
                $this->flash('Produto cadastrado com sucesso.');
                redirect('produtos/ver/' . $id);
            }
        }

        $this->_render_formulario($registro, TRUE);
    }

    public function ver($id)
    {
        $id       = (int) $id;
        $registro = $this->produto_model->por_id($id);

        if (empty($registro))
        {
            show_404();
        }

        if ($this->eh_post())
        {
            $dados = $this->_post_cadastro($id);

            if ($this->_validar_cadastro() === TRUE)
            {
                $this->produto_model->salvar_cadastro($dados, $id);
                $this->auditoria->registrar('alterar', 'produto', $id, $dados);
                $this->flash('Produto atualizado com sucesso.');
                redirect('produtos/ver/' . $id);
            }
        }

        $this->_render_formulario($registro, FALSE);
    }

    private function _render_formulario($registro, $novo)
    {
        $produto_id = $registro ? (int) $registro['id'] : 0;
        $saldo      = 0;

        if ($produto_id)
        {
            $row = $this->db->query(
                "SELECT COALESCE(quantidade, 0) AS saldo FROM " . T_ESTOQUE_SALDOS . " WHERE produto_id = ?",
                array($produto_id)
            )->row_array();

            $saldo = empty($row) ? 0 : (int) $row['saldo'];
        }

        $especiais = $produto_id ? $this->cliente_especial_model->clientes_do_produto($produto_id) : array();

        // Usuario que cadastrou / que fez a ultima alteracao (trilha de auditoria).
        $usuario_cadastro  = $novo && isset($this->usuario['nome']) ? $this->usuario['nome'] : '-';
        $usuario_alteracao = '-';

        if ($produto_id)
        {
            $eventos = $this->db->query(
                "SELECT a.acao, COALESCE(u.nome, a.usuario_login) AS nome
                   FROM " . T_AUDITORIA . " a
                   LEFT JOIN " . T_USUARIOS . " u ON u.login = a.usuario_login
                  WHERE a.entidade = 'produto' AND a.entidade_id = ?
                  ORDER BY a.created_at, a.id",
                array((string) $produto_id)
            )->result_array();

            if ($eventos)
            {
                $usuario_cadastro = $eventos[0]['nome'] ? $eventos[0]['nome'] : '-';

                foreach (array_reverse($eventos) as $evento)
                {
                    if ($evento['acao'] === 'alterar')
                    {
                        $usuario_alteracao = $evento['nome'] ? $evento['nome'] : '-';
                        break;
                    }
                }
            }
        }

        // Edicao de um preco especial ja cadastrado (via link na grade).
        $editar_esp = (int) $this->input->get('editar_esp');
        $esp_editar = NULL;

        foreach ($especiais as $esp)
        {
            if ((int) $esp['cliente_id'] === $editar_esp)
            {
                $esp_editar = $esp;
                break;
            }
        }

        if ($esp_editar === NULL)
        {
            $editar_esp = 0;
        }

        $dados = array(
            'registro'         => $registro,
            'novo'             => $novo,
            'ultimo'           => $this->produto_model->ultimo_cadastrado(),
            'proximo_codigo'   => $this->produto_model->proximo_codigo(),
            'grupos'           => $this->estoque_model->grupos(),
            'subgrupos'        => $this->estoque_model->subgrupos(),
            'estantes'         => $this->estoque_model->estantes(),
            'prateleiras'      => $this->estoque_model->prateleiras(),
            'vizinhos'         => $produto_id ? $this->produto_model->vizinhos($produto_id) : array(),
            'saldo'            => $saldo,
            'especiais'        => $especiais,
            'usuario_cadastro' => $usuario_cadastro,
            'usuario_alteracao'=> $usuario_alteracao,
            'editar_esp'       => $editar_esp,
            'esp_editar'       => $esp_editar,
        );

        $this->render('produtos/form', $dados);
    }

    private function _validar_cadastro()
    {
        $this->form_validation->set_rules('descricao', 'Descricao', 'trim|required|max_length[120]');
        $this->form_validation->set_error_delimiters('<div class="campo-erro">', '</div>');

        return $this->form_validation->run() === TRUE;
    }

    private function _post_cadastro($id)
    {
        $post = $this->input->post();

        $valor = function ($campo) use ($post) {
            return isset($post[$campo]) ? trim((string) $post[$campo]) : '';
        };

        $decimal = function ($campo) use ($valor) {
            $v = $valor($campo);

            if ($v === '')
            {
                return 0;
            }

            $v = str_replace(array('R$', ' ', "\xc2\xa0"), '', $v);

            if (strpos($v, ',') !== FALSE)
            {
                $v = str_replace('.', '', $v);
                $v = str_replace(',', '.', $v);
            }

            return round((float) $v, 4);
        };

        $texto = function ($campo, $tamanho) use ($valor) {
            $v = mb_strtoupper($valor($campo), 'UTF-8');
            return $v === '' ? NULL : mb_substr($v, 0, $tamanho, 'UTF-8');
        };

        $inteiro = function ($campo) use ($valor) {
            $v = $valor($campo);
            return $v === '' ? NULL : (int) $v;
        };

        $situacao = $valor('situacao');

        return array(
            'id'                 => $id === NULL ? $inteiro('id') : (int) $id,
            'codigo'             => $texto('codigo', 15),
            'descricao'          => $texto('descricao', 120),
            'unidade'            => $texto('unidade', 10),
            'estoque_minimo'     => (int) $decimal('estoque_minimo'),
            'valor_compra'       => $decimal('valor_compra'),
            'valor_venda'        => $decimal('valor_venda'),
            'percentual_lucro'   => $decimal('percentual_lucro'),
            'valor_ultima_compra'=> $decimal('valor_ultima_compra'),
            'grupo'              => $texto('grupo', 3),
            'subgrupo'           => $texto('subgrupo', 3),
            'item'               => $texto('item', 3),
            'estante'            => $inteiro('estante'),
            'prateleira'         => $inteiro('prateleira'),
            'marca'              => $texto('marca', 50),
            'cor'                => $texto('cor', 30),
            'referencia'         => $texto('referencia', 30),
            'tamanho'            => $texto('tamanho', 20),
            'capacidade'         => $texto('capacidade', 25),
            'aplicacao'          => $valor('aplicacao') === '' ? NULL : $valor('aplicacao'),
            'situacao'           => $situacao === '' ? NULL : (int) $situacao,
            'ativo'              => ! empty($post['ativo']),
        );
    }

    // ------------------------------------------------------------------
    // Acoes da tela
    // ------------------------------------------------------------------

    public function alternar_ativo($id)
    {
        if ( ! $this->eh_post())
        {
            show_error('Metodo nao permitido.', 405);
        }

        $id       = (int) $id;
        $registro = $this->produto_model->por_id($id);

        if (empty($registro))
        {
            show_404();
        }

        $ativo = ! bd_bool($registro['ativo']);

        $this->produto_model->atualizar($id, array('ativo' => $ativo));
        $this->auditoria->registrar('alterar', 'produto', $id, array(
            'descricao' => $registro['descricao'],
            'ativo'     => $ativo ? 'true' : 'false',
        ));
        $this->flash($ativo ? 'Produto reativado com sucesso.' : 'Produto inativado com sucesso.');

        $retorno = array('pagina' => max(1, (int) $this->input->post('pagina')));

        foreach (array('busca', 'grupo', 'subgrupo', 'situacao') as $campo)
        {
            $valor = trim((string) $this->input->post($campo));

            if ($valor !== '')
            {
                $retorno[$campo] = $valor;
            }
        }

        redirect('produtos?' . http_build_query($retorno));
    }

    /**
     * Atualizar preco de venda - geral.
     * Sem percentual informado, usa o percentual_lucro de cada produto.
     */
    public function atualizar_precos()
    {
        if ( ! $this->eh_post())
        {
            show_error('Metodo nao permitido.', 405);
        }

        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $bruto = trim((string) $this->input->post('percentual'));
        $perc  = $bruto === '' ? NULL : (float) str_replace(',', '.', $bruto);
        $grupo = trim((string) $this->input->post('grupo'));

        $n = $this->produto_model->atualizar_precos($perc, $grupo);

        $this->auditoria->registrar('alterar', 'produto_preco_geral', NULL, array(
            'percentual' => $perc,
            'grupo'      => $grupo,
            'produtos'   => $n,
        ));
        $this->flash('Precos de venda atualizados (' . $n . ' produto(s)).');
        redirect('produtos');
    }

    /**
     * Migrar os clientes especiais (POPULA_PRODUTOS_CLIENTES_03 do Access):
     * inclui o produto corrente para todos os clientes que ja tem preco especial.
     */
    public function migrar_especiais($id)
    {
        if ( ! $this->eh_post())
        {
            show_error('Metodo nao permitido.', 405);
        }

        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $id       = (int) $id;
        $produto  = $this->produto_model->por_id($id);

        if (empty($produto))
        {
            show_404();
        }

        if ($this->cliente_especial_model->clientes_do_produto($id))
        {
            $this->flash('Produto ja consta com clientes especiais cadastrados. Cadastre individualmente.', 'erro');
            redirect('produtos/ver/' . $id);
        }

        $bruto = trim((string) $this->input->post('percentual'));
        $perc  = $bruto === '' ? 15 : (float) str_replace(',', '.', $bruto);

        $n = $this->cliente_especial_model->incluir_clientes_especiais($id, $perc);

        $this->auditoria->registrar('criar', 'cliente_especial', $id, array(
            'percentual' => $perc,
            'clientes'   => $n,
        ));
        $this->flash('Clientes especiais acoplados ao produto (' . $n . ' cliente(s)).');
        redirect('produtos/ver/' . $id);
    }

    /**
     * Dados gerais do produto (F_PRODUTO_NF_01 do Access): notas de compra.
     */
    public function dados_gerais($id)
    {
        $id       = (int) $id;
        $registro = $this->produto_model->por_id($id);

        if (empty($registro))
        {
            show_404();
        }

        $notas = $this->db->query(
            "SELECT p.numero, p.num_nf, p.data_nf, f.razao_social AS fornecedor,
                    i.quantidade, i.valor_unitario, i.valor_total
               FROM " . T_COMPRAS_ITENS . " i
               JOIN " . T_COMPRAS_PEDIDOS . " p ON p.numero = i.pedido_numero
               LEFT JOIN " . T_FORNECEDORES . " f ON f.codigo = p.fornecedor_codigo
              WHERE i.produto_id = ?
              ORDER BY p.data_nf DESC NULLS LAST, p.numero DESC
              LIMIT 50",
            array($id)
        )->result_array();

        $this->render('produtos/dados_gerais', array(
            'registro' => $registro,
            'notas'    => $notas,
        ));
    }

    public function alteracoes($id)
    {
        $id       = (int) $id;
        $registro = $this->produto_model->por_id($id);

        if (empty($registro))
        {
            show_404();
        }

        $eventos = $this->auditoria->por_entidade('produto', $id, 100);
        $ultima  = $this->input->get('ultima') !== FALSE;

        if ($ultima)
        {
            $eventos = array_slice($eventos, 0, 1);
        }

        $this->render('produtos/alteracoes', array(
            'registro' => $registro,
            'eventos'  => $eventos,
            'ultima'   => $ultima,
        ));
    }

    public function excluir($id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN));

        if ( ! $this->eh_post())
        {
            show_error('Metodo nao permitido.', 405);
        }

        $id       = (int) $id;
        $registro = $this->produto_model->por_id($id);

        if (empty($registro))
        {
            show_404();
        }

        try
        {
            $this->produto_model->excluir($id);
            $this->auditoria->registrar('excluir', 'produto', $id, $registro);
            $this->flash('Produto excluido.');
        }
        catch (Exception $e)
        {
            $this->flash('Nao foi possivel excluir: existem registros vinculados ao produto.', 'erro');
        }

        redirect('produtos');
    }

    public function especial_salvar($id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('produtos/ver/' . (int) $id);
        }

        $produto = $this->produto_model->por_id((int) $id);
        $cliente = (int) $this->input->post('cliente_id');

        if (empty($produto) || $cliente <= 0)
        {
            $this->flash('Cliente ou produto invalido.', 'erro');
            redirect('produtos/ver/' . (int) $id);
        }

        $bruto = trim((string) $this->input->post('perc_lucro'));
        $perc  = $bruto === '' ? NULL : (float) str_replace(',', '.', $bruto);

        $bruto = trim((string) $this->input->post('valor_venda'));
        $valor = $bruto === '' ? NULL : (float) str_replace(array('.', ','), array('', '.'), $bruto);

        if ($valor === NULL && $perc !== NULL)
        {
            $valor = round((float) $produto['valor_compra'] * (1 + $perc / 100), 2);
        }

        $this->cliente_especial_model->salvar($cliente, (int) $id, $perc, $valor);
        $this->auditoria->registrar('alterar', 'cliente_especial', $cliente, array(
            'produto_id'  => (int) $id,
            'perc_lucro'  => $perc,
            'valor_venda' => $valor,
        ));
        $this->flash('Preco especial salvo.');
        redirect('produtos/ver/' . (int) $id);
    }

    /**
     * Exclui o preco especial de um cliente ja cadastrado na grade do produto.
     */
    public function especial_excluir($produto_id, $cliente_id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            show_error('Metodo nao permitido.', 405);
        }

        $produto_id = (int) $produto_id;
        $cliente_id = (int) $cliente_id;

        if (empty($this->produto_model->por_id($produto_id)) || $cliente_id <= 0)
        {
            show_404();
        }

        $this->cliente_especial_model->excluir_produto($cliente_id, $produto_id);
        $this->auditoria->registrar('excluir', 'cliente_especial', $produto_id, array(
            'cliente_id' => $cliente_id,
        ));
        $this->flash('Preco especial excluido.');
        redirect('produtos/ver/' . $produto_id);
    }

}
