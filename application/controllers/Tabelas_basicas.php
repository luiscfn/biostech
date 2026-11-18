<?php
/**
 * Tabelas_basicas - itens do menu F_MODULO_TABELAS do Access.
 *
 * Leitura liberada a todos os usuarios autenticados; inclusao, alteracao e
 * exclusao restritas a admin e gestor (mesma regra dos cadastros do
 * Financeiro). Acoes destrutivas exigem POST.
 */
class Tabelas_basicas extends MY_Controller
{
    /** @var bool usuario pode gravar/excluir */
    private $pode_editar = FALSE;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('tabelas_basicas_model');
        $this->load->library('auditoria');

        $this->menu_ativo = 'tabelas_basicas';
        $this->titulo     = 'Tabelas Basicas';
        $this->pode_editar = in_array(
            $this->usuario['perfil'],
            array(PERFIL_ADMIN, PERFIL_GESTOR),
            TRUE
        );
    }

    // ==================================================================
    // Menu
    // ==================================================================

    public function index()
    {
        $this->render('tabelas_basicas/index', array(
            'tipos'       => $this->tabelas_basicas_model->tipos(),
            'contagens'   => $this->tabelas_basicas_model->contagens(),
            'pode_editar' => $this->pode_editar,
        ));
    }

    // ==================================================================
    // Itens simples (dominios e setores)
    // ==================================================================

    public function categoria($chave)
    {
        $cfg = $this->tabelas_basicas_model->tipo($chave);

        if ($cfg === NULL)
        {
            show_404();
        }

        if ($cfg['fonte'] === 'parametros')
        {
            redirect('tabelas_basicas/parametros');
        }

        if ($cfg['fonte'] === 'permissoes')
        {
            redirect('tabelas_basicas/permissoes');
        }

        $this->menu_sub = $chave;
        $this->titulo   = 'Tabelas Basicas - ' . $cfg['rotulo'];

        $busca  = trim((string) $this->input->get('busca'));
        $pagina = (int) $this->input->get('pagina');
        $editar = (int) $this->input->get('editar');
        $registro = $editar > 0
            ? $this->tabelas_basicas_model->registro($chave, $editar)
            : array();

        if ($editar > 0 && empty($registro))
        {
            show_404();
        }

        $total = $this->tabelas_basicas_model->total_registros($chave, $busca);
        $pag   = $this->paginar($total, 7, $pagina);

        $this->render('tabelas_basicas/catalogo', array(
            'chave'         => $chave,
            'cfg'           => $cfg,
            'registros'     => $this->tabelas_basicas_model->registros($chave, $busca, $pag['por_pagina'], $pag['offset']),
            'busca'         => $busca,
            'pag'           => $pag,
            'registro'      => $registro,
            'pode_editar'   => $this->pode_editar,
            'proximo_codigo'=> $this->tabelas_basicas_model->proximo_codigo($chave),
        ));
    }

    public function salvar($chave)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $cfg = $this->tabelas_basicas_model->tipo($chave);

        if ($cfg === NULL)
        {
            show_404();
        }

        if ($cfg['fonte'] !== 'dominio' && $cfg['fonte'] !== 'setores')
        {
            show_404();
        }

        $editar = (int) $this->_post('editar', 0);

        $dados = array(
            'codigo' => $this->_post('codigo'),
            'nome'   => $this->_post('nome'),
            'valor'  => $this->_post('valor'),
            'ordem'  => $this->_post('ordem'),
            'ativo'  => $this->_post('ativo', NULL) !== NULL,
        );

        $r = $this->tabelas_basicas_model->salvar(
            $chave,
            $dados,
            $editar > 0 ? $editar : NULL
        );

        if ($r['ok'])
        {
            $codigo = $editar > 0 ? $editar : (int) $dados['codigo'];
            $this->auditoria->registrar($editar > 0 ? 'alterar' : 'criar',
                'tabela_basica_' . $chave, $codigo, $dados);
            $this->flash($r['mensagem']);
            redirect('tabelas_basicas/categoria/' . $chave);
        }

        $this->flash($r['erro'], 'danger');
        redirect('tabelas_basicas/categoria/' . $chave . ($editar > 0 ? '?editar=' . $editar : ''));
    }

    public function excluir($chave, $codigo)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $cfg = $this->tabelas_basicas_model->tipo($chave);

        if ($cfg === NULL)
        {
            show_404();
        }

        $r = $this->tabelas_basicas_model->excluir_item($chave, $codigo);

        if ($r['ok'])
        {
            $this->auditoria->registrar('excluir', 'tabela_basica_' . $chave, (int) $codigo);
        }

        $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'danger');
        redirect('tabelas_basicas/categoria/' . $chave);
    }

    // ==================================================================
    // Valor Orcamento Nao Aprovado
    // ==================================================================

    public function parametros()
    {
        $this->menu_sub = 'valor_orcamento_nao_aprovado';
        $this->titulo   = 'Tabelas Basicas - Valor Orcamento Nao Aprovado';

        $this->render('tabelas_basicas/parametros', array(
            'registro'    => $this->tabelas_basicas_model->parametros(),
            'pode_editar' => $this->pode_editar,
        ));
    }

    public function parametros_salvar()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $dados = array(
            'valor'               => $this->_post('valor'),
            'prazo_ret_equip'     => $this->_post('prazo_ret_equip'),
            'valor_taxa_perm'     => $this->_post('valor_taxa_perm'),
            'prazo_ini_envio_orc' => $this->_post('prazo_ini_envio_orc'),
            'prazo_fim_envio_orc' => $this->_post('prazo_fim_envio_orc'),
            'dias_limite'         => $this->_post('dias_limite'),
            'texto_rodape'        => $this->_post('texto_rodape'),
        );

        $r = $this->tabelas_basicas_model->salvar_parametros($dados);

        $this->auditoria->registrar('alterar', 'tabela_basica_parametros', 1, $dados);
        $this->flash($r['mensagem']);

        redirect('tabelas_basicas/parametros');
    }

    // ==================================================================
    // Permissoes de acesso
    // ==================================================================

    public function permissoes()
    {
        $this->menu_sub = 'permissoes';
        $this->titulo   = 'Tabelas Basicas - Permissoes de Acesso';

        $busca  = trim((string) $this->input->get('busca'));
        $pagina = (int) $this->input->get('pagina');
        $editar = (int) $this->input->get('editar');
        $registro = $editar > 0 ? $this->tabelas_basicas_model->modulo($editar) : array();

        if ($editar > 0 && empty($registro))
        {
            show_404();
        }

        $total = $this->tabelas_basicas_model->total_modulos($busca);
        $pag   = $this->paginar($total, 7, $pagina);

        $this->render('tabelas_basicas/permissoes', array(
            'modulos'     => $this->tabelas_basicas_model->modulos($busca, $pag['por_pagina'], $pag['offset']),
            'busca'       => $busca,
            'pag'         => $pag,
            'registro'    => $registro,
            'pode_editar' => $this->pode_editar,
            'proximo_codigo' => $this->tabelas_basicas_model->proximo_codigo_modulo(),
        ));
    }

    public function modulo_salvar()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $editar = (int) $this->_post('editar', 0);

        $dados = array(
            'codigo' => $this->_post('codigo'),
            'nome'   => $this->_post('nome'),
            'ordem'  => $this->_post('ordem'),
            'ativo'  => $this->_post('ativo', NULL) !== NULL,
        );

        $r = $this->tabelas_basicas_model->salvar_modulo($dados, $editar > 0 ? $editar : NULL);

        if ($r['ok'])
        {
            $this->auditoria->registrar($editar > 0 ? 'alterar' : 'criar',
                'tabela_basica_modulo', $r['codigo'], $dados);
            $this->flash($r['mensagem']);
            redirect('tabelas_basicas/permissoes');
        }

        $this->flash($r['erro'], 'danger');
        redirect('tabelas_basicas/permissoes' . ($editar > 0 ? '?editar=' . $editar : ''));
    }

    public function modulo_excluir($codigo)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $r = $this->tabelas_basicas_model->excluir_modulo($codigo);

        if ($r['ok'])
        {
            $this->auditoria->registrar('excluir', 'tabela_basica_modulo', (int) $codigo);
        }

        $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'danger');
        redirect('tabelas_basicas/permissoes');
    }

    public function programas($modulo)
    {
        $modulo = (int) $modulo;
        $registro_modulo = $this->tabelas_basicas_model->modulo($modulo);

        if (empty($registro_modulo))
        {
            show_404();
        }

        $this->menu_sub = 'permissoes';
        $this->titulo   = 'Tabelas Basicas - Programas de ' . $registro_modulo['nome'];

        $editar = (int) $this->input->get('editar');
        $pagina = (int) $this->input->get('pagina');
        $registro = $editar > 0
            ? $this->tabelas_basicas_model->programa($modulo, $editar)
            : array();

        if ($editar > 0 && empty($registro))
        {
            show_404();
        }

        $total = $this->tabelas_basicas_model->total_programas($modulo);
        $pag   = $this->paginar($total, 7, $pagina);

        $this->render('tabelas_basicas/programas', array(
            'modulo'      => $registro_modulo,
            'programas'   => $this->tabelas_basicas_model->programas($modulo, $pag['por_pagina'], $pag['offset']),
            'pag'         => $pag,
            'registro'    => $registro,
            'pode_editar' => $this->pode_editar,
            'proximo_codigo' => $this->tabelas_basicas_model->proximo_codigo_programa($modulo),
        ));
    }

    public function programa_salvar($modulo)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $modulo = (int) $modulo;
        $editar = (int) $this->_post('editar', 0);

        $dados = array(
            'codigo'     => $this->_post('codigo'),
            'nome'       => $this->_post('nome'),
            'formulario' => $this->_post('formulario'),
            'ordem'      => $this->_post('ordem'),
            'ativo'      => $this->_post('ativo', NULL) !== NULL,
        );

        $r = $this->tabelas_basicas_model->salvar_programa(
            $modulo, $dados, $editar > 0 ? $editar : NULL
        );

        if ($r['ok'])
        {
            $codigo = $editar > 0 ? $editar : (int) $dados['codigo'];
            $this->auditoria->registrar($editar > 0 ? 'alterar' : 'criar',
                'tabela_basica_programa', $codigo, $dados);
            $this->flash($r['mensagem']);
            redirect('tabelas_basicas/programas/' . $modulo);
        }

        $this->flash($r['erro'], 'danger');
        redirect('tabelas_basicas/programas/' . $modulo . ($editar > 0 ? '?editar=' . $editar : ''));
    }

    public function programa_excluir($modulo, $codigo)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));
        $this->_exigir_post();

        $modulo = (int) $modulo;
        $r = $this->tabelas_basicas_model->excluir_programa($modulo, $codigo);

        if ($r['ok'])
        {
            $this->auditoria->registrar('excluir', 'tabela_basica_programa', (int) $codigo);
        }

        $this->flash($r['ok'] ? $r['mensagem'] : $r['erro'], $r['ok'] ? 'success' : 'danger');
        redirect('tabelas_basicas/programas/' . $modulo);
    }

    // ==================================================================
    // Auxiliares
    // ==================================================================

    private function _post($campo, $default = NULL)
    {
        $v = $this->input->post($campo);

        return $v === FALSE ? $default : $v;
    }

    private function _exigir_post()
    {
        if ( ! $this->eh_post())
        {
            redirect('tabelas_basicas');
        }
    }
}
