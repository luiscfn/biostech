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
// Modulo Estoque
define('T_ESTOQUE_SALDOS',        DB_SCHEMA . '.biostech_estoque_saldos');
define('T_ESTOQUE_MOVIMENTOS',    DB_SCHEMA . '.biostech_estoque_movimentos');
define('T_ESTOQUE_GRUPOS',        DB_SCHEMA . '.biostech_estoque_grupos');
define('T_ESTOQUE_SUBGRUPOS',     DB_SCHEMA . '.biostech_estoque_subgrupos');
define('T_ESTOQUE_POSICOES',      DB_SCHEMA . '.biostech_estoque_posicoes');
define('T_ESTOQUE_ESTANTES',      DB_SCHEMA . '.biostech_estoque_estantes');
define('T_ESTOQUE_PRATELEIRAS',   DB_SCHEMA . '.biostech_estoque_prateleiras');
define('T_ESTOQUE_FINALIDADES',   DB_SCHEMA . '.biostech_estoque_finalidades');
define('T_ESTOQUE_INVENTARIOS',   DB_SCHEMA . '.biostech_estoque_inventarios');
define('T_ESTOQUE_INVENTARIO_ITENS', DB_SCHEMA . '.biostech_estoque_inventario_itens');
define('T_FORNECEDORES',          DB_SCHEMA . '.biostech_fornecedores');
define('T_PRODUTO_FORNECEDOR',    DB_SCHEMA . '.biostech_produto_fornecedor');
define('T_ORCAMENTOS',            DB_SCHEMA . '.biostech_orcamentos');
define('T_ORCAMENTO_ITENS',       DB_SCHEMA . '.biostech_orcamento_itens');
define('T_ORCAMENTO_SERVICOS',    DB_SCHEMA . '.biostech_orcamento_servicos');
define('T_ORCAMENTO_SERVICOS_VALORES', DB_SCHEMA . '.biostech_orcamento_servicos_valores');
define('T_ORCAMENTO_CONTATOS',    DB_SCHEMA . '.biostech_orcamento_contatos');
define('T_ORCAMENTO_LOG',         DB_SCHEMA . '.biostech_orcamento_log');
define('T_ORCAMENTOS_DIMENSIONAMENTO', DB_SCHEMA . '.biostech_orcamentos_dimensionamento');
define('T_ORCAMENTO_DIMENSIONAMENTO_ITENS', DB_SCHEMA . '.biostech_orcamento_dimensionamento_itens');
define('T_ORCAMENTO_DIMENSIONAMENTO_SERVICOS', DB_SCHEMA . '.biostech_orcamento_dimensionamento_servicos');
define('T_ORCAMENTO_DIMENSIONAMENTO_MATERIAIS', DB_SCHEMA . '.biostech_orcamento_dimensionamento_materiais');
define('T_ORCAMENTO_DIMENSIONAMENTO_COTACOES', DB_SCHEMA . '.biostech_orcamento_dimensionamento_cotacoes');
define('T_ORCAMENTO_DIMENSIONAMENTO_LOG', DB_SCHEMA . '.biostech_orcamento_dimensionamento_log');
define('T_CONTAS_RECEBER',        DB_SCHEMA . '.biostech_contas_receber');
define('T_CONTAS_RECEBER_BAIXAS', DB_SCHEMA . '.biostech_contas_receber_baixas');

// Modulo Compras
define('T_COMPRAS_PEDIDOS',           DB_SCHEMA . '.biostech_compras_pedidos');
define('T_COMPRAS_ITENS',             DB_SCHEMA . '.biostech_compras_itens');
define('T_COMPRAS_PARCELAS',          DB_SCHEMA . '.biostech_compras_parcelas');
define('T_COMPRAS_COTACOES',          DB_SCHEMA . '.biostech_compras_cotacoes');
define('T_COMPRAS_COTACAO_ITENS',     DB_SCHEMA . '.biostech_compras_cotacao_itens');
define('T_COMPRAS_SOLICITACOES',      DB_SCHEMA . '.biostech_compras_solicitacoes');
define('T_COMPRAS_SOLICITACAO_ITENS', DB_SCHEMA . '.biostech_compras_solicitacao_itens');
define('T_FORNECEDOR_PRAZOS',         DB_SCHEMA . '.biostech_fornecedor_prazos');
define('T_CONTAS_PAGAR',              DB_SCHEMA . '.biostech_contas_pagar');
define('T_COMPRAS_LOG',               DB_SCHEMA . '.biostech_compras_log');

// Modulo Financeiro
define('T_FIN_BANCOS',             DB_SCHEMA . '.biostech_fin_bancos');
define('T_FIN_AGENCIAS',           DB_SCHEMA . '.biostech_fin_agencias');
define('T_FIN_CONTAS',             DB_SCHEMA . '.biostech_fin_contas');
define('T_FIN_CARTOES',            DB_SCHEMA . '.biostech_fin_cartoes');
define('T_FIN_CENTROS_CUSTO',      DB_SCHEMA . '.biostech_fin_centros_custo');
define('T_FIN_NATUREZAS',          DB_SCHEMA . '.biostech_fin_naturezas_operacao');
define('T_FIN_STATUS_PAGAMENTO',   DB_SCHEMA . '.biostech_fin_status_pagamento');
define('T_FIN_PERIODICIDADES',     DB_SCHEMA . '.biostech_fin_periodicidades');
define('T_FIN_DESPESAS_FIXAS',     DB_SCHEMA . '.biostech_fin_despesas_fixas');
define('T_FIN_CHEQUES',            DB_SCHEMA . '.biostech_fin_cheques');
define('T_FIN_MOVIMENTOS',         DB_SCHEMA . '.biostech_fin_movimentos_bancarios');
define('T_FIN_CAIXA_DIARIO',       DB_SCHEMA . '.biostech_fin_caixa_diario');
define('T_FIN_CAIXA_MOVIMENTOS',   DB_SCHEMA . '.biostech_fin_caixa_movimentos');
define('T_FIN_BALANCO',            DB_SCHEMA . '.biostech_fin_balanco');
define('T_CONTAS_PAGAR_BAIXAS',    DB_SCHEMA . '.biostech_contas_pagar_baixas');
define('T_CONTAS_PAGAR_LOG',       DB_SCHEMA . '.biostech_contas_pagar_log');

// Nome do sistema
define('APP_NOME', 'BIOSTECH');
define('APP_VERSAO', '1.0.0');

// Perfis
define('PERFIL_ADMIN', 'admin');
define('PERFIL_GESTOR', 'gestor');
define('PERFIL_OPERADOR', 'operador');
