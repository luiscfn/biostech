<?php
class Setores extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('setor_model');
        $this->load->library('auditoria');
        $this->load->library('form_validation');

        $this->titulo     = 'Setores';
        $this->menu_ativo = 'setores';
    }

    // ------------------------------------------------------------------

    public function index()
    {
        $busca = trim((string) $this->input->get('busca'));

        $dados = array(
            'registros' => $this->setor_model->listar($busca),
            'busca'     => $busca,
        );

        $this->render('setores/index', $dados);
    }

    // ------------------------------------------------------------------

    public function novo()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ($this->input->server('REQUEST_METHOD') === 'POST')
        {
            $this->_validar();

            if ($this->form_validation->run() === TRUE)
            {
                $dados = array(
                    'id'    => $this->setor_model->proximo_id(),
                    'nome'  => strtoupper(trim($this->input->post('nome'))),
                    'ativo' => $this->input->post('ativo') ? TRUE : FALSE,
                );

                $this->setor_model->inserir($dados);
                $this->auditoria->registrar('criar', 'setor', $dados['id'], $dados);
                $this->flash('Setor cadastrado com sucesso.');

                redirect('setores');
            }
        }

        $this->render('setores/form', array(
            'registro' => NULL,
        ));
    }

    // ------------------------------------------------------------------

    public function editar($id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $registro = $this->setor_model->por_id((int) $id);

        if (empty($registro))
        {
            show_404();
        }

        if ($this->input->server('REQUEST_METHOD') === 'POST')
        {
            $this->_validar();

            if ($this->form_validation->run() === TRUE)
            {
                $dados = array(
                    'nome'  => strtoupper(trim($this->input->post('nome'))),
                    'ativo' => $this->input->post('ativo') ? TRUE : FALSE,
                );

                $this->setor_model->atualizar((int) $id, $dados);
                $this->auditoria->registrar('alterar', 'setor', (int) $id, $dados);
                $this->flash('Setor atualizado com sucesso.');

                redirect('setores');
            }
        }

        $this->render('setores/form', array(
            'registro' => $registro,
        ));
    }

    // ------------------------------------------------------------------

    public function excluir($id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN));

        if ($this->input->server('REQUEST_METHOD') !== 'POST')
        {
            show_error('Metodo nao permitido.', 405);
        }

        $id       = (int) $id;
        $registro = $this->setor_model->por_id($id);

        if (empty($registro))
        {
            show_404();
        }

        if ($this->setor_model->em_uso($id))
        {
            $this->flash('Nao e possivel excluir: existem usuarios vinculados a este setor.', 'danger');
            redirect('setores');
        }

        $this->setor_model->excluir($id);
        $this->auditoria->registrar('excluir', 'setor', $id, $registro);
        $this->flash('Setor excluido.');

        redirect('setores');
    }

    // ------------------------------------------------------------------

    private function _validar()
    {
        $this->form_validation->set_rules('nome', 'Nome', 'trim|required|min_length[2]|max_length[70]');
        $this->form_validation->set_error_delimiters('<div class="campo-erro">', '</div>');
    }
}
