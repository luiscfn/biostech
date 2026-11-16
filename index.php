<?php
/**
 * BIOSTECH - bootstrap da aplicacao
 *
 * CodeIgniter 2.1.2 (release de 28/01/2012)
 * Ambiente definido pela variavel de ambiente CI_ENV:
 *   development | testing | production
 */

define('ENVIRONMENT', getenv('CI_ENV') ? getenv('CI_ENV') : 'development');

switch (ENVIRONMENT)
{
    case 'development':
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_STRICT);
        ini_set('display_errors', 1);
    break;

    case 'testing':
    case 'production':
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_STRICT);
        ini_set('display_errors', 0);
        if (version_compare(PHP_VERSION, '5.3', '>='))
        {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        }
    break;

    default:
        header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
        echo 'The application environment is not set correctly.';
        exit(1);
}

date_default_timezone_set('America/Fortaleza');

$system_path = 'system';
$application_folder = 'application';

if (($_temp = realpath($system_path)) !== FALSE)
{
    $system_path = $_temp.DIRECTORY_SEPARATOR;
}
else
{
    $system_path = rtrim($system_path, '/\\').DIRECTORY_SEPARATOR;
}

if ( ! is_dir($system_path))
{
    header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
    echo 'Your system folder path does not appear to be set correctly.';
    exit(3);
}

define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
define('EXT', '.php');
define('BASEPATH', str_replace('\\', '/', $system_path));
define('FCPATH', str_replace(SELF, '', __FILE__));
define('SYSDIR', trim(strrchr(trim(BASEPATH, '/'), '/'), '/'));

if (is_dir($application_folder))
{
    if (($_temp = realpath($application_folder)) !== FALSE)
    {
        $application_folder = $_temp;
    }
    define('APPPATH', $application_folder.DIRECTORY_SEPARATOR);
}
else
{
    if ( ! is_dir(BASEPATH.$application_folder.DIRECTORY_SEPARATOR))
    {
        header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
        echo 'Your application folder path does not appear to be set correctly.';
        exit(3);
    }
    define('APPPATH', BASEPATH.$application_folder.DIRECTORY_SEPARATOR);
}

define('VIEWPATH', APPPATH.'views'.DIRECTORY_SEPARATOR);

require_once BASEPATH.'core/CodeIgniter.php';
