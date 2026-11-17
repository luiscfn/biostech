<?php
/**
 * BIOSTECH - carga do modulo Financeiro.
 *
 * Fontes (scripts/etl/dados/fin_*.json, exportadas do Access por
 * export-financeiro.ps1):
 *   cadastros : BANCOS, AGENCIA, CONTA_BANCARIA, CARTOES, CENTRO_CUSTOS,
 *               NAT_OPERACAO, STATUS_PAGAMENTO, CP_01, DESPESAS_FIXAS
 *   titulos   : CONTAS_PAGAR_01/02/LOG_01
 *               CONTAS_RECEBER_01/02 (ativas) e 03/04 (arquivadas)
 *   bancos    : MOV_BANCARIA_01/02
 *   caixa     : MOVICAIXA1/02/03
 *   balanco   : TB_BALANCO_FINANC_01
 *
 * Uso:
 *   php scripts/etl/financeiro.php
 *
 * A carga e idempotente. Ao final, a numeracao CP/CR/MB e posicionada acima do
 * maior codigo importado para que novos titulos/movimentos nao colidam.
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
date_default_timezone_set('America/Fortaleza');
mb_internal_encoding('UTF-8');
ini_set('memory_limit', '1024M');

$host   = getenv('DB_HOST') ?: 'sedsrvpostdesv.seduc.ce.gov.br';
$port   = getenv('DB_PORT') ?: '5432';
$name   = getenv('DB_NAME') ?: 'desenvolvimento';
$user   = getenv('DB_USER') ?: 'matgrafico';
$pass   = getenv('DB_PASS') ?: 'matgrafico';
$schema = getenv('DB_SCHEMA') ?: 'material_grafico';
$dir    = __DIR__ . '/dados';

// ------------------------------------------------------------------ helpers

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

function texto($valor, $limite = NULL)
{
    if ($valor === NULL)
    {
        return NULL;
    }

    $valor = trim((string) $valor);

    if ($valor === '')
    {
        return NULL;
    }

    if ( ! mb_check_encoding($valor, 'UTF-8'))
    {
        $valor = @iconv('UTF-8', 'UTF-8//IGNORE', $valor);
    }

    $valor = preg_replace('/[\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}\x{007F}-\x{009F}]/u', ' ', $valor);
    $valor = trim(preg_replace('/[ ]{2,}/', ' ', $valor));

    if ($valor === '')
    {
        return NULL;
    }

    return $limite === NULL ? $valor : mb_substr($valor, 0, $limite, 'UTF-8');
}

function inteiro($valor)
{
    if ($valor === NULL || $valor === '' || (int) $valor === 0)
    {
        return NULL;
    }

    return (int) $valor;
}

function inteiro_zero($valor)
{
    return ($valor === NULL || $valor === '') ? NULL : (int) $valor;
}

function decimal($valor)
{
    return ($valor === NULL || $valor === '') ? NULL : (float) $valor;
}

function decimal_zero($valor)
{
    $v = decimal($valor);
    return $v === NULL ? 0.0 : $v;
}

/** Data ISO (Y-m-d). Datas vazias/invalidas do Access viram NULL. */
function data($valor)
{
    $valor = texto($valor);

    if ($valor === NULL)
    {
        return NULL;
    }

    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $valor, $m))
    {
        $ano = (int) $m[1];

        if ($ano < 1901 || $ano > 2100 || ! checkdate((int) $m[2], (int) $m[3], $ano))
        {
            return NULL;
        }

        return $m[1] . '-' . $m[2] . '-' . $m[3];
    }

    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $valor, $m))
    {
        $ano = (int) $m[3];

        if ($ano < 1901 || $ano > 2100 || ! checkdate((int) $m[2], (int) $m[1], $ano))
        {
            return NULL;
        }

        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }

    return NULL;
}

/** Data e hora ISO (Y-m-d H:i:s). */
function momento($valor)
{
    $d = data($valor);

    if ($d === NULL)
    {
        return NULL;
    }

    $valor = (string) $valor;

    if (preg_match('/(\d{2}):(\d{2})(?::(\d{2}))?/', $valor, $m))
    {
        $h = (int) $m[1];
        $i = (int) $m[2];
        $s = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : 0;

        if ($h > 23 || $i > 59 || $s > 59)
        {
            return $d . ' 00:00:00';
        }

        return $d . ' ' . sprintf('%02d:%02d:%02d', $h, $i, $s);
    }

    return $d . ' 00:00:00';
}

function sim($valor)
{
    return is_string($valor) && in_array(mb_strtoupper(trim($valor)), array('S', 'SIM', '1', 'T', 'TRUE'), TRUE);
}

/** Booleano aceito pelo PostgreSQL via PDO. */
function b($valor)
{
    return sim($valor) ? 'true' : 'false';
}

function gravar_simples(PDO $pdo, $tabela, array $colunas, array $linhas, $tamanho = 250)
{
    if (empty($linhas))
    {
        return 0;
    }

    $grupo = '(' . rtrim(str_repeat('?, ', count($colunas)), ', ') . ')';
    $total = 0;

    foreach (array_chunk($linhas, $tamanho) as $bloco)
    {
        $valores = array();
        $params  = array();

        foreach ($bloco as $linha)
        {
            $valores[] = $grupo;

            foreach ($linha as $valor)
            {
                $params[] = $valor;
            }
        }

        $sql = 'INSERT INTO ' . $tabela . ' (' . implode(', ', $colunas) . ')
                VALUES ' . implode(', ', $valores);

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $total += count($bloco);
    }

    return $total;
}

function gravar_lote(PDO $pdo, $tabela, array $colunas, array $linhas, array $conflito, $atualizar = TRUE, $tamanho = 250)
{
    if (empty($linhas))
    {
        return 0;
    }

    $grupo = '(' . rtrim(str_repeat('?, ', count($colunas)), ', ') . ')';

    $set = array();

    if ($atualizar)
    {
        foreach ($colunas as $coluna)
        {
            if (in_array($coluna, $conflito, TRUE))
            {
                continue;
            }

            $set[] = $coluna . ' = EXCLUDED.' . $coluna;
        }
    }

    $conflitoSql = 'ON CONFLICT (' . implode(', ', $conflito) . ') '
        . ($set ? 'DO UPDATE SET ' . implode(', ', $set) : 'DO NOTHING');

    $total = 0;

    foreach (array_chunk($linhas, $tamanho) as $bloco)
    {
        $valores = array();
        $params  = array();

        foreach ($bloco as $linha)
        {
            $valores[] = $grupo;

            foreach ($linha as $valor)
            {
                $params[] = $valor;
            }
        }

        $sql = 'INSERT INTO ' . $tabela . ' (' . implode(', ', $colunas) . ')
                VALUES ' . implode(', ', $valores) . ' ' . $conflitoSql;

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $total += count($bloco);
    }

    return $total;
}

$pdo = new PDO("pgsql:host=$host;port=$port;dbname=$name", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("SET datestyle TO 'ISO'");
$pdo->beginTransaction();

$T = array();
foreach (array(
    'bancos' => 'biostech_fin_bancos',
    'agencias' => 'biostech_fin_agencias',
    'contas' => 'biostech_fin_contas',
    'cartoes' => 'biostech_fin_cartoes',
    'centros' => 'biostech_fin_centros_custo',
    'naturezas' => 'biostech_fin_naturezas_operacao',
    'status' => 'biostech_fin_status_pagamento',
    'periodicidades' => 'biostech_fin_periodicidades',
    'despesas' => 'biostech_fin_despesas_fixas',
    'cheques' => 'biostech_fin_cheques',
    'movimentos' => 'biostech_fin_movimentos_bancarios',
    'caixa_diario' => 'biostech_fin_caixa_diario',
    'caixa_movimentos' => 'biostech_fin_caixa_movimentos',
    'balanco' => 'biostech_fin_balanco',
    'cp' => 'biostech_contas_pagar',
    'cp_baixas' => 'biostech_contas_pagar_baixas',
    'cp_log' => 'biostech_contas_pagar_log',
    'cr' => 'biostech_contas_receber',
    'cr_baixas' => 'biostech_contas_receber_baixas',
    'numeracao' => 'biostech_numeracao',
) as $k => $t)
{
    $T[$k] = $schema . '.' . $t;
}

// ------------------------------------------------------------------ cadastros

echo "== cadastros financeiros\n";

$bancos = array();
foreach (ler_json($dir . '/fin_bancos.json') as $r)
{
    $bancos[] = array(texto($r['CODBANCO'], 20), texto($r['NOMBANCO'], 120));
}

$linhas = array();
foreach ($bancos as $b)
{
    if ($b[0] === NULL) { continue; }
    $linhas[] = array($b[0], $b[1] ?: $b[0]);
}
echo '   bancos: ' . gravar_lote($pdo, $T['bancos'], array('codigo', 'nome'), $linhas, array('codigo')) . "\n";

$linhas = array();
foreach (ler_json($dir . '/fin_agencias.json') as $r)
{
    $banco = texto($r['CODBANCO'], 20);
    $agencia = texto($r['AGENCIA'], 25);

    if ($banco === NULL || $agencia === NULL) { continue; }

    $linhas[] = array(
        $banco, $agencia, texto($r['NOME_AG'], 120), texto($r['ENDER_AG'], 120),
        texto($r['BAIRRO_AG'], 60), texto($r['NUMCEP_AG'], 10), texto($r['CIDADE_AG'], 60),
        texto($r['ESTADO_AG'], 2), texto($r['TELEF1_AG'], 30), texto($r['TELEF2_AG'], 30),
        texto($r['TELFAX_AG'], 30), texto($r['CONTAT_AG'], 80),
    );
}
echo '   agencias: ' . gravar_lote($pdo, $T['agencias'],
    array('banco_codigo', 'agencia', 'nome', 'endereco', 'bairro', 'cep', 'cidade', 'uf', 'telefone1', 'telefone2', 'fax', 'contato'),
    $linhas, array('banco_codigo', 'agencia')) . "\n";

$linhas = array();
$bancos_extra = array();
foreach (ler_json($dir . '/fin_contas_bancarias.json') as $r)
{
    $banco = texto($r['CODBANCO'], 20);
    $agencia = texto($r['AGENCIA'], 25);
    $conta = texto($r['CONTA'], 25);

    if ($banco === NULL || $agencia === NULL || $conta === NULL) { continue; }

    $titular = texto($r['TIT_CONTA'], 120);
    $linhas[] = array(
        $banco, $agencia, $conta, $titular,
        decimal($r['VALOR_LIMITE']), decimal($r['VALOR_REAL']),
    );

    if ( ! isset($bancos_extra[$banco]))
    {
        $bancos_extra[$banco] = $titular ?: $banco;
    }
}
echo '   contas bancarias: ' . gravar_lote($pdo, $T['contas'],
    array('banco_codigo', 'agencia', 'conta', 'titular', 'valor_limite', 'valor_real'),
    $linhas, array('banco_codigo', 'agencia', 'conta')) . "\n";

// Bancos citados em agencias/contas mas ausentes de BANCOS ('dm', 'DMINS').
$existentes = array();
foreach ($pdo->query("SELECT codigo FROM " . $T['bancos']) as $r)
{
    $existentes[$r['codigo']] = TRUE;
}
$linhas = array();
foreach ($bancos_extra as $codigo => $nome)
{
    if ( ! isset($existentes[$codigo]))
    {
        $linhas[] = array($codigo, $nome);
    }
}
if ($linhas)
{
    echo '   bancos complementares: ' . gravar_lote($pdo, $T['bancos'], array('codigo', 'nome'), $linhas, array('codigo')) . "\n";
}

$linhas = array();
foreach (ler_json($dir . '/fin_cartoes.json') as $r)
{
    if (inteiro_zero($r['CODCARTAO']) === NULL) { continue; }
    $linhas[] = array((int) $r['CODCARTAO'], texto($r['NOMCARTAO'], 80));
}
echo '   cartoes: ' . gravar_lote($pdo, $T['cartoes'], array('codigo', 'nome'), $linhas, array('codigo')) . "\n";

$linhas = array();
foreach (ler_json($dir . '/fin_centros_custo.json') as $r)
{
    if (inteiro_zero($r['COD_CUSTO']) === NULL) { continue; }
    $linhas[] = array((int) $r['COD_CUSTO'], texto($r['NOM_CUSTO'], 120));
}
echo '   centros de custo: ' . gravar_lote($pdo, $T['centros'], array('codigo', 'nome'), $linhas, array('codigo')) . "\n";

$linhas = array();
foreach (ler_json($dir . '/fin_naturezas_operacao.json') as $r)
{
    if (inteiro_zero($r['CODOPER']) === NULL) { continue; }
    $linhas[] = array((int) $r['CODOPER'], texto($r['NOMOPER'], 120));
}
echo '   naturezas: ' . gravar_lote($pdo, $T['naturezas'], array('codigo', 'nome'), $linhas, array('codigo')) . "\n";

$linhas = array();
foreach (ler_json($dir . '/fin_status_pagamento.json') as $r)
{
    if (inteiro_zero($r['COD_STATUS']) === NULL) { continue; }
    $linhas[] = array((int) $r['COD_STATUS'], texto($r['NOM_STATUS'], 80));
}
echo '   status: ' . gravar_lote($pdo, $T['status'], array('codigo', 'nome'), $linhas, array('codigo')) . "\n";

$linhas = array();
foreach (ler_json($dir . '/fin_cp_01.json') as $r)
{
    if (inteiro_zero($r['CODPAGTO']) === NULL) { continue; }
    $linhas[] = array((int) $r['CODPAGTO'], texto($r['NOMPAGTO'], 80));
}
echo '   periodicidades: ' . gravar_lote($pdo, $T['periodicidades'], array('codigo', 'nome'), $linhas, array('codigo')) . "\n";

$linhas = array();
foreach (ler_json($dir . '/fin_despesas_fixas.json') as $r)
{
    if (inteiro_zero($r['CODDESPESA']) === NULL) { continue; }
    $linhas[] = array((int) $r['CODDESPESA'], texto($r['NOMEDESPESA'], 120));
}
echo '   despesas fixas: ' . gravar_lote($pdo, $T['despesas'], array('codigo', 'nome'), $linhas, array('codigo')) . "\n";

// ------------------------------------------------------------------ contas a pagar

echo "\n== contas a pagar\n";

$cp = ler_json($dir . '/fin_contas_pagar.json');
$cp_baixas = ler_json($dir . '/fin_contas_pagar_baixas.json');
$cp_log = ler_json($dir . '/fin_contas_pagar_log.json');

$baixas_idx = array();
foreach ($cp_baixas as $r)
{
    $baixas_idx[(int) $r['CODIGO'] . '|' . (int) $r['PARCELA']] = $r;
}

$colunas_cp = array(
    'numero', 'parcela', 'total_parcelas', 'fornecedor_codigo', 'portador_setor',
    'emissao', 'lancamento', 'vencimento', 'valor', 'forma_recebimento',
    'documento_recebimento', 'centro_custo', 'documento', 'situacao', 'historico',
    'origem', 'origem_id', 'usuario_cadastro', 'data_cadastro', 'usuario_baixa',
    'data_baixa', 'valor_baixado', 'tipo', 'banco', 'agencia', 'conta', 'portador',
    'posicao', 'ret_caixa', 'data_caixa', 'saldo_caixa', 'saldo_conta',
    'selec_faturamento', 'faturado', 'usuario_alteracao', 'data_alteracao',
    'cancelado', 'usuario_cancelamento', 'data_cancelamento',
);

$linhas = array();
$cancelados = 0;

foreach ($cp as $r)
{
    $numero = inteiro_zero($r['CODIGO']);
    $parcela = inteiro_zero($r['PARCELA']);

    if ( ! $numero || ! $parcela)
    {
        continue;
    }

    $chave = $numero . '|' . $parcela;
    $baixa = isset($baixas_idx[$chave]) ? $baixas_idx[$chave] : NULL;
    $cancelado = (texto($r['USUARIO_CANCELAMENTO']) !== NULL || texto($r['DATA_CANCELAMENTO']) !== NULL);

    if ($cancelado)
    {
        $situacao = 3;
        $cancelados++;
    }
    elseif ($baixa !== NULL)
    {
        $situacao = 2;
    }
    else
    {
        $situacao = 1;
    }

    $lancamento = data($r['DATALANC']);
    if ($lancamento === NULL)
    {
        $lancamento = data($r['DATAEMI']);
    }

    $data_cadastro = momento($r['DATA_CADASTRO']);
    if ($data_cadastro === NULL)
    {
        $data_cadastro = $lancamento !== NULL ? $lancamento . ' 00:00:00' : date('Y-m-d H:i:s');
    }

    $linhas[] = array(
        $numero,
        $parcela,
        inteiro_zero($r['NUMPARC']) ?: 1,
        inteiro($r['FORNECEDOR']),
        inteiro($r['PORTADOR']),
        data($r['DATAEMI']),
        $lancamento,
        data($r['DATAVENC']),
        decimal_zero($r['VALOR']),
        inteiro($r['FORMA_REC']),
        inteiro($r['DOC_REC']),
        inteiro($r['COD_CC']),
        texto($r['NUMDOC'], 30),
        $situacao,
        texto($r['HISTORICO']),
        'legado',
        (string) $numero,
        texto($r['USUARIO_CADASTRO'], 50),
        $data_cadastro,
        texto($r['USUARIO_BAIXA'], 50),
        momento($r['DATA_BAIXA']),
        $baixa !== NULL ? decimal_zero($baixa['VALOR_RECEBIDO']) : NULL,
        inteiro($r['TIPO']),
        texto($r['BANCO'], 20),
        texto($r['AGENCIA'], 25),
        texto($r['CONTA'], 25),
        inteiro($r['PORTADOR']),
        inteiro($r['POSICAO']),
        b($r['RET_CAIXA']),
        data($r['DAT_CAIXA']),
        decimal($r['SALDO_CAIXA']),
        decimal($r['SALDO_CONTA']),
        b($r['SELEC_FATURAMENTO']),
        b($r['FATURADO']),
        texto($r['USUARIO_ULTIMA_ALTERACAO'], 50),
        momento($r['DATA_ULTIMA_ALTERACAO']),
        $cancelado ? 'true' : 'false',
        texto($r['USUARIO_CANCELAMENTO'], 50),
        momento($r['DATA_CANCELAMENTO']),
    );
}

echo '   titulos: ' . gravar_lote($pdo, $T['cp'], $colunas_cp, $linhas, array('numero', 'parcela')) . "\n";
echo '   cancelados: ' . $cancelados . "\n";

// Mapa (numero, parcela) -> id.
$mapa_cp = array();
foreach ($pdo->query("SELECT id, numero, parcela FROM " . $T['cp']) as $r)
{
    $mapa_cp[(int) $r['numero'] . '|' . (int) $r['parcela']] = (int) $r['id'];
}

$colunas_cpb = array(
    'conta_pagar_id', 'numero', 'parcela', 'data_pagamento', 'multa', 'juros',
    'desconto', 'valor_atualizado', 'valor_pago', 'saldo_caixa', 'saldo_conta',
    'conta_codigo', 'arquivada',
);

$linhas = array();
$orfaos = 0;

foreach ($cp_baixas as $r)
{
    $numero = inteiro_zero($r['CODIGO']);
    $parcela = inteiro_zero($r['PARCELA']);
    $chave = $numero . '|' . $parcela;

    if ( ! isset($mapa_cp[$chave]))
    {
        $orfaos++;
        continue;
    }

    $linhas[] = array(
        $mapa_cp[$chave], $numero, $parcela, data($r['DATA_PAGTO']),
        decimal_zero($r['MULTA']), decimal_zero($r['JUROS']), decimal_zero($r['DESCONTO']),
        decimal_zero($r['VALOR_ATUALIZADO']), decimal_zero($r['VALOR_RECEBIDO']),
        decimal($r['SALDO_CAIXA']), decimal($r['SALDO_CONTA']),
        inteiro_zero($r['COD_CONTA']), 'false',
    );
}

echo '   baixas: ' . gravar_lote($pdo, $T['cp_baixas'], $colunas_cpb, $linhas, array('numero', 'parcela')) . "\n";
if ($orfaos) { echo '   baixas sem titulo (ignoradas): ' . $orfaos . "\n"; }

// Log: CAMPO / CAMPO_OLD -> JSONB {campo: [antes, depois]}.
$pdo->exec("DELETE FROM " . $T['cp_log'] . " WHERE legado_id IS NOT NULL");

$colunas_log = array('legado_id', 'conta_pagar_numero', 'parcela', 'data', 'usuario', 'tipo_mov', 'dados');
$campos_log = array(
    'DATAEMI', 'NUMDOC', 'FORMA_REC', 'DOC_REC', 'DATALANC', 'DATAVENC', 'RET_CAIXA',
    'VALOR', 'BANCO', 'AGENCIA', 'CONTA', 'FORNECEDOR', 'PORTADOR', 'NUMPARC',
    'POSICAO', 'HISTORICO', 'COD_CC', 'USUARIO_CADASTRO', 'DATA_CADASTRO',
    'USUARIO_ULTIMA_ALTERACAO', 'DATA_ULTIMA_ALTERACAO', 'USUARIO_BAIXA',
    'DATA_BAIXA', 'USUARIO_CANCELAMENTO', 'DATA_CANCELAMENTO',
);

$linhas = array();

foreach ($cp_log as $r)
{
    $dados = array();

    foreach ($campos_log as $campo)
    {
        if ( ! array_key_exists($campo, $r) || ! array_key_exists($campo . '_OLD', $r))
        {
            continue;
        }

        $novo = $r[$campo];
        $antes = $r[$campo . '_OLD'];

        if (is_string($novo)) { $novo = texto($novo); }
        if (is_string($antes)) { $antes = texto($antes); }

        if ($novo == $antes)
        {
            continue;
        }

        $dados[$campo] = array($antes, $novo);
    }

    $json_dados = $dados ? json_encode($dados, JSON_UNESCAPED_UNICODE) : NULL;

    if ($json_dados === FALSE)
    {
        $json_dados = NULL;
    }

    $tipo = mb_strtoupper((string) texto($r['TIPOMOV']));

    $linhas[] = array(
        inteiro_zero($r['ID']),
        inteiro_zero($r['CODIGO']),
        inteiro_zero($r['PARCELA']),
        momento($r['DATA']),
        texto($r['USUARIO'], 50),
        in_array($tipo, array('A', 'C', 'I'), TRUE) ? $tipo : NULL,
        $json_dados,
    );
}

echo '   log: ' . gravar_simples($pdo, $T['cp_log'], $colunas_log, $linhas) . "\n";

// ------------------------------------------------------------------ contas a receber

echo "\n== contas a receber\n";

$cr1 = ler_json($dir . '/fin_contas_receber.json');
$cr2 = ler_json($dir . '/fin_contas_receber_baixas.json');
$cr3 = ler_json($dir . '/fin_contas_receber_arq.json');
$cr4 = ler_json($dir . '/fin_contas_receber_baixas_arq.json');

// O codigo e a PK. CONTAS_RECEBER_03 repete 4 codigos em 6 parcelas extras:
// a primeira linha mantem o codigo e as demais recebem codigos novos.
$origem = array();

foreach (array(array($cr1, FALSE), array($cr3, TRUE)) as $bloco)
{
    list($registros, $arquivada) = $bloco;

    foreach ($registros as $r)
    {
        $origem[] = array($r, $arquivada);
    }
}

usort($origem, function ($a, $b) {
    $ca = (int) $a[0]['CODIGO'];
    $cb = (int) $b[0]['CODIGO'];

    if ($ca !== $cb) { return $ca < $cb ? -1 : 1; }

    $pa = (int) $a[0]['PARCELA'];
    $pb = (int) $b[0]['PARCELA'];

    return $pa === $pb ? 0 : ($pa < $pb ? -1 : 1);
});

$max_cr = 0;
foreach ($origem as $o)
{
    $max_cr = max($max_cr, (int) $o[0]['CODIGO']);
}

$vistos = array();
$mapa_cr = array();   // 'codigo|parcela' legado -> codigo novo
$proximo_cr = $max_cr;

foreach ($origem as $o)
{
    $r = $o[0];
    $codigo = (int) $r['CODIGO'];
    $parcela = (int) $r['PARCELA'];
    $chave = $codigo . '|' . $parcela;

    if (isset($vistos[$codigo]))
    {
        $proximo_cr++;
        $novo = $proximo_cr;
    }
    else
    {
        $vistos[$codigo] = TRUE;
        $novo = $codigo;
    }

    $mapa_cr[$chave] = $novo;
}

$colunas_cr = array(
    'codigo', 'parcela', 'data_emissao', 'chamado_numero', 'numero_documento',
    'tipo', 'forma_recebimento_codigo', 'documento_codigo', 'dias',
    'data_vencimento', 'valor', 'desconto_especial', 'vdesp', 'aliq_issqn',
    'viss', 'vliq', 'banco', 'agencia', 'conta', 'cliente_id', 'portador_codigo',
    'num_parcelas', 'posicao', 'historico', 'codigo_centro_custo', 'ret_caixa',
    'selec_faturamento', 'faturado', 'numero_nota_fiscal', 'fechafin',
    'baixa_conta', 'arquivada', 'total_arquivada', 'cliente_nota_id',
    'usuario_cadastro', 'data_cadastro', 'usuario_alteracao', 'data_alteracao',
    'usuario_cancelamento', 'data_cancelamento', 'cancelado', 'usuario_baixa', 'data_baixa',
);

$linhas = array();
$renumerados = 0;

foreach ($origem as $o)
{
    list($r, $arquivada) = $o;

    $codigo_legado = (int) $r['CODIGO'];
    $parcela = (int) $r['PARCELA'];
    $codigo = $mapa_cr[$codigo_legado . '|' . $parcela];

    if ($codigo !== $codigo_legado)
    {
        $renumerados++;
    }

    $cancelado = (texto($r['USUARIO_CANCELAMENTO']) !== NULL || texto($r['DATA_CANCELAMENTO']) !== NULL);

    $data_emissao = data($r['DATAEMI']);
    $data_cadastro = momento($r['DATA_CADASTRO']);
    if ($data_cadastro === NULL)
    {
        $data_cadastro = $data_emissao !== NULL ? $data_emissao . ' 00:00:00' : date('Y-m-d H:i:s');
    }

    $linhas[] = array(
        $codigo,
        $parcela,
        $data_emissao,
        texto($r['NUM_OS'], 16),
        texto($r['NUMDOC'], 20),
        inteiro($r['TIPO']),
        inteiro($r['FORMA_REC']),
        inteiro($r['DOC_REC']),
        inteiro($r['QTDIAS']),
        data($r['DATAVENC']),
        decimal_zero($r['VALOR']),
        decimal_zero($r['DESC_ESP']),
        decimal_zero($r['VDESP']),
        decimal($r['ALIQ_ISSQN']),
        decimal_zero($r['VISS']),
        decimal_zero($r['VLIQ']),
        texto($r['BANCO'], 50),
        texto($r['AGENCIA'], 25),
        texto($r['CONTA'], 20),
        inteiro($r['CLIENTE']),
        inteiro($r['PORTADOR']),
        inteiro_zero($r['NUMPARC']) ?: 1,
        inteiro($r['POSICAO']),
        texto($r['HISTORICO']),
        inteiro($r['COD_CC']),
        b($r['RET_CAIXA']),
        b($r['SELEC_FATURAMENTO']),
        b($r['FATURADO']),
        texto($r['NUM_NOTA_FISCAL'], 30),
        b($r['FECHAFIN']),
        b($r['BAIXA_CONTA']),
        $arquivada ? 'true' : 'false',
        inteiro_zero($r['TOT_ARQ']),
        inteiro($r['CLIENTE_NOTA']),
        texto($r['USUARIO_CADASTRO'], 50),
        $data_cadastro,
        texto($r['USUARIO_ULTIMA_ALTERACAO'], 50),
        momento($r['DATA_ULTIMA_ALTERACAO']),
        texto($r['USUARIO_CANCELAMENTO'], 50),
        momento($r['DATA_CANCELAMENTO']),
        $cancelado ? 'true' : 'false',
        texto($r['USUARIO_BAIXA'], 50),
        momento($r['DATA_BAIXA']),
    );
}

echo '   titulos: ' . gravar_lote($pdo, $T['cr'], $colunas_cr, $linhas, array('codigo')) . "\n";
echo '   renumerados: ' . $renumerados . "\n";
echo '   maior codigo: ' . $proximo_cr . "\n";

// Baixas ativas (CR_02) e arquivadas (CR_04).
$colunas_crb = array(
    'conta_codigo', 'parcela', 'data_pagamento', 'multa', 'juros', 'desconto',
    'valor_atualizado', 'valor_recebido', 'arquivada',
);

$linhas = array();
$orfaos = 0;

foreach (array(array($cr2, FALSE), array($cr4, TRUE)) as $bloco)
{
    list($registros, $arquivada) = $bloco;

    foreach ($registros as $r)
    {
        $codigo = (int) $r['CODIGO'];
        $parcela = (int) $r['PARCELA'];
        $chave = $codigo . '|' . $parcela;

        if ( ! isset($mapa_cr[$chave]))
        {
            $orfaos++;
            continue;
        }

        $linhas[] = array(
            $mapa_cr[$chave], $parcela, data($r['DATA_PAGTO']),
            decimal_zero($r['MULTA']), decimal_zero($r['JUROS']), decimal_zero($r['DESCONTO']),
            decimal_zero($r['VALOR_ATUALIZADO']), decimal_zero($r['VALOR_RECEBIDO']),
            $arquivada ? 'true' : 'false',
        );
    }
}

echo '   baixas: ' . gravar_lote($pdo, $T['cr_baixas'], $colunas_crb, $linhas, array('conta_codigo', 'parcela')) . "\n";
if ($orfaos) { echo '   baixas sem titulo (ignoradas): ' . $orfaos . "\n"; }

// ------------------------------------------------------------------ movimentos bancarios

echo "\n== movimentos bancarios\n";

$mov1 = ler_json($dir . '/fin_mov_bancario.json');
$mov2 = ler_json($dir . '/fin_mov_bancario_02.json');

function chave_mov($r)
{
    return implode('|', array(
        substr((string) $r['DATA'], 0, 10),
        (string) $r['CODBANCO'],
        (string) $r['AGENCIA'],
        (string) $r['CONTA'],
        strtoupper((string) $r['TIPO']),
        number_format((float) $r['VALOR'], 2, '.', ''),
        (string) $r['COD_CP'],
        (string) $r['PARCELA_CP'],
        (string) $r['COD_CR'],
        (string) $r['PARCELA_CR'],
    ));
}

// MOV_02 nao guarda saldos; MOV_01 guarda. Reaproveita o saldo do mesmo
// movimento de negocio.
$saldos = array();
foreach ($mov1 as $r)
{
    $saldos[chave_mov($r)] = array(decimal($r['SALDO_ANTERIOR']), decimal($r['SALDO_ATUAL']));
}

$existentes = array();
foreach ($mov2 as $r)
{
    $existentes[chave_mov($r)] = TRUE;
}

$colunas_mov = array(
    'numero', 'data', 'banco_codigo', 'agencia', 'conta', 'tipo', 'valor',
    'historico', 'conta_pagar_numero', 'conta_pagar_parcela',
    'conta_receber_codigo', 'conta_receber_parcela', 'saldo_anterior',
    'saldo_atual', 'usuario_cadastro', 'data_cadastro', 'usuario_alteracao',
    'data_alteracao',
);

$linhas = array();
$max_mov = 0;
$sem_data = 0;

function linha_mov($r, $numero, $saldos)
{
    $d = data($r['DATA']);

    if ($d === NULL)
    {
        return NULL;
    }

    $tipo = mb_strtoupper(substr((string) texto($r['TIPO']), 0, 1));
    $chave = chave_mov($r);
    $saldo_anterior = decimal($r['SALDO_ANTERIOR']);
    $saldo_atual = decimal($r['SALDO_ATUAL']);

    if ($saldo_anterior === NULL && $saldo_atual === NULL && isset($saldos[$chave]))
    {
        list($saldo_anterior, $saldo_atual) = $saldos[$chave];
    }

    return array(
        $numero,
        $d,
        texto($r['CODBANCO'], 20),
        texto($r['AGENCIA'], 25),
        texto($r['CONTA'], 25),
        in_array($tipo, array('C', 'D'), TRUE) ? $tipo : NULL,
        decimal_zero($r['VALOR']),
        texto($r['HISTORICO']),
        inteiro_zero($r['COD_CP']),
        inteiro_zero($r['PARCELA_CP']),
        inteiro_zero($r['COD_CR']),
        inteiro_zero($r['PARCELA_CR']),
        $saldo_anterior,
        $saldo_atual,
        texto($r['USUARIO_CADASTRO'], 50),
        momento($r['DATA_CADASTRO']),
        texto($r['USUARIO_ULTIMA_ALTERACAO'], 50),
        momento($r['DATA_ULTIMA_ALTERACAO']),
    );
}

foreach ($mov2 as $r)
{
    $numero = (int) $r['NUM_MOV'];

    if ( ! $numero)
    {
        continue;
    }

    $linha = linha_mov($r, $numero, $saldos);

    if ($linha === NULL)
    {
        $sem_data++;
        continue;
    }

    $linhas[] = $linha;
    $max_mov = max($max_mov, $numero);
}

$extras = 0;
$proximo_mov = $max_mov;

foreach ($mov1 as $r)
{
    if (isset($existentes[chave_mov($r)]))
    {
        continue;
    }

    $proximo_mov++;
    $linha = linha_mov($r, $proximo_mov, $saldos);

    if ($linha === NULL)
    {
        $sem_data++;
        continue;
    }

    $linhas[] = $linha;
    $extras++;
}

echo '   base MOV_02: ' . count($mov2) . "\n";
echo '   exclusivos MOV_01: ' . $extras . "\n";
echo '   gravados: ' . gravar_lote($pdo, $T['movimentos'], $colunas_mov, $linhas, array('numero')) . "\n";
echo '   maior numero: ' . $proximo_mov . "\n";
if ($sem_data) { echo '   sem data valida (ignorados): ' . $sem_data . "\n"; }

// ------------------------------------------------------------------ caixa

echo "\n== caixa\n";

$caixa1 = ler_json($dir . '/fin_caixa_diario.json');
$caixa2 = ler_json($dir . '/fin_caixa_movimentos.json');

$colunas_caixa = array(
    'data', 'data_num', 'saldo_anterior', 'saldo_atual', 'total_entradas',
    'total_saidas', 'fechado', 'cheque', 'dinheiro',
);

// Totais por dia. O legado lanca a primeira linha do dia como "saldo anterior"
// dentro das entradas; nesse caso o fechamento e (entradas - saidas). Sem essa
// linha, o fechamento e (saldo de abertura + entradas - saidas).
$caixa_totais = array();

foreach ($caixa2 as $r)
{
    $d = data($r['DATA']);

    if ($d === NULL) { continue; }

    if ( ! isset($caixa_totais[$d]))
    {
        $caixa_totais[$d] = array(0.0, 0.0, FALSE);
    }

    $caixa_totais[$d][0] += decimal_zero($r['VALOR_ENTRADA']);
    $caixa_totais[$d][1] += decimal_zero($r['VALOR_SAIDA']);

    $hist = mb_strtoupper((string) texto($r['HISTÓRICO']));

    if (strpos($hist, 'SALDO ANTERIOR') !== FALSE)
    {
        $caixa_totais[$d][2] = TRUE;
    }
}

$linhas = array();

foreach ($caixa1 as $r)
{
    $d = data($r['DATA']);

    if ($d === NULL) { continue; }

    $abertura = decimal_zero($r['SALDOATUAL']);
    list($entradas, $saidas, $tem_abertura) = isset($caixa_totais[$d])
        ? $caixa_totais[$d]
        : array(0.0, 0.0, FALSE);

    $fechamento = $tem_abertura
        ? $entradas - $saidas
        : $abertura + $entradas - $saidas;

    $fechado = $r['FECHADO'];
    $linhas[] = array(
        $d,
        inteiro_zero($r['DATANUM']),
        $abertura,
        $fechamento,
        $entradas,
        $saidas,
        ($fechado === NULL || $fechado === '') ? NULL : b($fechado),
        decimal($r['CHEQUE']),
        decimal($r['DINHEIRO']),
    );
}

echo '   dias: ' . gravar_lote($pdo, $T['caixa_diario'], $colunas_caixa, $linhas, array('data')) . "\n";

$colunas_caixam = array(
    'data', 'numero', 'data_num', 'setor', 'documento', 'historico',
    'valor_entrada', 'valor_saida', 'chamado_numero', 'forma_recebimento',
    'documento_recebimento', 'usuario_cadastro', 'data_cadastro',
    'usuario_alteracao', 'data_alteracao',
);

$linhas = array();
$sem_data = 0;

foreach ($caixa2 as $r)
{
    $d = data($r['DATA']);
    $numero = inteiro_zero($r['NUMOV']);

    if ($d === NULL || $numero === NULL)
    {
        $sem_data++;
        continue;
    }

    $linhas[] = array(
        $d,
        $numero,
        inteiro_zero($r['DATANUM']),
        inteiro($r['SETOR']),
        texto($r['DOCUMENTO'], 30),
        texto($r['HISTÓRICO']),
        decimal_zero($r['VALOR_ENTRADA']),
        decimal_zero($r['VALOR_SAIDA']),
        texto($r['NUM_OS'], 16),
        inteiro($r['FORMA_REC']),
        inteiro($r['DOC_REC']),
        texto($r['USUARIO_CADASTRO'], 50),
        momento($r['DATA_CADASTRO']),
        texto($r['USUARIO_ULTIMA_ALTERACAO'], 50),
        momento($r['DATA_ULTIMA_ALTERACAO']),
    );
}

echo '   movimentos: ' . gravar_lote($pdo, $T['caixa_movimentos'], $colunas_caixam, $linhas, array('data', 'numero')) . "\n";
if ($sem_data) { echo '   sem data/numero (ignorados): ' . $sem_data . "\n"; }

// ------------------------------------------------------------------ balanco

echo "\n== balanco\n";

$balanco = ler_json($dir . '/fin_balanco.json');

$colunas_bal = array(
    'data_inicio', 'data_fim', 'mes_ano', 'bruto_faturado', 'iss_faturado',
    'multa_faturado', 'juros_faturado', 'desconto_faturado', 'liquido_faturado',
    'bruto_faturar', 'iss_faturar', 'liquido_faturar', 'valor_princ_pago',
    'juros_pago', 'multa_pago', 'desconto_pago', 'bruto_pago',
    'valor_princ_pagar', 'juros_pagar', 'multa_pagar', 'bruto_pagar',
);

$pdo->exec("DELETE FROM " . $T['balanco']);

$linhas = array();

foreach ($balanco as $r)
{
    $linhas[] = array(
        data($r['DATA_INICIO']), data($r['DATA_FIM']), texto($r['MES_ANO'], 7),
        decimal($r['BRUTO_FATURADO']), decimal($r['ISS_FATURADO']),
        decimal($r['MULTA_FATURADO']), decimal($r['JUROS_FATURADO']),
        decimal($r['DESCONTO_FATURADO']), decimal($r['LIQUIDO_FATURADO']),
        decimal($r['BRUTO_FATURAR']), decimal($r['ISS_FATURAR']),
        decimal($r['LIQUIDO_FATURAR']), decimal($r['VALOR_PRINC_PAGO']),
        decimal($r['JUROS_PAGO']), decimal($r['MULTA_PAGO']),
        decimal($r['DESCONTO_PAGO']), decimal($r['BRUTO_PAGO']),
        decimal($r['VALOR_PRINC_PAGAR']), decimal($r['JUROS_PAGAR']),
        decimal($r['MULTA_PAGAR']), decimal($r['BRUTO_PAGAR']),
    );
}

echo '   linhas: ' . gravar_simples($pdo, $T['balanco'], $colunas_bal, $linhas) . "\n";

// ------------------------------------------------------------------ numeracao

echo "\n== numeracao\n";

$sequencias = array(
    'CP' => (int) $pdo->query("SELECT COALESCE(MAX(numero), 0) FROM " . $T['cp'])->fetchColumn(),
    'CR' => (int) $pdo->query("SELECT COALESCE(MAX(codigo), 0) FROM " . $T['cr'])->fetchColumn(),
    'MB' => (int) $pdo->query("SELECT COALESCE(MAX(numero), 0) FROM " . $T['movimentos'])->fetchColumn(),
);

$sql = "INSERT INTO " . $T['numeracao'] . " (tipo, competencia, ultimo, updated_at)
        VALUES (?, '', ?, now())
        ON CONFLICT (tipo, competencia)
        DO UPDATE SET ultimo = GREATEST(" . $T['numeracao'] . ".ultimo, EXCLUDED.ultimo),
                      updated_at = now()";

foreach ($sequencias as $tipo => $ultimo)
{
    $st = $pdo->prepare($sql);
    $st->execute(array($tipo, $ultimo));
    echo '   ' . $tipo . ' = ' . $ultimo . "\n";
}

$pdo->commit();

// ------------------------------------------------------------------ resumo

echo "\n== resumo\n";

$resumo = array(
    'contas a pagar'   => array('cp' => 'titulos', 'cp_baixas' => 'baixas'),
    'contas a receber' => array('cr' => 'titulos', 'cr_baixas' => 'baixas'),
    'movimentos'       => array('movimentos' => 'movimentos'),
    'caixa'            => array('caixa_diario' => 'dias', 'caixa_movimentos' => 'movimentos'),
    'balanco'          => array('balanco' => 'linhas'),
);

foreach ($resumo as $rotulo => $chaves)
{
    foreach ($chaves as $k => $rotulo_item)
    {
        $n = (int) $pdo->query("SELECT count(*) FROM " . $T[$k])->fetchColumn();
        printf("   %-34s %8d\n", $rotulo . ' / ' . $rotulo_item, $n);
    }
}

$totais = $pdo->query(
    "SELECT
        (SELECT COALESCE(sum(valor), 0) FROM " . $T['cp'] . ") AS cp_valor,
        (SELECT COALESCE(sum(valor), 0) FROM " . $T['cp'] . " WHERE situacao = 1) AS cp_aberto,
        (SELECT COALESCE(sum(valor), 0) FROM " . $T['cr'] . " WHERE NOT cancelado) AS cr_valor"
)->fetch(PDO::FETCH_ASSOC);

echo "\n   CP total  : " . number_format((float) $totais['cp_valor'], 2, ',', '.') . "\n";
echo "   CP aberto : " . number_format((float) $totais['cp_aberto'], 2, ',', '.') . "\n";
echo "   CR total  : " . number_format((float) $totais['cr_valor'], 2, ',', '.') . "\n";

echo "\n[ok] carga financeira concluida.\n";
