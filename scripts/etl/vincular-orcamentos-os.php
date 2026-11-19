<?php
/**
 * BIOSTECH - vincula orcamentos e dimensionamentos aos chamados/OS.
 *
 * A carga de orcamentos (scripts/etl/orcamentos.php) roda antes da carga de
 * chamados e so gravava chamado_numero quando a OS ja existia em
 * biostech_chamados. Resultado: a coluna OS das telas de orcamento ficava
 * vazia, apesar de o Access ter o NUM_OS preenchido em 1.159 orcamentos.
 *
 * Este script relê as exportacoes originais (dados/orcamentos.json e
 * dados/dimensionamentos.json) e atualiza apenas chamado_numero, de forma
 * idempotente. Os demais campos nao sao tocados.
 *
 * Uso:
 *   php scripts/etl/vincular-orcamentos-os.php
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
date_default_timezone_set('America/Fortaleza');
mb_internal_encoding('UTF-8');
ini_set('memory_limit', '512M');

$host   = getenv('DB_HOST') ?: 'sedsrvpostdesv.seduc.ce.gov.br';
$port   = getenv('DB_PORT') ?: '5432';
$name   = getenv('DB_NAME') ?: 'desenvolvimento';
$user   = getenv('DB_USER') ?: 'matgrafico';
$pass   = getenv('DB_PASS') ?: 'matgrafico';
$schema = getenv('DB_SCHEMA') ?: 'material_grafico';
$dir    = __DIR__ . '/dados';

$pdo = new PDO("pgsql:host=$host;port=$port;dbname=$name", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("SET datestyle TO 'ISO'");

function ler_json($arquivo)
{
    if ( ! is_file($arquivo))
    {
        fwrite(STDERR, "arquivo nao encontrado: $arquivo\n");
        exit(1);
    }

    $raw = file_get_contents($arquivo);

    if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0)
    {
        $raw = substr($raw, 3);
    }

    $dados = json_decode($raw, TRUE);

    if ( ! is_array($dados))
    {
        fwrite(STDERR, "JSON invalido em $arquivo: " . json_last_error_msg() . "\n");
        exit(1);
    }

    return $dados;
}

function os_texto($valor)
{
    if ($valor === NULL)
    {
        return NULL;
    }

    $valor = trim((string) $valor);
    return $valor === '' ? NULL : $valor;
}

// ------------------------------------------------------------------ chamados

$existentes = array();
foreach ($pdo->query("SELECT numero FROM $schema.biostech_chamados") as $r)
{
    $existentes[(string) $r['numero']] = TRUE;
}

echo 'chamados na base: ' . count($existentes) . "\n";

$pdo->beginTransaction();

$atualizarOrc = $pdo->prepare(
    "UPDATE $schema.biostech_orcamentos
        SET chamado_numero = ?
      WHERE numero = ?
        AND chamado_numero IS NULL"
);

$atualizarOrcLegado = $pdo->prepare(
    "UPDATE $schema.biostech_orcamentos
        SET chamado_numero_legado = ?
      WHERE numero = ?
        AND chamado_numero_legado IS DISTINCT FROM ?::varchar"
);

$orc = ler_json($dir . '/orcamentos.json');
$lidos = $comOs = $links = $semOs = $legados = 0;

foreach ($orc as $r)
{
    $lidos++;
    $os = os_texto(isset($r['NUM_OS']) ? $r['NUM_OS'] : NULL);

    if ($os === NULL)
    {
        $semOs++;
        continue;
    }

    $comOs++;

    $atualizarOrcLegado->execute(array($os, (int) $r['NUM_ORC'], $os));
    $legados += $atualizarOrcLegado->rowCount();

    if ( ! isset($existentes[$os]))
    {
        continue;
    }

    $atualizarOrc->execute(array($os, (int) $r['NUM_ORC']));
    $links += $atualizarOrc->rowCount();
}

printf("orcamentos: %d lidos, %d com NUM_OS, %d sem NUM_OS, %d vinculos, %d numeros legados\n",
    $lidos, $comOs, $semOs, $links, $legados);

// ----------------------------------------------------------- dimensionamentos

$atualizarDim = $pdo->prepare(
    "UPDATE $schema.biostech_orcamentos_dimensionamento
        SET chamado_numero = ?
      WHERE numero = ?
        AND chamado_numero IS DISTINCT FROM ?::varchar"
);

$dim = ler_json($dir . '/dimensionamentos.json');
$lidos = $comOs = $links = 0;

foreach ($dim as $r)
{
    $lidos++;
    $os = os_texto(isset($r['NUM_OS']) ? $r['NUM_OS'] : NULL);

    if ($os === NULL || ! isset($existentes[$os]))
    {
        continue;
    }

    $comOs++;
    $atualizarDim->execute(array($os, (int) $r['NUM_PLA'], $os));
    $links += $atualizarDim->rowCount();
}

printf("dimensionamentos: %d lidos, %d com OS existente, %d vinculos gravados\n",
    $lidos, $comOs, $links);

$pdo->commit();

// ------------------------------------------------------------------ conferencia

echo "\nconferencia:\n";
foreach (array(
    'biostech_orcamentos' => "SELECT count(*) AS total, count(chamado_numero) AS com_os,
                                     count(chamado_numero_legado) AS com_legado
                                FROM $schema.biostech_orcamentos",
    'biostech_orcamentos_dimensionamento' => "SELECT count(*) AS total, count(chamado_numero) AS com_os,
                                     count(*) AS com_legado
                                FROM $schema.biostech_orcamentos_dimensionamento",
) as $tabela => $sql)
{
    $r = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
    printf("  %-40s %5d registros, %5d com OS, %5d com numero legado\n",
        $tabela, $r['total'], $r['com_os'], $r['com_legado']);
}
