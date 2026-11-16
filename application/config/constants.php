<?php
/**
 * BIOSTECH - configuracao de constantes
 */

// Schema onde ficam as tabelas da aplicacao.
// Em producao, o DBA pode provisionar um schema dedicado e definir DB_SCHEMA=biostech.
define('DB_SCHEMA', getenv('DB_SCHEMA') ? getenv('DB_SCHEMA') : 'material_grafico');

// Nomes qualificados das tabelas
define('T_SETORES',   DB_SCHEMA . '.biostech_setores');
define('T_USUARIOS',  DB_SCHEMA . '.biostech_usuarios');
define('T_CLIENTES',  DB_SCHEMA . '.biostech_clientes');
define('T_PRODUTOS',  DB_SCHEMA . '.biostech_produtos');
define('T_AUDITORIA', DB_SCHEMA . '.biostech_auditoria');

// Modulo Chamados / OS
define('T_DOMINIOS',              DB_SCHEMA . '.biostech_dominios');
define('T_NUMERACAO',             DB_SCHEMA . '.biostech_numeracao');
define('T_EQUIPAMENTOS',          DB_SCHEMA . '.biostech_equipamentos');
define('T_CHAMADOS',              DB_SCHEMA . '.biostech_chamados');
define('T_CHAMADO_EQUIPAMENTO',   DB_SCHEMA . '.biostech_chamado_equipamento');
define('T_CHAMADO_ATENDIMENTO',   DB_SCHEMA . '.biostech_chamado_atendimento');
define('T_CHAMADO_INTERVENCOES',  DB_SCHEMA . '.biostech_chamado_intervencoes');
define('T_CHAMADO_PECAS',         DB_SCHEMA . '.biostech_chamado_pecas');
define('T_CHAMADO_SERVICOS',      DB_SCHEMA . '.biostech_chamado_servicos');
define('T_CHAMADO_CONTATOS',      DB_SCHEMA . '.biostech_chamado_contatos');
define('T_CHAMADO_HORAS',         DB_SCHEMA . '.biostech_chamado_horas');
define('T_CHAMADO_VINCULOS',      DB_SCHEMA . '.biostech_chamado_vinculos');
define('T_CHAMADO_EVENTOS',       DB_SCHEMA . '.biostech_chamado_eventos');
define('T_CHAMADO_LOG',           DB_SCHEMA . '.biostech_chamado_log');
define('T_CHAMADO_PLANOS',        DB_SCHEMA . '.biostech_chamado_planos_pagamento');
define('T_CHAMADO_PARCELAS',      DB_SCHEMA . '.biostech_chamado_parcelas');
define('T_ESTOQUE_SALDOS',        DB_SCHEMA . '.biostech_estoque_saldos');
define('T_ESTOQUE_MOVIMENTOS',    DB_SCHEMA . '.biostech_estoque_movimentos');
define('T_ORCAMENTOS',            DB_SCHEMA . '.biostech_orcamentos');
define('T_ORCAMENTO_ITENS',       DB_SCHEMA . '.biostech_orcamento_itens');
define('T_ORCAMENTO_SERVICOS',    DB_SCHEMA . '.biostech_orcamento_servicos');
define('T_CONTAS_RECEBER',        DB_SCHEMA . '.biostech_contas_receber');
define('T_CONTAS_RECEBER_BAIXAS', DB_SCHEMA . '.biostech_contas_receber_baixas');

// Nome do sistema
define('APP_NOME', 'BIOSTECH');
define('APP_VERSAO', '1.0.0');

// Perfis
define('PERFIL_ADMIN', 'admin');
define('PERFIL_GESTOR', 'gestor');
define('PERFIL_OPERADOR', 'operador');
