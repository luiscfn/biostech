<?php
/**
 * BIOSTECH - configuracao da aplicacao (CodeIgniter 2.1.2)
 */

/* ---------------------------------------------------------------- URL ---- */
$config['base_url'] = (empty($_SERVER['HTTP_X_FORWARDED_PROTO']) ? 'http' : $_SERVER['HTTP_X_FORWARDED_PROTO'])
    . '://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost') . '/';

$config['index_page'] = '';                 // URLs limpas (usa .htaccess / router.php)
$config['uri_protocol'] = 'REQUEST_URI';
$config['url_suffix'] = '';

/* ------------------------------------------------------------ SISTEMA ---- */
$config['language'] = 'english';
$config['charset'] = 'UTF-8';
$config['enable_hooks'] = FALSE;
$config['subclass_prefix'] = 'MY_';
$config['permitted_uri_chars'] = 'a-z 0-9~%.:_\-';
$config['allow_get_array'] = TRUE;
$config['enable_query_strings'] = FALSE;
$config['controller_trigger'] = 'c';
$config['function_trigger'] = 'm';
$config['directory_trigger'] = 'd';

/* --------------------------------------------------------------- LOG ----- */
$config['log_threshold'] = (ENVIRONMENT === 'development') ? 1 : 0;
$config['log_path'] = '';
$config['log_date_format'] = 'Y-m-d H:i:s';
$config['cache_path'] = '';

/* ----------------------------------------------------------- SESSAO ------ */
// Sessoes usam a biblioteca Auth (session nativo do PHP) - ver libraries/Auth.php
$config['encryption_key'] = 'biostech-2026-conversao-legado-32b';

$config['sess_cookie_name'] = 'biostech_session';
$config['sess_expiration'] = 7200;
$config['sess_expire_on_close'] = FALSE;
$config['sess_encrypt_cookie'] = FALSE;
$config['sess_use_database'] = FALSE;
$config['sess_table_name'] = 'ci_sessions';
$config['sess_match_ip'] = FALSE;
$config['sess_match_useragent'] = TRUE;
$config['sess_time_to_update'] = 300;

/* ------------------------------------------------------------ COOKIES ---- */
$config['cookie_prefix'] = '';
$config['cookie_domain'] = '';
$config['cookie_path'] = '/';
$config['cookie_secure'] = FALSE;

/* ---------------------------------------------------------- SEGURANCA ---- */
$config['global_xss_filtering'] = FALSE;    // saida sempre escapada nas views
$config['csrf_protection'] = TRUE;
$config['csrf_token_name'] = 'biostech_csrf';
$config['csrf_cookie_name'] = 'biostech_csrf';
$config['csrf_expire'] = 7200;

/* -------------------------------------------------------------- OUTROS --- */
$config['compress_output'] = FALSE;
$config['time_reference'] = 'local';
$config['rewrite_short_tags'] = FALSE;
$config['proxy_ips'] = '';
