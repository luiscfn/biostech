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
        $pag   = $this->paginar($total, 25, $pagina);

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

        $this->render('clientes/ver', array('registro' => $registro));
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
}
