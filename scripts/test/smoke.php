<?php
/**
 * BIOSTECH - teste de fumaca (smoke test)
 *
 * Uso:
 *   php scripts/test/smoke.php [url-base] [login] [senha]
 *
 * Exemplo:
 *   php scripts/test/smoke.php http://127.0.0.1:8090 ANDRIUS 123123
 */

$base  = isset($argv[1]) ? rtrim($argv[1], '/') : 'http://127.0.0.1:8090';
$login = isset($argv[2]) ? $argv[2] : 'ANDRIUS';
$senha = isset($argv[3]) ? $argv[3] : '123123';

$jar = tempnam(sys_get_temp_dir(), 'biostech_cookie_');
$falhas = 0;

function req($url, $post = NULL)
{
    global $jar;

    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_COOKIEJAR      => $jar,
        CURLOPT_COOKIEFILE     => $jar,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_HEADER         => FALSE,
    ));

    if ($post !== NULL)
    {
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    $corpo  = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return array($status, (string) $corpo);
}

function checar($rotulo, $condicao, $detalhe = '')
{
    global $falhas;

    if ($condicao)
    {
        echo "[ok]   $rotulo\n";
        return;
    }

    $falhas++;
    echo "[FALHA] $rotulo" . ($detalhe !== '' ? " -> $detalhe" : '') . "\n";
}

function sem_erro($corpo)
{
    return stripos($corpo, 'Fatal error') === FALSE
        && stripos($corpo, 'A Database Error Occurred') === FALSE
        && stripos($corpo, 'Parse error') === FALSE
        && stripos($corpo, 'Uncaught') === FALSE;
}

/* 1. tela de login ------------------------------------------------------- */
list($status, $corpo) = req("$base/login");
checar('GET /login responde 200', $status === 200, "status $status");
checar('tela de login sem erro', sem_erro($corpo));

preg_match('/name="biostech_csrf" value="([^"]+)"/', $corpo, $m);
$token = isset($m[1]) ? $m[1] : '';
checar('token CSRF presente', $token !== '');

/* 2. autenticacao -------------------------------------------------------- */
list($status, $corpo) = req("$base/login/entrar", array(
    'biostech_csrf' => $token,
    'login'         => $login,
    'senha'         => $senha,
));

checar('login efetuado (painel)', strpos($corpo, 'Ultimos eventos') !== FALSE || strpos($corpo, 'Clientes') !== FALSE, 'status ' . $status);
checar('painel sem erro', sem_erro($corpo));

preg_match('/class="perfil">([a-z]+)/', $corpo, $m);
$perfil = isset($m[1]) ? $m[1] : '';
checar('perfil identificado', in_array($perfil, array('admin', 'gestor', 'operador'), TRUE), $perfil);
echo "       (perfil: $perfil)\n";

/* 3. paginas ------------------------------------------------------------- */
$paginas = array(
    '/dashboard'        => array('Painel', 'Ultimos eventos'),
    '/clientes'         => array('Clientes', 'Exibindo'),
    '/fornecedores'     => array('Razao social', 'Exibindo'),
    '/produtos'         => array('Catalogo de produtos', 'Exibindo'),
    '/setores'          => array('Setores cadastrados', 'GERENCIA'),
    '/usuarios'         => array('Usuarios do sistema', strtoupper($login)),
);

foreach ($paginas as $caminho => $marcadores)
{
    list($status, $corpo) = req($base . $caminho);

    checar("GET $caminho responde 200", $status === 200, "status $status");
    checar("GET $caminho sem erro", sem_erro($corpo));

    foreach ($marcadores as $marcador)
    {
        if (strpos($corpo, $marcador) === FALSE)
        {
            checar("GET $caminho contem \"$marcador\"", FALSE);
        }
    }
}

/* 4. pesquisa ------------------------------------------------------------ */
list($status, $corpo) = req("$base/clientes?busca=maria");
checar('pesquisa de clientes', $status === 200 && sem_erro($corpo) && strpos($corpo, 'Exibindo') !== FALSE);

list($status, $corpo) = req("$base/fornecedores?busca=ltda");
checar('pesquisa de fornecedores', $status === 200 && sem_erro($corpo) && strpos($corpo, 'Exibindo') !== FALSE);

list($status, $corpo) = req("$base/produtos?busca=papel");
checar('pesquisa de produtos', $status === 200 && sem_erro($corpo));

/* 5. CRUD de setor ------------------------------------------------------- */
list($status, $corpo) = req("$base/setores/novo");
checar('GET /setores/novo permite cadastro', $status === 200 && sem_erro($corpo), 'status ' . $status);

preg_match('/name="biostech_csrf" value="([^"]+)"/', $corpo, $m);
$token = isset($m[1]) ? $m[1] : '';

list($status, $corpo) = req("$base/setores/novo", array(
    'biostech_csrf' => $token,
    'nome'          => 'TESTE AUTOMATICO',
    'ativo'         => '1',
));

checar('criar setor', $status === 200 && strpos($corpo, 'TESTE AUTOMATICO') !== FALSE, 'status ' . $status);

preg_match('/TESTE AUTOMATICO.*?setores\/editar\/(\d+)/s', $corpo, $m);
$id_setor = isset($m[1]) ? (int) $m[1] : 0;
checar('setor criado tem id', $id_setor > 0);

/* 5b. editar setor ------------------------------------------------------- */
if ($id_setor > 0)
{
    list($status, $corpo) = req("$base/setores/editar/$id_setor");
    preg_match('/name="biostech_csrf" value="([^"]+)"/', $corpo, $m);
    $token = isset($m[1]) ? $m[1] : '';

    list($status, $corpo) = req("$base/setores/editar/$id_setor", array(
        'biostech_csrf' => $token,
        'nome'          => 'TESTE AUTOMATICO ALTERADO',
        'ativo'         => '1',
    ));

    checar('editar setor', $status === 200 && strpos($corpo, 'TESTE AUTOMATICO ALTERADO') !== FALSE, 'status ' . $status);
}

/* 5c. excluir setor ------------------------------------------------------- */
if ($id_setor > 0 && $perfil === 'admin')
{
    list($status, $corpo) = req("$base/setores");
    preg_match('/name="biostech_csrf" value="([^"]+)"/', $corpo, $m);
    $token = isset($m[1]) ? $m[1] : '';

    list($status, $corpo) = req("$base/setores/excluir/$id_setor", array(
        'biostech_csrf' => $token,
    ));

    checar('excluir setor de teste', $status === 200 && strpos($corpo, 'TESTE AUTOMATICO ALTERADO') === FALSE, 'status ' . $status);
}
elseif ($id_setor > 0)
{
    echo "[info] perfil $perfil nao pode excluir setor - limpeza feita direto no banco\n";
}

/* limpeza (garante que o teste nao deixe lixo no banco) */
try
{
    $pdo = new PDO(
        'pgsql:host=' . (getenv('DB_HOST') ?: 'sedsrvpostdesv.seduc.ce.gov.br')
        . ';port=' . (getenv('DB_PORT') ?: '5432')
        . ';dbname=' . (getenv('DB_NAME') ?: 'desenvolvimento'),
        getenv('DB_USER') ?: 'matgrafico',
        getenv('DB_PASS') ?: 'matgrafico'
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $schema = getenv('DB_SCHEMA') ?: 'material_grafico';
    $pdo->exec("DELETE FROM $schema.biostech_setores WHERE nome LIKE 'TESTE AUTOMATICO%'");
}
catch (Exception $e)
{
    echo "[info] limpeza via banco ignorada: " . $e->getMessage() . "\n";
}

/* 6. logout -------------------------------------------------------------- */
list($status, $corpo) = req("$base/logout");
checar('logout volta para o login', strpos($corpo, 'Acesse com seu usuario') !== FALSE);

/* ----------------------------------------------------------------------- */
@unlink($jar);

echo "\n";
if ($falhas > 0)
{
    echo "FALHAS: $falhas\n";
    exit(1);
}

echo "TODOS OS TESTES PASSARAM\n";
exit(0);
