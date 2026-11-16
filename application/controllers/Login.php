<?php
class Login extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('auth');
    }

    // ------------------------------------------------------------------

    public function index()
    {
        if ($this->auth->logado())
        {
            redirect('dashboard');
        }

        $this->load->view('login/index', array('erro' => NULL, 'login' => ''));
    }

    // ------------------------------------------------------------------

    public function entrar()
    {
        if ($this->auth->logado())
        {
            redirect('dashboard');
        }

        if ($this->input->server('REQUEST_METHOD') !== 'POST')
        {
            redirect('login');
        }

        $login = trim((string) $this->input->post('login'));
        $senha = (string) $this->input->post('senha');

        if ($login === '' || $senha === '')
        {
            $this->load->view('login/index', array(
                'erro'  => 'Informe o usuario e a senha.',
                'login' => $login,
            ));
            return;
        }

        if ($this->auth->tentar($login, $senha))
        {
            redirect('dashboard');
        }

        $this->load->view('login/index', array(
            'erro'  => $this->auth->motivo() !== '' ? $this->auth->motivo() : 'Usuario ou senha invalidos.',
            'login' => $login,
        ));
    }

    // ------------------------------------------------------------------

    public function sair()
    {
        $this->auth->sair();
        redirect('login');
    }
}
