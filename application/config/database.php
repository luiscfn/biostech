<?php
/**
 * BIOSTECH - banco de dados (PostgreSQL)
 *
 * Todas as tabelas da aplicacao ficam no schema "biostech".
 * Os dados brutos do Access ficam no schema "biostech_legacy" (staging 1:1).
 *
 * Credenciais podem ser sobrescritas por variaveis de ambiente:
 *   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
 */

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
    'dsn'      => '',
    'hostname' => getenv('DB_HOST') ? getenv('DB_HOST') : 'sedsrvpostdesv.seduc.ce.gov.br',
    'username' => getenv('DB_USER') ? getenv('DB_USER') : 'matgrafico',
    'password' => getenv('DB_PASS') ? getenv('DB_PASS') : 'matgrafico',
    'database' => getenv('DB_NAME') ? getenv('DB_NAME') : 'desenvolvimento',
    'dbdriver' => 'postgre',
    'dbprefix' => '',
    'port'     => getenv('DB_PORT') ? getenv('DB_PORT') : '5432',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'autoinit' => TRUE,
    'encrypt'  => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
);
