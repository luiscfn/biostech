<?php
/**
 * BIOSTECH - carga do modulo Chamados / Ordens de Servico.
 *
 * Fontes (scripts/etl/dados/chamado_*.json, exportadas do Access por
 * export-chamados.ps1):
 *   cabecalho  : CHAMADO_01 + CHAMADO_02 + CHAMADO_03A
 *   equipamento: CHAMADO_03            (serie resolvida em EQUIPAMENTO)
 *   atendimento: CHAMADO_04
 *   intervencoes: CHAMADO_05A
 *   pecas      : CHAMADO_06
 *   servicos   : CHAMADO_55 (previsto) e CHAMADO_44 (executado)
 *   horas      : CHAMADO_09
 *   pagamento  : CHAMADO_10 (plano + parcelas)
 *   vinculos   : CHAMADO_99
 *   log        : CHAMADO_LOG_01 (eventos + log campo a campo)
 *
 * EQUIPAMENTO tambem e importado (biostech_equipamentos) porque o chamado
 * aponta para a serie do equipamento.
 *
 * Uso:
 *   php scripts/etl/chamados.php
 *
 * A carga e idempotente: chamados/equipamentos/atendimentos/planos sao
 * atualizados por chave; pecas, servicos, intervencoes e vinculos usam
 * "on conflict do nothing"; eventos/log importados sao refeitos.
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
date_default_timezone_set('America/Fortaleza');
mb_internal_encoding('UTF-8');
ini_set('memory_limit', '1024M');
ob_implicit_flush(TRUE);

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

function decimal_positivo($valor)
{
    $v = decimal($valor);

    return ($v === NULL || $v < 0) ? 0.0 : $v;
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

/** Hora ISO (H:i:s). Horarios vazios/invalidos viram NULL. */
function hora($valor)
{
    $valor = texto($valor);

    if ($valor === NULL)
    {
        return NULL;
    }

    if (preg_match('/(\d{1,2}):(\d{2})(?::(\d{2}))?/', $valor, $m))
    {
        $h = (int) $m[1];
        $i = (int) $m[2];
        $s = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : 0;

        if ($h > 23 || $i > 59 || $s > 59)
        {
            return NULL;
        }

        return sprintf('%02d:%02d:%02d', $h, $i, $s);
    }

    return NULL;
}

/** Data + hora ISO (Y-m-d H:i:s). */
function momento($valor)
{
    $d = data($valor);

    if ($d === NULL)
    {
        return NULL;
    }

    $h = hora($valor);

    return $d . ' ' . ($h === NULL ? '00:00:00' : $h);
}

function sim($valor)
{
    return is_string($valor) && in_array(mb_strtoupper(trim($valor)), array('S', 'SIM', '1', 'T', 'TRUE'), TRUE);
}

function b($valor)
{
    return sim($valor) ? 'true' : 'false';
}

/** Insere varias linhas em um unico INSERT. */
function gravar_lote(PDO $pdo, $tabela, array $colunas, array $linhas, $conflito = NULL, $atualizar = TRUE, $tamanho = 300, array $casts = array())
{
    if (empty($linhas))
    {
        return 0;
    }

    // placeholders com cast explicito (ex.: jsonb)
    $marcas = array();

    foreach ($colunas as $coluna)
    {
        $marcas[] = isset($casts[$coluna]) ? '?::' . $casts[$coluna] : '?';
    }

    $grupo = '(' . implode(', ', $marcas) . ')';

    $conflitoSql = '';

    if ($conflito === 'ANY')
    {
        $conflitoSql = ' ON CONFLICT DO NOTHING';
    }
    elseif ($conflito !== NULL)
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

        $conflitoSql = ' ON CONFLICT (' . implode(', ', $conflito) . ') '
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
                VALUES ' . implode(', ', $valores) . $conflitoSql;

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $total += count($bloco);
    }

    return $total;
}

// ------------------------------------------------------------------ conexao

$pdo = new PDO("pgsql:host=$host;port=$port;dbname=$name", $user, $pass, array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
));
$pdo->exec("SET datestyle TO 'ISO'");
$pdo->exec("SET TIME ZONE 'America/Fortaleza'");

echo "== carga de chamados ($schema) ==\n";

// ------------------------------------------------------------------ fontes

$equipamentos_src = ler_json("$dir/chamado_equipamentos.json");
$c01 = ler_json("$dir/chamado_01.json");
$c02 = ler_json("$dir/chamado_02.json");
$c03 = ler_json("$dir/chamado_03.json");
$c03a = ler_json("$dir/chamado_03a.json");
$c04 = ler_json("$dir/chamado_04.json");
$c05a = ler_json("$dir/chamado_05a.json");
$c06 = ler_json("$dir/chamado_06.json");
$c09 = ler_json("$dir/chamado_09.json");
$c10 = ler_json("$dir/chamado_10.json");
$c44 = ler_json("$dir/chamado_44.json");
$c55 = ler_json("$dir/chamado_55.json");
$c99 = ler_json("$dir/chamado_99.json");
$clog = ler_json("$dir/chamado_log_01.json");

// ------------------------------------------------------------------ mapas

$clientes = array();
foreach ($pdo->query("SELECT id FROM $schema.biostech_clientes") as $r)
{
    $clientes[(int) $r['id']] = TRUE;
}

$produtos = array();
foreach ($pdo->query("SELECT id FROM $schema.biostech_produtos") as $r)
{
    $produtos[(int) $r['id']] = TRUE;
}

$tecnicos = array();
foreach ($pdo->query("SELECT funcionario_id, min(id) AS id FROM $schema.biostech_usuarios
                      WHERE funcionario_id IS NOT NULL GROUP BY funcionario_id") as $r)
{
    $tecnicos[(int) $r['funcionario_id']] = (int) $r['id'];
}

// cliente inferido pelo titulo a receber quando o chamado nao tem CHAMADO_02
$cliente_por_titulo = array();
foreach ($pdo->query("SELECT chamado_numero, min(cliente_id) AS cliente_id, count(DISTINCT cliente_id) AS qtd
                        FROM $schema.biostech_contas_receber
                       WHERE chamado_numero IS NOT NULL AND cliente_id IS NOT NULL
                       GROUP BY chamado_numero HAVING count(DISTINCT cliente_id) = 1") as $r)
{
    $cliente_por_titulo[$r['chamado_numero']] = (int) $r['cliente_id'];
}

// ------------------------------------------------------------------ equipamentos

$pdo->beginTransaction();

$total = 0;

foreach (array_chunk($equipamentos_src, 300) as $bloco)
{
    $linhas = array();

    foreach ($bloco as $r)
    {
        $serie = texto($r['NUS_EQUIP'], 30);

        if ($serie === NULL)
        {
            continue;
        }

        $linhas[] = array(
            $serie,
            NULL,
            inteiro_zero($r['TIPO_EQUIP']),
            texto($r['DES_EQUIP'], 120),
            texto($r['CONFIGURACAO']),
            inteiro($r['FABRICANTE']),
            texto($r['NUM_NF'], 20),
            data($r['DATA_NF']),
            data($r['DT_FIM_GARANTIA']),
            texto($r['ULT_OS'], 16),
            data($r['DT_ULT_OS']),
            data($r['DATA_EMPRESTIMO']),
            data($r['DATA_DEVOLUCAO']),
            texto($r['NUM_OS_EMPRESTIMO'], 16),
            texto($r['NUMPAT'], 25),
        );
    }

    $total += gravar_lote($pdo, "$schema.biostech_equipamentos", array(
        'numero_serie', 'cliente_id', 'tipo_codigo', 'descricao', 'configuracao',
        'fabricante', 'nota_fiscal', 'data_nf', 'garantia_fim', 'ultimo_chamado',
        'data_ultimo_chamado', 'data_emprestimo', 'data_devolucao',
        'chamado_emprestimo', 'patrimonio',
    ), $linhas, array('numero_serie'), FALSE);
}

$pdo->commit();
printf("%-14s %6d\n", 'equipamentos', $total);

$series = array();
foreach ($pdo->query("SELECT numero_serie FROM $schema.biostech_equipamentos") as $r)
{
    $series[$r['numero_serie']] = TRUE;
}

// ------------------------------------------------------------------ chamados

$por_os_02 = array();
foreach ($c02 as $r)
{
    $os = texto($r['NUM_OS']);

    if ($os !== NULL)
    {
        $por_os_02[$os] = $r;
    }
}

$por_os_03a = array();
foreach ($c03a as $r)
{
    $os = texto($r['NUM_OS']);

    if ($os !== NULL)
    {
        $por_os_03a[$os] = $r;
    }
}

// cliente de apoio para chamados sem cliente no Access
$sem_cliente_nome = 'CLIENTE NAO IDENTIFICADO (IMPORTACAO ACCESS)';
$st = $pdo->prepare("SELECT id FROM $schema.biostech_clientes WHERE nome = ? ORDER BY id LIMIT 1");
$st->execute(array($sem_cliente_nome));
$cliente_apoio = $st->fetchColumn();

if ($cliente_apoio === FALSE)
{
    $novo_id = (int) $pdo->query("SELECT COALESCE(max(id), 0) + 1 FROM $schema.biostech_clientes")->fetchColumn();

    $st = $pdo->prepare("INSERT INTO $schema.biostech_clientes
        (id, nome, observacao, ativo, created_at, updated_at)
        VALUES (?, ?, 'Criado pela importacao dos chamados do Access (registros sem cliente).', true, now(), now())");
    $st->execute(array($novo_id, $sem_cliente_nome));

    $cliente_apoio = $novo_id;
    echo "cliente de apoio criado: id $cliente_apoio\n";
}

$cliente_apoio = (int) $cliente_apoio;

$pdo->beginTransaction();

$linhas = array();
$ignorados = array('sem_numero' => 0, 'cliente_apoio' => 0, 'cliente_titulo' => 0);
$numeros = array();

foreach ($c01 as $r)
{
    $numero = texto($r['NUM_OS'], 16);

    if ($numero === NULL)
    {
        $ignorados['sem_numero']++;
        continue;
    }

    $r02  = isset($por_os_02[$numero]) ? $por_os_02[$numero] : NULL;
    $r03a = isset($por_os_03a[$numero]) ? $por_os_03a[$numero] : NULL;

    $cliente_id = $r02 !== NULL ? inteiro($r02['COD_CLI']) : NULL;

    if ($cliente_id === NULL || ! isset($clientes[$cliente_id]))
    {
        if (isset($cliente_por_titulo[$numero]) && isset($clientes[$cliente_por_titulo[$numero]]))
        {
            $cliente_id = $cliente_por_titulo[$numero];
            $ignorados['cliente_titulo']++;
        }
        else
        {
            $cliente_id = $cliente_apoio;
            $ignorados['cliente_apoio']++;
        }
    }

    $categoria = texto($r['CATEGORIA']);
    $categoria = ($categoria !== NULL && mb_stripos($categoria, 'Ordem') !== FALSE) ? 'Ordem Serviço' : 'Chamado';

    $status = (int) ($r['STATUS'] === NULL ? 1 : $r['STATUS']);

    if ($status < 1 || $status > 6)
    {
        $status = 1;
    }

    $qtd_parcelas = inteiro_zero($r['QTDE_PARC']);

    if ($qtd_parcelas !== NULL && ($qtd_parcelas < 1 || $qtd_parcelas > 12))
    {
        $qtd_parcelas = NULL;
    }

    $abertura = data($r['DAT_CHA']);

    if ($abertura === NULL)
    {
        $ano = substr($numero, 0, 4);
        $mes = substr($numero, 4, 2);

        if (ctype_digit($ano) && ctype_digit($mes) && (int) $mes >= 1 && (int) $mes <= 12)
        {
            $abertura = $ano . '-' . $mes . '-01';
        }
        else
        {
            $abertura = '1901-01-01';
        }
    }

    $cadastro = momento($r['DATA_CADASTRO']);

    if ($cadastro === NULL)
    {
        $cadastro = $abertura . ' 00:00:00';
    }

    $fechatot = texto($r['FECHATOT']);

    $linhas[] = array(
        $numero,
        texto($r['NUM_OS_PARC'], 16),
        $categoria,
        $cliente_id,
        inteiro($r02 === NULL ? NULL : $r02['COD_ESC']),
        inteiro_zero($r['ORC']),
        $abertura,
        hora($r['HOR_CHA']),
        $status,
        inteiro_zero($r['SIT_ATUAL_02']),
        $fechatot !== NULL && mb_strtoupper($fechatot) === 'S' ? 'true' : 'false',
        $fechatot !== NULL && mb_strtoupper($fechatot) === 'A' ? 'true' : 'false',
        b($r['FECHAFIN']),
        b($r['BAIXA_AUTOMATICA']),
        decimal_positivo($r['VALMAT']),
        decimal_positivo($r['VALSERV']),
        decimal_positivo($r['VALMOBRA']),
        decimal_positivo($r['VALIMPOSTO']),
        decimal_positivo($r['ACRESCIMO_CUSTO']),
        decimal_positivo($r['MAT_FIXACAO']),
        decimal_positivo($r['VALDESC']),
        decimal_positivo($r['VALFATUG']),
        $qtd_parcelas,
        texto($r['OS_GERADA'], 16),
        data($r['DATADA']),
        $r03a === NULL ? NULL : texto($r03a['DEF_RELATADO']),
        $r03a === NULL ? NULL : texto($r03a['DEF_CONSTATADO']),
        $r03a === NULL ? NULL : texto($r03a['SERVICO_EXECUTADO']),
        $r03a === NULL ? NULL : texto($r03a['OBSERVACAO']),
        texto($r['USUARIO_CADASTRO'], 50),
        $cadastro,
        texto($r['USUARIO_ULTIMA_ALTERACAO'], 50),
        momento($r['DATA_ULTIMA_ALTERACAO']),
        texto($r['USUARIO_FECHAMENTO'], 50),
        momento($r['DATA_FECHAMENTO']),
        texto($r['USUARIO_ENCERRAMENTO'], 50),
        momento($r['DATA_ENCERRAMENTO']),
    );

    $numeros[$numero] = TRUE;
}

$total = gravar_lote($pdo, "$schema.biostech_chamados", array(
    'numero', 'numero_par', 'categoria', 'cliente_id', 'escritorio_id',
    'orcamento_numero', 'data_abertura', 'hora_abertura', 'status_codigo',
    'situacao_codigo', 'encerrado', 'reaberto', 'financeiro_bloqueado',
    'baixa_estoque_feita', 'valor_material', 'valor_servico', 'valor_mao_obra',
    'valor_imposto', 'acrescimo_custo', 'material_fixacao', 'valor_desconto',
    'valor_faturado', 'qtd_parcelas', 'numero_gerado', 'data_geracao',
    'defeito_relatado', 'defeito_constatado', 'servico_executado', 'observacao',
    'usuario_cadastro', 'data_cadastro', 'usuario_alteracao', 'data_alteracao',
    'usuario_fechamento', 'data_fechamento', 'usuario_encerramento', 'data_encerramento',
), $linhas, array('numero'), TRUE, 200);

$pdo->commit();
printf("%-14s %6d  (sem numero: %d | cliente inferido do titulo: %d | cliente de apoio: %d)\n",
    'chamados', $total, $ignorados['sem_numero'], $ignorados['cliente_titulo'], $ignorados['cliente_apoio']);

// chamados validos no banco (inclui os ja existentes)
$existentes = array();
foreach ($pdo->query("SELECT numero FROM $schema.biostech_chamados") as $r)
{
    $existentes[$r['numero']] = TRUE;
}

// ------------------------------------------------------------------ equipamento do chamado

$pdo->beginTransaction();

$linhas = array();
$ignorados = array('sem_chamado' => 0);

foreach ($c03 as $r)
{
    $os = texto($r['NUM_OS']);

    if ($os === NULL || ! isset($existentes[$os]))
    {
        $ignorados['sem_chamado']++;
        continue;
    }

    $serie = texto($r['COD_EQUIP'], 30);

    if ($serie !== NULL && ! isset($series[$serie]))
    {
        $serie = NULL;
    }

    $recolhido = texto($r['RECOLHIDO']);

    $linhas[] = array(
        $os,
        $serie,
        inteiro_zero($r['VOLTAGEM']),
        inteiro_zero($r['TIPO_GARANTIA']),
        inteiro($r['AUTORIZADA']),
        texto($r['ACESSORIOS']),
        texto($r['LISTA_EQUIP']),
        texto($r['NUMPAT'], 25),
        texto($r['SOLICITANTE'], 50),
        texto($r['LOCAL_ATEND'], 50),
        inteiro_zero($r['VOLTSAIDA']),
        texto($r['NF_CHEGADA'], 20),
        ($recolhido !== NULL && mb_strtoupper($recolhido) === 'S') ? 'true' : 'false',
        texto($r['CONTATO'], 50),
    );
}

$total = gravar_lote($pdo, "$schema.biostech_chamado_equipamento", array(
    'chamado_numero', 'equipamento_serie', 'voltagem', 'tipo_garantia_codigo',
    'autorizada', 'acessorios', 'lista_equipamento', 'patrimonio', 'solicitante',
    'local_atendimento', 'voltagem_saida', 'nota_fiscal_chegada', 'recolhido', 'contato',
), $linhas, array('chamado_numero'), TRUE);

$pdo->commit();
printf("%-14s %6d  (sem chamado: %d)\n", 'equipamento', $total, $ignorados['sem_chamado']);

// ------------------------------------------------------------------ atendimento

$pdo->beginTransaction();

$linhas = array();
$ignorados = array('sem_chamado' => 0);

foreach ($c04 as $r)
{
    $os = texto($r['NUM_OS']);

    if ($os === NULL || ! isset($existentes[$os]))
    {
        $ignorados['sem_chamado']++;
        continue;
    }

    $atendimento = data($r['DT_REAL_ATEND']);
    $devolucao   = data($r['DATA_DEVOLUCAO']);

    if ($devolucao !== NULL && $atendimento !== NULL && $devolucao < $atendimento)
    {
        $devolucao = NULL;
    }

    $linhas[] = array(
        $os,
        data($r['DT_PREV_ATEND']),
        hora($r['HR_PREV_ATEND']),
        $atendimento,
        hora($r['HR_REAL_ATEND']),
        hora($r['HR_REAL_ATEND_FIM']),
        data($r['DT_REAL_SOLUCAO']),
        hora($r['HR_REAL_SOLUCAO']),
        $devolucao,
        hora($r['HORA_DEVOLUCAO']),
        isset($tecnicos[(int) $r['TECNICO_RESP_ATEND']]) ? $tecnicos[(int) $r['TECNICO_RESP_ATEND']] : NULL,
        isset($tecnicos[(int) $r['TEC_ABRIU_OS']]) ? $tecnicos[(int) $r['TEC_ABRIU_OS']] : NULL,
        isset($tecnicos[(int) $r['TEC_CONCLUIU']]) ? $tecnicos[(int) $r['TEC_CONCLUIU']] : NULL,
        isset($tecnicos[(int) $r['RESP_ENCERRAMENTO']]) ? $tecnicos[(int) $r['RESP_ENCERRAMENTO']] : NULL,
        texto($r['RESP_ENTREGA_EQUIP'], 50),
        isset($tecnicos[(int) $r['RESP_RECEBIM_EQUIP']]) ? $tecnicos[(int) $r['RESP_RECEBIM_EQUIP']] : NULL,
        isset($tecnicos[(int) $r['QUEM_DEVOLVEU']]) ? $tecnicos[(int) $r['QUEM_DEVOLVEU']] : NULL,
        texto($r['QUEM_RECEBEU'], 50),
        inteiro_zero($r['PRIORIDADE']),
        inteiro_zero($r['SEGMENTO']),
        decimal_positivo($r['VALOR_RECOLHIMENTO']),
        decimal_positivo($r['VALOR_ENTREGA']),
        b($r['PAGO_RECOLHIMENTO']),
        b($r['PAGO_ENTREGA']),
        inteiro($r['AUTORIZADA']),
        b($r['GARANTIA']),
        b($r['RECOLHIDO']),
        texto($r['CONTATO'], 50),
        texto($r['FONE_CONTATO'], 30),
        texto($r['SOLICITANTE'], 50),
        texto($r['FONE_SOLICITANTE'], 30),
        texto($r['LOCAL_ATEND'], 50),
        isset($tecnicos[(int) $r['RESP_DEVOLUCAO']]) ? $tecnicos[(int) $r['RESP_DEVOLUCAO']] : NULL,
    );
}

$total = gravar_lote($pdo, "$schema.biostech_chamado_atendimento", array(
    'chamado_numero', 'previsao_data', 'previsao_hora', 'atendimento_data',
    'atendimento_hora', 'atendimento_hora_fim', 'solucao_data', 'solucao_hora',
    'devolucao_data', 'devolucao_hora', 'tecnico_atendimento_id',
    'tecnico_abertura_id', 'tecnico_conclusao_id', 'responsavel_encerramento_id',
    'responsavel_entrega', 'responsavel_recebimento', 'quem_devolveu',
    'quem_recebeu', 'prioridade_codigo', 'segmento_codigo', 'valor_recolhimento',
    'valor_entrega', 'pago_recolhimento', 'pago_entrega', 'autorizada',
    'garantia', 'recolhido', 'contato', 'fone_contato', 'solicitante',
    'fone_solicitante', 'local_atendimento', 'devolucao_responsavel_id',
), $linhas, array('chamado_numero'), TRUE);

$pdo->commit();
printf("%-14s %6d  (sem chamado: %d)\n", 'atendimento', $total, $ignorados['sem_chamado']);

// ------------------------------------------------------------------ intervencoes (append-only)

$pdo->beginTransaction();

$linhas  = array();
$usados  = array();
$ignorados = array('sem_chamado' => 0, 'sem_data' => 0);

foreach ($c05a as $r)
{
    $os = texto($r['NUM_OS']);

    if ($os === NULL || ! isset($existentes[$os]))
    {
        $ignorados['sem_chamado']++;
        continue;
    }

    $mov = data($r['DATAMOV']);
    $hmv = hora($r['HORAMOV']);

    if ($mov === NULL || $hmv === NULL)
    {
        $ignorados['sem_data']++;
        continue;
    }

    // a tabela e imutavel: garante a chave unica (chamado, data, hora)
    $chave = $os . '|' . $mov . '|' . $hmv;

    if (isset($usados[$chave]))
    {
        $partes = explode(':', $hmv);
        $seg    = (int) $partes[2];

        do {
            $seg++;
            $hmv = sprintf('%02d:%02d:%02d', (int) $partes[0], (int) $partes[1], $seg);
            $chave = $os . '|' . $mov . '|' . $hmv;
        } while (isset($usados[$chave]) && $seg < 60);
    }

    $usados[$chave] = TRUE;

    $cod = inteiro_zero($r['TECNICO']);

    if ($cod === NULL)
    {
        $cod = inteiro_zero($r['CODIFUNC']);
    }

    $linhas[] = array(
        $os,
        $mov,
        $hmv,
        hora($r['HORA_INI']),
        hora($r['HORA_FIM']),
        ($cod !== NULL && isset($tecnicos[$cod])) ? $tecnicos[$cod] : 0,
        texto($r['DESCMOV']) === NULL ? '(sem descricao)' : texto($r['DESCMOV']),
        b($r['RESOLVEU']),
    );
}

$total = gravar_lote($pdo, "$schema.biostech_chamado_intervencoes", array(
    'chamado_numero', 'data_movimento', 'hora_movimento', 'hora_inicio', 'hora_fim',
    'tecnico_id', 'descricao', 'resolveu',
), $linhas, 'ANY');

$pdo->commit();
printf("%-14s %6d  (sem chamado: %d | sem data/hora: %d)\n",
    'intervencoes', $total, $ignorados['sem_chamado'], $ignorados['sem_data']);

// ------------------------------------------------------------------ pecas

$pdo->beginTransaction();

$linhas = array();
$itens  = array();
$prods  = array();
$ignorados = array('sem_chamado' => 0, 'produto_duplicado' => 0, 'item_renumerado' => 0);

foreach ($c06 as $r)
{
    $os = texto($r['NUM_OS']);

    if ($os === NULL || ! isset($existentes[$os]))
    {
        $ignorados['sem_chamado']++;
        continue;
    }

    $item = inteiro_zero($r['NUMREQ']);

    if ($item === NULL)
    {
        $item = isset($itens[$os]) ? max($itens[$os]) + 1 : 1;
    }

    if (isset($itens[$os][$item]))
    {
        $item = max(array_keys($itens[$os])) + 1;
        $ignorados['item_renumerado']++;
    }

    $itens[$os][$item] = TRUE;

    $produto = inteiro($r['CODPROD']);

    if ($produto !== NULL)
    {
        if ( ! isset($produtos[$produto]))
        {
            $produto = NULL;
        }
        elseif (isset($prods[$os][$produto]))
        {
            $produto = NULL;
            $ignorados['produto_duplicado']++;
        }
        else
        {
            $prods[$os][$produto] = TRUE;
        }
    }

    $qtde = (int) ($r['QTDE'] === NULL ? 0 : $r['QTDE']);

    if ($qtde <= 0)
    {
        $qtde = 1;
    }

    $atendida = (int) ($r['QTDE_ATENDIDA'] === NULL ? 0 : $r['QTDE_ATENDIDA']);

    if ($atendida < 0)
    {
        $atendida = 0;
    }

    $linhas[] = array(
        $os,
        $item,
        $produto,
        texto($r['DESPROD'], 120),
        $qtde,
        decimal_positivo($r['VALUNIT']),
        decimal_positivo($r['VALTOT']),
        decimal_positivo($r['VALMOBRA']),
        decimal_positivo($r['VALDESC']),
        $atendida,
        max(0, (int) $r['QTDE_EXCESSIVA']),
        max(0, (int) $r['QTDE_EXCESSIVA_ATEND']),
        b($r['SAIU_ESTOQUE']),
        data($r['DATA_BAIXA']),
    );
}

$total = gravar_lote($pdo, "$schema.biostech_chamado_pecas", array(
    'chamado_numero', 'numero_item', 'produto_id', 'descricao_produto', 'quantidade',
    'valor_unitario', 'valor_total', 'valor_mao_obra', 'valor_desconto',
    'quantidade_atendida', 'quantidade_excessiva', 'quantidade_excessiva_atendida',
    'saiu_estoque', 'data_baixa',
), $linhas, 'ANY');

$pdo->commit();
printf("%-14s %6d  (sem chamado: %d | item renumerado: %d)\n",
    'pecas', $total, $ignorados['sem_chamado'], $ignorados['item_renumerado']);

// ------------------------------------------------------------------ servicos

$pdo->beginTransaction();

$linhas = array();
$ignorados = array('sem_chamado' => 0);

foreach (array('previsto' => $c55, 'executado' => $c44) as $tipo => $fonte)
{
    foreach ($fonte as $r)
    {
        $os = texto($r['NUM_OS']);

        if ($os === NULL || ! isset($existentes[$os]))
        {
            $ignorados['sem_chamado']++;
            continue;
        }

        $cod = inteiro_zero($r['TECNICO']);

        $linhas[] = array(
            $os,
            (int) $r['NUMITEM'],
            $tipo,
            texto($r['DESC_SERV']) === NULL ? '(sem descricao)' : texto($r['DESC_SERV']),
            1,
            0,
            0,
            b($r['CONCLUIDO']),
            data(isset($r['DATA']) ? $r['DATA'] : NULL),
            ($cod !== NULL && isset($tecnicos[$cod])) ? $tecnicos[$cod] : NULL,
        );
    }
}

$total = gravar_lote($pdo, "$schema.biostech_chamado_servicos", array(
    'chamado_numero', 'numero_item', 'tipo', 'descricao', 'quantidade',
    'valor_unitario', 'valor_total', 'concluido', 'data_execucao', 'tecnico_id',
), $linhas, 'ANY');

$pdo->commit();
printf("%-14s %6d  (sem chamado: %d)\n", 'servicos', $total, $ignorados['sem_chamado']);

// ------------------------------------------------------------------ horas

$pdo->beginTransaction();

$pdo->exec("DELETE FROM $schema.biostech_chamado_horas WHERE origem = 'chamado'");

$linhas = array();
$ignorados = array('sem_chamado' => 0, 'sem_data' => 0);

foreach ($c09 as $r)
{
    $os = texto($r['NUM_OS']);

    if ($os === NULL || ! isset($existentes[$os]))
    {
        $ignorados['sem_chamado']++;
        continue;
    }

    $inicio = data($r['DATA_INICIAL']);

    if ($inicio === NULL)
    {
        $ignorados['sem_data']++;
        continue;
    }

    $fim = data($r['DATA_FINAL']);

    if ($fim !== NULL && $fim < $inicio)
    {
        $fim = NULL;
    }

    $cod = inteiro_zero($r['TECNICO']);

    $linhas[] = array(
        $os,
        ($cod !== NULL && isset($tecnicos[$cod])) ? $tecnicos[$cod] : NULL,
        $inicio,
        hora($r['HORA_INICIAL']),
        $fim,
        NULL,
    );
}

$total = gravar_lote($pdo, "$schema.biostech_chamado_horas", array(
    'chamado_numero', 'tecnico_id', 'data_inicial', 'hora_inicial', 'data_final', 'hora_final',
), $linhas);

$pdo->commit();
printf("%-14s %6d  (sem chamado: %d | sem data: %d)\n",
    'horas', $total, $ignorados['sem_chamado'], $ignorados['sem_data']);

// ------------------------------------------------------------------ planos de pagamento

$pdo->beginTransaction();

$linhas   = array();
$parcelas = array();
$ignorados = array('sem_chamado' => 0);

foreach ($c10 as $r)
{
    $os = texto($r['NUM_OS']);

    if ($os === NULL || ! isset($existentes[$os]))
    {
        $ignorados['sem_chamado']++;
        continue;
    }

    $qtd = inteiro_zero($r['TOTPARC']);

    if ($qtd === NULL || $qtd < 1 || $qtd > 12)
    {
        $qtd = 1;
    }

    $valores = array();

    for ($i = 1; $i <= 12; $i++)
    {
        if ( ! isset($r['NPARC' . $i]) || $r['NPARC' . $i] === NULL)
        {
            continue;
        }

        $valores[] = max(0, decimal_zero($r['VPARC' . $i]));
    }

    $valor_total = decimal($r['VALTOT']);

    if ($valor_total === NULL)
    {
        $valor_total = array_sum($valores);
    }

    $cliente_nota = inteiro($r['CLIENTE_NOTA']);

    if ($cliente_nota !== NULL && ! isset($clientes[$cliente_nota]))
    {
        $cliente_nota = NULL;
    }

    $linhas[] = array(
        $os,
        data($r['DATAPAG']),
        data($r['DATAVENC']),
        inteiro_zero($r['TIPOPAG']),
        inteiro($r['CODIFUNC']),
        inteiro($r['COD_CC']),
        inteiro_zero($r['COND_PAGTO']),
        inteiro_zero($r['FORMA_REC']),
        inteiro_zero($r['DOC_REC']),
        $cliente_nota,
        max(0, $valor_total),
        $qtd,
        b($r['TEM_MAT_TRABALHO']),
        b($r['MATERIAL_DEVOLVIDO']),
        inteiro($r['NRECPAG']),
    );

    $vistos = array();

    for ($i = 1; $i <= 12; $i++)
    {
        if ( ! isset($r['NPARC' . $i]) || $r['NPARC' . $i] === NULL)
        {
            continue;
        }

        $numero = (int) $r['NPARC' . $i];

        if ($numero < 1 || $numero > 12 || isset($vistos[$numero]))
        {
            continue;
        }

        $vistos[$numero] = TRUE;

        $dias = (int) ($r['QTDIAS' . $i] === NULL ? 0 : $r['QTDIAS' . $i]);

        if ($numero > 1 && $dias < 1)
        {
            $dias = 1;
        }

        if ($dias < 0)
        {
            $dias = 0;
        }

        $parcelas[] = array(
            $os,
            $numero,
            max(0, decimal_zero($r['VPARC' . $i])),
            $dias,
            data($r['DVENC' . $i]),
        );
    }
}

$total = gravar_lote($pdo, "$schema.biostech_chamado_planos_pagamento", array(
    'chamado_numero', 'data_emissao', 'data_vencimento_base', 'tipo_pagamento',
    'funcionario_id', 'codigo_centro_custo', 'condicao_pagamento_codigo',
    'forma_recebimento_codigo', 'documento_codigo', 'cliente_nota_id',
    'valor_total', 'quantidade_parcelas', 'tem_material_trabalho',
    'material_devolvido', 'numero_recibo',
), $linhas, array('chamado_numero'), TRUE);

$total_parcelas = gravar_lote($pdo, "$schema.biostech_chamado_parcelas", array(
    'chamado_numero', 'numero', 'valor', 'dias', 'vencimento',
), $parcelas, array('chamado_numero', 'numero'), TRUE);

$pdo->commit();
printf("%-14s %6d  (parcelas: %d | sem chamado: %d)\n",
    'planos', $total, $total_parcelas, $ignorados['sem_chamado']);

// ------------------------------------------------------------------ vinculos

$pdo->beginTransaction();

$linhas = array();
$ignorados = 0;

foreach ($c99 as $r)
{
    $pai   = texto($r['NUM_OS']);
    $filho = texto($r['NUM_FILHO']);

    if ($pai === NULL || $filho === NULL || $pai === $filho
        || ! isset($existentes[$pai]) || ! isset($existentes[$filho]))
    {
        $ignorados++;
        continue;
    }

    $linhas[] = array($pai, $filho);
}

$total = gravar_lote($pdo, "$schema.biostech_chamado_vinculos", array(
    'pai_numero', 'filho_numero',
), $linhas, 'ANY');

$pdo->commit();
printf("%-14s %6d  (ignorados: %d)\n", 'vinculos', $total, $ignorados);

// ------------------------------------------------------------------ log / eventos

$pdo->beginTransaction();

// refaz apenas o log importado
$pdo->exec("DELETE FROM $schema.biostech_chamado_log l
             USING $schema.biostech_chamado_eventos e
             WHERE l.evento_id = e.id AND e.tipo LIKE 'log_%'");
$pdo->exec("DELETE FROM $schema.biostech_chamado_eventos WHERE tipo LIKE 'log_%'");

$ignorados = 0;
$validos   = array();

foreach ($clog as $r)
{
    $os = texto($r['NUM_OS']);

    if ($os === NULL || ! isset($existentes[$os]))
    {
        $ignorados++;
        continue;
    }

    $validos[] = $r;
}

// ids dos eventos em bloco (evita um RETURNING por linha)
$ids = array();

if ($validos)
{
    $ids = $pdo->query("SELECT nextval('$schema.biostech_chamado_eventos_id_seq')
                          FROM generate_series(1, " . count($validos) . ")")
        ->fetchAll(PDO::FETCH_COLUMN);
}

$rotulos = array(
    'CATEGORIA'              => 'categoria',
    'DAT_CHA'                => 'data_abertura',
    'HOR_CHA'                => 'hora_abertura',
    'SIT_ATUAL_01'           => 'situacao_anterior',
    'STATUS'                 => 'status',
    'SIT_ATUAL_02'           => 'situacao',
    'QTDE_PARC'              => 'qtd_parcelas',
    'VALMAT'                 => 'valor_material',
    'VALSERV'                => 'valor_servico',
    'VALIMPOSTO'             => 'valor_imposto',
    'VALMOBRA'               => 'valor_mao_obra',
    'VALDESC'                => 'valor_desconto',
    'VALFATUG'               => 'valor_faturado',
    'MAT_FIXACAO'            => 'material_fixacao',
    'ACRESCIMO_CUSTO'        => 'acrescimo_custo',
    'DEF_RELATADO'           => 'defeito_relatado',
    'DEF_CONSTATADO'         => 'defeito_constatado',
    'SERVICO_EXECUTADO'      => 'servico_executado',
    'OBSERVACAO'             => 'observacao',
    'DT_PREV_ATEND'          => 'previsao_data',
    'HR_PREV_ATEND'          => 'previsao_hora',
    'DT_REAL_ATEND'          => 'atendimento_data',
    'HR_REAL_ATEND'          => 'atendimento_hora',
    'DT_REAL_ATEND_FIM'      => 'atendimento_data_fim',
    'HR_REAL_ATEND_FIM'      => 'atendimento_hora_fim',
    'DT_REAL_SOLUCAO'        => 'solucao_data',
    'HR_REAL_SOLUCAO'        => 'solucao_hora',
    'DATA_DEVOLUCAO'         => 'devolucao_data',
    'HORA_DEVOLUCAO'         => 'devolucao_hora',
    'TECNICO_RESP_ATEND'     => 'tecnico_atendimento',
    'RESP_ENTREGA_EQUIP'     => 'responsavel_entrega',
    'RESP_RECEBIM_EQUIP'     => 'responsavel_recebimento',
    'TEC_ABRIU_OS'           => 'tecnico_abertura',
    'QUEM_DEVOLVEU'          => 'quem_devolveu',
    'QUEM_RECEBEU'           => 'quem_recebeu',
    'TEC_CONCLUIU'           => 'tecnico_conclusao',
    'CONTATO'                => 'contato',
    'SOLICITANTE'            => 'solicitante',
    'LOCAL_ATEND'            => 'local_atendimento',
    'USUARIO_ULTIMA_ALTERACAO' => 'usuario_alteracao',
    'DATA_ULTIMA_ALTERACAO'  => 'data_alteracao',
);

$eventos = array();
$logs    = array();

foreach ($validos as $i => $r)
{
    $os      = texto($r['NUM_OS']);
    $quando  = momento($r['DATAMOV']);
    $usuario = texto($r['USUARIO'], 50);
    $mov     = texto($r['TIPOMOV'], 26);

    $tipo    = 'log_' . ($mov === NULL ? 'a' : mb_strtolower($mov, 'UTF-8'));

    $dados = json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    $id    = (int) $ids[$i];

    $eventos[] = array(
        $id,
        $os,
        $tipo,
        'Alteracao importada do Access (LOG ' . (int) $r['ID'] . ')',
        $usuario,
        $dados,
        $quando,
    );

    foreach ($rotulos as $campo => $rotulo)
    {
        $novo  = isset($r[$campo]) ? $r[$campo] : NULL;
        $velho = isset($r[$campo . '_OLD']) ? $r[$campo . '_OLD'] : NULL;

        if ($novo === NULL && $velho === NULL)
        {
            continue;
        }

        if ((string) $novo === (string) $velho)
        {
            continue;
        }

        $logs[] = array(
            $os,
            $id,
            $rotulo,
            $velho === NULL ? NULL : texto((string) $velho),
            $novo === NULL ? NULL : texto((string) $novo),
            $usuario,
            $quando,
        );
    }
}

$gravados = gravar_lote($pdo, "$schema.biostech_chamado_eventos", array(
    'id', 'chamado_numero', 'tipo', 'descricao', 'usuario', 'dados', 'created_at',
), $eventos, NULL, TRUE, 200, array('dados' => 'jsonb'));

$campos = gravar_lote($pdo, "$schema.biostech_chamado_log", array(
    'chamado_numero', 'evento_id', 'campo', 'valor_anterior', 'valor_novo', 'usuario', 'created_at',
), $logs, NULL, TRUE, 400);

$pdo->commit();
printf("%-14s %6d  (campos alterados: %d | sem chamado: %d)\n",
    'log', $gravados, $campos, $ignorados);

// ------------------------------------------------------------------ resumo

echo "\n== resumo ==\n";

foreach (array(
    'biostech_equipamentos', 'biostech_chamados', 'biostech_chamado_equipamento',
    'biostech_chamado_atendimento', 'biostech_chamado_intervencoes',
    'biostech_chamado_pecas', 'biostech_chamado_servicos', 'biostech_chamado_horas',
    'biostech_chamado_planos_pagamento', 'biostech_chamado_parcelas',
    'biostech_chamado_vinculos', 'biostech_chamado_eventos', 'biostech_chamado_log',
) as $tabela)
{
    $n = $pdo->query("SELECT count(*) FROM $schema.$tabela")->fetchColumn();
    printf("%-34s %7d\n", $tabela, $n);
}

echo "\ndata de referencia (chamados encerrados/abertos):\n";
foreach ($pdo->query("SELECT categoria, count(*) AS n,
                             min(data_abertura) AS de, max(data_abertura) AS ate
                        FROM $schema.biostech_chamados GROUP BY categoria ORDER BY categoria") as $r)
{
    printf("  %-16s %5d  %s .. %s\n", $r['categoria'], $r['n'], $r['de'], $r['ate']);
}
