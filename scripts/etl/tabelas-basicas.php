<?php
/**
 * BIOSTECH - ETL do modulo Tabelas Basicas
 *
 * Carrega os itens do menu F_MODULO_TABELAS do Access para o PostgreSQL:
 *   - dominios simples  -> biostech_dominios (tipo + codigo)
 *   - parametros        -> biostech_basico_parametros (VALOR_ORC_NAPROV)
 *   - permissoes        -> biostech_basico_modulos (PERMISSOES_MODULO_01)
 *
 * Pre-requisito: rodar o export de 32 bits
 *   C:\Windows\SysWOW64\WindowsPowerShell\v1.0\powershell.exe ^
 *       -File scripts\etl\export-tabelas-basicas.ps1
 *
 * Uso:
 *   php scripts/etl/tabelas-basicas.php
 *
 * Variaveis de ambiente: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_SCHEMA
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
date_default_timezone_set('America/Fortaleza');

$host   = getenv('DB_HOST') ?: 'sedsrvpostdesv.seduc.ce.gov.br';
$port   = getenv('DB_PORT') ?: '5432';
$name   = getenv('DB_NAME') ?: 'desenvolvimento';
$user   = getenv('DB_USER') ?: 'matgrafico';
$pass   = getenv('DB_PASS') ?: 'matgrafico';
$schema = getenv('DB_SCHEMA') ?: 'material_grafico';
$dados  = __DIR__ . '/dados';

function ok($msg)  { echo "[ok]  $msg\n"; }
function err($msg) { echo "[erro] $msg\n"; }

function ler_json($arquivo) {
    if ( ! is_file($arquivo)) {
        throw new RuntimeException('JSON nao encontrado: ' . $arquivo);
    }
    $raw = file_get_contents($arquivo);
    if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) { $raw = substr($raw, 3); }
    $d = json_decode($raw, true);
    if ( ! is_array($d)) {
        throw new RuntimeException('JSON invalido: ' . $arquivo . ' (' . json_last_error_msg() . ')');
    }
    return $d;
}

function txt($v, $max = NULL) {
    if ($v === NULL) { return NULL; }
    $s = trim((string) $v);
    if ($s === '') { return NULL; }
    if ($max !== NULL && mb_strlen($s) > $max) { $s = mb_substr($s, 0, $max); }
    return $s;
}

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$name", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    ok("conectado em $host:$port/$name (schema $schema)");
} catch (Exception $e) {
    err('conexao: ' . $e->getMessage());
    exit(1);
}

/* Dominios ---------------------------------------------------------------- */

$mapa = array(
    'tipo_equipamento'   => array('TIPO_EQUIPAMENTO.json',  'CODTIPO',    'NOMTIPO'),
    'status_os'          => array('STATUS_OS.json',         'CODSTATUS',  'NOMSTATUS'),
    'status_equipamento' => array('STATUS_EQUIP.json',      'CODSTATUS',  'NOMSTATUS'),
    'condicao_pagamento' => array('CODICAO_PAGTO.json',     'CODPAGTO',   'NOMPAGTO'),
    'situacao_orcamento' => array('SIT_ORCAMENTO_01.json',  'COD_SIT_01', 'NOM_SIT_01'),
    'resultado_orcamento'=> array('SIT_ORCAMENTO_02.json',  'COD_SIT_02', 'NOM_SIT_02'),
    'finalidade'         => array('FINALIDADE.json',        'CODFINAL',   'NOMFINAL'),
    'funcao'             => array('FUNCOES.json',           'CODFUNCAO',  'NOMEFUNCAO'),
    'tipo_pagamento'     => array('TIPO_PAGTO.json',        'CODPAGTO',   'NOMPAGTO'),
    'natureza_operacao'  => array('NAT_OPERACAO.json',      'CODOPER',    'NOMOPER'),
);

$st = $pdo->prepare(
    "INSERT INTO $schema.biostech_dominios (tipo, codigo, nome, ordem, ativo)
     VALUES (:tipo, :codigo, :nome, :ordem, true)
     ON CONFLICT (tipo, codigo) DO UPDATE
        SET nome = EXCLUDED.nome, ordem = EXCLUDED.ordem"
);

foreach ($mapa as $tipo => $cfg) {
    list($arquivo, $col_cod, $col_nome) = $cfg;

    try {
        $regs = ler_json("$dados/$arquivo");
    } catch (Exception $e) {
        err($e->getMessage());
        continue;
    }

    usort($regs, function ($a, $b) use ($col_cod) {
        return (int) $a[$col_cod] - (int) $b[$col_cod];
    });

    $pdo->beginTransaction();
    $n = 0;
    $ordem = 0;

    foreach ($regs as $r) {
        if ($r[$col_cod] === NULL) { continue; }
        $ordem++;

        $st->execute(array(
            ':tipo'   => $tipo,
            ':codigo' => (int) $r[$col_cod],
            ':nome'   => txt($r[$col_nome], 120) ?: ('ITEM ' . $r[$col_cod]),
            ':ordem'  => $ordem,
        ));
        $n++;
    }

    $pdo->commit();
    ok(sprintf('%-22s %4d registros', $tipo, $n));
}

// STATUS_EQUIP nao possui o codigo 6 (registro especulativo da carga inicial).
$pdo->exec("DELETE FROM $schema.biostech_dominios WHERE tipo = 'status_equipamento' AND codigo = 6");

/* Locais de atendimento --------------------------------------------------- */

try {
    $locais = ler_json("$dados/basico_locais_atendimento.json");

    $existentes = array();
    $q = $pdo->query("SELECT codigo, nome FROM $schema.biostech_dominios WHERE tipo = 'local_atendimento'");
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $existentes[mb_strtoupper($r['nome'])] = (int) $r['codigo'];
    }

    $proximo = 1;
    foreach ($existentes as $codigo) { $proximo = max($proximo, $codigo + 1); }

    $ins = $pdo->prepare(
        "INSERT INTO $schema.biostech_dominios (tipo, codigo, nome, ordem, ativo)
         VALUES ('local_atendimento', :codigo, :nome, :ordem, true)
         ON CONFLICT (tipo, codigo) DO UPDATE SET nome = EXCLUDED.nome"
    );

    $pdo->beginTransaction();
    $n = 0;
    $ordem = 0;

    foreach ($locais as $r) {
        $nome = txt($r['LOCAL_ATEND'], 120);
        if ($nome === NULL) { continue; }
        $ordem++;

        $chave  = mb_strtoupper($nome);
        $codigo = isset($existentes[$chave]) ? $existentes[$chave] : $proximo++;

        $ins->execute(array(':codigo' => $codigo, ':nome' => $nome, ':ordem' => $ordem));
        $n++;
    }

    $pdo->commit();
    ok(sprintf('%-22s %4d registros', 'local_atendimento', $n));
} catch (Exception $e) {
    $pdo->rollBack();
    err('locais de atendimento: ' . $e->getMessage());
}

/* Parametros -------------------------------------------------------------- */

try {
    $par = ler_json("$dados/VALOR_ORC_NAPROV.json");
    $p   = $par[0];

    $st = $pdo->prepare(
        "INSERT INTO $schema.biostech_basico_parametros
            (id, valor, prazo_ret_equip, valor_taxa_perm, prazo_ini_envio_orc,
             prazo_fim_envio_orc, dias_limite, texto_rodape, updated_at)
         VALUES (1, :valor, :ret, :taxa, :ini, :fim, :dias, :texto, now())
         ON CONFLICT (id) DO UPDATE SET
            valor               = EXCLUDED.valor,
            prazo_ret_equip     = EXCLUDED.prazo_ret_equip,
            valor_taxa_perm     = EXCLUDED.valor_taxa_perm,
            prazo_ini_envio_orc = EXCLUDED.prazo_ini_envio_orc,
            prazo_fim_envio_orc = EXCLUDED.prazo_fim_envio_orc,
            dias_limite         = EXCLUDED.dias_limite,
            texto_rodape        = EXCLUDED.texto_rodape,
            updated_at          = now()"
    );

    $nul = function ($v) { return ($v === NULL || $v === '') ? NULL : $v; };

    $st->execute(array(
        ':valor' => $nul($p['VALOR']),
        ':ret'   => $nul($p['PRAZO_RET_EQUIP']),
        ':taxa'  => $nul($p['VALOR_TAXA_PERM']),
        ':ini'   => $nul($p['PRAZO_INI_ENVIO_ORC']),
        ':fim'   => $nul($p['PRAZO_FIM_ENVIO_ORC']),
        ':dias'  => $nul($p['DIAS_LIMITE']),
        ':texto' => txt($p['TEXTO_RODAPE']),
    ));

    ok('valor_orcamento_nao_aprovado 1 registro');
} catch (Exception $e) {
    err('parametros: ' . $e->getMessage());
}

/* Permissoes -------------------------------------------------------------- */

try {
    $mods = ler_json("$dados/PERMISSOES_MODULO_01.json");
    usort($mods, function ($a, $b) {
        return (int) $a['COD_MODULO'] - (int) $b['COD_MODULO'];
    });

    $st = $pdo->prepare(
        "INSERT INTO $schema.biostech_basico_modulos (codigo, nome, ordem, ativo)
         VALUES (:codigo, :nome, :ordem, true)
         ON CONFLICT (codigo) DO UPDATE SET nome = EXCLUDED.nome, ordem = EXCLUDED.ordem"
    );

    $pdo->beginTransaction();
    $n = 0;
    $ordem = 0;

    foreach ($mods as $m) {
        $ordem++;
        $st->execute(array(
            ':codigo' => (int) $m['COD_MODULO'],
            ':nome'   => txt($m['NOM_MODULO'], 80) ?: ('MODULO ' . $m['COD_MODULO']),
            ':ordem'  => $ordem,
        ));
        $n++;
    }

    $pdo->commit();
    ok(sprintf('%-22s %4d registros', 'permissoes_modulos', $n));
} catch (Exception $e) {
    $pdo->rollBack();
    err('permissoes: ' . $e->getMessage());
}

/* Resumo ------------------------------------------------------------------ */

echo "\n== resumo ($schema) ==\n";

foreach (array('biostech_dominios', 'biostech_basico_parametros',
               'biostech_basico_modulos', 'biostech_basico_programas') as $t) {
    $q = $pdo->query("SELECT count(*) FROM $schema.$t");
    printf("%-32s %6d\n", $t, $q->fetchColumn());
}

$q = $pdo->query(
    "SELECT count(*) FROM $schema.biostech_dominios
      WHERE tipo IN ('tipo_equipamento', 'status_os', 'status_equipamento',
                     'condicao_pagamento', 'situacao_orcamento', 'resultado_orcamento',
                     'local_atendimento', 'finalidade', 'natureza_operacao',
                     'funcao', 'tipo_pagamento')"
);
printf("%-32s %6d\n", 'dominios das tabelas basicas', $q->fetchColumn());
