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
