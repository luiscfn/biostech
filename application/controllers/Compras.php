<?php
/**
 * Compras - pedidos de compra, itens, parcelas, fechamento, cotacoes de
 * precos, solicitacoes internas, prazos de fornecedor e impressao.
 *
 * Regras principais (ver docs/08-regras-compras.md):
 *  - somente pedido aberto pode ser alterado, excluido ou cancelado;
 *  - fechamento gera entrada de estoque e contas a pagar;
 *  - cancelamento do fechamento estorna o estoque e cancela os titulos;
 *  - cotacao compara ate 3 fornecedores por item;
 *  - solicitacao interna pode gerar cotacao e/ou pedido de compra.
 */
class Compras extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('compras_model');
        $this->load->model('financeiro_model');
        $this->load->library('auditoria');
        $this->load->library('form_validation');

        $this->titulo     = 'Compras';
        $this->menu_ativo = 'compras';
    }

    // ==================================================================
    // Pedidos
    // ==================================================================

    public function index()
    {
        $filtros = array(
            'busca'      => trim((string) $this->input->get('busca')),
            'fornecedor' => (int) $this->input->get('fornecedor'),
            'situacao'   => trim((string) $this->input->get('situacao')),
            'setor'      => (int) $this->input->get('setor'),
            'data_de'    => trim((string) $this->input->get('data_de')),
            'data_ate'   => trim((string) $this->input->get('data_ate')),
        );

        $pagina = (int) $this->input->get('pagina');
        $total  = $this->compras_model->total_pedidos($filtros);
        $pag    = $this->paginar($total, 10, $pagina);

        $dados = array(
            'registros' => $this->compras_model->listar_pedidos($filtros, $pag['por_pagina'], $pag['offset']),
            'filtros'   => $filtros,
            'pag'       => $pag,
            'setores'   => $this->compras_model->setores(),
        );

        $this->titulo = 'Compras';
        $this->render('compras/index', $dados);
    }

    // ------------------------------------------------------------------

    public function novo()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ($this->eh_post())
        {
            $dados = $this->_post_pedido();
            $itens = $this->_post_itens_pedido();

            $resultado = $this->compras_model->salvar_pedido($dados, $itens, $this->usuario);

            if ( ! $resultado['ok'])
            {
                $this->flash($resultado['erro'], 'erro');
                redirect('compras/novo');
            }

            $this->auditoria->registrar('criar', 'compra_pedido', $resultado['numero'], array(
                'fornecedor' => isset($dados['fornecedor_codigo']) ? $dados['fornecedor_codigo'] : NULL,
                'itens'      => $resultado['itens'],
                'total'      => $resultado['total'],
            ));

            $this->flash('Pedido de compra ' . $resultado['numero'] . ' criado com ' . $resultado['itens'] . ' item(ns).');
            redirect('compras/ver/' . $resultado['numero']);
        }

        $this->render('compras/form', array(
            'registro'    => array(),
            'itens'       => array(),
            'setores'     => $this->compras_model->setores(),
            'funcionarios'=> $this->compras_model->funcionarios(),
            'dominios'    => $this->_dominios(),
            'centros_custo' => $this->financeiro_model->centros_custo(),
            'contas'      => array(),
        ));
    }

    // ------------------------------------------------------------------

    public function editar($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $numero = (int) $numero;
        $pedido = $this->compras_model->pedido($numero);

        if (empty($pedido))
        {
            show_404();
        }

        if ($pedido['situacao'] !== 'aberto')
        {
            $this->flash('Somente pedidos abertos podem ser alterados.', 'erro');
            redirect('compras/ver/' . $numero);
        }

        if ($this->eh_post())
        {
            $dados = $this->_post_pedido();
            $dados['numero'] = $numero;
            $itens = $this->_post_itens_pedido();

            $resultado = $this->compras_model->salvar_pedido($dados, $itens, $this->usuario);

            if ( ! $resultado['ok'])
            {
                $this->flash($resultado['erro'], 'erro');
                redirect('compras/editar/' . $numero);
            }

            $this->auditoria->registrar('alterar', 'compra_pedido', $numero, array(
                'itens' => $resultado['itens'],
                'total' => $resultado['total'],
            ));

            $this->flash('Pedido ' . $numero . ' atualizado.');
            redirect('compras/ver/' . $numero);
        }

        $this->render('compras/form', array(
            'registro'    => $pedido,
            'itens'       => $this->compras_model->itens_pedido($numero),
            'setores'     => $this->compras_model->setores(),
            'funcionarios'=> $this->compras_model->funcionarios(),
            'dominios'    => $this->_dominios(),
            'centros_custo' => $this->financeiro_model->centros_custo(),
            'contas'      => $this->compras_model->contas_pagar($numero),
        ));
    }

    // ------------------------------------------------------------------

    public function ver($numero)
    {
        $numero = (int) $numero;
        $pedido = $this->compras_model->pedido($numero);

        if (empty($pedido))
        {
            show_404();
        }

        $aba  = (string) $this->input->get('aba');
        $abas = array('dados', 'itens', 'parcelas', 'contas', 'log');

        if ( ! in_array($aba, $abas, TRUE))
        {
            $aba = 'dados';
        }

        $dados = array(
            'registro'    => $pedido,
            'itens'       => $this->compras_model->itens_pedido($numero),
            'parcelas'    => $this->compras_model->parcelas($numero),
            'contas'      => $this->compras_model->contas_pagar($numero),
            'log'         => $this->compras_model->log_pedido($numero),
            'aba'         => $aba,
            'abas'        => $abas,
            'prazos'      => ! empty($pedido['fornecedor_codigo'])
                                ? $this->compras_model->prazos($pedido['fornecedor_codigo'])
                                : array(),
        );

        $this->titulo = 'Pedido de compra ' . $numero;
        $this->render('compras/ver', $dados);
    }

    // ------------------------------------------------------------------

    public function excluir($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('compras/ver/' . (int) $numero);
        }

        $resultado = $this->compras_model->excluir_pedido((int) $numero, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
            redirect('compras/ver/' . (int) $numero);
        }

        $this->auditoria->registrar('excluir', 'compra_pedido', (int) $numero, NULL);

        $this->flash('Pedido excluido.');
        redirect('compras');
    }

    // ------------------------------------------------------------------

    public function cancelar($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('compras/ver/' . (int) $numero);
        }

        $motivo = trim((string) $this->input->post('motivo'));

        if ($motivo === '')
        {
            $this->flash('Informe o motivo do cancelamento.', 'erro');
            redirect('compras/ver/' . (int) $numero);
        }

        $resultado = $this->compras_model->cancelar_pedido((int) $numero, $motivo, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'compra_pedido', (int) $numero, array('cancelamento' => $motivo));
            $this->flash('Pedido cancelado.');
        }

        redirect('compras/ver/' . (int) $numero);
    }

    // ------------------------------------------------------------------

    public function reabrir($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('compras/ver/' . (int) $numero);
        }

        $resultado = $this->compras_model->reabrir_pedido((int) $numero, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'compra_pedido', (int) $numero, array('reabertura' => TRUE));
            $this->flash('Pedido reaberto.');
        }

        redirect('compras/ver/' . (int) $numero);
    }

    // ------------------------------------------------------------------

    public function replicar($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('compras/ver/' . (int) $numero);
        }

        $resultado = $this->compras_model->replicar_pedido((int) $numero, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
            redirect('compras/ver/' . (int) $numero);
        }

        $this->auditoria->registrar('criar', 'compra_pedido', $resultado['numero'], array(
            'replicado_de' => (int) $numero,
        ));

        $this->flash('Pedido ' . $resultado['numero'] . ' criado a partir do pedido ' . (int) $numero . '.');
        redirect('compras/ver/' . $resultado['numero']);
    }

    // ==================================================================
    // Itens
    // ==================================================================

    public function excluir_item($numero, $produto_id)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('compras/ver/' . (int) $numero);
        }

        $resultado = $this->compras_model->excluir_item((int) $numero, (int) $produto_id, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('excluir', 'compra_item', (int) $numero, array('produto_id' => (int) $produto_id));
            $this->flash('Item excluido do pedido.');
        }

        redirect('compras/ver/' . (int) $numero . '?aba=itens');
    }

    // ------------------------------------------------------------------

    public function transferir_item($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('compras/ver/' . (int) $numero . '?aba=itens');
        }

        $resultado = $this->compras_model->transferir_item(
            (int) $numero,
            (int) $this->input->post('destino'),
            (int) $this->input->post('produto_id'),
            $this->usuario
        );

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'compra_item', (int) $numero, array(
                'transferencia' => (int) $this->input->post('destino'),
                'produto_id'    => (int) $this->input->post('produto_id'),
            ));
            $this->flash('Item transferido.');
        }

        redirect('compras/ver/' . (int) $numero . '?aba=itens');
    }

    // ==================================================================
    // Fechamento
    // ==================================================================

    public function fechar($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        $numero = (int) $numero;
        $pedido = $this->compras_model->pedido($numero);

        if (empty($pedido))
        {
            show_404();
        }

        if ($pedido['situacao'] !== 'aberto')
        {
            $this->flash('Somente pedidos abertos podem ser fechados.', 'erro');
            redirect('compras/ver/' . $numero);
        }

        $itens = $this->compras_model->itens_pedido($numero);

        if (empty($itens))
        {
            $this->flash('Inclua pelo menos um item antes de fechar o pedido.', 'erro');
            redirect('compras/editar/' . $numero);
        }

        if ($this->eh_post())
        {
            $dados = array(
                'data_compra'            => $this->input->post('data_compra'),
                'num_nf'                 => $this->input->post('num_nf'),
                'data_nf'                => $this->input->post('data_nf'),
                'data_vencimento'        => $this->input->post('data_vencimento'),
                'condicao_pagamento'     => $this->input->post('condicao_pagamento'),
                'forma_recebimento'      => $this->input->post('forma_recebimento'),
                'documento_recebimento'  => $this->input->post('documento_recebimento'),
                'centro_custo'           => $this->input->post('centro_custo'),
                'prazo_entrega'          => $this->input->post('prazo_entrega'),
                'gerar_contas'           => $this->input->post('gerar_contas'),
                'fornecedor_estoque'     => $this->input->post('fornecedor_estoque'),
            );

            $parcelas = $this->_post_parcelas();

            $resultado = $this->compras_model->fechar_pedido($numero, $dados, $parcelas, $this->usuario);

            if ( ! $resultado['ok'])
            {
                $this->flash($resultado['erro'], 'erro');
                redirect('compras/fechar/' . $numero);
            }

            $this->auditoria->registrar('alterar', 'compra_pedido', $numero, array(
                'fechamento'  => TRUE,
                'parcelas'    => count($resultado['parcelas']),
                'conta_pagar' => $resultado['conta_pagar'],
                'lote'        => $resultado['lote'],
            ));

            $mensagem = 'Pedido ' . $numero . ' fechado. Estoque atualizado (lote ' . $resultado['lote'] . ').';

            if ( ! empty($resultado['conta_pagar']))
            {
                $mensagem .= ' Contas a pagar geradas sob o numero ' . $resultado['conta_pagar'] . '.';
            }

            $this->flash($mensagem);
            redirect('compras/ver/' . $numero . '?aba=parcelas');
        }

        $this->titulo = 'Fechar pedido ' . $numero;
        $this->render('compras/fechar', array(
            'registro' => $pedido,
            'itens'    => $itens,
            'prazos'   => ! empty($pedido['fornecedor_codigo'])
                            ? $this->compras_model->prazos($pedido['fornecedor_codigo'])
                            : array(),
            'dominios' => $this->_dominios(),
        ));
    }

    // ------------------------------------------------------------------

    public function cancelar_fechamento($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('compras/ver/' . (int) $numero);
        }

        $motivo = trim((string) $this->input->post('motivo'));

        $resultado = $this->compras_model->cancelar_fechamento((int) $numero, $motivo, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'compra_pedido', (int) $numero, array(
                'cancelamento_fechamento' => $motivo,
                'estornos'                => $resultado['estornos'],
            ));
            $this->flash('Fechamento cancelado. ' . $resultado['estornos'] . ' movimento(s) de estoque estornado(s) e titulos cancelados.');
        }

        redirect('compras/ver/' . (int) $numero);
    }

    // ==================================================================
    // Cotacoes
    // ==================================================================

    public function cotacoes()
    {
        $filtros = array(
            'busca'   => trim((string) $this->input->get('busca')),
            'data_de' => trim((string) $this->input->get('data_de')),
            'data_ate'=> trim((string) $this->input->get('data_ate')),
        );

        $pagina = (int) $this->input->get('pagina');
        $total  = $this->compras_model->total_cotacoes($filtros);
        $pag    = $this->paginar($total, 25, $pagina);

        $this->titulo = 'Cotacoes de precos';
        $this->render('compras/cotacoes', array(
            'registros' => $this->compras_model->listar_cotacoes($filtros, $pag['por_pagina'], $pag['offset']),
            'filtros'   => $filtros,
            'pag'       => $pag,
        ));
    }

    // ------------------------------------------------------------------

    public function cotacao($codigo = NULL)
    {
        $codigo = (int) $codigo;
        $registro = $codigo > 0 ? $this->compras_model->cotacao($codigo) : NULL;

        if ($codigo > 0 && empty($registro))
        {
            show_404();
        }

        if ($this->eh_post())
        {
            $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

            $dados = array(
                'codigo'       => $codigo,
                'data_cotacao' => $this->input->post('data_cotacao'),
                'num_os'       => $this->input->post('num_os'),
                'solicitante_id' => $this->input->post('solicitante_id'),
                'funcionario_id' => $this->input->post('funcionario_id'),
                'observacao'   => $this->input->post('observacao'),
                'solicitacao_numero' => $this->input->post('solicitacao_numero'),
            );

            $itens = $this->_post_itens_cotacao();

            $resultado = $this->compras_model->salvar_cotacao($dados, $itens, $this->usuario);

            if ( ! $resultado['ok'])
            {
                $this->flash($resultado['erro'], 'erro');
                redirect($codigo > 0 ? 'compras/cotacao/' . $codigo : 'compras/cotacao');
            }

            $this->auditoria->registrar($codigo > 0 ? 'alterar' : 'criar', 'compra_cotacao', $resultado['codigo'], array(
                'itens' => $resultado['itens'],
            ));

            $this->flash('Cotacao ' . $resultado['codigo'] . ' gravada com ' . $resultado['itens'] . ' item(ns).');
            redirect('compras/cotacao/' . $resultado['codigo']);
        }

        $this->titulo = $codigo > 0 ? 'Cotacao ' . $codigo : 'Nova cotacao';
        $this->render('compras/cotacao', array(
            'registro'    => $registro,
            'itens'       => $codigo > 0 ? $this->compras_model->itens_cotacao($codigo) : array(),
            'funcionarios'=> $this->compras_model->funcionarios(),
        ));
    }

    // ------------------------------------------------------------------

    public function cotacao_excluir($codigo)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('compras/cotacoes');
        }

        $resultado = $this->compras_model->excluir_cotacao((int) $codigo, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('excluir', 'compra_cotacao', (int) $codigo, NULL);
            $this->flash('Cotacao excluida.');
        }

        redirect('compras/cotacoes');
    }

    // ------------------------------------------------------------------

    public function importar_solicitacao($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('compras/solicitacao/' . (int) $numero);
        }

        $resultado = $this->compras_model->importar_solicitacao_cotacao((int) $numero, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
            redirect('compras/solicitacao/' . (int) $numero);
        }

        $this->auditoria->registrar('criar', 'compra_cotacao', $resultado['codigo'], array(
            'solicitacao' => (int) $numero,
        ));

        $this->flash('Solicitacao ' . (int) $numero . ' importada para a cotacao ' . $resultado['codigo'] . '.');
        redirect('compras/cotacao/' . $resultado['codigo']);
    }

    // ==================================================================
    // Solicitacoes internas
    // ==================================================================

    public function solicitacoes()
    {
        $filtros = array(
            'busca'    => trim((string) $this->input->get('busca')),
            'situacao' => trim((string) $this->input->get('situacao')),
            'setor'    => (int) $this->input->get('setor'),
            'data_de'  => trim((string) $this->input->get('data_de')),
            'data_ate' => trim((string) $this->input->get('data_ate')),
        );

        $pagina = (int) $this->input->get('pagina');
        $total  = $this->compras_model->total_solicitacoes($filtros);
        $pag    = $this->paginar($total, 10, $pagina);

        $this->titulo = 'Solicitacoes internas';
        $this->render('compras/solicitacoes', array(
            'registros' => $this->compras_model->listar_solicitacoes($filtros, $pag['por_pagina'], $pag['offset']),
            'filtros'   => $filtros,
            'pag'       => $pag,
            'setores'   => $this->compras_model->setores(),
        ));
    }

    // ------------------------------------------------------------------

    public function solicitacao($numero = NULL)
    {
        $numero   = (int) $numero;
        $registro = $numero > 0 ? $this->compras_model->solicitacao($numero) : NULL;

        if ($numero > 0 && empty($registro))
        {
            show_404();
        }

        if ($this->eh_post())
        {
            $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

            $dados = array(
                'numero'                  => $numero,
                'data'                    => $this->input->post('data'),
                'hora'                    => $this->input->post('hora'),
                'setor_id'                => $this->input->post('setor_id'),
                'funcionario_id'          => $this->input->post('funcionario_id'),
                'local_entrega'           => $this->input->post('local_entrega'),
                'responsavel_recebimento' => $this->input->post('responsavel_recebimento'),
                'fone_responsavel'        => $this->input->post('fone_responsavel'),
                'observacao'              => $this->input->post('observacao'),
            );

            $itens = $this->_post_itens_solicitacao();

            $resultado = $this->compras_model->salvar_solicitacao($dados, $itens, $this->usuario);

            if ( ! $resultado['ok'])
            {
                $this->flash($resultado['erro'], 'erro');
                redirect($numero > 0 ? 'compras/solicitacao/' . $numero : 'compras/solicitacao');
            }

            $this->auditoria->registrar($numero > 0 ? 'alterar' : 'criar', 'compra_solicitacao', $resultado['numero'], array(
                'itens' => $resultado['itens'],
            ));

            $this->flash('Solicitacao ' . $resultado['numero'] . ' gravada com ' . $resultado['itens'] . ' item(ns).');
            redirect('compras/solicitacao/' . $resultado['numero']);
        }

        $this->titulo = $numero > 0 ? 'Solicitacao ' . $numero : 'Nova solicitacao interna';
        $this->render('compras/solicitacao', array(
            'registro'    => $registro,
            'itens'       => $numero > 0 ? $this->compras_model->itens_solicitacao($numero) : array(),
            'setores'     => $this->compras_model->setores(),
            'funcionarios'=> $this->compras_model->funcionarios(),
        ));
    }

    // ------------------------------------------------------------------

    public function solicitacao_excluir($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('compras/solicitacao/' . (int) $numero);
        }

        $resultado = $this->compras_model->excluir_solicitacao((int) $numero, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
            redirect('compras/solicitacao/' . (int) $numero);
        }

        $this->auditoria->registrar('excluir', 'compra_solicitacao', (int) $numero, NULL);
        $this->flash('Solicitacao excluida.');
        redirect('compras/solicitacoes');
    }

    // ------------------------------------------------------------------

    public function solicitacao_fechar($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('compras/solicitacao/' . (int) $numero);
        }

        $resultado = $this->compras_model->fechar_solicitacao((int) $numero, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'compra_solicitacao', (int) $numero, array('fechamento' => TRUE));
            $this->flash('Solicitacao fechada.');
        }

        redirect('compras/solicitacao/' . (int) $numero);
    }

    // ------------------------------------------------------------------

    public function solicitacao_gerar_pedido($numero)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('compras/solicitacao/' . (int) $numero);
        }

        $resultado = $this->compras_model->gerar_pedido_solicitacao((int) $numero, $this->usuario);

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
            redirect('compras/solicitacao/' . (int) $numero);
        }

        $this->auditoria->registrar('criar', 'compra_pedido', $resultado['pedido'], array(
            'solicitacao' => (int) $numero,
        ));

        $this->flash('Pedido ' . $resultado['pedido'] . ' gerado a partir da solicitacao ' . (int) $numero . '.');
        redirect('compras/ver/' . $resultado['pedido']);
    }

    // ==================================================================
    // Prazos de fornecedor
    // ==================================================================

    public function prazos($fornecedor = NULL)
    {
        $fornecedor = (int) $fornecedor;

        if ($fornecedor <= 0)
        {
            $fornecedor = (int) $this->input->get('fornecedor');
        }

        $registro = $fornecedor > 0 ? $this->compras_model->fornecedor($fornecedor) : NULL;

        $this->titulo = 'Prazos de fornecedor';
        $this->render('compras/prazos', array(
            'fornecedor' => $registro,
            'registros'  => $fornecedor > 0 ? $this->compras_model->prazos($fornecedor) : array(),
        ));
    }

    // ------------------------------------------------------------------

    public function prazo_salvar()
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('compras/prazos');
        }

        $fornecedor = (int) $this->input->post('fornecedor_codigo');

        $resultado = $this->compras_model->salvar_prazo(array(
            'fornecedor_codigo' => $fornecedor,
            'codigo'            => $this->input->post('codigo'),
            'forma_pagamento'   => $this->input->post('forma_pagamento'),
            'prazo'             => $this->input->post('prazo'),
        ));

        if ( ! $resultado['ok'])
        {
            $this->flash($resultado['erro'], 'erro');
        }
        else
        {
            $this->auditoria->registrar('alterar', 'fornecedor_prazo', $fornecedor, array(
                'codigo' => $resultado['codigo'],
            ));
            $this->flash('Prazo salvo.');
        }

        redirect('compras/prazos/' . $fornecedor);
    }

    // ------------------------------------------------------------------

    public function prazo_excluir($fornecedor, $codigo)
    {
        $this->exigir_perfil(array(PERFIL_ADMIN, PERFIL_GESTOR));

        if ( ! $this->eh_post())
        {
            redirect('compras/prazos/' . (int) $fornecedor);
        }

        $this->compras_model->excluir_prazo((int) $fornecedor, (int) $codigo);
        $this->auditoria->registrar('excluir', 'fornecedor_prazo', (int) $fornecedor, array('codigo' => (int) $codigo));

        $this->flash('Prazo removido.');
        redirect('compras/prazos/' . (int) $fornecedor);
    }

    // ==================================================================
    // Impressao
    // ==================================================================

    public function imprimir_pedido($numero)
    {
        $numero = (int) $numero;
        $pedido = $this->compras_model->pedido($numero);

        if (empty($pedido))
        {
            show_404();
        }

        $this->load->view('compras/imprimir_pedido', array(
            'registro' => $pedido,
            'itens'    => $this->compras_model->itens_pedido($numero),
            'parcelas' => $this->compras_model->parcelas($numero),
        ));
    }

    // ------------------------------------------------------------------

    public function imprimir_cotacao($codigo)
    {
        $codigo = (int) $codigo;
        $cotacao = $this->compras_model->cotacao($codigo);

        if (empty($cotacao))
        {
            show_404();
        }

        $this->load->view('compras/imprimir_cotacao', array(
            'registro' => $cotacao,
            'itens'    => $this->compras_model->itens_cotacao($codigo),
        ));
    }

    // ------------------------------------------------------------------

    public function imprimir_solicitacao($numero)
    {
        $numero = (int) $numero;
        $solicitacao = $this->compras_model->solicitacao($numero);

        if (empty($solicitacao))
        {
            show_404();
        }

        $this->load->view('compras/imprimir_solicitacao', array(
            'registro' => $solicitacao,
            'itens'    => $this->compras_model->itens_solicitacao($numero),
        ));
    }

    // ==================================================================
    // Autocomplete (JSON)
    // ==================================================================

    public function buscar_produtos()
    {
        $termo = trim((string) $this->input->get('termo'));
        $lista = $termo === '' ? array() : $this->compras_model->buscar_produtos($termo, 25);

        $this->_json($lista);
    }

    // ------------------------------------------------------------------

    public function buscar_fornecedores()
    {
        $termo = trim((string) $this->input->get('termo'));
        $lista = $termo === '' ? array() : $this->compras_model->buscar_fornecedores($termo, 25);

        $this->_json($lista);
    }

    // ==================================================================
    // Helpers
    // ==================================================================

    private function _post_pedido()
    {
        return array(
            'numero'                  => 0,
            'data'                    => $this->input->post('data'),
            'hora'                    => $this->input->post('hora'),
            'setor_id'                => $this->input->post('setor_id'),
            'funcionario_id'          => $this->input->post('funcionario_id'),
            'fornecedor_codigo'       => $this->input->post('fornecedor_codigo'),
            'tipo'                    => $this->input->post('tipo'),
            'solicitacao_numero'      => $this->input->post('solicitacao_numero'),
            'observacao'              => $this->input->post('observacao'),
            'local_entrega'           => $this->input->post('local_entrega'),
            'responsavel_recebimento' => $this->input->post('responsavel_recebimento'),
            'fone_responsavel'        => $this->input->post('fone_responsavel'),
            'numero_proposta'         => $this->input->post('numero_proposta'),
            'desconto'                => $this->input->post('desconto'),
            'desconto_percentual'     => $this->input->post('desconto_pedido_percentual'),
            'condicao_pagamento'      => $this->input->post('condicao_pagamento'),
            'prazo_entrega'           => $this->input->post('prazo_entrega'),
            'forma_recebimento'       => $this->input->post('forma_recebimento'),
            'documento_recebimento'   => $this->input->post('documento_recebimento'),
            'centro_custo'            => $this->input->post('centro_custo'),
            'data_vencimento'         => $this->input->post('data_vencimento'),
            'num_nf'                  => $this->input->post('num_nf'),
            'data_nf'                 => $this->input->post('data_nf'),
        );
    }

    // ------------------------------------------------------------------

    private function _post_itens_pedido()
    {
        $ids      = $this->_array_post('produto_id');
        $qtds     = $this->_array_post('quantidade');
        $units    = $this->_array_post('valor_unitario');
        $novos    = $this->_array_post('novo_valor_unitario');
        $percs    = $this->_array_post('desconto_percentual');
        $descs    = $this->_array_post('desconto_item');
        $obs      = $this->_array_post('observacao_item');
        $atualiza = $this->_array_post('atualiza_preco_compra');

        $itens = array();

        foreach ($ids as $i => $produto_id)
        {
            $itens[] = array(
                'produto_id'            => $produto_id,
                'quantidade'            => isset($qtds[$i]) ? $qtds[$i] : NULL,
                'valor_unitario'        => isset($units[$i]) ? $units[$i] : NULL,
                'novo_valor_unitario'   => isset($novos[$i]) ? $novos[$i] : NULL,
                'desconto_percentual'   => isset($percs[$i]) ? $percs[$i] : NULL,
                'desconto_item'         => isset($descs[$i]) ? $descs[$i] : NULL,
                'observacao'            => isset($obs[$i]) ? $obs[$i] : NULL,
                'atualiza_preco_compra' => isset($atualiza[$i]) ? $atualiza[$i] : NULL,
            );
        }

        return $itens;
    }

    // ------------------------------------------------------------------

    private function _post_itens_cotacao()
    {
        $ids  = $this->_array_post('produto_id');
        $qtds = $this->_array_post('quantidade');

        $f1 = $this->_array_post('fornecedor_1');
        $p1 = $this->_array_post('preco_1');
        $f2 = $this->_array_post('fornecedor_2');
        $p2 = $this->_array_post('preco_2');
        $f3 = $this->_array_post('fornecedor_3');
        $p3 = $this->_array_post('preco_3');
        $ef = $this->_array_post('fornecedor_escolhido');

        $itens = array();

        foreach ($ids as $i => $produto_id)
        {
            $itens[] = array(
                'produto_id'           => $produto_id,
                'quantidade'           => isset($qtds[$i]) ? $qtds[$i] : NULL,
                'fornecedor_1'         => isset($f1[$i]) ? $f1[$i] : NULL,
                'preco_1'              => isset($p1[$i]) ? $p1[$i] : NULL,
                'fornecedor_2'         => isset($f2[$i]) ? $f2[$i] : NULL,
                'preco_2'              => isset($p2[$i]) ? $p2[$i] : NULL,
                'fornecedor_3'         => isset($f3[$i]) ? $f3[$i] : NULL,
                'preco_3'              => isset($p3[$i]) ? $p3[$i] : NULL,
                'fornecedor_escolhido' => isset($ef[$i]) ? $ef[$i] : NULL,
            );
        }

        return $itens;
    }

    // ------------------------------------------------------------------

    private function _post_itens_solicitacao()
    {
        $ids    = $this->_array_post('produto_id');
        $qtds   = $this->_array_post('quantidade');
        $repor  = $this->_array_post('repor_estoque');
        $datas  = $this->_array_post('data_utilizacao');
        $period = $this->_array_post('periodo');

        $itens = array();

        foreach ($ids as $i => $produto_id)
        {
            $itens[] = array(
                'produto_id'      => $produto_id,
                'quantidade'      => isset($qtds[$i]) ? $qtds[$i] : NULL,
                'repor_estoque'   => isset($repor[$i]) ? $repor[$i] : NULL,
                'data_utilizacao' => isset($datas[$i]) ? $datas[$i] : NULL,
                'periodo'         => isset($period[$i]) ? $period[$i] : NULL,
            );
        }

        return $itens;
    }

    // ------------------------------------------------------------------

    private function _post_parcelas()
    {
        $dias  = $this->_array_post('parcela_dias');
        $vencs = $this->_array_post('parcela_vencimento');

        $parcelas = array();

        foreach ($dias as $i => $d)
        {
            $parcelas[] = array(
                'dias'       => $d,
                'vencimento' => isset($vencs[$i]) ? $vencs[$i] : NULL,
            );
        }

        return $parcelas;
    }

    // ------------------------------------------------------------------

    private function _array_post($campo)
    {
        $valor = $this->input->post($campo);

        return is_array($valor) ? $valor : array();
    }

    // ------------------------------------------------------------------

    private function _dominios()
    {
        return array(
            'condicao_pagamento' => $this->compras_model->dominios('condicao_pagamento'),
            'forma_recebimento'  => $this->compras_model->dominios('forma_recebimento'),
            'documento'          => $this->compras_model->dominios('documento'),
        );
    }

    // ------------------------------------------------------------------

    private function _json($dados)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($dados));
    }
}
