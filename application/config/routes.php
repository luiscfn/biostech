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
