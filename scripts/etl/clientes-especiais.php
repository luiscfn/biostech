<?php
/**
 * BIOSTECH - carga dos precos especiais por cliente.
 *
 * Origem: PRODUTO_CLIENTES_ESPECIAIS_01 (SISBIOSD_01.mdb)
 * Destino: {schema}.biostech_produto_clientes_especiais
 *
 * A exportacao do Access e feita com:
 *   powershell -File scripts/etl/export.ps1 ^
 *       -Config scripts/etl/clientes-especiais.json
 *
 * Depois:
 *   php scripts/etl/clientes-especiais.php
 *
 * A carga e idempotente (upsert por cliente + produto). Registros cujo cliente
 * ou produto nao existam na base nova sao contados e ignorados.
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
date_default_timezone_set('America/Fortaleza');
mb_internal_encoding('UTF-8');

$host   = getenv('DB_HOST') ?: 'sedsrvpostdesv.seduc.ce.gov.br';
$port   = getenv('DB_PORT') ?: '5432';
$name   = getenv('DB_NAME') ?: 'desenvolvimento';
$user   = getenv('DB_USER') ?: 'matgrafico';
$pass   = getenv('DB_PASS') ?: 'matgrafico';
$schema = getenv('DB_SCHEMA') ?: 'material_grafico';

$arquivo = __DIR__ . '/dados/PRODUTO_CLIENTES_ESPECIAIS_01.json';

if ( ! is_file($arquivo))
{
    fwrite(STDERR, "arquivo nao encontrado: $arquivo\n");
    fwrite(STDERR, "rode antes o export.ps1 com -Config scripts/etl/clientes-especiais.json\n");
    exit(1);
}

$raw = file_get_contents($arquivo);

if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0)
{
    $raw = substr($raw, 3);
}

$registros = json_decode($raw, TRUE);

if ( ! is_array($registros))
{
    fwrite(STDERR, "JSON invalido: " . json_last_error_msg() . "\n");
    exit(1);
}

$pdo = new PDO("pgsql:host=$host;port=$port;dbname=$name", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tabela = "$schema.biostech_produto_clientes_especiais";

$existe = $pdo->query(
    "SELECT 1 FROM information_schema.tables
      WHERE table_schema = " . $pdo->quote($schema) . "
        AND table_name = 'biostech_produto_clientes_especiais'"
)->fetchColumn();

if ( ! $existe)
{
    fwrite(STDERR, "tabela $tabela nao existe. rode: php scripts/sql/aplicar.php scripts/sql/01-schema.sql\n");
    exit(1);
}

// ------------------------------------------------------------ referencias

$clientes = array();
foreach ($pdo->query("SELECT id FROM $schema.biostech_clientes") as $r)
{
    $clientes[(int) $r['id']] = TRUE;
}

$produtos = array();
foreach ($pdo->query("SELECT id, valor_compra FROM $schema.biostech_produtos") as $r)
{
    $produtos[(int) $r['id']] = (float) $r['valor_compra'];
}

echo 'clientes na base : ' . count($clientes) . "\n";
echo 'produtos na base : ' . count($produtos) . "\n";
echo 'registros origem : ' . count($registros) . "\n";

// ------------------------------------------------------------ carga

$sql = "INSERT INTO $tabela (cliente_id, produto_id, perc_lucro, valor_venda)
        VALUES (?, ?, ?, ?)
        ON CONFLICT (cliente_id, produto_id)
        DO UPDATE SET perc_lucro = EXCLUDED.perc_lucro,
                      valor_venda = EXCLUDED.valor_venda,
                      updated_at = now()";

$stmt = $pdo->prepare($sql);

$gravados     = 0;
$sem_cliente  = 0;
$sem_produto  = 0;
$recalculados = 0;

$pdo->beginTransaction();

foreach ($registros as $r)
{
    $cliente_id = (int) $r['CODCLI'];
    $produto_id = (int) $r['COD_PROD'];

    if ( ! isset($clientes[$cliente_id]))
    {
        $sem_cliente++;
        continue;
    }

    if ( ! isset($produtos[$produto_id]))
    {
        $sem_produto++;
        continue;
    }

    $perc  = ($r['PERC_LUCRO'] === NULL || $r['PERC_LUCRO'] === '') ? NULL : (float) $r['PERC_LUCRO'];
    $valor = ($r['VALOR_VENDA'] === NULL || $r['VALOR_VENDA'] === '') ? NULL : (float) $r['VALOR_VENDA'];

    // Mantem o comportamento do Access: percentual sem valor gera o preco
    // a partir do custo atual do produto.
    if ($valor === NULL && $perc !== NULL)
    {
        $valor = round($produtos[$produto_id] * (1 + ($perc / 100)), 2);
        $recalculados++;
    }

    $stmt->execute(array($cliente_id, $produto_id, $perc, $valor));
    $gravados++;
}

$pdo->commit();

echo "gravados         : $gravados\n";
echo "sem cliente      : $sem_cliente (ignorados)\n";
echo "sem produto      : $sem_produto (ignorados)\n";
echo "valor calculado  : $recalculados\n";

$total = $pdo->query("SELECT count(*) FROM $tabela")->fetchColumn();
$cli   = $pdo->query("SELECT count(DISTINCT cliente_id) FROM $tabela")->fetchColumn();

echo "total na tabela  : $total\n";
echo "clientes com preco especial : $cli\n";
echo "\n[ok] carga concluida.\n";
