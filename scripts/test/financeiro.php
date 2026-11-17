<?php
/**
 * BIOSTECH - teste funcional do modulo Financeiro
 *
 * Percorre todas as telas do modulo e exercita contas a pagar, contas a
 * receber, baixas, estornos, cancelamentos, movimentos bancarios, caixa,
 * cheques, balanco, cadastros de apoio e relatorios. Ao final remove os
 * dados de teste.
 *
 * Uso:
 *   php scripts/test/financeiro.php [url-base] [login] [senha]
 */

$base  = isset($argv[1]) ? rtrim($argv[1], '/') : 'http://127.0.0.1:8090';
$login = isset($argv[2]) ? $argv[2] : 'ANDRIUS';
$senha = isset($argv[3]) ? $argv[3] : '123123';

$jar    = tempnam(sys_get_temp_dir(), 'biostech_fin_');
$falhas = 0;
$marca  = 'TESTE FINANCEIRO ' . date('YmdHis');

// Dados criados (para limpeza).
$criados = array(
    'cp_numeros'   => array(),
    'cr_codigos'   => array(),
    'cheques'      => array(),
    'movimentos'   => array(),
    'balancos'     => array(),
    'conta_id'     => NULL,
    'banco_novo'   => FALSE,
    'conta_nova'   => FALSE,
    'caixa_data'   => '2099-12-30',
);

$schema = getenv('DB_SCHEMA') ?: 'material_grafico';

// ---------------------------------------------------------------------
// Banco
// ---------------------------------------------------------------------

try
{
    $pdo = new PDO(
        'pgsql:host=' . (getenv('DB_HOST') ?: 'sedsrvpostdesv.seduc.ce.gov.br')
        . ';port=' . (getenv('DB_PORT') ?: '5432')
        . ';dbname=' . (getenv('DB_NAME') ?: 'desenvolvimento'),
        getenv('DB_USER') ?: 'matgrafico',
        getenv('DB_PASS') ?: 'matgrafico'
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (Exception $e)
{
    fwrite(STDERR, "Sem acesso ao banco: " . $e->getMessage() . "\n");
    exit(2);
}

function db($sql, $params = array())
{
    global $pdo;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st;
}

function valor($sql, $params = array())
{
    $row = db($sql, $params)->fetch(PDO::FETCH_ASSOC);
    return $row ? reset($row) : NULL;
}

// ---------------------------------------------------------------------
// HTTP
// ---------------------------------------------------------------------

function req($url, $post = NULL)
{
    global $jar;

    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_COOKIEJAR      => $jar,
        CURLOPT_COOKIEFILE     => $jar,
        CURLOPT_TIMEOUT        => 60,
    ));

    if ($post !== NULL)
    {
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    $corpo  = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $final  = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    return array($status, (string) $corpo, $final);
}

function erro_pagina($corpo)
{
    foreach (array('An Error Was Encountered', 'Database Error', 'A PHP Error was encountered',
                   'Severity:', 'Fatal error', 'Parse error', 'Uncaught') as $marca)
    {
        if (strpos($corpo, $marca) !== FALSE)
        {
            return $marca;
        }
    }

    return NULL;
}

function checar($rotulo, $condicao, $detalhe = '')
{
    global $falhas;

    if ($condicao)
    {
        echo "[ok]   $rotulo\n";
    }
    else
    {
        $falhas++;
        echo "[FALHA] $rotulo" . ($detalhe !== '' ? " - $detalhe" : '') . "\n";
    }
}

function token($url)
{
    global $base;

    list($status, $corpo) = req($base . $url);

    if (preg_match('/name="biostech_csrf" value="([^"]+)"/', $corpo, $m))
    {
        return $m[1];
    }

    return '';
}

function paginar($url, $marcadores = array())
{
    global $base;

    list($status, $corpo, $final) = req($base . $url);
    $erro = erro_pagina($corpo);

    checar('GET ' . $url . ' => 200', $status === 200, 'status ' . $status);
    checar('GET ' . $url . ' sem erro', $erro === NULL, (string) $erro);

    foreach ($marcadores as $marca)
    {
        checar('GET ' . $url . ' contem "' . $marca . '"', strpos($corpo, $marca) !== FALSE);
    }

    return array($corpo, $final);
}

// ---------------------------------------------------------------------
// Login
// ---------------------------------------------------------------------

list($status, $corpo) = req($base . '/login');
preg_match('/name="biostech_csrf" value="([^"]+)"/', $corpo, $m);
$token = isset($m[1]) ? $m[1] : '';

list($status, $corpo) = req($base . '/login/entrar', array(
    'biostech_csrf' => $token,
    'login'         => $login,
    'senha'         => $senha,
));

checar('login efetuado', strpos($corpo, 'Painel') !== FALSE || strpos($corpo, 'Ultimos eventos') !== FALSE);
checar('perfil gestor/admin', strpos($corpo, 'class="perfil">gestor') !== FALSE || strpos($corpo, 'class="perfil">admin') !== FALSE);

// ---------------------------------------------------------------------
// Dados base
// ---------------------------------------------------------------------

$fornecedor = db(
    "SELECT codigo, razao_social FROM {$schema}.biostech_fornecedores
      WHERE ativo ORDER BY (codigo = 908) DESC, codigo LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

checar('fornecedor de teste encontrado', ! empty($fornecedor));

$cliente = db(
    "SELECT id, nome FROM {$schema}.biostech_clientes ORDER BY id LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

checar('cliente de teste encontrado', ! empty($cliente));

$centro = db(
    "SELECT codigo, nome FROM {$schema}.biostech_fin_centros_custo
      WHERE ativo ORDER BY codigo LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

// ---------------------------------------------------------------------
// 1. Navegacao por todas as telas
// ---------------------------------------------------------------------

paginar('/financeiro', array('Posicao bancaria', 'Contas a pagar em aberto'));
paginar('/financeiro/contas_pagar', array('Contas a pagar', 'Situacao'));
paginar('/financeiro/contas_receber', array('Contas a receber', 'Situacao'));
paginar('/financeiro/movimentos', array('Movimentos bancarios', 'Saldo bancario total'));
paginar('/financeiro/caixa', array('Caixa diario', 'Saldo atual do caixa'));
paginar('/financeiro/cheques', array('Cheques', 'Situacao'));
paginar('/financeiro/balanco', array('Balanco por periodo'));
paginar('/financeiro/cadastros', array('Cadastros do financeiro', 'Bancos'));
paginar('/financeiro/cadastro/bancos', array('Cadastro de bancos'));
paginar('/financeiro/cadastro/centros', array('Cadastro de centros de custo'));
paginar('/financeiro/cadastro/contas', array('Cadastro de contas bancarias'));
paginar('/financeiro/relatorios?tipo=cp', array('Contas a pagar'));
paginar('/financeiro/relatorios?tipo=cr', array('Contas a receber'));
paginar('/financeiro/relatorios?tipo=fluxo', array('Fluxo de caixa'));
paginar('/financeiro/relatorios?tipo=posicao', array('Posicao bancaria'));

// Filtros de situacao nao devem gerar erro.
paginar('/financeiro/contas_pagar?situacao=vencido', array('Contas a pagar'));
paginar('/financeiro/contas_receber?situacao=aberta', array('Contas a receber'));

// ---------------------------------------------------------------------
// 2. Contas a pagar: criar, editar, baixar, estornar, cancelar, excluir
// ---------------------------------------------------------------------

$token = token('/financeiro/conta_pagar_novo');

list($status, $corpo) = req($base . '/financeiro/conta_pagar_novo', array(
    'biostech_csrf'  => $token,
    'fornecedor_codigo' => (int) $fornecedor['codigo'],
    'centro_custo'   => $centro ? (int) $centro['codigo'] : '',
    'emissao'        => date('Y-m-d'),
    'lancamento'     => date('Y-m-d'),
    'vencimento'     => date('Y-m-d', strtotime('+5 days')),
    'valor'          => '100,00',
    'parcelas'       => '2',
    'intervalo_dias' => '30',
    'documento'      => 'DOC-' . date('His'),
    'historico'      => $marca . ' CP',
));

checar('criar conta a pagar sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));

$cp_numero = (int) valor(
    "SELECT max(numero) FROM {$schema}.biostech_contas_pagar
      WHERE historico = ? AND usuario_cadastro = ?",
    array($marca . ' CP', $login)
);

checar('conta a pagar numerada (CP)', $cp_numero > 0, (string) $cp_numero);
$criados['cp_numeros'][] = $cp_numero;

$parcelas = db(
    "SELECT * FROM {$schema}.biostech_contas_pagar
      WHERE numero = ? ORDER BY parcela",
    array($cp_numero)
)->fetchAll(PDO::FETCH_ASSOC);

checar('conta a pagar com 2 parcelas', count($parcelas) === 2, (string) count($parcelas));
checar('parcelas abertas (situacao 1)',
    count(array_filter($parcelas, function ($p) { return (int) $p['situacao'] === 1; })) === 2);
checar('soma das parcelas 100,00',
    abs(array_sum(array_map('floatval', array_column($parcelas, 'valor'))) - 100.0) < 0.001);

$cp1 = $parcelas[0];
$cp2 = $parcelas[1];

paginar('/financeiro/contas_pagar?busca=' . $cp_numero, array('Contas a pagar', (string) $cp_numero));
paginar('/financeiro/conta_pagar_ver/' . $cp1['id'], array('Conta a pagar ' . $cp_numero . '/1', $fornecedor['razao_social']));

// Editar a primeira parcela para 120,00.
$token = token('/financeiro/conta_pagar_editar/' . $cp1['id']);

list($status, $corpo) = req($base . '/financeiro/conta_pagar_editar/' . $cp1['id'], array(
    'biostech_csrf'  => $token,
    'fornecedor_codigo' => (int) $fornecedor['codigo'],
    'centro_custo'   => $centro ? (int) $centro['codigo'] : '',
    'emissao'        => date('Y-m-d'),
    'lancamento'     => date('Y-m-d'),
    'vencimento'     => $cp1['vencimento'],
    'valor'          => '120,00',
    'documento'      => $cp1['documento'],
    'historico'      => $marca . ' CP',
));

checar('editar conta a pagar sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('valor editado para 120,00',
    abs((float) valor("SELECT valor FROM {$schema}.biostech_contas_pagar WHERE id = ?", array($cp1['id'])) - 120.0) < 0.001);

// Baixar a primeira parcela: 120 + 5 multa + 2,50 juros - 1 desconto = 126,50.
$token = token('/financeiro/conta_pagar_baixar/' . $cp1['id']);

list($status, $corpo) = req($base . '/financeiro/conta_pagar_baixar/' . $cp1['id'], array(
    'biostech_csrf' => $token,
    'data_pagamento'=> date('Y-m-d'),
    'multa'         => '5,00',
    'juros'         => '2,50',
    'desconto'      => '1,00',
    'valor_pago'    => '126,50',
));

checar('baixar conta a pagar sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));

$baixa = db(
    "SELECT * FROM {$schema}.biostech_contas_pagar_baixas WHERE conta_pagar_id = ?",
    array($cp1['id'])
)->fetch(PDO::FETCH_ASSOC);

checar('baixa gravada', ! empty($baixa));
checar('valor atualizado da baixa 127,50', $baixa && abs((float) $baixa['valor_atualizado'] - 127.5) < 0.001,
    $baixa ? $baixa['valor_atualizado'] : '');
checar('valor pago da baixa 126,50', $baixa && abs((float) $baixa['valor_pago'] - 126.5) < 0.001,
    $baixa ? $baixa['valor_pago'] : '');

$cp1_db = db("SELECT * FROM {$schema}.biostech_contas_pagar WHERE id = ?", array($cp1['id']))->fetch(PDO::FETCH_ASSOC);
checar('titulo baixado (situacao 2)', (int) $cp1_db['situacao'] === 2, (string) $cp1_db['situacao']);
checar('valor baixado gravado no titulo', abs((float) $cp1_db['valor_baixado'] - 126.5) < 0.001);

paginar('/financeiro/conta_pagar_ver/' . $cp1['id'], array('Conta a pagar ' . $cp_numero . '/1', 'Baixada', '126,50'));
paginar('/financeiro/conta_pagar_comprovante/' . $cp1['id'], array('Comprovante de pagamento', '126,50'));

// Estornar a baixa.
$token = token('/financeiro/conta_pagar_ver/' . $cp1['id']);

list($status, $corpo) = req($base . '/financeiro/conta_pagar_estornar/' . $cp1['id'], array(
    'biostech_csrf' => $token,
));

checar('estornar baixa sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('baixa removida',
    (int) valor("SELECT count(*) FROM {$schema}.biostech_contas_pagar_baixas WHERE conta_pagar_id = ?", array($cp1['id'])) === 0);
checar('titulo reaberto (situacao 1)',
    (int) valor("SELECT situacao FROM {$schema}.biostech_contas_pagar WHERE id = ?", array($cp1['id'])) === 1);

// Cancelar o titulo.
$token = token('/financeiro/conta_pagar_ver/' . $cp1['id']);

list($status, $corpo) = req($base . '/financeiro/conta_pagar_cancelar/' . $cp1['id'], array(
    'biostech_csrf' => $token,
    'motivo'        => 'TESTE AUTOMATICO',
));

checar('cancelar conta a pagar sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
$cp1_db = db("SELECT situacao, cancelado FROM {$schema}.biostech_contas_pagar WHERE id = ?", array($cp1['id']))->fetch(PDO::FETCH_ASSOC);
checar('titulo cancelado (situacao 3)', (int) $cp1_db['situacao'] === 3, (string) $cp1_db['situacao']);
checar('flag cancelado gravada', $cp1_db['cancelado'] === TRUE || $cp1_db['cancelado'] === 't');

// Excluir as duas parcelas.
foreach (array($cp1['id'], $cp2['id']) as $id)
{
    $token = token('/financeiro/conta_pagar_ver/' . $id);

    req($base . '/financeiro/conta_pagar_excluir/' . $id, array('biostech_csrf' => $token));
}

checar('titulos excluidos',
    (int) valor("SELECT count(*) FROM {$schema}.biostech_contas_pagar WHERE numero = ?", array($cp_numero)) === 0);

// ---------------------------------------------------------------------
// 3. Contas a receber: criar, receber, estornar, cancelar, excluir
// ---------------------------------------------------------------------

$token = token('/financeiro/conta_receber_novo');

list($status, $corpo) = req($base . '/financeiro/conta_receber_novo', array(
    'biostech_csrf'           => $token,
    'cliente_id'              => (int) $cliente['id'],
    'data_emissao'            => date('Y-m-d'),
    'data_vencimento'         => date('Y-m-d', strtotime('+10 days')),
    'valor'                   => '200,00',
    'parcelas'                => '2',
    'intervalo_dias'          => '30',
    'numero_documento'        => 'DOC-' . date('His'),
    'desconto_especial'       => '0,00',
    'vdesp'                   => '0,00',
    'aliq_issqn'              => '0',
    'viss'                    => '0,00',
    'vliq'                    => '200,00',
    'historico'               => $marca . ' CR',
));

checar('criar conta a receber sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));

$cr_linhas = db(
    "SELECT * FROM {$schema}.biostech_contas_receber
      WHERE historico = ? AND usuario_cadastro = ? ORDER BY codigo",
    array($marca . ' CR', $login)
)->fetchAll(PDO::FETCH_ASSOC);

checar('conta a receber com 2 parcelas', count($cr_linhas) === 2, (string) count($cr_linhas));
checar('parcelas com codigos distintos',
    count($cr_linhas) === 2 && (int) $cr_linhas[0]['codigo'] !== (int) $cr_linhas[1]['codigo']);
checar('parcelas numeradas 1 e 2',
    count($cr_linhas) === 2 && (int) $cr_linhas[0]['parcela'] === 1 && (int) $cr_linhas[1]['parcela'] === 2);
checar('soma das parcelas 200,00',
    abs(array_sum(array_map('floatval', array_column($cr_linhas, 'valor'))) - 200.0) < 0.001);

$cr_codigo  = count($cr_linhas) > 0 ? (int) $cr_linhas[0]['codigo'] : 0;
$cr_codigo2 = count($cr_linhas) > 1 ? (int) $cr_linhas[1]['codigo'] : 0;

checar('conta a receber numerada (CR)', $cr_codigo > 0, (string) $cr_codigo);
$criados['cr_codigos'][] = $cr_codigo;

if ($cr_codigo2 > 0)
{
    $criados['cr_codigos'][] = $cr_codigo2;
}

$cr = $cr_linhas ? $cr_linhas[0] : array();

checar('conta a receber criada', ! empty($cr));
checar('valor da primeira parcela 100,00', $cr && abs((float) $cr['valor'] - 100.0) < 0.001);
checar('conta a receber nao cancelada', $cr && ($cr['cancelado'] === FALSE || $cr['cancelado'] === 'f'));

paginar('/financeiro/contas_receber?busca=' . $cr_codigo, array('Contas a receber', (string) $cr_codigo));
paginar('/financeiro/conta_receber_ver/' . $cr_codigo, array('Conta a receber ' . $cr_codigo . '/1', $cliente['nome']));

// Receber o titulo.
$token = token('/financeiro/conta_receber_baixar/' . $cr_codigo);

list($status, $corpo) = req($base . '/financeiro/conta_receber_baixar/' . $cr_codigo, array(
    'biostech_csrf'  => $token,
    'data_pagamento' => date('Y-m-d'),
    'multa'          => '0,00',
    'juros'          => '0,00',
    'desconto'       => '0,00',
    'valor_recebido' => '100,00',
));

checar('receber conta a receber sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('baixa de conta a receber gravada',
    (int) valor("SELECT count(*) FROM {$schema}.biostech_contas_receber_baixas WHERE conta_codigo = ?", array($cr_codigo)) === 1);
checar('data de baixa gravada',
    valor("SELECT data_baixa FROM {$schema}.biostech_contas_receber WHERE codigo = ? AND parcela = 1", array($cr_codigo)) !== NULL);

paginar('/financeiro/conta_receber_comprovante/' . $cr_codigo, array('Comprovante de recebimento', '100,00'));

// Estornar o recebimento.
$token = token('/financeiro/conta_receber_ver/' . $cr_codigo);

list($status, $corpo) = req($base . '/financeiro/conta_receber_estornar/' . $cr_codigo, array(
    'biostech_csrf' => $token,
));

checar('estornar recebimento sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('baixa de conta a receber removida',
    (int) valor("SELECT count(*) FROM {$schema}.biostech_contas_receber_baixas WHERE conta_codigo = ?", array($cr_codigo)) === 0);

// Cancelar e excluir.
$token = token('/financeiro/conta_receber_ver/' . $cr_codigo);

req($base . '/financeiro/conta_receber_cancelar/' . $cr_codigo, array(
    'biostech_csrf' => $token,
    'motivo'        => 'TESTE AUTOMATICO',
));

$cr_db = db("SELECT cancelado FROM {$schema}.biostech_contas_receber WHERE codigo = ? AND parcela = 1", array($cr_codigo))->fetch(PDO::FETCH_ASSOC);
checar('conta a receber cancelada', $cr_db && ($cr_db['cancelado'] === TRUE || $cr_db['cancelado'] === 't'));

$token = token('/financeiro/conta_receber_ver/' . $cr_codigo);
req($base . '/financeiro/conta_receber_excluir/' . $cr_codigo, array('biostech_csrf' => $token));

checar('conta a receber excluida',
    (int) valor("SELECT count(*) FROM {$schema}.biostech_contas_receber WHERE codigo = ?", array($cr_codigo)) === 0);

// Segunda parcela: cancelar e excluir (codigo proprio).
if ($cr_codigo2 > 0)
{
    $token = token('/financeiro/conta_receber_ver/' . $cr_codigo2);

    req($base . '/financeiro/conta_receber_cancelar/' . $cr_codigo2, array(
        'biostech_csrf' => $token,
        'motivo'        => 'TESTE AUTOMATICO PARCELA 2',
    ));

    $cr2_db = db("SELECT cancelado FROM {$schema}.biostech_contas_receber WHERE codigo = ?", array($cr_codigo2))->fetch(PDO::FETCH_ASSOC);
    checar('segunda parcela cancelada', $cr2_db && ($cr2_db['cancelado'] === TRUE || $cr2_db['cancelado'] === 't'));

    $token = token('/financeiro/conta_receber_ver/' . $cr_codigo2);
    req($base . '/financeiro/conta_receber_excluir/' . $cr_codigo2, array('biostech_csrf' => $token));

    checar('segunda parcela excluida',
        (int) valor("SELECT count(*) FROM {$schema}.biostech_contas_receber WHERE codigo = ?", array($cr_codigo2)) === 0);
}

// ---------------------------------------------------------------------
// 4. Cadastros de apoio (banco, conta) + movimentos bancarios
// ---------------------------------------------------------------------

$banco_codigo = 'TSTFIN';
$banco_existia = (int) valor(
    "SELECT count(*) FROM {$schema}.biostech_fin_bancos WHERE codigo = ?",
    array($banco_codigo)
) > 0;

$token = token('/financeiro/cadastro/bancos');

list($status, $corpo) = req($base . '/financeiro/cadastro/bancos', array(
    'biostech_csrf' => $token,
    'codigo'        => $banco_codigo,
    'nome'          => 'BANCO TESTE FINANCEIRO',
));

checar('salvar banco sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('banco gravado',
    (int) valor("SELECT count(*) FROM {$schema}.biostech_fin_bancos WHERE codigo = ?", array($banco_codigo)) === 1);
$criados['banco_novo'] = ! $banco_existia;

// Atualiza o nome (upsert) para exercitar a edicao.
$token = token('/financeiro/cadastro/bancos/' . $banco_codigo);

req($base . '/financeiro/cadastro/bancos/' . $banco_codigo, array(
    'biostech_csrf' => $token,
    'codigo'        => $banco_codigo,
    'nome'          => 'BANCO TESTE FINANCEIRO',
));

checar('banco editado sem erro',
    valor("SELECT nome FROM {$schema}.biostech_fin_bancos WHERE codigo = ?", array($banco_codigo)) === 'BANCO TESTE FINANCEIRO');

$agencia_teste = 'T001';
$conta_teste   = 'T999999';

$token = token('/financeiro/cadastro/contas');

list($status, $corpo) = req($base . '/financeiro/cadastro/contas', array(
    'biostech_csrf' => $token,
    'banco_codigo'  => $banco_codigo,
    'agencia'       => $agencia_teste,
    'conta'         => $conta_teste,
    'titular'       => 'CONTA TESTE FINANCEIRO',
    'valor_limite'  => '0,00',
    'valor_real'    => '0,00',
));

checar('salvar conta bancaria sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));

$conta_id = (int) valor(
    "SELECT id FROM {$schema}.biostech_fin_contas
      WHERE banco_codigo = ? AND agencia = ? AND conta = ?",
    array($banco_codigo, $agencia_teste, $conta_teste)
);

checar('conta bancaria gravada', $conta_id > 0, (string) $conta_id);
$criados['conta_id']   = $conta_id;
$criados['conta_nova'] = $conta_id > 0;

$token = token('/financeiro/movimento_novo');

list($status, $corpo) = req($base . '/financeiro/movimento_novo', array(
    'biostech_csrf' => $token,
    'data'          => date('Y-m-d'),
    'banco_codigo'  => $banco_codigo,
    'agencia'       => $agencia_teste,
    'conta'         => $conta_teste,
    'tipo'          => 'C',
    'valor'         => '1000,00',
    'historico'     => $marca . ' CREDITO',
));

checar('criar movimento de credito sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));

$mov_credito = db(
    "SELECT * FROM {$schema}.biostech_fin_movimentos_bancarios
      WHERE historico = ? ORDER BY id DESC LIMIT 1",
    array($marca . ' CREDITO')
)->fetch(PDO::FETCH_ASSOC);

checar('movimento de credito gravado', ! empty($mov_credito));
$criados['movimentos'][] = $mov_credito ? (int) $mov_credito['id'] : 0;

if ($mov_credito)
{
    checar('saldo do credito = anterior + 1000',
        abs((float) $mov_credito['saldo_atual'] - ((float) $mov_credito['saldo_anterior'] + 1000.0)) < 0.001);

    $token = token('/financeiro/movimento_novo');

    req($base . '/financeiro/movimento_novo', array(
        'biostech_csrf' => $token,
        'data'          => date('Y-m-d'),
        'banco_codigo'  => $banco_codigo,
        'agencia'       => $agencia_teste,
        'conta'         => $conta_teste,
        'tipo'          => 'D',
        'valor'         => '300,00',
        'historico'     => $marca . ' DEBITO',
    ));

    $mov_debito = db(
        "SELECT * FROM {$schema}.biostech_fin_movimentos_bancarios
          WHERE historico = ? ORDER BY id DESC LIMIT 1",
        array($marca . ' DEBITO')
    )->fetch(PDO::FETCH_ASSOC);

    checar('movimento de debito gravado', ! empty($mov_debito));
    $criados['movimentos'][] = $mov_debito ? (int) $mov_debito['id'] : 0;

    if ($mov_debito)
    {
        checar('saldo do debito = 1000 - 300',
            abs((float) $mov_debito['saldo_atual'] - 700.0) < 0.001,
            (string) $mov_debito['saldo_atual']);

        paginar('/financeiro/movimentos?busca=' . rawurlencode($marca), array('Movimentos bancarios', $marca));
        paginar('/financeiro/relatorios?tipo=posicao', array('BANCO TESTE FINANCEIRO'));

        // Excluir os movimentos.
        foreach ($criados['movimentos'] as $id)
        {
            if ($id <= 0) { continue; }

            $token = token('/financeiro/movimento_editar/' . $id);
            req($base . '/financeiro/movimento_excluir/' . $id, array('biostech_csrf' => $token));
        }

        checar('movimentos excluidos',
            (int) valor("SELECT count(*) FROM {$schema}.biostech_fin_movimentos_bancarios WHERE historico IN (?, ?)",
                array($marca . ' CREDITO', $marca . ' DEBITO')) === 0);
    }
}

// ---------------------------------------------------------------------
// 5. Caixa diario: lancar, fechar, reabrir, excluir
// ---------------------------------------------------------------------

$dia = $criados['caixa_data'];
$token = token('/financeiro/caixa_dia/' . $dia);

list($status, $corpo) = req($base . '/financeiro/caixa_movimento_salvar', array(
    'biostech_csrf' => $token,
    'data'          => $dia,
    'historico'     => $marca . ' CAIXA ENTRADA',
    'valor_entrada' => '100,00',
));

checar('lancar entrada de caixa sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));

$dia_db = db("SELECT * FROM {$schema}.biostech_fin_caixa_diario WHERE data = ?", array($dia))->fetch(PDO::FETCH_ASSOC);
checar('dia de caixa criado', ! empty($dia_db));
checar('total de entradas 100,00', $dia_db && abs((float) $dia_db['total_entradas'] - 100.0) < 0.001,
    $dia_db ? $dia_db['total_entradas'] : '');
checar('saldo do dia = anterior + 100',
    $dia_db && abs((float) $dia_db['saldo_atual'] - ((float) $dia_db['saldo_anterior'] + 100.0)) < 0.001);

$token = token('/financeiro/caixa_dia/' . $dia);

req($base . '/financeiro/caixa_movimento_salvar', array(
    'biostech_csrf' => $token,
    'data'          => $dia,
    'historico'     => $marca . ' CAIXA SAIDA',
    'valor_saida'   => '40,00',
));

$dia_db = db("SELECT * FROM {$schema}.biostech_fin_caixa_diario WHERE data = ?", array($dia))->fetch(PDO::FETCH_ASSOC);
checar('total de saidas 40,00', $dia_db && abs((float) $dia_db['total_saidas'] - 40.0) < 0.001);
checar('saldo do dia = anterior + 60',
    $dia_db && abs((float) $dia_db['saldo_atual'] - ((float) $dia_db['saldo_anterior'] + 60.0)) < 0.001);

// Fechar e reabrir.
$token = token('/financeiro/caixa_dia/' . $dia);
req($base . '/financeiro/caixa_fechar/' . $dia, array('biostech_csrf' => $token));

checar('caixa fechado',
    valor("SELECT fechado FROM {$schema}.biostech_fin_caixa_diario WHERE data = ?", array($dia)) === TRUE
    || valor("SELECT fechado FROM {$schema}.biostech_fin_caixa_diario WHERE data = ?", array($dia)) === 't');

$token = token('/financeiro/caixa_dia/' . $dia);
req($base . '/financeiro/caixa_reabrir/' . $dia, array('biostech_csrf' => $token));

checar('caixa reaberto',
    valor("SELECT fechado FROM {$schema}.biostech_fin_caixa_diario WHERE data = ?", array($dia)) === FALSE
    || valor("SELECT fechado FROM {$schema}.biostech_fin_caixa_diario WHERE data = ?", array($dia)) === 'f');

// Excluir os movimentos de caixa.
$movs_caixa = db(
    "SELECT id FROM {$schema}.biostech_fin_caixa_movimentos WHERE data = ?",
    array($dia)
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($movs_caixa as $m)
{
    $token = token('/financeiro/caixa_dia/' . $dia);
    req($base . '/financeiro/caixa_movimento_excluir/' . $m['id'], array('biostech_csrf' => $token));
}

checar('movimentos de caixa excluidos',
    (int) valor("SELECT count(*) FROM {$schema}.biostech_fin_caixa_movimentos WHERE data = ?", array($dia)) === 0);

// ---------------------------------------------------------------------
// 6. Cheques: emitir, compensar, excluir
// ---------------------------------------------------------------------

$cheque_numero = 'TST-' . date('His');

$token = token('/financeiro/cheque_novo');

list($status, $corpo) = req($base . '/financeiro/cheque_novo', array(
    'biostech_csrf'  => $token,
    'numero'         => $cheque_numero,
    'banco_codigo'   => $banco_codigo,
    'agencia'        => $agencia_teste,
    'conta'          => $conta_teste,
    'valor'          => '150,00',
    'favorecido'     => $marca . ' CHEQUE',
    'data_emissao'   => date('Y-m-d'),
    'data_vencimento'=> date('Y-m-d', strtotime('+15 days')),
    'situacao'       => '1',
));

checar('criar cheque sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));

$cheque = db(
    "SELECT * FROM {$schema}.biostech_fin_cheques WHERE numero = ? ORDER BY id DESC LIMIT 1",
    array($cheque_numero)
)->fetch(PDO::FETCH_ASSOC);

checar('cheque gravado', ! empty($cheque));
$criados['cheques'][] = $cheque ? (int) $cheque['id'] : 0;

if ($cheque)
{
    checar('cheque emitido (situacao 1)', (int) $cheque['situacao'] === 1);
    paginar('/financeiro/cheques?busca=' . $cheque_numero, array('Cheques', $cheque_numero));

    $token = token('/financeiro/cheques');
    req($base . '/financeiro/cheque_compensar/' . $cheque['id'], array(
        'biostech_csrf'     => $token,
        'data_compensacao'  => date('Y-m-d'),
    ));

    $cheque_db = db("SELECT * FROM {$schema}.biostech_fin_cheques WHERE id = ?", array($cheque['id']))->fetch(PDO::FETCH_ASSOC);
    checar('cheque compensado (situacao 2)', (int) $cheque_db['situacao'] === 2, (string) $cheque_db['situacao']);
    checar('data de compensacao gravada', ! empty($cheque_db['data_compensacao']));

    $token = token('/financeiro/cheques');
    req($base . '/financeiro/cheque_excluir/' . $cheque['id'], array('biostech_csrf' => $token));

    checar('cheque excluido',
        (int) valor("SELECT count(*) FROM {$schema}.biostech_fin_cheques WHERE id = ?", array($cheque['id'])) === 0);
}

// ---------------------------------------------------------------------
// 7. Balanco: gerar e excluir
// ---------------------------------------------------------------------

$inicio = date('Y-m-01');
$fim    = date('Y-m-t');
$token  = token('/financeiro/balanco');

list($status, $corpo) = req($base . '/financeiro/balanco_gerar', array(
    'biostech_csrf' => $token,
    'inicio'        => $inicio,
    'fim'           => $fim,
));

checar('gerar balanco sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));

$balanco_id = (int) valor(
    "SELECT max(id) FROM {$schema}.biostech_fin_balanco WHERE data_inicio = ? AND data_fim = ?",
    array($inicio, $fim)
);

checar('balanco gravado', $balanco_id > 0, (string) $balanco_id);
$criados['balancos'][] = $balanco_id;

if ($balanco_id > 0)
{
    $bal = db("SELECT * FROM {$schema}.biostech_fin_balanco WHERE id = ?", array($balanco_id))->fetch(PDO::FETCH_ASSOC);
    checar('balanco com mes/ano', ! empty($bal['mes_ano']), $bal ? (string) $bal['mes_ano'] : '');
    checar('balanco com liquido calculado',
        abs((float) $bal['liquido_faturado'] - ((float) $bal['bruto_faturado'] - (float) $bal['iss_faturado'])) < 0.001);

    paginar('/financeiro/balanco?de=' . $inicio . '&ate=' . $fim, array('Balanco por periodo'));
    paginar('/financeiro/relatorios?tipo=balanco&de=' . $inicio . '&ate=' . $fim, array('Balanco'));

    $token = token('/financeiro/balanco');
    req($base . '/financeiro/balanco_excluir/' . $balanco_id, array('biostech_csrf' => $token));

    checar('balanco excluido',
        (int) valor("SELECT count(*) FROM {$schema}.biostech_fin_balanco WHERE id = ?", array($balanco_id)) === 0);
}

// ---------------------------------------------------------------------
// 8. Cadastros de apoio: demais catalogos e despesas fixas
// ---------------------------------------------------------------------

$codigo_cad = (int) date('His') + 900000;

$catalogos = array(
    'centros'        => array('codigo' => $codigo_cad, 'nome' => $marca . ' CENTRO'),
    'naturezas'      => array('codigo' => $codigo_cad, 'nome' => $marca . ' NATUREZA'),
    'status'         => array('codigo' => $codigo_cad, 'nome' => $marca . ' STATUS'),
    'periodicidades' => array('codigo' => $codigo_cad, 'nome' => $marca . ' PERIODICIDADE'),
    'cartoes'        => array('codigo' => $codigo_cad, 'nome' => $marca . ' CARTAO'),
);

$tabelas_cad = array(
    'centros'        => 'biostech_fin_centros_custo',
    'naturezas'      => 'biostech_fin_naturezas_operacao',
    'status'         => 'biostech_fin_status_pagamento',
    'periodicidades' => 'biostech_fin_periodicidades',
    'cartoes'        => 'biostech_fin_cartoes',
);

foreach ($catalogos as $tipo => $dados)
{
    $token = token('/financeiro/cadastro/' . $tipo);

    list($status, $corpo) = req($base . '/financeiro/cadastro/' . $tipo, array_merge(
        array('biostech_csrf' => $token),
        $dados
    ));

    checar('salvar cadastro de ' . $tipo . ' sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
    checar('cadastro de ' . $tipo . ' gravado',
        (int) valor("SELECT count(*) FROM {$schema}." . $tabelas_cad[$tipo] . " WHERE codigo = ?", array($dados['codigo'])) === 1);

    $token = token('/financeiro/cadastro/' . $tipo);
    req($base . '/financeiro/cadastro_excluir/' . $tipo . '/' . $dados['codigo'], array('biostech_csrf' => $token));

    checar('cadastro de ' . $tipo . ' excluido',
        (int) valor("SELECT count(*) FROM {$schema}." . $tabelas_cad[$tipo] . " WHERE codigo = ?", array($dados['codigo'])) === 0);
}

// Agencia do banco de teste.
$agencia_cad = 'T' . date('His');

$token = token('/financeiro/cadastro/agencias');
list($status, $corpo) = req($base . '/financeiro/cadastro/agencias', array(
    'biostech_csrf' => $token,
    'banco_codigo'  => $banco_codigo,
    'agencia'       => $agencia_cad,
    'nome'          => $marca . ' AGENCIA',
    'cidade'        => 'FORTALEZA',
    'uf'            => 'CE',
));

checar('salvar agencia sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));

$agencia_id = (int) valor(
    "SELECT id FROM {$schema}.biostech_fin_agencias WHERE banco_codigo = ? AND agencia = ?",
    array($banco_codigo, $agencia_cad)
);

checar('agencia gravada', $agencia_id > 0, (string) $agencia_id);

$token = token('/financeiro/cadastro/agencias');
req($base . '/financeiro/cadastro_excluir/agencias/' . $agencia_id, array('biostech_csrf' => $token));

checar('agencia excluida', $agencia_id > 0 &&
    (int) valor("SELECT count(*) FROM {$schema}.biostech_fin_agencias WHERE id = ?", array($agencia_id)) === 0);

// Despesa fixa.
$nat_cod = valor("SELECT codigo FROM {$schema}.biostech_fin_naturezas_operacao ORDER BY codigo LIMIT 1");
$per_cod = valor("SELECT codigo FROM {$schema}.biostech_fin_periodicidades ORDER BY codigo LIMIT 1");
$cen_cod = valor("SELECT codigo FROM {$schema}.biostech_fin_centros_custo ORDER BY codigo LIMIT 1");

$token = token('/financeiro/cadastro/despesas');
list($status, $corpo) = req($base . '/financeiro/cadastro/despesas', array(
    'biostech_csrf'    => $token,
    'codigo'           => $codigo_cad,
    'nome'             => $marca . ' DESPESA',
    'natureza_codigo'  => $nat_cod,
    'periodicidade'    => $per_cod,
    'centro_custo'     => $cen_cod,
    'valor'            => '10,00',
    'dia_vencimento'   => '5',
    'observacao'       => 'TESTE AUTOMATICO',
    'ativo'            => '1',
));

checar('salvar despesa fixa sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));

$despesa = db(
    "SELECT * FROM {$schema}.biostech_fin_despesas_fixas WHERE codigo = ?",
    array($codigo_cad)
)->fetch(PDO::FETCH_ASSOC);

checar('despesa fixa gravada', ! empty($despesa));
checar('valor da despesa fixa 10,00', $despesa && abs((float) $despesa['valor'] - 10.0) < 0.001,
    $despesa ? $despesa['valor'] : '');
checar('dia de vencimento gravado', $despesa && (int) $despesa['dia_vencimento'] === 5);
checar('despesa fixa ativa', $despesa && ($despesa['ativo'] === TRUE || $despesa['ativo'] === 't'));

$token = token('/financeiro/cadastro/despesas');
req($base . '/financeiro/cadastro_excluir/despesas/' . $codigo_cad, array('biostech_csrf' => $token));

checar('despesa fixa excluida',
    (int) valor("SELECT count(*) FROM {$schema}.biostech_fin_despesas_fixas WHERE codigo = ?", array($codigo_cad)) === 0);

// ---------------------------------------------------------------------
// 9. Limpeza
// ---------------------------------------------------------------------

echo "\nLimpando dados de teste...\n";

try
{
    // Contas a pagar remanescentes (caso algum teste tenha falhado no meio).
    foreach ($criados['cp_numeros'] as $n)
    {
        db("DELETE FROM {$schema}.biostech_contas_pagar_baixas WHERE numero = ?", array($n));
        db("DELETE FROM {$schema}.biostech_contas_pagar_log WHERE conta_pagar_numero = ?", array($n));
        db("DELETE FROM {$schema}.biostech_contas_pagar WHERE numero = ?", array($n));
    }

    foreach ($criados['cr_codigos'] as $c)
    {
        db("DELETE FROM {$schema}.biostech_contas_receber_baixas WHERE conta_codigo = ?", array($c));
        db("DELETE FROM {$schema}.biostech_contas_receber WHERE codigo = ?", array($c));
    }

    foreach ($criados['cheques'] as $id)
    {
        if ($id > 0)
        {
            db("DELETE FROM {$schema}.biostech_fin_cheques WHERE id = ?", array($id));
        }
    }

    foreach ($criados['movimentos'] as $id)
    {
        if ($id > 0)
        {
            db("DELETE FROM {$schema}.biostech_fin_movimentos_bancarios WHERE id = ?", array($id));
        }
    }

    foreach ($criados['balancos'] as $id)
    {
        if ($id > 0)
        {
            db("DELETE FROM {$schema}.biostech_fin_balanco WHERE id = ?", array($id));
        }
    }

    // Caixa do dia de teste.
    db("DELETE FROM {$schema}.biostech_fin_caixa_movimentos WHERE data = ?", array($criados['caixa_data']));
    db("DELETE FROM {$schema}.biostech_fin_caixa_diario WHERE data = ?", array($criados['caixa_data']));

    // Cadastros de teste.
    if ($criados['conta_nova'] && $criados['conta_id'])
    {
        db("DELETE FROM {$schema}.biostech_fin_contas WHERE id = ?", array($criados['conta_id']));
    }

    if ($criados['banco_novo'])
    {
        db("DELETE FROM {$schema}.biostech_fin_bancos WHERE codigo = ?", array($banco_codigo));
    }

    echo "Dados de teste removidos.\n";
}
catch (Exception $e)
{
    echo "[aviso] falha na limpeza: " . $e->getMessage() . "\n";
}

@unlink($jar);

echo "\n";
if ($falhas > 0)
{
    echo "FALHAS: $falhas\n";
    exit(1);
}

echo "TODOS OS TESTES DO FINANCEIRO PASSARAM\n";
exit(0);
