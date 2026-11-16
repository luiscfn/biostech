<?php
/**
 * BIOSTECH - diagnostico do ambiente
 *
 * Uso:
 *   php scripts/diag.php
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$ok = 0;
$erros = 0;

function item($rotulo, $condicao, $detalhe = '')
{
    global $ok, $erros;

    if ($condicao)
    {
        $ok++;
        printf("[ok]   %-38s %s\n", $rotulo, $detalhe);
        return;
    }

    $erros++;
    printf("[erro] %-38s %s\n", $rotulo, $detalhe);
}

echo "=== BIOSTECH - diagnostico ===\n\n";

/* PHP --------------------------------------------------------------------- */
item('versao do PHP >= 7.0', version_compare(PHP_VERSION, '7.0', '>='), PHP_VERSION);

$ini = php_ini_loaded_file();
item('php.ini carregado', $ini !== FALSE, $ini ? $ini : 'nenhum');

foreach (array('pgsql', 'pdo_pgsql', 'mbstring', 'json', 'curl', 'openssl') as $ext)
{
    item("extensao $ext", extension_loaded($ext), extension_loaded($ext) ? 'carregada' : 'FALTA habilitar no php.ini');
}

$base = dirname(__DIR__);
item('pasta system/ do CodeIgniter', is_dir($base . '/system'), $base . '/system');
item('pasta application/', is_dir($base . '/application'), $base . '/application');

$cache = $base . '/application/cache/sessions';
if ( ! is_dir($cache))
{
    @mkdir($cache, 0777, TRUE);
}
item('cache de sessoes gravavel', is_dir($cache) && is_writable($cache), $cache);

/* Banco ------------------------------------------------------------------- */
$host   = getenv('DB_HOST') ?: 'sedsrvpostdesv.seduc.ce.gov.br';
$port   = getenv('DB_PORT') ?: '5432';
$name   = getenv('DB_NAME') ?: 'desenvolvimento';
$user   = getenv('DB_USER') ?: 'matgrafico';
$pass   = getenv('DB_PASS') ?: 'matgrafico';
$schema = getenv('DB_SCHEMA') ?: 'material_grafico';

$pdo = NULL;
try
{
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$name", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    item('conexao PostgreSQL', TRUE, "$host:$port/$name");
}
catch (Exception $e)
{
    item('conexao PostgreSQL', FALSE, $e->getMessage());
}

if ($pdo)
{
    item('schema configurado', TRUE, $schema . ' (DB_SCHEMA)');

    $tabelas = array('setores', 'usuarios', 'clientes', 'produtos', 'auditoria');

    foreach ($tabelas as $t)
    {
        try
        {
            $n = $pdo->query("SELECT count(*) FROM $schema.biostech_$t")->fetchColumn();
            item("tabela biostech_$t", TRUE, "$n registro(s)");
        }
        catch (Exception $e)
        {
            item("tabela biostech_$t", FALSE, 'nao existe - rode scripts/etl/instalar.php');
        }
    }

    try
    {
        $n = $pdo->query("SELECT count(*) FROM $schema.biostech_usuarios WHERE ativo AND senha_hash LIKE '\$2y\$%'")->fetchColumn();
        item('senhas em bcrypt', $n > 0, "$n usuario(s)");
    }
    catch (Exception $e)
    {
        item('senhas em bcrypt', FALSE, $e->getMessage());
    }
}

/* Servidor ---------------------------------------------------------------- */
$conexao = @fsockopen('127.0.0.1', 8090, $errno, $errstr, 1);
item('servidor de desenvolvimento na porta 8090', (bool) $conexao, $conexao ? 'ativo - http://127.0.0.1:8090' : 'parado - use iniciar.bat ou a task do VS Code');
if ($conexao)
{
    fclose($conexao);
}

/* Resumo ------------------------------------------------------------------ */
echo "\n== resumo ==\n";
printf("%d verificacao(oes) ok, %d problema(s)\n", $ok, $erros);

exit($erros > 0 ? 1 : 0);
