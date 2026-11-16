<?php
/**
 * Router para o servidor embutido do PHP:
 *   php -S 127.0.0.1:8080 router.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$arquivo = __DIR__ . $uri;

if ($uri !== '/' && file_exists($arquivo) && !is_dir($arquivo)) {
    return false; // serve o arquivo estatico normalmente
}

require __DIR__ . '/index.php';
