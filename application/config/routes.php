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
