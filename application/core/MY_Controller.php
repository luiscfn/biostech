<?php
/**
 * MY_Controller - controller base com autenticacao e layout
 */
class MY_Controller extends CI_Controller
{
    /** @var array dados do usuario logado */
    protected $usuario = array();

    /** item de menu ativo */
    protected $menu_ativo = '';

    /** titulo da pagina */
    protected $titulo = '';

    public function __construct()
    {
        parent::__construct();

        $this->load->library('auth');

        if ( ! $this->auth->logado())
        {
            redirect('login');
        }

        $this->usuario = $this->auth->usuario();

        if (empty($this->usuario))
        {
            $this->auth->sair();
            redirect('login');
        }
    }

    // ------------------------------------------------------------------

    /**
     * Exige perfil de administrador.
     */
    protected function exigir_admin()
    {
        if ($this->usuario['perfil'] !== PERFIL_ADMIN)
        {
            show_error('Acesso restrito a administradores.', 403, 'Acesso negado');
        }
    }

    /**
     * Exige um dos perfis informados.
     */
    protected function exigir_perfil($perfis)
    {
        if ( ! in_array($this->usuario['perfil'], (array) $perfis, TRUE))
        {
            show_error('Voce nao tem permissao para acessar esta area.', 403, 'Acesso negado');
        }
    }

    // ------------------------------------------------------------------

    protected function flash($mensagem, $tipo = 'success')
    {
        $_SESSION['flash'] = array('mensagem' => $mensagem, 'tipo' => $tipo);
    }

    protected function pegar_flash()
    {
        if (empty($_SESSION['flash']))
        {
            return NULL;
        }

        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }

    // ------------------------------------------------------------------

    /**
     * Renderiza a view dentro do layout.
     */
    protected function render($view, $dados = array())
    {
        $dados['usuario']     = $this->usuario;
        $dados['menu_ativo']  = $this->menu_ativo;
        $dados['titulo']      = $this->titulo;

        $dados['conteudo']    = $this->load->view($view, $dados, TRUE);
        $dados['flash']       = $this->pegar_flash();

        $this->load->view('layouts/main', $dados);
    }

    // ------------------------------------------------------------------

    /**
     * Paginacao simples por limit/offset.
     */
    protected function paginar($total, $por_pagina, $pagina)
    {
        $paginas = max(1, (int) ceil($total / $por_pagina));
        $pagina  = max(1, min($pagina, $paginas));

        return array(
            'total'      => (int) $total,
            'por_pagina' => (int) $por_pagina,
            'pagina'     => (int) $pagina,
            'paginas'    => (int) $paginas,
            'offset'     => (int) (($pagina - 1) * $por_pagina),
        );
    }
}
