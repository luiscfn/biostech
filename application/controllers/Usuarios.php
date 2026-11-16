<?php
class Usuarios extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('usuario_model');
        $this->load->model('setor_model');
        $this->load->library('auditoria');
        $this->load->library('form_validation');

        $this->titulo     = 'Usuarios';
        $this->menu_ativo = 'usuarios';
    }

    // ------------------------------------------------------------------

    public function index()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $busca  = trim((string) $this->input->get('busca'));
        $pagina = (int) $this->input->get('pagina');

        $total = $this->usuario_model->total($busca);
        $pag   = $this->paginar($total, 20, $pagina);

        $dados = array(
            'registros' => $this->usuario_model->listar($busca, $pag['por_pagina'], $pag['offset']),
            'busca'     => $busca,
            'pag'       => $pag,
        );

        $this->render('usuarios/index', $dados);
    }

    // ------------------------------------------------------------------

    public function novo()
    {
        $this->exigir_admin();

        if ($this->input->server('REQUEST_METHOD') === 'POST')
        {
            $this->_validar(TRUE);

            $login = strtoupper(trim($this->input->post('login')));

            if ($this->usuario_model->login_existe($login))
            {
                $this->form_validation->set_rules('login', 'Login', 'callback_login_unico');
            }

            if ($this->form_validation->run() === TRUE)
            {
                $dados = $this->_dados_post(TRUE);

                $this->usuario_model->criar($dados);
                $this->auditoria->registrar('criar', 'usuario', $dados['login'], array(
                    'login'  => $dados['login'],
                    'perfil' => $dados['perfil'],
                ));
                $this->flash('Usuario cadastrado. Senha provisoria: ' . $dados['senha']);

                redirect('usuarios');
            }
        }

        $this->render('usuarios/form', array(
            'registro' => NULL,
            'setores'  => $this->setor_model->opcoes(),
        ));
    }

    // ------------------------------------------------------------------

    public function editar($id)
    {
        $this->exigir_admin();

        $registro = $this->usuario_model->por_id((int) $id);

        if (empty($registro))
        {
            show_404();
        }

        if ($this->input->server('REQUEST_METHOD') === 'POST')
        {
            $this->_validar(FALSE);

            $login = strtoupper(trim($this->input->post('login')));

            if ($this->usuario_model->login_existe($login, (int) $id))
            {
                $this->form_validation->set_rules('login', 'Login', 'callback_login_unico');
            }

            if ($this->form_validation->run() === TRUE)
            {
                $dados = $this->_dados_post(FALSE);

                $this->usuario_model->alterar((int) $id, $dados);
                $this->auditoria->registrar('alterar', 'usuario', (int) $id, array(
                    'login'  => $dados['login'],
                    'perfil' => $dados['perfil'],
                    'ativo'  => $dados['ativo'],
                ));
                $this->flash('Usuario atualizado.');

                redirect('usuarios');
            }
        }

        $this->render('usuarios/form', array(
            'registro' => $registro,
            'setores'  => $this->setor_model->opcoes(),
        ));
    }

    // ------------------------------------------------------------------

    public function senha($id)
    {
        $this->exigir_admin();

        $registro = $this->usuario_model->por_id((int) $id);

        if (empty($registro))
        {
            show_404();
        }

        if ($this->input->server('REQUEST_METHOD') === 'POST')
        {
            $this->form_validation->set_rules('senha', 'Senha', 'trim|required|min_length[6]|max_length[64]');
            $this->form_validation->set_rules('senha2', 'Confirmacao', 'trim|required|matches[senha]');
            $this->form_validation->set_error_delimiters('<div class="campo-erro">', '</div>');

            if ($this->form_validation->run() === TRUE)
            {
                $this->usuario_model->alterar_senha((int) $id, $this->input->post('senha'));
                $this->auditoria->registrar('alterar_senha', 'usuario', (int) $id);
                $this->flash('Senha alterada.');

                redirect('usuarios');
            }
        }

        $this->render('usuarios/senha', array(
            'registro' => $registro,
        ));
    }

    // ------------------------------------------------------------------

    public function alternar($id)
    {
        $this->exigir_admin();

        if ($this->input->server('REQUEST_METHOD') !== 'POST')
        {
            show_error('Metodo nao permitido.', 405);
        }

        $registro = $this->usuario_model->por_id((int) $id);

        if (empty($registro))
        {
            show_404();
        }

        $this->usuario_model->alternar_ativo((int) $id);
        $this->auditoria->registrar('alterar', 'usuario', (int) $id, array('ativo' => ! $registro['ativo']));
        $this->flash('Situacao do usuario alterada.');

        redirect('usuarios');
    }

    // ------------------------------------------------------------------

    public function login_unico($login)
    {
        if ($this->usuario_model->login_existe($login))
        {
            $this->form_validation->set_message('login_unico', 'Este login ja esta em uso.');
            return FALSE;
        }

        return TRUE;
    }

    // ------------------------------------------------------------------

    private function _validar($novo)
    {
        $this->form_validation->set_rules('login', 'Login', 'trim|required|min_length[3]|max_length[30]|alpha_dash');
        $this->form_validation->set_rules('nome', 'Nome', 'trim|required|min_length[3]|max_length[100]');
        $this->form_validation->set_rules('perfil', 'Perfil', 'trim|required|in_list[admin,gestor,operador]');
        $this->form_validation->set_rules('setor_id', 'Setor', 'trim|integer');

        if ($novo)
        {
            $this->form_validation->set_rules('senha', 'Senha', 'trim|required|min_length[6]|max_length[64]');
        }

        $this->form_validation->set_error_delimiters('<div class="campo-erro">', '</div>');
    }

    // ------------------------------------------------------------------

    private function _dados_post($novo)
    {
        $setor = trim((string) $this->input->post('setor_id'));

        return array(
            'login'                 => strtoupper(trim($this->input->post('login'))),
            'nome'                  => trim($this->input->post('nome')),
            'senha'                 => $novo ? $this->input->post('senha') : NULL,
            'perfil'                => $this->input->post('perfil'),
            'setor_id'              => $setor === '' ? NULL : (int) $setor,
            'acesso_almoxarifado'   => $this->input->post('acesso_almoxarifado') ? TRUE : FALSE,
            'ativo'                 => $this->input->post('ativo') ? TRUE : FALSE,
        );
    }
}
