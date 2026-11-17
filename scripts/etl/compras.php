<?php
/**
 * BIOSTECH - carga do modulo Compras.
 *
 * Fontes (scripts/etl/dados/*.json, exportados do Access por export-compras.ps1):
 *   compras_pedidos, compras_itens, compras_cotacoes, compras_cotacao_itens,
 *   compras_prazos, compras_solicitacoes, compras_solicitacao_itens,
 *   compras_log_pedidos, compras_log_solicitacoes
 *
 * Uso:
 *   php scripts/etl/compras.php
 *
 * A carga e idempotente: cabecalhos sao atualizados (ON CONFLICT) e os itens
 * sao inseridos/atualizados pela chave natural. As gravacoes sao em lote para
 * reduzir a latencia de rede com o PostgreSQL.
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

    // Textos herdados do Access podem trazer bytes invalidos ou controles C1.
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
    if ($valor === NULL || $valor === '' || (float) $valor == 0)
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
        if ((int) $m[3] < 1901)
        {
            return NULL;
        }

        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }

    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $valor, $m))
    {
        if ((int) $m[1] < 1901)
        {
            return NULL;
        }

        return $m[1] . '-' . $m[2] . '-' . $m[3];
    }

    return NULL;
}

/** Hora (HH:MM:SS). */
function hora($valor)
{
    $valor = texto($valor);

    if ($valor === NULL)
    {
        return NULL;
    }

    if (preg_match('/(\d{2}):(\d{2})(?::(\d{2}))?/', $valor, $m))
    {
        return $m[1] . ':' . $m[2] . ':' . (isset($m[3]) && $m[3] !== '' ? $m[3] : '00');
    }

    return NULL;
}

function sim($valor)
{
    return is_string($valor) && mb_strtoupper(trim($valor)) === 'S';
}

/** Booleano no formato aceito pelo PostgreSQL via PDO ('true'/'false'). */
function b($valor)
{
    return sim($valor) ? 'true' : 'false';
}

/**
 * Insere/atualiza em lote.
 *
 * @param PDO    $pdo
 * @param string $tabela   nome qualificado
 * @param array  $colunas  lista de colunas
 * @param array  $linhas   lista de arrays de valores (na ordem das colunas)
 * @param array  $conflito colunas da chave para ON CONFLICT
 * @param bool   $atualizar TRUE atualiza as demais colunas; FALSE faz DO NOTHING
 * @param int    $tamanho  linhas por comando
 */
function gravar_lote(PDO $pdo, $tabela, array $colunas, array $linhas, array $conflito, $atualizar = TRUE, $tamanho = 250)
{
    if (empty($linhas))
    {
        return 0;
    }

    $grupo  = '(' . rtrim(str_repeat('?, ', count($colunas)), ', ') . ')';

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

function json_detalhe($r)
{
    $detalhe = array();

    foreach ($r as $campo => $valor)
    {
        if (in_array($campo, array('ID', 'DATAMOV', 'USUARIO', 'TIPOMOV', 'NUMREQ'), TRUE))
        {
            continue;
        }

        if ($valor !== NULL && $valor !== '')
        {
            if (is_string($valor))
            {
                $valor = texto($valor);

                if ($valor === NULL)
                {
                    continue;
                }
            }

            $detalhe[$campo] = $valor;
        }
    }

    return $detalhe ? json_encode($detalhe, JSON_UNESCAPED_UNICODE) : NULL;
}

$pdo = new PDO("pgsql:host=$host;port=$port;dbname=$name", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("SET datestyle TO 'ISO'");

// Produtos existentes (os itens legados podem citar produtos nao migrados).
$produtos = array();
foreach ($pdo->query("SELECT id FROM $schema.biostech_produtos") as $r)
{
    $produtos[(int) $r['id']] = TRUE;
}

$pdo->beginTransaction();

// ------------------------------------------------------------------- pedidos

$dados = ler_json($dir . '/compras_pedidos.json');
$linhas = array();
$ignorados = 0;

foreach ($dados as $r)
{
    $numero = inteiro_zero($r['NUMREQ']);

    if ( ! $numero)
    {
        $ignorados++;
        continue;
    }

    $linhas[] = array(
        $numero,
        data($r['DATA']) ?: date('Y-m-d'),
        hora($r['HORA']),
        inteiro($r['SETOR']),
        inteiro($r['FUNC']),
        inteiro($r['COD_FORN']),
        inteiro_zero($r['TIPO']),
        sim($r['FECHAMENTO']) ? 'fechado' : 'aberto',
        inteiro($r['SOLIC_COMPRA']),
        texto($r['OBSERV_GERAL']),
        texto($r['LOCAL_ENTREGA']),
        texto($r['RESP_RECEBIMENTO'], 80),
        texto($r['FONE_RESP_REC'], 20),
        texto($r['NUM_PROPOSTA'], 30),
        decimal($r['DESCONTO']) ?: 0,
        decimal($r['DESC_PERC']),
        inteiro($r['CODPAG']),
        inteiro($r['ENTREGA']),
        inteiro($r['FORMA_REC']),
        inteiro($r['DOC_REC']),
        inteiro($r['COD_CC']),
        data($r['DATAVENC']),
        texto($r['NUM_NF_FORNEC'], 30),
        data($r['DATA_NF_FORNEC']),
        inteiro($r['COD_CP']),
        texto($r['USUARIO_CADASTRO'] ?: $r['USUARIO'], 50),
        ($d = data($r['DATA_CADASTRO'])) ? $d . ' 00:00:00' : date('Y-m-d H:i:s'),
        texto($r['USUARIO_FECHAMENTO'], 50),
        ($d = data($r['DATA_FECHAMENTO'])) ? $d . ' 00:00:00' : NULL,
    );
}

$n = gravar_lote($pdo, "$schema.biostech_compras_pedidos", array(
    'numero', 'data', 'hora', 'setor_id', 'funcionario_id', 'fornecedor_codigo',
    'tipo', 'situacao', 'solicitacao_numero', 'observacao', 'local_entrega',
    'responsavel_recebimento', 'fone_responsavel', 'numero_proposta', 'desconto',
    'desconto_percentual', 'condicao_pagamento', 'prazo_entrega',
    'forma_recebimento', 'documento_recebimento', 'centro_custo',
    'data_vencimento', 'num_nf', 'data_nf', 'conta_pagar_numero',
    'usuario_cadastro', 'data_cadastro', 'usuario_fechamento', 'data_fechamento',
), $linhas, array('numero'));

echo "[ok] pedidos: $n (" . $ignorados . " sem numero ignorados)\n";

// --------------------------------------------------------------------- itens

$dados = ler_json($dir . '/compras_itens.json');
$linhas = array();
$ignorados = 0;

foreach ($dados as $r)
{
    $pedido  = inteiro_zero($r['NUMREQ']);
    $produto = inteiro($r['COD_PROD']);
    $qtde    = decimal($r['QTDE']);

    if ( ! $pedido || ! $produto || ! isset($produtos[$produto]) || $qtde === NULL || $qtde <= 0)
    {
        $ignorados++;
        continue;
    }

    $linhas[] = array(
        $pedido,
        $produto,
        $qtde,
        decimal($r['VALUNIT']) ?: 0,
        decimal($r['NEW_VALUNIT']),
        decimal($r['DESC_PERC']),
        decimal($r['DESC_ITEM']) ?: 0,
        decimal($r['VALTOT']) ?: 0,
        texto($r['OBS_PROD']),
        b($r['ATUALIZA_PCOMPRA']),
    );
}

$n = gravar_lote($pdo, "$schema.biostech_compras_itens", array(
    'pedido_numero', 'produto_id', 'quantidade', 'valor_unitario',
    'novo_valor_unitario', 'desconto_percentual', 'desconto_item',
    'valor_total', 'observacao', 'atualiza_preco_compra',
), $linhas, array('pedido_numero', 'produto_id'));

echo "[ok] itens: $n (" . $ignorados . " ignorados por produto/qtde)\n";

// ------------------------------------------------------------------ parcelas

$dados = ler_json($dir . '/compras_pedidos.json');
$linhas = array();

foreach ($dados as $r)
{
    $pedido = inteiro_zero($r['NUMREQ']);

    if ( ! $pedido)
    {
        continue;
    }

    for ($i = 1; $i <= 12; $i++)
    {
        $valor = decimal($r['VPARC' . $i]);

        if ($valor === NULL)
        {
            continue;
        }

        $venc = data($r['DVENC' . $i]);

        if ($venc === NULL)
        {
            continue;
        }

        $linhas[] = array($pedido, $i, (int) decimal($r['QTDIAS' . $i]), $venc, $valor);
    }
}

$n = gravar_lote($pdo, "$schema.biostech_compras_parcelas", array(
    'pedido_numero', 'numero_par', 'dias', 'vencimento', 'valor',
), $linhas, array('pedido_numero', 'numero_par'));

echo "[ok] parcelas: $n\n";

// ------------------------------------------------------------------- cotacoes

$dados = ler_json($dir . '/compras_cotacoes.json');
$linhas = array();
$ignorados = 0;

foreach ($dados as $r)
{
    $codigo = inteiro_zero($r['COD_COTACAO']);

    if ( ! $codigo)
    {
        $ignorados++;
        continue;
    }

    $linhas[] = array(
        $codigo,
        data($r['DATA_COTACAO']) ?: date('Y-m-d'),
        texto($r['NUM_OS'], 16),
        inteiro($r['SOLICITANTE']),
        inteiro($r['FUNC_COTOU']),
        texto($r['OBSERVACAO']),
    );
}

$n = gravar_lote($pdo, "$schema.biostech_compras_cotacoes", array(
    'codigo', 'data_cotacao', 'num_os', 'solicitante_id', 'funcionario_id', 'observacao',
), $linhas, array('codigo'));

echo "[ok] cotacoes: $n (" . $ignorados . " sem codigo ignoradas)\n";

// ------------------------------------------------------------- cotacao itens

$dados = ler_json($dir . '/compras_cotacao_itens.json');
$linhas = array();
$ignorados = 0;

foreach ($dados as $r)
{
    $cotacao = inteiro_zero($r['COD_COTACAO']);
    $produto = inteiro($r['COD_PROD']);
    $qtde    = decimal($r['QTDE']);

    if ( ! $cotacao || ! $produto || ! isset($produtos[$produto]) || $qtde === NULL || $qtde <= 0)
    {
        $ignorados++;
        continue;
    }

    $linhas[] = array(
        $cotacao,
        $produto,
        $qtde,
        inteiro($r['FORNEC_01']),
        decimal($r['PRECO_01']),
        inteiro($r['FORNEC_02']),
        decimal($r['PRECO_02']),
        inteiro($r['FORNEC_03']),
        decimal($r['PRECO_03']),
        inteiro($r['FORNEC_ESCOLHIDO']),
    );
}

$n = gravar_lote($pdo, "$schema.biostech_compras_cotacao_itens", array(
    'cotacao_codigo', 'produto_id', 'quantidade', 'fornecedor_1', 'preco_1',
    'fornecedor_2', 'preco_2', 'fornecedor_3', 'preco_3', 'fornecedor_escolhido',
), $linhas, array('cotacao_codigo', 'produto_id'));

echo "[ok] cotacao_itens: $n (" . $ignorados . " ignorados)\n";

// --------------------------------------------------------------------- prazos

$dados = ler_json($dir . '/compras_prazos.json');
$linhas = array();

foreach ($dados as $r)
{
    $forn = inteiro($r['CODFOR']);
    $cod  = inteiro_zero($r['COD_PRAZO']);

    if ( ! $forn || $cod === NULL)
    {
        continue;
    }

    $linhas[] = array($forn, $cod, texto($r['FORMA_PAGTO'], 40), texto($r['PRAZO'], 60));
}

$n = gravar_lote($pdo, "$schema.biostech_fornecedor_prazos", array(
    'fornecedor_codigo', 'codigo', 'forma_pagamento', 'prazo',
), $linhas, array('fornecedor_codigo', 'codigo'));

echo "[ok] prazos: $n\n";

// ------------------------------------------------------------- solicitacoes

$dados = ler_json($dir . '/compras_solicitacoes.json');
$linhas = array();
$ignorados = 0;

foreach ($dados as $r)
{
    $numero = inteiro_zero($r['NUMREQ']);

    if ( ! $numero)
    {
        $ignorados++;
        continue;
    }

    $linhas[] = array(
        $numero,
        data($r['DATA']) ?: date('Y-m-d'),
        hora($r['HORA']),
        inteiro($r['SETOR']),
        inteiro($r['FUNC']),
        sim($r['FECHAMENTO']) ? 'fechada' : 'aberta',
        texto($r['LOCAL_ENTREGA']),
        texto($r['RESP_RECEBIMENTO'], 80),
        texto($r['FONE_RESP_REC'], 20),
        inteiro($r['COD_PED_COMPRA']),
        texto($r['USUARIO_CADASTRO'], 50),
        ($d = data($r['DATA_CADASTRO'])) ? $d . ' 00:00:00' : date('Y-m-d H:i:s'),
        texto($r['USUARIO_FECHAMENTO'], 50),
        ($d = data($r['DATA_FECHAMENTO'])) ? $d . ' 00:00:00' : NULL,
    );
}

$n = gravar_lote($pdo, "$schema.biostech_compras_solicitacoes", array(
    'numero', 'data', 'hora', 'setor_id', 'funcionario_id', 'situacao',
    'local_entrega', 'responsavel_recebimento', 'fone_responsavel',
    'pedido_numero', 'usuario_cadastro', 'data_cadastro', 'usuario_fechamento',
    'data_fechamento',
), $linhas, array('numero'));

echo "[ok] solicitacoes: $n (" . $ignorados . " sem numero ignoradas)\n";

// -------------------------------------------------------- solicitacao itens

$dados = ler_json($dir . '/compras_solicitacao_itens.json');
$linhas = array();
$ignorados = 0;

foreach ($dados as $r)
{
    $solic   = inteiro_zero($r['NUMREQ']);
    $produto = inteiro($r['COD_PROD']);
    $qtde    = decimal($r['QTDE']);

    if ( ! $solic || ! $produto || ! isset($produtos[$produto]) || $qtde === NULL || $qtde <= 0)
    {
        $ignorados++;
        continue;
    }

    $linhas[] = array(
        $solic,
        $produto,
        $qtde,
        b($r['REP_EST']),
        data($r['DATA_UTILIZACAO']),
        texto($r['PERIODO'], 20),
        b($r['SELECIONADO']),
        b($r['EXPORTADO']),
    );
}

$n = gravar_lote($pdo, "$schema.biostech_compras_solicitacao_itens", array(
    'solicitacao_numero', 'produto_id', 'quantidade', 'repor_estoque',
    'data_utilizacao', 'periodo', 'selecionado', 'exportado',
), $linhas, array('solicitacao_numero', 'produto_id'));

echo "[ok] solicitacao_itens: $n (" . $ignorados . " ignorados)\n";

// ----------------------------------------------------------------------- log

$pdo->exec("DELETE FROM $schema.biostech_compras_log WHERE pedido_numero IS NOT NULL AND solicitacao_numero IS NULL");

$dados = ler_json($dir . '/compras_log_pedidos.json');
$linhas = array();

foreach ($dados as $r)
{
    $linhas[] = array(
        inteiro($r['NUMREQ']),
        texto($r['TIPOMOV'], 10),
        texto($r['USUARIO'], 50),
        texto($r['DATAMOV']),
        json_detalhe($r),
    );
}

$n = gravar_lote($pdo, "$schema.biostech_compras_log", array(
    'pedido_numero', 'acao', 'usuario', 'data_mov', 'detalhe',
), $linhas, array('id'), FALSE);

echo "[ok] log de pedidos: $n\n";

$pdo->exec("DELETE FROM $schema.biostech_compras_log WHERE solicitacao_numero IS NOT NULL");

$dados = ler_json($dir . '/compras_log_solicitacoes.json');
$linhas = array();

foreach ($dados as $r)
{
    $linhas[] = array(
        inteiro($r['NUMREQ']),
        texto($r['TIPOMOV'], 10),
        texto($r['USUARIO'], 50),
        texto($r['DATAMOV']),
        json_detalhe($r),
    );
}

$n = gravar_lote($pdo, "$schema.biostech_compras_log", array(
    'solicitacao_numero', 'acao', 'usuario', 'data_mov', 'detalhe',
), $linhas, array('id'), FALSE);

echo "[ok] log de solicitacoes: $n\n";

// -------------------------------------------------- totais e sequenciais

$pdo->exec(
    "UPDATE $schema.biostech_compras_pedidos p
        SET total_bruto = COALESCE(t.bruto, 0),
            total = GREATEST(COALESCE(t.bruto, 0) - p.desconto, 0)
       FROM (SELECT pedido_numero, SUM(valor_total) AS bruto
               FROM $schema.biostech_compras_itens
              GROUP BY pedido_numero) t
      WHERE t.pedido_numero = p.numero"
);

$pdo->exec("UPDATE $schema.biostech_compras_pedidos SET total = 0, total_bruto = 0
             WHERE numero NOT IN (SELECT pedido_numero FROM $schema.biostech_compras_itens)");

// ------------------------------------------------- dados de compra do produto

$pdo->exec(
    "UPDATE $schema.biostech_produtos
        SET percentual_lucro = ROUND(((valor_venda / valor_compra) - 1) * 100, 4)
      WHERE percentual_lucro IS NULL
        AND valor_compra > 0
        AND valor_venda > 0"
);

$pdo->exec(
    "UPDATE $schema.biostech_produtos p
        SET ultimo_pedido_compra = t.numero
       FROM (SELECT i.produto_id, MAX(pd.numero) AS numero
               FROM $schema.biostech_compras_itens i
               JOIN $schema.biostech_compras_pedidos pd ON pd.numero = i.pedido_numero
              WHERE pd.situacao = 'fechado'
              GROUP BY i.produto_id) t
      WHERE t.produto_id = p.id
        AND (p.ultimo_pedido_compra IS NULL OR t.numero > p.ultimo_pedido_compra)"
);

foreach (array(
    'PC'  => "SELECT COALESCE(MAX(numero), 0) FROM $schema.biostech_compras_pedidos",
    'COT' => "SELECT COALESCE(MAX(codigo), 0) FROM $schema.biostech_compras_cotacoes",
    'SC'  => "SELECT COALESCE(MAX(numero), 0) FROM $schema.biostech_compras_solicitacoes",
    'CP'  => "SELECT COALESCE(MAX(numero), 0) FROM $schema.biostech_contas_pagar",
) as $tipo => $sql)
{
    $ultimo = (int) $pdo->query($sql)->fetchColumn();

    $st = $pdo->prepare(
        "INSERT INTO $schema.biostech_numeracao (tipo, competencia, ultimo)
         VALUES (?, '', ?)
         ON CONFLICT (tipo, competencia) DO UPDATE SET
            ultimo = GREATEST($schema.biostech_numeracao.ultimo, EXCLUDED.ultimo),
            updated_at = now()"
    );
    $st->execute(array($tipo, $ultimo));
    echo "[ok] numeracao $tipo: $ultimo\n";
}

$pdo->commit();

// ------------------------------------------------------------------- resumo

echo "\n== resumo ==\n";
foreach (array(
    'biostech_compras_pedidos'            => 'pedidos',
    'biostech_compras_itens'              => 'itens do pedido',
    'biostech_compras_parcelas'           => 'parcelas',
    'biostech_compras_cotacoes'           => 'cotacoes',
    'biostech_compras_cotacao_itens'      => 'itens da cotacao',
    'biostech_compras_solicitacoes'       => 'solicitacoes internas',
    'biostech_compras_solicitacao_itens'  => 'itens da solicitacao',
    'biostech_fornecedor_prazos'          => 'prazos de fornecedor',
    'biostech_compras_log'                => 'log',
) as $tabela => $rotulo)
{
    $total = $pdo->query("SELECT count(*) FROM $schema.$tabela")->fetchColumn();
    printf("%-30s %7d\n", $rotulo, $total);
}
