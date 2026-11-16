<?php
class Dashboard extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cliente_model');
        $this->load->model('produto_model');
        $this->load->model('setor_model');
        $this->load->model('usuario_model');
        $this->load->library('auditoria');

        $this->titulo     = 'Painel';
        $this->menu_ativo = 'dashboard';
    }

    // ------------------------------------------------------------------

    public function index()
    {
        $clientes = $this->cliente_model->stats();
        $produtos = $this->produto_model->stats();

        $dados = array(
            'clientes_total'   => (int) $clientes['total'],
            'clientes_ativos'  => (int) $clientes['ativos'],
            'produtos_total'   => (int) $produtos['total'],
            'produtos_ativos'  => (int) $produtos['ativos'],
            'produtos_valor'   => (float) $produtos['valor_total'],
            'setores_total'    => $this->setor_model->contar(),
            'usuarios_total'   => $this->usuario_model->contar(),
            'eventos'          => $this->auditoria->ultimos(12),
        );

        $this->render('dashboard/index', $dados);
    }
}
