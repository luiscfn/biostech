<?php
/**
 * Router para o servidor embutido do PHP:
 *   php -S 127.0.0.1:8090 router.php
 */

$uri     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$arquivo = __DIR__ . $uri;

// Serve arquivos estaticos existentes (css, js, imagens) diretamente
if ($uri !== '/' && file_exists($arquivo) && !is_dir($arquivo))
{
    return false;
}

// Arquivo estatico inexistente: 404, sem cair no index.php
if (preg_match('/\.(?:css|js|mjs|png|jpe?g|gif|svg|ico|webp|woff2?|ttf|eot|map|pdf)$/i', $uri))
{
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Arquivo nao encontrado: ' . $uri;
    return true;
}

require __DIR__ . '/index.php';
