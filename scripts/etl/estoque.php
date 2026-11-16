<?php
/**
 * BIOSTECH - carga dos saldos de estoque (origem: ESTOQUE_02).
 *
 * Uso:
 *   php scripts/etl/estoque.php
 *
 * QTDE_01 -> saldo atual; QTDE_03 -> consumido.
 * Somente produtos existentes em biostech_produtos sao considerados.
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
date_default_timezone_set('America/Fortaleza');

$host   = getenv('DB_HOST') ?: 'sedsrvpostdesv.seduc.ce.gov.br';
$port   = getenv('DB_PORT') ?: '5432';
$name   = getenv('DB_NAME') ?: 'desenvolvimento';
$user   = getenv('DB_USER') ?: 'matgrafico';
$pass   = getenv('DB_PASS') ?: 'matgrafico';
$schema = getenv('DB_SCHEMA') ?: 'material_grafico';

$arquivo = __DIR__ . '/dados/ESTOQUE_02.json';

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
    fwrite(STDERR, "JSON invalido: " . json_last_error_msg() . "\n");
    exit(1);
}

$pdo = new PDO("pgsql:host=$host;port=$port;dbname=$name", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$ids = array();
foreach ($pdo->query("SELECT id FROM $schema.biostech_produtos") as $r)
{
    $ids[(int) $r['id']] = TRUE;
}

$linhas = array();
$ignorados = 0;

foreach ($dados as $r)
{
    if ($r['COD_PROD'] === NULL)
    {
        continue;
    }

    $id = (int) $r['COD_PROD'];

    if ( ! isset($ids[$id]))
    {
        $ignorados++;
        continue;
    }

    $qtd  = (int) round((float) $r['QTDE_01']);
    $cons = (int) round((float) $r['QTDE_03']);

    $linhas[] = array($id, max(0, $qtd), max(0, $cons));
}

$pdo->beginTransaction();

$n = 0;
foreach (array_chunk($linhas, 400) as $chunk)
{
    $marcas = array();
    $params = array();

    foreach ($chunk as $l)
    {
        $marcas[] = '(?, ?, ?, now())';
        $params[] = $l[0];
        $params[] = $l[1];
        $params[] = $l[2];
    }

    $sql = "INSERT INTO $schema.biostech_estoque_saldos
                (produto_id, quantidade, quantidade_consumida, updated_at)
            VALUES " . implode(', ', $marcas) . "
            ON CONFLICT (produto_id)
            DO UPDATE SET quantidade = EXCLUDED.quantidade,
                          quantidade_consumida = EXCLUDED.quantidade_consumida,
                          updated_at = now()";

    $st = $pdo->prepare($sql);
    $st->execute($params);
    $n += count($chunk);
}

$pdo->commit();

echo "[ok] saldos gravados: $n\n";
echo "[..] produtos inexistentes ignorados: $ignorados\n";

$r = $pdo->query("SELECT count(*) AS t, COALESCE(sum(quantidade),0) AS q FROM $schema.biostech_estoque_saldos")
        ->fetch(PDO::FETCH_ASSOC);
echo "[..] linhas na tabela: " . $r['t'] . " / quantidade total: " . $r['q'] . "\n";
