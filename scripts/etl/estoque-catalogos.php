<?php
/**
 * BIOSTECH - carga dos catalogos do estoque.
 *
 * Fontes (scripts/etl/dados/*.json, exportados do Access):
 *   FORNECEDOR, PROD_FORNEC_01, GRUPO, SUB_GRUPO,
 *   POSICAO, ESTANTE_01, PRATELEIRA_01, FINALIDADE
 *
 * Uso:
 *   php scripts/etl/estoque-catalogos.php
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
    return ($valor === NULL || $valor === '') ? NULL : (int) $valor;
}

function decimal($valor)
{
    return ($valor === NULL || $valor === '') ? NULL : (float) $valor;
}

$pdo = new PDO("pgsql:host=$host;port=$port;dbname=$name", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("SET datestyle TO 'ISO'");

$pdo->beginTransaction();

// --------------------------------------------------------------- fornecedores

$dados = ler_json($dir . '/FORNECEDOR.json');
$st = $pdo->prepare(
    "INSERT INTO $schema.biostech_fornecedores
        (codigo, razao_social, nome_fantasia, inscricao_estadual, cnpj,
         endereco, numero, complemento, bairro, cidade, cep, uf,
         fone, fax, email, contato, ponto_referencia, ativo)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, true)
     ON CONFLICT (codigo) DO UPDATE SET
        razao_social = EXCLUDED.razao_social,
        nome_fantasia = EXCLUDED.nome_fantasia,
        inscricao_estadual = EXCLUDED.inscricao_estadual,
        cnpj = EXCLUDED.cnpj,
        endereco = EXCLUDED.endereco,
        numero = EXCLUDED.numero,
        complemento = EXCLUDED.complemento,
        bairro = EXCLUDED.bairro,
        cidade = EXCLUDED.cidade,
        cep = EXCLUDED.cep,
        uf = EXCLUDED.uf,
        fone = EXCLUDED.fone,
        fax = EXCLUDED.fax,
        email = EXCLUDED.email,
        contato = EXCLUDED.contato,
        ponto_referencia = EXCLUDED.ponto_referencia,
        updated_at = now()"
);

$n = 0;
foreach ($dados as $r)
{
    if (empty($r['CODFOR']))
    {
        continue;
    }

    $st->execute(array(
        inteiro($r['CODFOR']),
        mb_strtoupper(texto($r['NOMFOR'], 100)) ?: 'FORNECEDOR ' . inteiro($r['CODFOR']),
        texto($r['NOMFANT'], 100),
        texto($r['INS_EST'], 20),
        texto($r['CGC_FOR'], 20),
        texto($r['ENDFOR'], 60),
        texto($r['NUMERO'], 10),
        texto($r['COMPLEMENTO'], 50),
        texto($r['BAIRRO'], 50),
        texto($r['CIDADE'], 20),
        texto($r['CEP'], 10),
        mb_strtoupper(texto($r['UF'], 2)),
        texto($r['FONE'], 15),
        texto($r['FAX'], 15),
        mb_strtolower(texto($r['EMAIL'], 50)),
        texto($r['CONTATO'], 25),
        texto($r['PONTO_REF']),
    ));
    $n++;
}
echo "[ok] fornecedores: $n\n";

// -------------------------------------------------------- produto x fornecedor

$dados = ler_json($dir . '/PROD_FORNEC_01.json');
$st = $pdo->prepare(
    "INSERT INTO $schema.biostech_produto_fornecedor
        (fornecedor_codigo, produto_id, codigo_fornecedor, nome_fornecedor, preco_venda)
     VALUES (?, ?, ?, ?, ?)
     ON CONFLICT (fornecedor_codigo, produto_id) DO UPDATE SET
        codigo_fornecedor = EXCLUDED.codigo_fornecedor,
        nome_fornecedor = EXCLUDED.nome_fornecedor,
        preco_venda = EXCLUDED.preco_venda"
);

$n = 0;
foreach ($dados as $r)
{
    if (empty($r['COD_FORN']) || $r['COD_PROD'] === NULL)
    {
        continue;
    }

    $st->execute(array(
        inteiro($r['COD_FORN']),
        inteiro($r['COD_PROD']),
        texto($r['COD_PFORN'], 25) ?: '-',
        texto($r['NOME_PFORN'], 80),
        decimal($r['PRECO_VENDA']),
    ));
    $n++;
}
echo "[ok] produto_fornecedor: $n\n";

// -------------------------------------------------------------------- grupos

$dados = ler_json($dir . '/GRUPO.json');
$st = $pdo->prepare(
    "INSERT INTO $schema.biostech_estoque_grupos (codigo, nome, percentual_venda)
     VALUES (?, ?, ?)
     ON CONFLICT (codigo) DO UPDATE SET
        nome = EXCLUDED.nome,
        percentual_venda = EXCLUDED.percentual_venda"
);

$n = 0;
foreach ($dados as $r)
{
    $codigo = texto($r['COD_GRUPO'], 3);
    if ($codigo === NULL)
    {
        continue;
    }

    $st->execute(array($codigo, mb_strtoupper(texto($r['NOM_GRUPO'], 40)) ?: $codigo, decimal($r['PERC_VENDA'])));
    $n++;
}
echo "[ok] grupos: $n\n";

// ------------------------------------------------------------------ subgrupos

$dados = ler_json($dir . '/SUB_GRUPO.json');
$st = $pdo->prepare(
    "INSERT INTO $schema.biostech_estoque_subgrupos (grupo_codigo, codigo, nome, ultimo_item)
     VALUES (?, ?, ?, ?)
     ON CONFLICT (grupo_codigo, codigo) DO UPDATE SET
        nome = EXCLUDED.nome,
        ultimo_item = EXCLUDED.ultimo_item"
);

$n = 0;
foreach ($dados as $r)
{
    $grupo = texto($r['COD_GRUPO'], 3);
    $codigo = texto($r['COD_SUB_GRUPO'], 3);

    if ($grupo === NULL || $codigo === NULL)
    {
        continue;
    }

    $st->execute(array($grupo, $codigo, mb_strtoupper(texto($r['NOM_SUB_GRUPO'], 40)) ?: $codigo, texto($r['ULTIMO_ITEM'], 3)));
    $n++;
}
echo "[ok] subgrupos: $n\n";

// ------------------------------------------------------------------- posicoes

$dados = ler_json($dir . '/POSICAO.json');
$st = $pdo->prepare(
    "INSERT INTO $schema.biostech_estoque_posicoes (codigo, nome)
     VALUES (?, ?)
     ON CONFLICT (codigo) DO UPDATE SET nome = EXCLUDED.nome"
);

$n = 0;
foreach ($dados as $r)
{
    if ($r['CODPOS'] === NULL)
    {
        continue;
    }

    $st->execute(array(inteiro($r['CODPOS']), mb_strtoupper(texto($r['NOMPOS'], 40)) ?: 'POSICAO'));
    $n++;
}
echo "[ok] posicoes: $n\n";

// ------------------------------------------------------------------- estantes

$dados = ler_json($dir . '/ESTANTE_01.json');
$st = $pdo->prepare(
    "INSERT INTO $schema.biostech_estoque_estantes (codigo, nome)
     VALUES (?, ?)
     ON CONFLICT (codigo) DO UPDATE SET nome = EXCLUDED.nome"
);

$n = 0;
foreach ($dados as $r)
{
    if ($r['COD_ESTANTE'] === NULL)
    {
        continue;
    }

    $st->execute(array(inteiro($r['COD_ESTANTE']), mb_strtoupper(texto($r['NOME_ESTANTE'], 50)) ?: 'ESTANTE'));
    $n++;
}
echo "[ok] estantes: $n\n";

// ---------------------------------------------------------------- prateleiras

$dados = ler_json($dir . '/PRATELEIRA_01.json');
$st = $pdo->prepare(
    "INSERT INTO $schema.biostech_estoque_prateleiras (estante_codigo, codigo, nome)
     VALUES (?, ?, ?)
     ON CONFLICT (estante_codigo, codigo) DO UPDATE SET nome = EXCLUDED.nome"
);

$n = 0;
$ignorados = 0;
foreach ($dados as $r)
{
    if ($r['COD_ESTANTE'] === NULL || $r['COD_PRATELEIRA'] === NULL)
    {
        $ignorados++;
        continue;
    }

    $st->execute(array(
        inteiro($r['COD_ESTANTE']),
        inteiro($r['COD_PRATELEIRA']),
        mb_strtoupper(texto($r['NOME_PRATELEIRA'], 50)) ?: 'PRATELEIRA',
    ));
    $n++;
}
echo "[ok] prateleiras: $n (" . $ignorados . " sem codigo ignoradas)\n";

// ---------------------------------------------------------------- finalidades

$dados = ler_json($dir . '/FINALIDADE.json');
$st = $pdo->prepare(
    "INSERT INTO $schema.biostech_estoque_finalidades (codigo, nome)
     VALUES (?, ?)
     ON CONFLICT (codigo) DO UPDATE SET nome = EXCLUDED.nome"
);

$n = 0;
foreach ($dados as $r)
{
    if ($r['CODFINAL'] === NULL)
    {
        continue;
    }

    $st->execute(array(inteiro($r['CODFINAL']), mb_strtoupper(texto($r['NOMFINAL'], 40)) ?: 'FINALIDADE'));
    $n++;
}
echo "[ok] finalidades: $n\n";

$pdo->commit();

// ------------------------------------------------------------------ resumo

echo "\n== resumo ==\n";
foreach (array(
    'biostech_fornecedores'          => 'fornecedores',
    'biostech_produto_fornecedor'    => 'produto x fornecedor',
    'biostech_estoque_grupos'        => 'grupos',
    'biostech_estoque_subgrupos'     => 'subgrupos',
    'biostech_estoque_posicoes'      => 'posicoes',
    'biostech_estoque_estantes'      => 'estantes',
    'biostech_estoque_prateleiras'   => 'prateleiras',
    'biostech_estoque_finalidades'   => 'finalidades',
) as $tabela => $rotulo)
{
    $total = $pdo->query("SELECT count(*) FROM $schema.$tabela")->fetchColumn();
    printf("%-28s %6d\n", $rotulo, $total);
}

