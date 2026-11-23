<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| ROUTES
| -------------------------------------------------------------------------
*/

$route['default_controller'] = 'dashboard';
$route['404_override'] = '';

// Autenticacao
$route['login']          = 'login/index';
$route['login/entrar']   = 'login/entrar';
$route['logout']         = 'login/sair';

// Cadastros
$route['setores']              = 'setores/index';
$route['setores/novo']         = 'setores/novo';
$route['setores/editar/(:num)'] = 'setores/editar/$1';
$route['setores/excluir/(:num)'] = 'setores/excluir/$1';

$route['usuarios']              = 'usuarios/index';
$route['usuarios/novo']         = 'usuarios/novo';
$route['usuarios/editar/(:num)'] = 'usuarios/editar/$1';
$route['usuarios/senha/(:num)']  = 'usuarios/senha/$1';

$route['clientes']               = 'clientes/index';
$route['clientes/novo']          = 'clientes/novo';
$route['clientes/editar/(:num)'] = 'clientes/editar/$1';
$route['clientes/ver/(:num)']    = 'clientes/ver/$1';
$route['clientes/buscar_clientes'] = 'clientes/buscar_clientes';
$route['clientes/ordens/(:num)'] = 'clientes/ordens/$1';
$route['clientes/orcamentos/(:num)'] = 'clientes/orcamentos/$1';
$route['clientes/notas/(:num)']  = 'clientes/notas/$1';
$route['clientes/nota/(:num)']   = 'clientes/nota/$1';
$route['clientes/especiais/(:num)']            = 'clientes/especiais/$1';
$route['clientes/especial_alternar/(:num)']    = 'clientes/especial_alternar/$1';
$route['clientes/especial_salvar/(:num)']      = 'clientes/especial_salvar/$1';
$route['clientes/especial_excluir/(:num)/(:num)'] = 'clientes/especial_excluir/$1/$2';

$route['fornecedores']                    = 'fornecedores/index';
$route['fornecedores/novo']               = 'fornecedores/novo';
$route['fornecedores/editar/(:num)']      = 'fornecedores/editar/$1';
$route['fornecedores/excluir/(:num)']     = 'fornecedores/excluir/$1';
$route['fornecedores/copiar_cliente/(:num)'] = 'fornecedores/copiar_cliente/$1';
$route['fornecedores/prazo_salvar']       = 'fornecedores/prazo_salvar';
$route['fornecedores/prazo_excluir']      = 'fornecedores/prazo_excluir';
$route['fornecedores/produto_vincular']   = 'fornecedores/produto_vincular';
$route['fornecedores/produto_desvincular'] = 'fornecedores/produto_desvincular';

$route['produtos']               = 'produtos/index';
$route['produtos/ver/(:num)']    = 'produtos/ver/$1';

// Chamados / Ordens de Servico
$route['chamados']                            = 'chamados/index';
$route['chamados/novo']                       = 'chamados/novo';
$route['chamados/buscar_clientes']            = 'chamados/buscar_clientes';
$route['chamados/buscar_produtos']            = 'chamados/buscar_produtos';
$route['chamados/ver/(:any)']                 = 'chamados/ver/$1';
$route['chamados/editar/(:any)']              = 'chamados/editar/$1';
$route['chamados/status/(:any)']              = 'chamados/status/$1';
$route['chamados/encerrar/(:any)']            = 'chamados/encerrar/$1';
$route['chamados/reabrir/(:any)']             = 'chamados/reabrir/$1';
$route['chamados/cancelar_encerramento/(:any)'] = 'chamados/cancelar_encerramento/$1';
$route['chamados/gerar/(:any)']               = 'chamados/gerar/$1';
$route['chamados/equipamento/(:any)']         = 'chamados/equipamento/$1';
$route['chamados/atendimento/(:any)']         = 'chamados/atendimento/$1';
$route['chamados/intervencoes/(:any)']        = 'chamados/intervencoes/$1';
$route['chamados/pecas/(:any)']               = 'chamados/pecas/$1';
$route['chamados/excluir_peca/(:any)/(:num)'] = 'chamados/excluir_peca/$1/$2';
$route['chamados/baixar_estoque/(:any)']      = 'chamados/baixar_estoque/$1';
$route['chamados/servicos/(:any)']            = 'chamados/servicos/$1';
$route['chamados/concluir_servico/(:any)/(:num)'] = 'chamados/concluir_servico/$1/$2';
$route['chamados/contatos/(:any)']            = 'chamados/contatos/$1';
$route['chamados/financeiro/(:any)']          = 'chamados/financeiro/$1';
$route['chamados/imprimir/(:any)']            = 'chamados/imprimir/$1';

// Estoque
$route['estoque']                              = 'estoque/index';
$route['estoque/movimentos']                   = 'estoque/movimentos';
$route['estoque/entrada']                      = 'estoque/entrada';
$route['estoque/saida']                        = 'estoque/saida';
$route['estoque/ajuste']                       = 'estoque/ajuste';
$route['estoque/ver/(:num)']                   = 'estoque/ver/$1';
$route['estoque/estornar/(:num)']              = 'estoque/estornar/$1';
$route['estoque/catalogos']                    = 'estoque/catalogos';
$route['estoque/catalogo_salvar']              = 'estoque/catalogo_salvar';
$route['estoque/catalogo_excluir']             = 'estoque/catalogo_excluir';
$route['estoque/fornecedores']                 = 'estoque/fornecedores';
$route['estoque/fornecedor']                   = 'estoque/fornecedor';
$route['estoque/fornecedor/(:num)']            = 'estoque/fornecedor/$1';
$route['estoque/fornecedor_salvar']            = 'estoque/fornecedor_salvar';
$route['estoque/fornecedor_vincular']          = 'estoque/fornecedor_vincular';
$route['estoque/fornecedor_desvincular']       = 'estoque/fornecedor_desvincular';
$route['estoque/buscar_produtos']              = 'estoque/buscar_produtos';
$route['estoque/buscar_fornecedores']          = 'estoque/buscar_fornecedores';

// Compras
$route['compras']                              = 'compras/index';
$route['compras/novo']                         = 'compras/novo';
$route['compras/editar/(:num)']                = 'compras/editar/$1';
$route['compras/ver/(:num)']                   = 'compras/ver/$1';
$route['compras/excluir/(:num)']               = 'compras/excluir/$1';
$route['compras/cancelar/(:num)']              = 'compras/cancelar/$1';
$route['compras/reabrir/(:num)']               = 'compras/reabrir/$1';
$route['compras/replicar/(:num)']              = 'compras/replicar/$1';
$route['compras/excluir_item/(:num)/(:num)']   = 'compras/excluir_item/$1/$2';
$route['compras/transferir_item/(:num)']       = 'compras/transferir_item/$1';
$route['compras/fechar/(:num)']                = 'compras/fechar/$1';
$route['compras/cancelar_fechamento/(:num)']   = 'compras/cancelar_fechamento/$1';
$route['compras/cotacoes']                     = 'compras/cotacoes';
$route['compras/cotacao']                      = 'compras/cotacao';
$route['compras/cotacao/(:num)']               = 'compras/cotacao/$1';
$route['compras/cotacao_excluir/(:num)']       = 'compras/cotacao_excluir/$1';
$route['compras/importar_solicitacao/(:num)']  = 'compras/importar_solicitacao/$1';
$route['compras/solicitacoes']                 = 'compras/solicitacoes';
$route['compras/solicitacao']                  = 'compras/solicitacao';
$route['compras/solicitacao/(:num)']           = 'compras/solicitacao/$1';
$route['compras/solicitacao_excluir/(:num)']   = 'compras/solicitacao_excluir/$1';
$route['compras/solicitacao_fechar/(:num)']    = 'compras/solicitacao_fechar/$1';
$route['compras/solicitacao_gerar_pedido/(:num)'] = 'compras/solicitacao_gerar_pedido/$1';
$route['compras/prazos']                       = 'compras/prazos';
$route['compras/prazos/(:num)']                = 'compras/prazos/$1';
$route['compras/prazo_salvar']                 = 'compras/prazo_salvar';
$route['compras/prazo_excluir/(:num)/(:num)']  = 'compras/prazo_excluir/$1/$2';
$route['compras/imprimir_pedido/(:num)']       = 'compras/imprimir_pedido/$1';
$route['compras/imprimir_cotacao/(:num)']      = 'compras/imprimir_cotacao/$1';
$route['compras/imprimir_solicitacao/(:num)']  = 'compras/imprimir_solicitacao/$1';
$route['compras/buscar_produtos']              = 'compras/buscar_produtos';
$route['compras/buscar_fornecedores']          = 'compras/buscar_fornecedores';

// Orcamentos
$route['orcamentos']                                   = 'orcamentos/index';
$route['orcamentos/novo']                              = 'orcamentos/novo';
$route['orcamentos/editar/(:num)']                     = 'orcamentos/editar/$1';
$route['orcamentos/ver/(:num)']                        = 'orcamentos/ver/$1';
$route['orcamentos/excluir/(:num)']                    = 'orcamentos/excluir/$1';
$route['orcamentos/status/(:num)']                     = 'orcamentos/status/$1';
$route['orcamentos/gerar_chamado/(:num)']              = 'orcamentos/gerar_chamado/$1';
$route['orcamentos/imprimir/(:num)']                   = 'orcamentos/imprimir/$1';
$route['orcamentos/adicionar_item/(:num)']             = 'orcamentos/adicionar_item/$1';
$route['orcamentos/atualizar_item/(:num)/(:num)']      = 'orcamentos/atualizar_item/$1/$2';
$route['orcamentos/excluir_item/(:num)/(:num)']        = 'orcamentos/excluir_item/$1/$2';
$route['orcamentos/aplicar_percentual/(:num)']         = 'orcamentos/aplicar_percentual/$1';
$route['orcamentos/restaurar_valores/(:num)']          = 'orcamentos/restaurar_valores/$1';
$route['orcamentos/restaurar_percentual/(:num)/(:any)'] = 'orcamentos/restaurar_percentual/$1/$2';
$route['orcamentos/salvar_financeiro/(:num)']           = 'orcamentos/salvar_financeiro/$1';
$route['orcamentos/adicionar_servico/(:num)']          = 'orcamentos/adicionar_servico/$1';
$route['orcamentos/atualizar_servico/(:num)/(:num)']   = 'orcamentos/atualizar_servico/$1/$2';
$route['orcamentos/excluir_servico/(:num)/(:num)']     = 'orcamentos/excluir_servico/$1/$2';
$route['orcamentos/adicionar_servico_valor/(:num)']    = 'orcamentos/adicionar_servico_valor/$1';
$route['orcamentos/atualizar_servico_valor/(:num)/(:num)'] = 'orcamentos/atualizar_servico_valor/$1/$2';
$route['orcamentos/excluir_servico_valor/(:num)/(:num)']   = 'orcamentos/excluir_servico_valor/$1/$2';
$route['orcamentos/adicionar_contato/(:num)']          = 'orcamentos/adicionar_contato/$1';
$route['orcamentos/excluir_contato/(:num)/(:num)']     = 'orcamentos/excluir_contato/$1/$2';
$route['orcamentos/buscar_clientes']                   = 'orcamentos/buscar_clientes';
$route['orcamentos/buscar_produtos']                   = 'orcamentos/buscar_produtos';

// Orcamentos - dimensionamento
$route['orcamentos/dimensionamentos']                  = 'orcamentos/dimensionamentos';
$route['orcamentos/dimensionamento_novo']              = 'orcamentos/dimensionamento_novo';
$route['orcamentos/dimensionamento_editar/(:num)']     = 'orcamentos/dimensionamento_editar/$1';
$route['orcamentos/dimensionamento/(:num)']            = 'orcamentos/dimensionamento/$1';
$route['orcamentos/dimensionamento_excluir/(:num)']    = 'orcamentos/dimensionamento_excluir/$1';
$route['orcamentos/dimensionamento_gerar_orcamento/(:num)'] = 'orcamentos/dimensionamento_gerar_orcamento/$1';
$route['orcamentos/dimensionamento_imprimir/(:num)']   = 'orcamentos/dimensionamento_imprimir/$1';
$route['orcamentos/dim_adicionar_item/(:num)']         = 'orcamentos/dim_adicionar_item/$1';
$route['orcamentos/dim_adicionar_item_classificacao/(:num)'] = 'orcamentos/dim_adicionar_item_classificacao/$1';
$route['orcamentos/dim_atualizar_item/(:num)/(:num)']  = 'orcamentos/dim_atualizar_item/$1/$2';
$route['orcamentos/dim_excluir_item/(:num)/(:num)']    = 'orcamentos/dim_excluir_item/$1/$2';
$route['orcamentos/dim_adicionar_material/(:num)']     = 'orcamentos/dim_adicionar_material/$1';
$route['orcamentos/dim_atualizar_material/(:num)/(:num)'] = 'orcamentos/dim_atualizar_material/$1/$2';
$route['orcamentos/dim_excluir_material/(:num)/(:num)'] = 'orcamentos/dim_excluir_material/$1/$2';
$route['orcamentos/dim_adicionar_servico/(:num)']      = 'orcamentos/dim_adicionar_servico/$1';
$route['orcamentos/dim_atualizar_servico/(:num)/(:num)'] = 'orcamentos/dim_atualizar_servico/$1/$2';
$route['orcamentos/dim_excluir_servico/(:num)/(:num)'] = 'orcamentos/dim_excluir_servico/$1/$2';

// Financeiro
$route['financeiro'] = 'financeiro/index';

$route['financeiro/contas_pagar']                     = 'financeiro/contas_pagar';
$route['financeiro/conta_pagar_novo']                 = 'financeiro/conta_pagar_novo';
$route['financeiro/conta_pagar_editar/(:num)']        = 'financeiro/conta_pagar_editar/$1';
$route['financeiro/conta_pagar_ver/(:num)']           = 'financeiro/conta_pagar_ver/$1';
$route['financeiro/conta_pagar_baixar/(:num)']        = 'financeiro/conta_pagar_baixar/$1';
$route['financeiro/conta_pagar_estornar/(:num)']      = 'financeiro/conta_pagar_estornar/$1';
$route['financeiro/conta_pagar_cancelar/(:num)']      = 'financeiro/conta_pagar_cancelar/$1';
$route['financeiro/conta_pagar_excluir/(:num)']       = 'financeiro/conta_pagar_excluir/$1';
$route['financeiro/conta_pagar_comprovante/(:num)']   = 'financeiro/conta_pagar_comprovante/$1';

$route['financeiro/contas_receber']                   = 'financeiro/contas_receber';
$route['financeiro/conta_receber_novo']               = 'financeiro/conta_receber_novo';
$route['financeiro/conta_receber_editar/(:num)']      = 'financeiro/conta_receber_editar/$1';
$route['financeiro/conta_receber_ver/(:num)']         = 'financeiro/conta_receber_ver/$1';
$route['financeiro/conta_receber_baixar/(:num)']      = 'financeiro/conta_receber_baixar/$1';
$route['financeiro/conta_receber_estornar/(:num)']    = 'financeiro/conta_receber_estornar/$1';
$route['financeiro/conta_receber_cancelar/(:num)']    = 'financeiro/conta_receber_cancelar/$1';
$route['financeiro/conta_receber_excluir/(:num)']     = 'financeiro/conta_receber_excluir/$1';
$route['financeiro/conta_receber_comprovante/(:num)'] = 'financeiro/conta_receber_comprovante/$1';

$route['financeiro/movimentos']                       = 'financeiro/movimentos';
$route['financeiro/movimento_novo']                   = 'financeiro/movimento_novo';
$route['financeiro/movimento_editar/(:num)']          = 'financeiro/movimento_editar/$1';
$route['financeiro/movimento_excluir/(:num)']         = 'financeiro/movimento_excluir/$1';

$route['financeiro/caixa']                            = 'financeiro/caixa';
$route['financeiro/caixa_dia/(:any)']                 = 'financeiro/caixa_dia/$1';
$route['financeiro/caixa_movimento_salvar']           = 'financeiro/caixa_movimento_salvar';
$route['financeiro/caixa_movimento_excluir/(:num)']   = 'financeiro/caixa_movimento_excluir/$1';
$route['financeiro/caixa_fechar/(:any)']              = 'financeiro/caixa_fechar/$1';
$route['financeiro/caixa_reabrir/(:any)']             = 'financeiro/caixa_reabrir/$1';

$route['financeiro/cheques']                          = 'financeiro/cheques';
$route['financeiro/cheque_novo']                      = 'financeiro/cheque_novo';
$route['financeiro/cheque_editar/(:num)']             = 'financeiro/cheque_editar/$1';
$route['financeiro/cheque_excluir/(:num)']            = 'financeiro/cheque_excluir/$1';
$route['financeiro/cheque_compensar/(:num)']          = 'financeiro/cheque_compensar/$1';

$route['financeiro/balanco']                          = 'financeiro/balanco';
$route['financeiro/balanco_gerar']                    = 'financeiro/balanco_gerar';
$route['financeiro/balanco_excluir/(:num)']           = 'financeiro/balanco_excluir/$1';

$route['financeiro/cadastros']                        = 'financeiro/cadastros';
$route['financeiro/cadastro/(:any)']                  = 'financeiro/cadastro/$1';
$route['financeiro/cadastro/(:any)/(:any)']           = 'financeiro/cadastro/$1/$2';
$route['financeiro/cadastro_excluir/(:any)/(:any)']   = 'financeiro/cadastro_excluir/$1/$2';

$route['financeiro/relatorios']                       = 'financeiro/relatorios';
$route['financeiro/buscar_fornecedores']              = 'financeiro/buscar_fornecedores';
$route['financeiro/buscar_clientes']                  = 'financeiro/buscar_clientes';

// Tabelas Basicas
$route['tabelas_basicas']                                  = 'tabelas_basicas/index';
$route['tabelas_basicas/categoria/(:any)']                 = 'tabelas_basicas/categoria/$1';
$route['tabelas_basicas/salvar/(:any)']                    = 'tabelas_basicas/salvar/$1';
$route['tabelas_basicas/excluir/(:any)/(:any)']            = 'tabelas_basicas/excluir/$1/$2';

$route['tabelas_basicas/parametros']                       = 'tabelas_basicas/parametros';
$route['tabelas_basicas/parametros_salvar']                = 'tabelas_basicas/parametros_salvar';

$route['tabelas_basicas/permissoes']                       = 'tabelas_basicas/permissoes';
$route['tabelas_basicas/modulo_salvar']                    = 'tabelas_basicas/modulo_salvar';
$route['tabelas_basicas/modulo_excluir/(:num)']            = 'tabelas_basicas/modulo_excluir/$1';

$route['tabelas_basicas/programas/(:num)']                 = 'tabelas_basicas/programas/$1';
$route['tabelas_basicas/programa_salvar/(:num)']           = 'tabelas_basicas/programa_salvar/$1';
$route['tabelas_basicas/programa_excluir/(:num)/(:num)']   = 'tabelas_basicas/programa_excluir/$1/$2';
