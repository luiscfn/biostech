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

// Nome do sistema
define('APP_NOME', 'BIOSTECH');
define('APP_VERSAO', '1.0.0');

// Perfis
define('PERFIL_ADMIN', 'admin');
define('PERFIL_GESTOR', 'gestor');
define('PERFIL_OPERADOR', 'operador');
