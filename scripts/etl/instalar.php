<?php
/**
 * BIOSTECH - instalador do banco e carga inicial
 *
 * Uso:
 *   php scripts/etl/instalar.php
 *
 * Variaveis de ambiente:
 *   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_SCHEMA
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
date_default_timezone_set('America/Fortaleza');

$host   = getenv('DB_HOST') ?: 'sedsrvpostdesv.seduc.ce.gov.br';
$port   = getenv('DB_PORT') ?: '5432';
$name   = getenv('DB_NAME') ?: 'desenvolvimento';
$user   = getenv('DB_USER') ?: 'matgrafico';
$pass   = getenv('DB_PASS') ?: 'matgrafico';
$schema = getenv('DB_SCHEMA') ?: 'material_grafico';

$base   = dirname(__DIR__, 2);
$dados  = __DIR__ . '/dados';
$schemaFile = $base . '/scripts/sql/01-schema.sql';

function ok($msg)  { echo "[ok]  $msg\n"; }
function err($msg) { echo "[erro] $msg\n"; }
function ler_json($arquivo) {
    if (!is_file($arquivo)) { return null; }
    $raw = file_get_contents($arquivo);
    if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) { $raw = substr($raw, 3); }
    $d = json_decode($raw, true);
    if (!is_array($d)) { throw new RuntimeException('JSON invalido: ' . $arquivo . ' (' . json_last_error_msg() . ')'); }
    return $d;
}
function txt($v, $max = null) {
    if ($v === null) { return null; }
    $s = trim((string)$v);
    if ($s === '') { return null; }
    return ($max !== null && mb_strlen($s) > $max) ? mb_substr($s, 0, $max) : $s;
}
function num($v) { return ($v === null || $v === '') ? 0 : (float)$v; }
function inteiro($v) { return ($v === null || $v === '') ? null : (int)$v; }
function bool_sn($v) {
    if ($v === null) { return false; }
    $s = strtoupper(trim((string)$v));
    return !in_array($s, array('', 'N', 'NAO', 'NÃO', '0', 'FALSE', 'F'), true);
}

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$name", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    ok("conectado em $host:$port/$name (schema $schema)");
} catch (Exception $e) {
    err('conexao: ' . $e->getMessage());
    exit(1);
}

/* 1) schema --------------------------------------------------------------- */
try {
    $sql = str_replace('{schema}', $schema, file_get_contents($schemaFile));
    $pdo->exec($sql);
    ok('estrutura aplicada');
} catch (Exception $e) {
    err('estrutura: ' . $e->getMessage());
    exit(1);
}

/* 2) setores -------------------------------------------------------------- */
$setores = ler_json("$dados/SETORES.json");
if ($setores) {
    $pdo->beginTransaction();
    $st = $pdo->prepare(
        "INSERT INTO $schema.biostech_setores (id, nome, ativo)
         VALUES (:id, :nome, true)
         ON CONFLICT (id) DO UPDATE SET nome = EXCLUDED.nome, updated_at = now()"
    );
    $n = 0;
    foreach ($setores as $r) {
        if ($r['CODSETOR'] === null) { continue; }
        $st->execute(array(
            ':id'   => (int)$r['CODSETOR'],
            ':nome' => txt($r['NOMSETOR'], 70) ?: ('SETOR ' . $r['CODSETOR']),
        ));
        $n++;
    }
    $pdo->commit();
    ok("setores: $n");
} else {
    err('SETORES.json nao encontrado - rode scripts/etl/export.ps1');
}

/* 3) usuarios ------------------------------------------------------------- */
$usuarios = ler_json("$dados/USUARIOS.json");
if ($usuarios) {
    $pdo->beginTransaction();
    $pdo->exec("TRUNCATE $schema.biostech_usuarios RESTART IDENTITY CASCADE");

    $st = $pdo->prepare(
        "INSERT INTO $schema.biostech_usuarios
            (login, nome, senha_hash, perfil, setor_id, funcionario_id,
             acesso_almoxarifado, ativo, senha_provisoria)
         VALUES (:login, :nome, :hash, :perfil, :setor, :func, :almox, true, true)"
    );

    $usados = array();
    $n = 0; $sem_senha = 0;
    foreach ($usuarios as $r) {
        $login = strtoupper(txt($r['USUARIO'], 30));
        if ($login === null) { continue; }
        if (isset($usados[$login])) { continue; }
        $usados[$login] = true;

        $senha = txt($r['SENHA']);
        if ($senha === null) { $senha = 'biostech'; $sem_senha++; }

        $st->execute(array(
            ':login'  => $login,
            ':nome'   => txt($r['NOMUSU'], 100) ?: $login,
            ':hash'   => password_hash($senha, PASSWORD_BCRYPT),
            ':perfil' => ((int)$r['PRIVILEGIADO'] >= 2) ? 'admin' : 'gestor',
            ':setor'  => inteiro($r['SETOR']),
            ':func'   => inteiro($r['CODFUN']),
            ':almox'  => bool_sn($r['ACESSO_ALMOX']) ? 'true' : 'false',
        ));
        $n++;
    }
    $pdo->commit();
    ok("usuarios: $n" . ($sem_senha ? " (senha padrao 'biostech' para $sem_senha)" : ''));
} else {
    err('USUARIOS.json nao encontrado');
}

/* 4) clientes ------------------------------------------------------------- */
$clientes = ler_json("$dados/CLIENTES.json");
if ($clientes) {
    $pdo->beginTransaction();
    $pdo->exec("TRUNCATE $schema.biostech_clientes");

    $st = $pdo->prepare(
        "INSERT INTO $schema.biostech_clientes
            (id, nome, fantasia, tipo, cpf_cnpj, ie, rg, endereco, numero, complemento,
             bairro, cidade, uf, cep, telefone, celular, fax, contato, email, observacao)
         VALUES
            (:id, :nome, :fantasia, :tipo, :doc, :ie, :rg, :end, :num, :compl,
             :bairro, :cidade, :uf, :cep, :fone, :cel, :fax, :contato, :email, :obs)"
    );

    $n = 0;
    foreach ($clientes as $r) {
        if ($r['CODCLI'] === null) { continue; }
        $id = (int)$r['CODCLI'];

        $doc = txt($r['CGC_CLI'], 20);
        if ($doc === null) { $doc = txt($r['CPF'], 20); }

        $uf = txt($r['UF'], 2);
        if ($uf !== null) { $uf = strtoupper($uf); }

        $st->execute(array(
            ':id'       => $id,
            ':nome'     => txt($r['NOMCLI'], 120) ?: ('CLIENTE ' . $id),
            ':fantasia' => txt($r['NOME_FANTASIA'], 120),
            ':tipo'     => inteiro($r['TIPO']),
            ':doc'      => $doc,
            ':ie'       => txt($r['INS_EST'], 20),
            ':rg'       => txt($r['RG'], 20),
            ':end'      => txt($r['ENDCLI'], 80),
            ':num'      => txt($r['NUMERO'], 10),
            ':compl'    => txt($r['COMPLEMENTO'], 50),
            ':bairro'   => txt($r['BAIRRO'], 50),
            ':cidade'   => txt($r['CIDADE'], 60),
            ':uf'       => $uf,
            ':cep'      => txt($r['CEP'], 10),
            ':fone'     => txt($r['FONE'], 20),
            ':cel'      => txt($r['CELULAR'], 20),
            ':fax'      => txt($r['FAX'], 20),
            ':contato'  => txt($r['CONTATO'], 40),
            ':email'    => txt($r['EMAIL'], 120),
            ':obs'      => txt($r['OBSERVACAO']),
        ));
        $n++;
    }
    $pdo->commit();
    ok("clientes: $n");
} else {
    err('CLIENTES.json nao encontrado');
}

/* 5) produtos ------------------------------------------------------------- */
$produtos = ler_json("$dados/PRODUTO.json");
if ($produtos) {
    $pdo->beginTransaction();
    $pdo->exec("TRUNCATE $schema.biostech_produtos");

    $st = $pdo->prepare(
        "INSERT INTO $schema.biostech_produtos
            (id, codigo, descricao, unidade, estoque_minimo, valor_compra, valor_venda,
             grupo, subgrupo, estante, prateleira, marca, aplicacao, situacao, ativo)
         VALUES
            (:id, :codigo, :descricao, :unidade, :minimo, :compra, :venda,
             :grupo, :subgrupo, :estante, :prateleira, :marca, :aplicacao, :situacao, :ativo)"
    );

    $n = 0;
    foreach ($produtos as $r) {
        if ($r['COD_PROD'] === null) { continue; }
        $id = (int)$r['COD_PROD'];

        $st->execute(array(
            ':id'         => $id,
            ':codigo'     => txt($r['COD_COMPLETO'], 15),
            ':descricao'  => txt($r['DES_PROD'], 120) ?: ('PRODUTO ' . $id),
            ':unidade'    => txt($r['UNI_PROD'], 10),
            ':minimo'     => (int)num($r['EST_MINIMO']),
            ':compra'     => num($r['VALOR_COMPRA']),
            ':venda'      => num($r['VALOR_VENDA']),
            ':grupo'      => txt($r['GRUPO'], 3),
            ':subgrupo'   => txt($r['SUB_GRUPO'], 3),
            ':estante'    => inteiro($r['ESTANTE']),
            ':prateleira' => inteiro($r['PRATELEIRA']),
            ':marca'      => txt($r['MARCA'], 50),
            ':aplicacao'  => txt($r['APLICACAO']),
            ':situacao'   => inteiro($r['SITUACAO']),
            ':ativo'      => !in_array(strtoupper(trim((string)$r['ATIVO'])), array('N', 'NAO', '0', 'FALSE'), true) ? 'true' : 'false',
        ));
        $n++;
    }
    $pdo->commit();
    ok("produtos: $n");
} else {
    err('PRODUTO.json nao encontrado');
}

/* 6) resumo --------------------------------------------------------------- */
echo "\n== resumo ($schema) ==\n";
foreach (array('biostech_setores', 'biostech_usuarios', 'biostech_clientes', 'biostech_produtos') as $t) {
    $q = $pdo->query("SELECT count(*) FROM $schema.$t");
    printf("%-24s %6d\n", $t, $q->fetchColumn());
}
