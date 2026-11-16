<?php
/**
 * BIOSTECH - aplica um arquivo .sql no banco configurado.
 *
 * Uso:
 *   php scripts/sql/aplicar.php scripts/sql/02-chamados-os.sql
 *   php scripts/sql/aplicar.php scripts/sql/02-chamados-os.sql --sim
 *
 * O marcador {schema} e substituido por DB_SCHEMA (padrao: material_grafico).
 * Sem --sim apenas mostra o que seria feito.
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
date_default_timezone_set('America/Fortaleza');

$host   = getenv('DB_HOST') ?: 'sedsrvpostdesv.seduc.ce.gov.br';
$port   = getenv('DB_PORT') ?: '5432';
$name   = getenv('DB_NAME') ?: 'desenvolvimento';
$user   = getenv('DB_USER') ?: 'matgrafico';
$pass   = getenv('DB_PASS') ?: 'matgrafico';
$schema = getenv('DB_SCHEMA') ?: 'material_grafico';

$arquivo = isset($argv[1]) ? $argv[1] : null;
$sim     = in_array('--sim', $argv, true);

if ($arquivo === null || !is_file($arquivo)) {
    fwrite(STDERR, "uso: php scripts/sql/aplicar.php <arquivo.sql> [--sim]\n");
    exit(1);
}

$sql = file_get_contents($arquivo);
$sql = str_replace('{schema}', $schema, $sql);

echo "arquivo : $arquivo\n";
echo "banco   : $host:$port/$name\n";
echo "schema  : $schema\n";
echo "tamanho : " . number_format(strlen($sql), 0, ',', '.') . " bytes\n";

if ($sim) {
    echo "\n--sim: nada foi executado.\n";
    exit(0);
}

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$name", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec($sql);
    echo "\n[ok] script aplicado.\n";
} catch (Exception $e) {
    fwrite(STDERR, "\n[erro] " . $e->getMessage() . "\n");
    exit(1);
}

echo "\n== tabelas do modulo ==\n";
$q = $pdo->query(
    "SELECT table_name FROM information_schema.tables
      WHERE table_schema = " . $pdo->quote($schema) . "
        AND table_name LIKE 'biostech_%'
      ORDER BY table_name"
);
foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $t) {
    $c = $pdo->query("SELECT count(*) FROM \"$schema\".\"$t\"")->fetchColumn();
    printf("%-46s %6d\n", $t, $c);
}
