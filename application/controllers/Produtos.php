<?php
class Produtos extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('produto_model');

        $this->titulo     = 'Produtos';
        $this->menu_ativo = 'produtos';
    }

    // ------------------------------------------------------------------

    public function index()
    {
        $busca  = trim((string) $this->input->get('busca'));
        $pagina = (int) $this->input->get('pagina');

        $total = $this->produto_model->total($busca);
        $pag   = $this->paginar($total, 25, $pagina);

        $dados = array(
            'registros' => $this->produto_model->listar($busca, $pag['por_pagina'], $pag['offset']),
            'busca'     => $busca,
            'pag'       => $pag,
        );

        $this->render('produtos/index', $dados);
    }

    // ------------------------------------------------------------------

    public function ver($id)
    {
        $registro = $this->produto_model->por_id((int) $id);

        if (empty($registro))
        {
            show_404();
        }

        $this->render('produtos/ver', array('registro' => $registro));
    }
}
