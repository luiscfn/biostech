<?php
/**
 * BIOSTECH - carga do modulo Orcamentos (comercial + dimensionamento).
 *
 * Fontes (scripts/etl/dados/*.json, exportados do Access por export-orcamentos.ps1):
 *   orcamentos, orcamento_clientes, orcamento_comercial,
 *   orcamento_comercial_c, orcamento_itens, orcamento_servicos_valores,
 *   orcamento_contatos, orcamento_servicos, orcamento_log,
 *   dimensionamentos, dimensionamento_itens, dimensionamento_levantamento,
 *   dimensionamento_levantamento_a, dimensionamento_cotacoes,
 *   dimensionamento_servicos, dimensionamento_materiais, dimensionamento_log
 *
 * Uso:
 *   php scripts/etl/orcamentos.php
 *
 * Regras de normalizacao:
 *   - ORCAMENTO_01B prevalece sobre ORCAMENTO_01C campo a campo
 *     (COALESCE 01B, 01C).
 *   - registros orfaos (NUM_ORC inexistente em ORCAMENTO_01) sao ignorados;
 *   - NUMITEM e renumerado 1..N por orcamento na ordem original
 *     (NUMITEM, CODIGO), garantindo a chave unica da tabela;
 *   - chamado_numero / cliente_id / produto_id so sao gravados quando o
 *     registro referenciado existe (integridade referencial);
 *   - datas do Access anteriores a 1901 viram NULL;
 *   - itens da tabela ORCAMENTO_02 com QTDE <= 0 recebem quantidade 1
 *     (a tabela exige quantidade > 0);
 *   - a carga e idempotente: cabecalhos e itens usam ON CONFLICT e o log
 *     usa o ID legado.
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
date_default_timezone_set('America/Fortaleza');
mb_internal_encoding('UTF-8');

// ORCAMENTO_02 tem 45.914 itens; os JSONs decodificados passam de 200 MB.
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
    if ($valor === NULL || is_bool($valor))
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
    if ($valor === NULL || $valor === '' || $valor === FALSE)
    {
        return NULL;
    }

    return (int) $valor;
}

function decimal($valor)
{
    if ($valor === NULL || $valor === '' || is_bool($valor))
    {
        return NULL;
    }

    return (float) $valor;
}

/** Data ISO (Y-m-d). Datas vazias do Access (1899/1900) viram NULL. */
function data($valor)
{
    $valor = texto($valor);

    if ($valor === NULL)
    {
        return NULL;
    }

    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $valor, $m))
    {
        return ((int) $m[3] < 1901) ? NULL : $m[3] . '-' . $m[2] . '-' . $m[1];
    }

    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $valor, $m))
    {
        return ((int) $m[1] < 1901) ? NULL : $m[1] . '-' . $m[2] . '-' . $m[3];
    }

    return NULL;
}

function hora($valor)
{
    $valor = texto($valor);

    if ($valor === NULL || ! preg_match('/(\d{2}):(\d{2})(?::(\d{2}))?/', $valor, $m))
    {
        return NULL;
    }

    return $m[1] . ':' . $m[2] . ':' . (isset($m[3]) && $m[3] !== '' ? $m[3] : '00');
}

/** Horas decimais (HH:MM:SS -> H.hh). Valores vazios/booleanos viram 0. */
function horas($valor)
{
    if (is_bool($valor) || $valor === NULL || $valor === '')
    {
        return 0;
    }

    if (is_numeric($valor))
    {
        return round((float) $valor, 2);
    }

    if (preg_match('/(\d{1,2}):(\d{2})(?::(\d{2}))?/', (string) $valor, $m))
    {
        $h = (int) $m[1] + ((int) $m[2] / 60) + (isset($m[3]) ? ((int) $m[3] / 3600) : 0);
        return round($h, 2);
    }

    return 0;
}

/** Booleano legado (campos Yes/No e campos texto usados como flag). */
function flag($valor)
{
    if (is_bool($valor))
    {
        return $valor;
    }

    if ($valor === NULL)
    {
        return FALSE;
    }

    $v = mb_strtoupper(trim((string) $valor));

    return ! in_array($v, array('', '0', 'N', 'NAO', 'F', 'FALSE', 'NO'), TRUE);
}

function b($valor)
{
    return flag($valor) ? 'true' : 'false';
}

/** Campo opcional do registro legado. */
function c($r, $k, $default = NULL)
{
    return array_key_exists($k, $r) ? $r[$k] : $default;
}

/** Timestamp (Y-m-d H:i:s) a partir de uma data legada. */
function ts($valor, $fallback = TRUE)
{
    $d = data($valor);

    if ($d === NULL)
    {
        return $fallback ? date('Y-m-d H:i:s') : NULL;
    }

    return $d . ' 00:00:00';
}

function json_seguro($valor)
{
    if (is_array($valor))
    {
        $saida = array();
        foreach ($valor as $k => $v)
        {
            $saida[$k] = json_seguro($v);
        }
        return $saida;
    }

    if (is_string($valor))
    {
        $valor = preg_replace('/[\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}\x{007F}-\x{009F}]/u', ' ', $valor);
        return trim($valor);
    }

    return $valor;
}

/**
 * Insere/atualiza em lote.
 * $conflito NULL gera "ON CONFLICT DO NOTHING" (sem alvo), util para indices
 * unicos parciais.
 */
function gravar_lote(PDO $pdo, $tabela, array $colunas, array $linhas, $conflito = NULL, $atualizar = TRUE, $tamanho = 250)
{
    if (empty($linhas))
    {
        return 0;
    }

    $grupo = '(' . rtrim(str_repeat('?, ', count($colunas)), ', ') . ')';

    if ($conflito === NULL)
    {
        $conflitoSql = 'ON CONFLICT DO NOTHING';
    }
    else
    {
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
    }

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

/** Ordena os itens de um orcamento e renumera 1..N. */
function renumerar(array $itens, $chaveNumero, $chaveOrdem)
{
    $saida = array();

    foreach ($itens as $item)
    {
        $chave = $item[$chaveOrdem];
        $numero = $item[$chaveNumero];
        $saida[$chave][] = array($numero === NULL || $numero === '' ? PHP_INT_MAX : (int) $numero, $item);
    }

    $resultado = array();

    foreach ($saida as $chave => $lista)
    {
        usort($lista, function ($a, $b) {
            return $a[0] === $b[0] ? 0 : ($a[0] < $b[0] ? -1 : 1);
        });

        $n = 0;
        foreach ($lista as $par)
        {
            $resultado[$chave][] = array(++$n, $par[1]);
        }
    }

    return $resultado;
}

$pdo = new PDO("pgsql:host=$host;port=$port;dbname=$name", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("SET datestyle TO 'ISO'");

// ------------------------------------------------------- referencias validas

$produtos = array();
foreach ($pdo->query("SELECT id FROM $schema.biostech_produtos") as $r)
{
    $produtos[(int) $r['id']] = TRUE;
}

$clientes = array();
foreach ($pdo->query("SELECT id FROM $schema.biostech_clientes") as $r)
{
    $clientes[(int) $r['id']] = TRUE;
}

$chamados = array();
foreach ($pdo->query("SELECT numero FROM $schema.biostech_chamados") as $r)
{
    $chamados[(string) $r['numero']] = TRUE;
}

$pdo->beginTransaction();

// --------------------------------------------------------------- orcamentos

$clientesOrc   = array();
$comercial     = array();
$comercialC    = array();

foreach (ler_json($dir . '/orcamento_clientes.json') as $r)
{
    $num = inteiro(c($r, 'NUM_ORC'));

    if ($num !== NULL)
    {
        $clientesOrc[$num] = $r;
    }
}

foreach (ler_json($dir . '/orcamento_comercial.json') as $r)
{
    $num = inteiro(c($r, 'NUM_ORC'));

    if ($num !== NULL)
    {
        $comercial[$num] = $r;
    }
}

foreach (ler_json($dir . '/orcamento_comercial_c.json') as $r)
{
    $num = inteiro(c($r, 'NUM_ORC'));

    if ($num !== NULL && ! isset($comercialC[$num]))
    {
        $comercialC[$num] = $r;
    }
}

$dados = ler_json($dir . '/orcamentos.json');
$linhas = array();
$numeros = array();
$ignorados = 0;

foreach ($dados as $r)
{
    $numero = inteiro(c($r, 'NUM_ORC'));

    if ($numero === NULL || $numero <= 0 || isset($numeros[$numero]))
    {
        $ignorados++;
        continue;
    }

    $numeros[$numero] = TRUE;

    $a = isset($clientesOrc[$numero]) ? $clientesOrc[$numero] : array();
    $b = isset($comercial[$numero]) ? $comercial[$numero] : array();
    $z = isset($comercialC[$numero]) ? $comercialC[$numero] : array();

    // 01B prevalece sobre 01C
    $val = function ($campo) use ($b, $z) {
        if (array_key_exists($campo, $b) && $b[$campo] !== NULL && $b[$campo] !== '')
        {
            return $b[$campo];
        }

        return array_key_exists($campo, $z) ? $z[$campo] : NULL;
    };

    $chamado = texto(c($r, 'NUM_OS'), 16);
    if ($chamado !== NULL && ! isset($chamados[$chamado]))
    {
        $chamado = NULL;
    }

    $cliente = inteiro(c($a, 'COD_CLI'));
    if ($cliente !== NULL && ! isset($clientes[$cliente]))
    {
        $cliente = NULL;
    }

    $situacao = inteiro(c($r, 'SIT_ORC_01'));
    $resultado = inteiro(c($r, 'SIT_ORC_02'));

    $linhas[] = array(
        $numero,
        $chamado,
        data(c($r, 'DATA')) ?: (data(c($r, 'DATA_CADASTRO')) ?: '1901-01-01'),
        $situacao,
        $resultado,
        $resultado === 1 ? 'true' : 'false',
        $resultado === 2 ? 'true' : 'false',
        texto(c($r, 'ATENCAO'), 70),
        data(c($r, 'DATA_APROVACAO')),
        data(c($r, 'DATA_ENVIO')),
        inteiro(c($r, 'NUM_ORC_DIMEN')),
        $cliente,
        texto(c($a, 'COD_EQUIP'), 30),
        texto(c($a, 'NUM_NF_CHEGADA'), 20),
        inteiro($val('FINALIDADE')),
        texto($val('OBSERVACAO')),
        inteiro($val('CODPAG')),
        inteiro($val('ENTREGA')),
        inteiro($val('GARANTIA')),
        inteiro($val('VALIDADE')),
        inteiro($val('VALID_PECAS')),
        texto($val('ASSINA'), 50),
        max(0, (float) (decimal($val('VALMAT')) ?: 0)),
        max(0, (float) (decimal($val('VALMOBRA')) ?: 0)),
        max(0, (float) (decimal($val('VALSERV')) ?: 0)),
        max(0, (float) (decimal($val('VALIMPOSTO')) ?: 0)),
        max(0, (float) (decimal($val('VALDESC')) ?: 0)),
        max(0, (float) (decimal($val('VALTOTAL')) ?: 0)),
        decimal($b ? c($b, 'VALMAT_ORIGINAL') : NULL) ?: 0,
        decimal($b ? c($b, 'MAT_FIXACAO') : NULL) ?: 0,
        decimal($b ? c($b, 'ACRESCIMO_CUSTO') : NULL) ?: 0,
        decimal($b ? c($b, 'VALOR_CUSTOS_INDIRETOS') : NULL) ?: 0,
        decimal($b ? c($b, 'VALOR_MAXIMO_CLIENTE') : NULL) ?: 0,
        decimal($val('DESCPERC')),
        decimal($val('PERCIMP')),
        decimal($b ? c($b, 'PERC_MAO_OBRA') : NULL),
        decimal($b ? c($b, 'PERC_SERVICO') : NULL),
        decimal($b ? c($b, 'PERC_NOTA_FISCAL') : NULL),
        decimal($b ? c($b, 'PERC_MAT_FIXACAO') : NULL),
        decimal($b ? c($b, 'PERC_ACRESCIMO_CUSTO') : NULL),
        decimal($b ? c($b, 'PERC_DESCONTO_ITEM') : NULL),
        decimal($b ? c($b, 'PERC_ACRESCIMO_ITEM') : NULL),
        texto(c($r, 'USUARIO_CADASTRO') ?: 'IMPORTACAO', 50),
        ts(c($r, 'DATA_CADASTRO') ?: c($r, 'DATA')),
        texto(c($r, 'USUARIO_ULTIMA_ALTERACAO'), 50),
        ts(c($r, 'DATA_ULTIMA_ALTERACAO'), FALSE),
        texto(c($r, 'USUARIO_FECHAMENTO'), 50),
        ts(c($r, 'DATA_FECHAMENTO'), FALSE),
    );
}

$n = gravar_lote($pdo, "$schema.biostech_orcamentos", array(
    'numero', 'chamado_numero', 'data', 'situacao', 'resultado', 'aprovado',
    'nao_aprovado', 'atencao', 'data_aprovacao', 'data_envio',
    'dimensionamento_numero', 'cliente_id', 'equipamento_serie',
    'nota_fiscal_chegada', 'finalidade', 'observacao', 'condicao_pagamento',
    'entrega', 'garantia', 'validade', 'validade_pecas', 'assinatura',
    'valor_material', 'valor_mao_obra', 'valor_servico', 'valor_imposto',
    'valor_desconto', 'valor_total', 'valor_material_original',
    'material_fixacao', 'acrescimo_custo', 'valor_custos_indiretos',
    'valor_maximo_cliente', 'percentual_desconto', 'percentual_imposto',
    'percentual_mao_obra', 'percentual_servico', 'percentual_nota_fiscal',
    'percentual_mat_fixacao', 'percentual_acrescimo_custo',
    'percentual_desconto_item', 'percentual_acrescimo_item',
    'usuario_cadastro', 'data_cadastro', 'usuario_alteracao',
    'data_alteracao', 'usuario_fechamento', 'data_fechamento',
), $linhas, array('numero'));

echo "[ok] orcamentos: $n (" . $ignorados . " ignorados)\n";

// ---------------------------------------------------------- itens do orcamento

$dados = ler_json($dir . '/orcamento_itens.json');
$porOrcamento = array();
$ignorados = 0;

foreach ($dados as $r)
{
    $orcamento = inteiro(c($r, 'NUM_ORC'));

    if ($orcamento === NULL || ! isset($numeros[$orcamento]))
    {
        $ignorados++;
        continue;
    }

    $porOrcamento[$orcamento][] = $r;
}

$linhas = array();

foreach ($porOrcamento as $orcamento => $itens)
{
    usort($itens, function ($a, $b) {
        $na = c($a, 'NUMITEM');
        $nb = c($b, 'NUMITEM');
        $na = ($na === NULL || $na === '') ? PHP_INT_MAX : (int) $na;
        $nb = ($nb === NULL || $nb === '') ? PHP_INT_MAX : (int) $nb;

        if ($na !== $nb)
        {
            return $na < $nb ? -1 : 1;
        }

        return (int) c($a, 'CODIGO') < (int) c($b, 'CODIGO') ? -1 : 1;
    });

    $numeroItem = 0;

    foreach ($itens as $r)
    {
        $numeroItem++;

        $produto = inteiro(c($r, 'CODPROD'));
        if ($produto !== NULL && ! isset($produtos[$produto]))
        {
            $produto = NULL;
        }

        $qtde = (int) round((float) (decimal(c($r, 'QTDE')) ?: 0));
        if ($qtde <= 0)
        {
            $qtde = 1;
        }

        $linhas[] = array(
            $orcamento,
            $numeroItem,
            $produto,
            texto(c($r, 'DESCRICAO'), 100),
            texto(c($r, 'DESPROD'), 100),
            $qtde,
            decimal(c($r, 'VALUNIT')) ?: 0,
            decimal(c($r, 'VALTOT')) ?: 0,
            decimal(c($r, 'VALUNIT_ORIGINAL')) ?: 0,
            decimal(c($r, 'VALTOT_ORIGINAL')) ?: 0,
        );
    }
}

$n = gravar_lote($pdo, "$schema.biostech_orcamento_itens", array(
    'orcamento_numero', 'numero_item', 'produto_id', 'descricao',
    'descricao_produto', 'quantidade', 'valor_unitario', 'valor_total',
    'valor_unitario_original', 'valor_total_original',
), $linhas, array('orcamento_numero', 'numero_item'));

echo "[ok] itens: $n (" . $ignorados . " orfaos ignorados)\n";

unset($linhas, $dados, $porOrcamento);

// ------------------------------------------------- servicos a executar (05)

$dados = ler_json($dir . '/orcamento_servicos.json');
$porOrcamento = array();
$ignorados = 0;

foreach ($dados as $r)
{
    $orcamento = inteiro(c($r, 'NUM_ORC'));

    if ($orcamento === NULL || ! isset($numeros[$orcamento]))
    {
        $ignorados++;
        continue;
    }

    $porOrcamento[$orcamento][] = $r;
}

$linhas = array();

foreach ($porOrcamento as $orcamento => $servicos)
{
    usort($servicos, function ($a, $b) {
        return (int) c($a, 'NUMITEM') < (int) c($b, 'NUMITEM') ? -1 : 1;
    });

    $numeroItem = 0;

    foreach ($servicos as $r)
    {
        $numeroItem++;

        $linhas[] = array(
            $orcamento,
            $numeroItem,
            texto(c($r, 'DESC_SERV')) ?: '(sem descricao)',
            b(c($r, 'CONCLUIDO')),
        );
    }
}

$n = gravar_lote($pdo, "$schema.biostech_orcamento_servicos", array(
    'orcamento_numero', 'numero_item', 'descricao', 'concluido',
), $linhas, array('orcamento_numero', 'numero_item'));

echo "[ok] servicos: $n (" . $ignorados . " orfaos ignorados)\n";

unset($linhas, $dados, $porOrcamento);

// ------------------------------------------ servicos com valor (03)

$dados = ler_json($dir . '/orcamento_servicos_valores.json');
$linhas = array();
$ignorados = 0;
$usados = array();

foreach ($dados as $r)
{
    $orcamento = inteiro(c($r, 'NUM_ORC'));
    $numeroItem = inteiro(c($r, 'NUM_ITEM'));

    if ($orcamento === NULL || ! isset($numeros[$orcamento]))
    {
        $ignorados++;
        continue;
    }

    if ($numeroItem === NULL || $numeroItem <= 0 || isset($usados[$orcamento][$numeroItem]))
    {
        $numeroItem = 1;

        while (isset($usados[$orcamento][$numeroItem]))
        {
            $numeroItem++;
        }
    }

    $usados[$orcamento][$numeroItem] = TRUE;

    $qtde = (int) round((float) (decimal(c($r, 'QTDE')) ?: 0));

    $linhas[] = array(
        $orcamento,
        $numeroItem,
        texto(c($r, 'DES_ITEM')) ?: '(sem descricao)',
        $qtde > 0 ? $qtde : 1,
        decimal(c($r, 'VALUNIT')) ?: 0,
        decimal(c($r, 'VALTOT')) ?: 0,
    );
}

$n = gravar_lote($pdo, "$schema.biostech_orcamento_servicos_valores", array(
    'orcamento_numero', 'numero_item', 'descricao', 'quantidade',
    'valor_unitario', 'valor_total',
), $linhas, array('orcamento_numero', 'numero_item'));

echo "[ok] servicos com valor: $n (" . $ignorados . " orfaos ignorados)\n";

// ------------------------------------------------------------- contatos (04)

$pdo->exec("DELETE FROM $schema.biostech_orcamento_contatos");

$dados = ler_json($dir . '/orcamento_contatos.json');
$linhas = array();

foreach ($dados as $r)
{
    $orcamento = inteiro(c($r, 'NUM_ORC'));

    if ($orcamento === NULL || ! isset($numeros[$orcamento]))
    {
        continue;
    }

    $linhas[] = array(
        $orcamento,
        data(c($r, 'DATAMOV')) ?: '1901-01-01',
        hora(c($r, 'HORAMOV')),
        texto(c($r, 'ASSUNTO'), 80),
        inteiro(c($r, 'CODIFUNC')),
        texto(c($r, 'CONTATO'), 50),
        texto(c($r, 'DESCRICAO')),
        data(c($r, 'DATA_PROX_CONT')),
        hora(c($r, 'HORA_PROX_CONT')),
    );
}

$n = gravar_lote($pdo, "$schema.biostech_orcamento_contatos", array(
    'orcamento_numero', 'data_mov', 'hora_mov', 'assunto', 'funcionario_id',
    'contato', 'descricao', 'data_proximo_contato', 'hora_proximo_contato',
), $linhas, NULL);

echo "[ok] contatos: $n\n";

// ----------------------------------------------------------------- log (01)

$camposLog = array(
    'DATA', 'APROVADO', 'NAPROVADO', 'NUM_OS', 'ATENCAO', 'DATA_APROVACAO',
    'DATA_ENVIO', 'SIT_ORC_01', 'SIT_ORC_02', 'USUARIO_CADASTRO',
    'DATA_CADASTRO', 'USUARIO_ULTIMA_ALTERACAO', 'DATA_ULTIMA_ALTERACAO',
);

$dados = ler_json($dir . '/orcamento_log.json');
$linhas = array();

foreach ($dados as $r)
{
    $orcamento = inteiro(c($r, 'NUM_ORC'));

    if ($orcamento === NULL)
    {
        continue;
    }

    $alteracoes = array();

    foreach ($camposLog as $campo)
    {
        $novo = c($r, $campo);
        $antigo = c($r, $campo . '_OLD');

        if (texto($novo) === texto($antigo))
        {
            continue;
        }

        $alteracoes[$campo] = array(
            'de'   => texto($antigo),
            'para' => texto($novo),
        );
    }

    $campoUnico = count($alteracoes) === 1 ? key($alteracoes) : NULL;

    $linhas[] = array(
        inteiro(c($r, 'ID')),
        $orcamento,
        ts(c($r, 'DATAMOV')),
        texto(c($r, 'USUARIO'), 50),
        texto(c($r, 'TIPOMOV'), 1) ?: 'A',
        $campoUnico,
        $campoUnico !== NULL ? $alteracoes[$campoUnico]['de'] : NULL,
        $campoUnico !== NULL ? $alteracoes[$campoUnico]['para'] : NULL,
        json_encode(json_seguro(array(
            'origem'      => 'etl',
            'legado_id'   => inteiro(c($r, 'ID')),
            'alteracoes'  => $alteracoes,
        )), JSON_UNESCAPED_UNICODE),
    );
}

$n = gravar_lote($pdo, "$schema.biostech_orcamento_log", array(
    'legado_id', 'orcamento_numero', 'data_mov', 'usuario', 'tipo_mov',
    'campo', 'valor_anterior', 'valor_novo', 'dados',
), $linhas, NULL);

echo "[ok] log de orcamentos: $n\n";

// ------------------------------------------------------------ dimensionamento

$dados = ler_json($dir . '/dimensionamentos.json');
$levantamento = array();

foreach (ler_json($dir . '/dimensionamento_levantamento.json') as $r)
{
    $num = inteiro(c($r, 'NUM_PLA'));

    if ($num !== NULL)
    {
        $levantamento[$num] = $r;
    }
}

$levantamentoA = array();

foreach (ler_json($dir . '/dimensionamento_levantamento_a.json') as $r)
{
    $num = inteiro(c($r, 'NUM_PLA'));

    if ($num !== NULL)
    {
        $levantamentoA[$num] = $r;
    }
}

$numerosDim = array();
$linhas = array();

foreach ($dados as $r)
{
    $numero = inteiro(c($r, 'NUM_PLA'));

    if ($numero === NULL || $numero <= 0 || isset($numerosDim[$numero]))
    {
        continue;
    }

    $numerosDim[$numero] = TRUE;

    $l  = isset($levantamento[$numero]) ? $levantamento[$numero] : array();
    $la = isset($levantamentoA[$numero]) ? $levantamentoA[$numero] : array();

    $v = function ($campo) use ($l, $la) {
        if (array_key_exists($campo, $l) && $l[$campo] !== NULL && $l[$campo] !== '')
        {
            return $l[$campo];
        }

        return array_key_exists($campo, $la) ? $la[$campo] : NULL;
    };

    $chamado = texto(c($r, 'NUM_OS'), 16);
    if ($chamado !== NULL && ! isset($chamados[$chamado]))
    {
        $chamado = NULL;
    }

    $orcamento = inteiro(c($r, 'NUM_ORC'));
    if ($orcamento !== NULL && ! isset($numeros[$orcamento]))
    {
        $orcamento = NULL;
    }

    $cliente = inteiro(c($r, 'COD_CLI'));
    if ($cliente !== NULL && ! isset($clientes[$cliente]))
    {
        $cliente = NULL;
    }

    $linhas[] = array(
        $numero,
        data(c($r, 'DATA')) ?: '1901-01-01',
        $chamado,
        $orcamento,
        $cliente,
        texto(c($r, 'CONTATO'), 30),
        inteiro(c($r, 'TECNICO')),
        texto(c($r, 'LOCAL_INST')),
        texto(c($r, 'TIPO_CONTATO'), 15),
        texto(c($r, 'SETOR'), 30),
        texto(c($r, 'EMAIL'), 100),
        inteiro(c($r, 'QTDE_TECNICOS')),
        decimal(c($r, 'QTD_COMERCIAIS')) ?: 0,
        decimal(c($r, 'QTD_NAO_COMERCIAIS')) ?: 0,
        texto(c($r, 'SOLICITANTE'), 50),
        texto(c($r, 'USUARIO'), 50),
        texto(c($r, 'USUARIO_CADASTRO') ?: 'IMPORTACAO', 50),
        ts(c($r, 'DATA_CADASTRO') ?: c($r, 'DATA')),
        texto(c($r, 'USUARIO_ULTIMA_ALTERACAO'), 50),
        ts(c($r, 'DATA_ULTIMA_ALTERACAO'), FALSE),
        texto(c($r, 'USUARIO_FECHAMENTO'), 50),
        ts(c($r, 'DATA_FECHAMENTO'), FALSE),
        // levantamento / cronograma (ORCAM_DIMEN_03 + 03A)
        inteiro($v('QTDE_DIAS')),
        texto($v('QUE_FUNCIONA')),
        texto($v('QUER_FAZER')),
        texto($v('DIFICULDADES')),
        texto($v('MAT_PRECISA')),
        texto($v('PROVIDENCIAR')),
        texto($v('OBSERV')),
        decimal($v('ALTURA')),
        decimal($v('CUMPRIM_MURO')),
        decimal($v('LADO_DIREITO')),
        decimal($v('LADO_ESQUERDO')),
        decimal($v('FRENTE')),
        decimal($v('FUNDO')),
        b($v('REBOCADO')),
        b($v('CERCA_ARAME')),
        b($v('TELHADO')),
        b($v('ARVORE')),
        b($v('CINTA_CIMENTO')),
        b($v('PEGA_LADRAO')),
        decimal($v('PERC_MAT_FIXACAO')),
        decimal($v('MAT_FIXACAO')) ?: 0,
        decimal($v('PERC_MAO_OBRA')),
        decimal($v('MAO_OBRA')) ?: 0,
        decimal($v('PERC_NOTA_FISCAL')),
        decimal($v('NOTA')) ?: 0,
        decimal($v('PERC_ACRESCIMO_CUSTO')),
        decimal($v('ACRESCIMO_CUSTO')) ?: 0,
        b($v('SEGUNDA')),
        horas($v('HORA_SEG_01')),
        horas($v('HORA_SEG_02')),
        b($v('TERCA')),
        horas($v('HORA_TER_01')),
        horas($v('HORA_TER_02')),
        b($v('QUARTA')),
        horas($v('HORA_QUA_01')),
        horas($v('HORA_QUA_02')),
        b($v('QUINTA')),
        horas($v('HORA_QUI_01')),
        horas($v('HORA_QUI_02')),
        b($v('SEXTA')),
        horas($v('HORA_SEX_01')),
        horas($v('HORA_SEX_02')),
        b($v('SABADO')),
        horas($v('HORA_SAB_01')),
        horas($v('HORA_SAB_02')),
        b($v('DOMINGO')),
        horas($v('HORA_DOM_01')),
        horas($v('HORA_DOM_02')),
    );
}

$n = gravar_lote($pdo, "$schema.biostech_orcamentos_dimensionamento", array(
    'numero', 'data', 'chamado_numero', 'orcamento_numero', 'cliente_id',
    'contato', 'tecnico_id', 'local_instalacao', 'tipo_contato', 'setor',
    'email', 'qtd_tecnicos', 'qtd_comerciais', 'qtd_nao_comerciais',
    'solicitante', 'usuario', 'usuario_cadastro', 'data_cadastro',
    'usuario_alteracao', 'data_alteracao', 'usuario_fechamento',
    'data_fechamento',
    'qtd_dias', 'que_funciona', 'quer_fazer', 'dificuldades',
    'material_precisa', 'providenciar', 'observacao', 'altura',
    'comprimento_muro', 'lado_direito', 'lado_esquerdo', 'frente', 'fundo',
    'rebocado', 'cerca_arame', 'telhado', 'arvore', 'cinta_cimento',
    'pega_ladrao', 'percentual_mat_fixacao', 'material_fixacao',
    'percentual_mao_obra', 'mao_obra', 'percentual_nota_fiscal',
    'nota_fiscal', 'percentual_acrescimo_custo', 'acrescimo_custo',
    'segunda', 'segunda_inicio', 'segunda_fim', 'terca', 'terca_inicio',
    'terca_fim', 'quarta', 'quarta_inicio', 'quarta_fim', 'quinta',
    'quinta_inicio', 'quinta_fim', 'sexta', 'sexta_inicio', 'sexta_fim',
    'sabado', 'sabado_inicio', 'sabado_fim', 'domingo', 'domingo_inicio',
    'domingo_fim',
), $linhas, array('numero'));

echo "[ok] dimensionamentos: $n\n";

// ---------------------------------------------- itens do dimensionamento (02)

$dados = ler_json($dir . '/dimensionamento_itens.json');
$porDim = array();
$ignorados = 0;

foreach ($dados as $r)
{
    $dim = inteiro(c($r, 'NUM_PLA'));

    if ($dim === NULL || ! isset($numerosDim[$dim]))
    {
        $ignorados++;
        continue;
    }

    $porDim[$dim][] = $r;
}

$linhas = array();

foreach ($porDim as $dim => $itens)
{
    usort($itens, function ($a, $b) {
        $na = c($a, 'NUMITEM');
        $nb = c($b, 'NUMITEM');
        $na = ($na === NULL || $na === '') ? PHP_INT_MAX : (int) $na;
        $nb = ($nb === NULL || $nb === '') ? PHP_INT_MAX : (int) $nb;

        return $na === $nb ? 0 : ($na < $nb ? -1 : 1);
    });

    $numeroItem = 0;

    foreach ($itens as $r)
    {
        $numeroItem++;

        $produto = inteiro(c($r, 'CODPROD'));
        if ($produto !== NULL && ! isset($produtos[$produto]))
        {
            $produto = NULL;
        }

        $linhas[] = array(
            $dim,
            $numeroItem,
            $produto,
            texto(c($r, 'DESCRICAO'), 80),
            texto(c($r, 'DESPROD'), 100),
            (int) round((float) (decimal(c($r, 'QTDE')) ?: 0)),
            decimal(c($r, 'VALOR_COMPRA')) ?: 0,
            decimal(c($r, 'VALOR_GASTO')) ?: 0,
            decimal(c($r, 'TOTAL_COMPRA')) ?: 0,
            decimal(c($r, 'VALOR_VENDA')) ?: 0,
            decimal(c($r, 'TOTAL_VENDA')) ?: 0,
            texto(c($r, 'GRUPO'), 3),
            texto(c($r, 'SUB_GRUPO'), 3),
            texto(c($r, 'PG'), 3),
            texto(c($r, 'PSG'), 3),
        );
    }
}

$n = gravar_lote($pdo, "$schema.biostech_orcamento_dimensionamento_itens", array(
    'dimensionamento_numero', 'numero_item', 'produto_id', 'descricao',
    'descricao_produto', 'quantidade', 'valor_compra', 'valor_gasto',
    'total_compra', 'valor_venda', 'total_venda', 'grupo', 'subgrupo', 'pg',
    'psg',
), $linhas, array('dimensionamento_numero', 'numero_item'));

echo "[ok] itens do dimensionamento: $n (" . $ignorados . " orfaos ignorados)\n";

unset($linhas, $dados, $porDim);

// -------------------------------------- servicos do dimensionamento (06)

$dados = ler_json($dir . '/dimensionamento_servicos.json');
$porDim = array();

foreach ($dados as $r)
{
    $dim = inteiro(c($r, 'NUM_PLA'));

    if ($dim === NULL || ! isset($numerosDim[$dim]))
    {
        continue;
    }

    $porDim[$dim][] = $r;
}

$linhas = array();

foreach ($porDim as $dim => $servicos)
{
    usort($servicos, function ($a, $b) {
        return (int) c($a, 'NUMITEM') < (int) c($b, 'NUMITEM') ? -1 : 1;
    });

    $numeroItem = 0;

    foreach ($servicos as $r)
    {
        $numeroItem++;

        $linhas[] = array(
            $dim,
            $numeroItem,
            texto(c($r, 'DESC_SERV')) ?: '(sem descricao)',
            b(c($r, 'CONCLUIDO')),
        );
    }
}

$n = gravar_lote($pdo, "$schema.biostech_orcamento_dimensionamento_servicos", array(
    'dimensionamento_numero', 'numero_item', 'descricao', 'concluido',
), $linhas, array('dimensionamento_numero', 'numero_item'));

echo "[ok] servicos do dimensionamento: $n\n";

// -------------------------------------- materiais do dimensionamento (07)

$dados = ler_json($dir . '/dimensionamento_materiais.json');
$linhas = array();

foreach ($dados as $r)
{
    $dim = inteiro(c($r, 'NUM_PLA'));

    if ($dim === NULL || ! isset($numerosDim[$dim]))
    {
        continue;
    }

    $linhas[] = array(
        $dim,
        inteiro(c($r, 'NUMITEM')) ?: 1,
        texto(c($r, 'DESC_MAT')) ?: '(sem descricao)',
        decimal(c($r, 'QTDE')) ?: 0,
    );
}

$n = gravar_lote($pdo, "$schema.biostech_orcamento_dimensionamento_materiais", array(
    'dimensionamento_numero', 'numero_item', 'descricao', 'quantidade',
), $linhas, array('dimensionamento_numero', 'numero_item'));

echo "[ok] materiais do dimensionamento: $n\n";

// --------------------------------------- cotacoes do dimensionamento (04)

$dados = ler_json($dir . '/dimensionamento_cotacoes.json');
$linhas = array();

foreach ($dados as $r)
{
    $dim = inteiro(c($r, 'NUM_PLA'));

    if ($dim === NULL || ! isset($numerosDim[$dim]))
    {
        continue;
    }

    $produto = inteiro(c($r, 'CODPROD'));
    if ($produto !== NULL && ! isset($produtos[$produto]))
    {
        $produto = NULL;
    }

    $linhas[] = array(
        $dim,
        $produto,
        inteiro(c($r, 'CODFOR')),
        texto(c($r, 'DES_PROD'), 100),
        data(c($r, 'DATA')) ?: '1901-01-01',
        decimal(c($r, 'PRECO_UNIT')) ?: 0,
    );
}

$n = gravar_lote($pdo, "$schema.biostech_orcamento_dimensionamento_cotacoes", array(
    'dimensionamento_numero', 'produto_id', 'fornecedor_codigo',
    'descricao_produto', 'data', 'preco_unitario',
), $linhas, NULL);

echo "[ok] cotacoes do dimensionamento: $n\n";

// -------------------------------------------- log do dimensionamento (log)

$camposLog = array(
    'NUM_PLA', 'DATA', 'NUM_OS', 'NUM_ORC', 'COD_CLI', 'CONTATO', 'TECNICO',
    'LOCAL_INST', 'TIPO_CONTATO', 'SETOR', 'EMAIL', 'QTDE_TECNICOS',
    'QTD_COMERCIAIS', 'QTD_NAO_COMERCIAIS', 'SOLICITANTE',
    'USUARIO_ULTIMA_ALTERACAO', 'DATA_ULTIMA_ALTERACAO',
);

$dados = ler_json($dir . '/dimensionamento_log.json');
$linhas = array();

foreach ($dados as $r)
{
    $dim = inteiro(c($r, 'NUM_PLA'));

    if ($dim === NULL)
    {
        continue;
    }

    $alteracoes = array();

    foreach ($camposLog as $campo)
    {
        $novo = c($r, $campo);
        $antigo = c($r, $campo . '_OLD');

        if (texto($novo) === texto($antigo))
        {
            continue;
        }

        $alteracoes[$campo] = array(
            'de'   => texto($antigo),
            'para' => texto($novo),
        );
    }

    $campoUnico = count($alteracoes) === 1 ? key($alteracoes) : NULL;

    $linhas[] = array(
        inteiro(c($r, 'ID')),
        $dim,
        ts(c($r, 'DATAMOV')),
        texto(c($r, 'USUARIO'), 50),
        texto(c($r, 'TIPOMOV'), 1) ?: 'A',
        $campoUnico,
        $campoUnico !== NULL ? $alteracoes[$campoUnico]['de'] : NULL,
        $campoUnico !== NULL ? $alteracoes[$campoUnico]['para'] : NULL,
        json_encode(json_seguro(array(
            'origem'     => 'etl',
            'legado_id'  => inteiro(c($r, 'ID')),
            'alteracoes' => $alteracoes,
        )), JSON_UNESCAPED_UNICODE),
    );
}

$n = gravar_lote($pdo, "$schema.biostech_orcamento_dimensionamento_log", array(
    'legado_id', 'dimensionamento_numero', 'data_mov', 'usuario', 'tipo_mov',
    'campo', 'valor_anterior', 'valor_novo', 'dados',
), $linhas, NULL);

echo "[ok] log do dimensionamento: $n\n";

// --------------------------------------------------------------- totais
// O Access nao gravava VALTOTAL ao alterar os itens (1.939 orcamentos com
// itens ficaram com total zerado) e alguns orcamentos so existem em
// ORCAMENTO_01, sem a linha comercial com VALMAT. Reconstroi apenas o que
// esta faltando, preservando os valores gravados no legado.

$n = $pdo->exec(
    "WITH itens AS (
         SELECT orcamento_numero, SUM(valor_total) AS total
           FROM $schema.biostech_orcamento_itens
          GROUP BY orcamento_numero
     ),
     servicos AS (
         SELECT orcamento_numero, SUM(valor_total) AS total
           FROM $schema.biostech_orcamento_servicos_valores
          GROUP BY orcamento_numero
     ),
     calc AS (
         SELECT o.numero,
                CASE WHEN o.valor_material = 0 AND COALESCE(i.total, 0) > 0
                     THEN ROUND(i.total, 2) ELSE o.valor_material END AS material,
                CASE WHEN o.valor_servico = 0 AND COALESCE(s.total, 0) > 0
                     THEN ROUND(s.total, 2) ELSE o.valor_servico END AS servico
           FROM $schema.biostech_orcamentos o
           LEFT JOIN itens i ON i.orcamento_numero = o.numero
           LEFT JOIN servicos s ON s.orcamento_numero = o.numero
     )
     UPDATE $schema.biostech_orcamentos o
        SET valor_material = c.material,
            valor_servico  = c.servico,
            valor_total    = CASE WHEN o.valor_total = 0
                                  THEN GREATEST(0, ROUND(c.material + o.valor_mao_obra
                                       + c.servico + o.valor_imposto - o.valor_desconto, 2))
                                  ELSE o.valor_total END
       FROM calc c
      WHERE c.numero = o.numero
        AND (o.valor_material IS DISTINCT FROM c.material
          OR o.valor_servico IS DISTINCT FROM c.servico
          OR (o.valor_total = 0
              AND (c.material + o.valor_mao_obra + c.servico
                   + o.valor_imposto - o.valor_desconto) > 0))"
);

echo "[ok] totais reconstruidos: $n\n";

// -------------------------------------------------------------- numeracao

$ultimo = (int) $pdo->query("SELECT COALESCE(MAX(numero), 0) FROM $schema.biostech_orcamentos")->fetchColumn();

$st = $pdo->prepare(
    "INSERT INTO $schema.biostech_numeracao (tipo, competencia, ultimo)
     VALUES ('ORC', '', ?)
     ON CONFLICT (tipo, competencia) DO UPDATE SET
        ultimo = GREATEST($schema.biostech_numeracao.ultimo, EXCLUDED.ultimo),
        updated_at = now()"
);
$st->execute(array($ultimo));
echo "[ok] numeracao ORC: $ultimo\n";

$ultimoDim = (int) $pdo->query("SELECT COALESCE(MAX(numero), 0) FROM $schema.biostech_orcamentos_dimensionamento")->fetchColumn();

$st = $pdo->prepare(
    "INSERT INTO $schema.biostech_numeracao (tipo, competencia, ultimo)
     VALUES ('DIM', '', ?)
     ON CONFLICT (tipo, competencia) DO UPDATE SET
        ultimo = GREATEST($schema.biostech_numeracao.ultimo, EXCLUDED.ultimo),
        updated_at = now()"
);
$st->execute(array($ultimoDim));
echo "[ok] numeracao DIM: $ultimoDim\n";

$pdo->commit();

// ------------------------------------------------------------------- resumo

echo "\n== resumo ==\n";
foreach (array(
    'biostech_orcamentos'                              => 'orcamentos',
    'biostech_orcamento_itens'                         => 'itens',
    'biostech_orcamento_servicos'                      => 'servicos a executar',
    'biostech_orcamento_servicos_valores'              => 'servicos com valor',
    'biostech_orcamento_contatos'                      => 'contatos',
    'biostech_orcamento_log'                           => 'log',
    'biostech_orcamentos_dimensionamento'              => 'dimensionamentos',
    'biostech_orcamento_dimensionamento_itens'         => 'itens do dimensionamento',
    'biostech_orcamento_dimensionamento_servicos'      => 'servicos do dimensionamento',
    'biostech_orcamento_dimensionamento_materiais'     => 'materiais',
    'biostech_orcamento_dimensionamento_cotacoes'      => 'cotacoes',
    'biostech_orcamento_dimensionamento_log'           => 'log do dimensionamento',
) as $tabela => $rotulo)
{
    $total = $pdo->query("SELECT count(*) FROM $schema.$tabela")->fetchColumn();
    printf("%-32s %7d\n", $rotulo, $total);
}
