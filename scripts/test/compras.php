<?php
/**
 * BIOSTECH - teste funcional do modulo Compras
 *
 * Cria pedidos, fecha, cancela o fechamento, replica, transfere itens,
 * percorre solicitacao -> cotacao -> pedido e, ao final, remove os dados
 * de teste e restaura os produtos/saldos utilizados.
 *
 * Uso:
 *   php scripts/test/compras.php [url-base] [login] [senha]
 */

$base  = isset($argv[1]) ? rtrim($argv[1], '/') : 'http://127.0.0.1:8090';
$login = isset($argv[2]) ? $argv[2] : 'ANDRIUS';
$senha = isset($argv[3]) ? $argv[3] : '123123';

$jar     = tempnam(sys_get_temp_dir(), 'biostech_compras_');
$falhas  = 0;
$criados = array('pedidos' => array(), 'cotacoes' => array(), 'solicitacoes' => array());

// ---------------------------------------------------------------------
// Banco (para asserts e limpeza)
// ---------------------------------------------------------------------

$schema = getenv('DB_SCHEMA') ?: 'material_grafico';

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
      WHERE codigo = 908"
)->fetch(PDO::FETCH_ASSOC);

if ( ! $fornecedor)
{
    $fornecedor = db(
        "SELECT codigo, razao_social FROM {$schema}.biostech_fornecedores
          ORDER BY codigo LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);
}

checar('fornecedor de teste encontrado', ! empty($fornecedor), 'sem fornecedores');

$produtos = db(
    "SELECT id, descricao, valor_compra FROM {$schema}.biostech_produtos
      WHERE ativo AND id > 0 AND valor_compra > 0
      ORDER BY id LIMIT 2"
)->fetchAll(PDO::FETCH_ASSOC);

checar('dois produtos de teste encontrados', count($produtos) === 2);

$p1 = $produtos[0];
$p2 = $produtos[1];

$snapshot = array();

foreach (array($p1['id'], $p2['id']) as $pid)
{
    $prod = db(
        "SELECT valor_compra, valor_compra_desconto, ultimo_pedido_compra,
                data_ultima_entrada, ultima_nf_entrada, percentual_lucro,
                valor_venda, updated_at
           FROM {$schema}.biostech_produtos WHERE id = ?",
        array($pid)
    )->fetch(PDO::FETCH_ASSOC);

    $saldo = db(
        "SELECT quantidade, quantidade_consumida
           FROM {$schema}.biostech_estoque_saldos WHERE produto_id = ?",
        array($pid)
    )->fetch(PDO::FETCH_ASSOC);

    $snapshot[$pid] = array('produto' => $prod, 'saldo' => $saldo);
}

// ---------------------------------------------------------------------
// 1. Criar pedido
// ---------------------------------------------------------------------

$nf = 'TESTE-COMPRAS-' . date('YmdHis');

$token = token('/compras/novo');

list($status, $corpo, $final) = req($base . '/compras/novo', array(
    'biostech_csrf'           => $token,
    'data'                    => date('Y-m-d'),
    'hora'                    => date('H:i'),
    'fornecedor_codigo'       => (int) $fornecedor['codigo'],
    'observacao'              => 'TESTE AUTOMATICO COMPRAS',
    'desconto'                => '0,00',
    'desconto_pedido_percentual' => '',
    'produto_id'              => array($p1['id'], $p2['id']),
    'quantidade'              => array('1', '2'),
    'valor_unitario'          => array('10,00', '5,50'),
    'novo_valor_unitario'     => array('', ''),
    'desconto_percentual'     => array('', ''),
    'desconto_item'           => array('', ''),
    'observacao_item'         => array('teste 1', 'teste 2'),
    'atualiza_preco_compra'   => array('N', 'N'),
));

$erro = erro_pagina($corpo);
checar('criar pedido sem erro', $erro === NULL, (string) $erro);

$pedido = (int) valor(
    "SELECT max(numero) FROM {$schema}.biostech_compras_pedidos
      WHERE usuario_cadastro = ?",
    array($login)
);

checar('pedido criado', $pedido > 0);

$criados['pedidos'][] = $pedido;

$reg = db("SELECT * FROM {$schema}.biostech_compras_pedidos WHERE numero = ?", array($pedido))->fetch(PDO::FETCH_ASSOC);
$itens = db("SELECT * FROM {$schema}.biostech_compras_itens WHERE pedido_numero = ? ORDER BY produto_id", array($pedido))->fetchAll(PDO::FETCH_ASSOC);

checar('pedido aberto', $reg && $reg['situacao'] === 'aberto', $reg ? $reg['situacao'] : '');
checar('pedido com 2 itens', count($itens) === 2, (string) count($itens));
checar('total bruto 21,00', abs((float) $reg['total_bruto'] - 21.0) < 0.001, (string) $reg['total_bruto']);
checar('total 21,00', abs((float) $reg['total'] - 21.0) < 0.001, (string) $reg['total']);

// ---------------------------------------------------------------------
// 2. Editar pedido (desconto de 10%)
// ---------------------------------------------------------------------

$token = token('/compras/editar/' . $pedido);

list($status, $corpo, $final) = req($base . '/compras/editar/' . $pedido, array(
    'biostech_csrf'           => $token,
    'data'                    => date('Y-m-d'),
    'hora'                    => date('H:i'),
    'fornecedor_codigo'       => (int) $fornecedor['codigo'],
    'observacao'              => 'TESTE AUTOMATICO COMPRAS',
    'desconto'                => '0,00',
    'desconto_pedido_percentual' => '10,00',
    'produto_id'              => array($p1['id'], $p2['id']),
    'quantidade'              => array('1', '2'),
    'valor_unitario'          => array('10,00', '5,50'),
    'novo_valor_unitario'     => array('', ''),
    'desconto_percentual'     => array('', ''),
    'desconto_item'           => array('', ''),
    'observacao_item'         => array('teste 1', 'teste 2'),
    'atualiza_preco_compra'   => array('N', 'N'),
));

$erro = erro_pagina($corpo);
checar('editar pedido sem erro', $erro === NULL, (string) $erro);

$reg = db("SELECT * FROM {$schema}.biostech_compras_pedidos WHERE numero = ?", array($pedido))->fetch(PDO::FETCH_ASSOC);

checar('desconto de 10% calculado', abs((float) $reg['desconto'] - 2.10) < 0.001, (string) $reg['desconto']);
checar('total com desconto 18,90', abs((float) $reg['total'] - 18.90) < 0.001, (string) $reg['total']);

// ---------------------------------------------------------------------
// 3. Fechar pedido
// ---------------------------------------------------------------------

$token = token('/compras/fechar/' . $pedido);

list($status, $corpo, $final) = req($base . '/compras/fechar/' . $pedido, array(
    'biostech_csrf'         => $token,
    'data_compra'           => date('Y-m-d'),
    'num_nf'                => $nf,
    'data_nf'               => date('Y-m-d'),
    'data_vencimento'       => date('Y-m-d'),
    'condicao_pagamento'    => '',
    'forma_recebimento'     => '',
    'documento_recebimento' => '',
    'centro_custo'          => '',
    'prazo_entrega'         => '',
    'gerar_contas'          => 'S',
    'fornecedor_estoque'    => (int) $fornecedor['codigo'],
    'parcela_dias'          => array('0', '30'),
    'parcela_vencimento'    => array(
        date('Y-m-d'),
        date('Y-m-d', strtotime('+30 days')),
    ),
));

$erro = erro_pagina($corpo);
checar('fechar pedido sem erro', $erro === NULL, (string) $erro);

$reg = db("SELECT * FROM {$schema}.biostech_compras_pedidos WHERE numero = ?", array($pedido))->fetch(PDO::FETCH_ASSOC);
$parcelas = db("SELECT * FROM {$schema}.biostech_compras_parcelas WHERE pedido_numero = ? ORDER BY numero_par", array($pedido))->fetchAll(PDO::FETCH_ASSOC);
$contas = db("SELECT * FROM {$schema}.biostech_contas_pagar WHERE origem = 'compra' AND origem_id = ? ORDER BY parcela", array((string) $pedido))->fetchAll(PDO::FETCH_ASSOC);
$movs = db("SELECT * FROM {$schema}.biostech_estoque_movimentos WHERE origem = 'compra' AND origem_id = ? ORDER BY id", array((string) $pedido))->fetchAll(PDO::FETCH_ASSOC);

checar('pedido fechado', $reg['situacao'] === 'fechado', $reg['situacao']);
checar('2 parcelas geradas', count($parcelas) === 2, (string) count($parcelas));
checar('soma das parcelas = total', abs(array_sum(array_map('floatval', array_column($parcelas, 'valor'))) - 18.90) < 0.001);
checar('2 contas a pagar geradas', count($contas) === 2, (string) count($contas));
checar('contas abertas (situacao 1)', count(array_filter($contas, function ($c) { return (int) $c['situacao'] === 1; })) === 2);
checar('soma das contas = total', abs(array_sum(array_map('floatval', array_column($contas, 'valor'))) - 18.90) < 0.001);
checar('titulo do pedido gravado', (int) $reg['conta_pagar_numero'] > 0, (string) $reg['conta_pagar_numero']);
checar('2 movimentos de estoque da compra', count($movs) === 2, (string) count($movs));

$saldo_p1 = db("SELECT quantidade FROM {$schema}.biostech_estoque_saldos WHERE produto_id = ?", array($p1['id']))->fetch(PDO::FETCH_ASSOC);
checar('saldo do produto 1 aumentou 1', (int) $saldo_p1['quantidade'] === (int) $snapshot[$p1['id']]['saldo']['quantidade'] + 1,
    $saldo_p1['quantidade'] . ' vs ' . $snapshot[$p1['id']]['saldo']['quantidade']);

$prod1 = db("SELECT valor_compra, valor_compra_desconto, ultimo_pedido_compra, ultima_nf_entrada FROM {$schema}.biostech_produtos WHERE id = ?", array($p1['id']))->fetch(PDO::FETCH_ASSOC);
checar('preco de compra atualizado', abs((float) $prod1['valor_compra'] - 10.0) < 0.001, (string) $prod1['valor_compra']);
checar('ultimo pedido gravado no produto', (int) $prod1['ultimo_pedido_compra'] === $pedido, (string) $prod1['ultimo_pedido_compra']);
checar('ultima NF gravada no produto', $prod1['ultima_nf_entrada'] === $nf, (string) $prod1['ultima_nf_entrada']);

paginar('/compras/ver/' . $pedido, array('Pedido de compra ' . $pedido, 'Fechado'));
paginar('/compras/ver/' . $pedido . '?aba=parcelas', array('Parcelas', '18,90'));
paginar('/compras/ver/' . $pedido . '?aba=contas', array('Contas a pagar', 'Aberto'));
paginar('/compras/imprimir_pedido/' . $pedido, array('Pedido de compra ' . $pedido, $nf));

// ---------------------------------------------------------------------
// 4. Cancelar o fechamento
// ---------------------------------------------------------------------

$token = token('/compras/ver/' . $pedido . '?aba=parcelas');

list($status, $corpo, $final) = req($base . '/compras/cancelar_fechamento/' . $pedido, array(
    'biostech_csrf' => $token,
    'motivo'        => 'TESTE AUTOMATICO',
));

$erro = erro_pagina($corpo);
checar('cancelar fechamento sem erro', $erro === NULL, (string) $erro);

$reg = db("SELECT * FROM {$schema}.biostech_compras_pedidos WHERE numero = ?", array($pedido))->fetch(PDO::FETCH_ASSOC);
$contas = db("SELECT * FROM {$schema}.biostech_contas_pagar WHERE origem = 'compra' AND origem_id = ?", array((string) $pedido))->fetchAll(PDO::FETCH_ASSOC);
$movs = db("SELECT * FROM {$schema}.biostech_estoque_movimentos WHERE origem = 'compra' AND origem_id = ?", array((string) $pedido))->fetchAll(PDO::FETCH_ASSOC);
$estornos = db("SELECT * FROM {$schema}.biostech_estoque_movimentos WHERE origem = 'estorno' AND documento = ?", array($nf))->fetchAll(PDO::FETCH_ASSOC);

checar('pedido voltou para aberto', $reg['situacao'] === 'aberto', $reg['situacao']);
checar('titulos cancelados (situacao 3)', count(array_filter($contas, function ($c) { return (int) $c['situacao'] === 3; })) === 2);
checar('movimentos originais estornados', (int) valor("SELECT count(*) FROM {$schema}.biostech_estoque_movimentos WHERE origem = 'compra' AND origem_id = ? AND estornado", array((string) $pedido)) === 2);
checar('2 movimentos de estorno gerados', count($estornos) === 2, (string) count($estornos));

$saldo_p1 = db("SELECT quantidade FROM {$schema}.biostech_estoque_saldos WHERE produto_id = ?", array($p1['id']))->fetch(PDO::FETCH_ASSOC);
checar('saldo do produto 1 restaurado', (int) $saldo_p1['quantidade'] === (int) $snapshot[$p1['id']]['saldo']['quantidade'],
    $saldo_p1['quantidade'] . ' vs ' . $snapshot[$p1['id']]['saldo']['quantidade']);

// ---------------------------------------------------------------------
// 5. Replicar, excluir itens e transferir
// ---------------------------------------------------------------------

$token = token('/compras/ver/' . $pedido);

list($status, $corpo, $final) = req($base . '/compras/replicar/' . $pedido, array('biostech_csrf' => $token));

$erro = erro_pagina($corpo);
checar('replicar pedido sem erro', $erro === NULL, (string) $erro);

$replica = (int) valor("SELECT max(numero) FROM {$schema}.biostech_compras_pedidos WHERE usuario_cadastro = ?", array($login));
checar('pedido replicado criado', $replica > $pedido, (string) $replica);

$criados['pedidos'][] = $replica;

$reg = db("SELECT * FROM {$schema}.biostech_compras_pedidos WHERE numero = ?", array($replica))->fetch(PDO::FETCH_ASSOC);
checar('replica aberta', $reg['situacao'] === 'aberto', $reg['situacao']);
checar('replica com 2 itens', (int) valor("SELECT count(*) FROM {$schema}.biostech_compras_itens WHERE pedido_numero = ?", array($replica)) === 2);
checar('replica com total 18,90', abs((float) $reg['total'] - 18.90) < 0.001, (string) $reg['total']);

$token = token('/compras/ver/' . $replica . '?aba=itens');

list($status, $corpo, $final) = req($base . '/compras/excluir_item/' . $replica . '/' . $p1['id'], array('biostech_csrf' => $token));
checar('excluir item da replica sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('replica com 1 item', (int) valor("SELECT count(*) FROM {$schema}.biostech_compras_itens WHERE pedido_numero = ?", array($replica)) === 1);

list($status, $corpo, $final) = req($base . '/compras/excluir_item/' . $replica . '/' . $p2['id'], array('biostech_csrf' => $token));
checar('excluir segundo item da replica', (int) valor("SELECT count(*) FROM {$schema}.biostech_compras_itens WHERE pedido_numero = ?", array($replica)) === 0);
checar('replica sem valor', abs((float) valor("SELECT total FROM {$schema}.biostech_compras_pedidos WHERE numero = ?", array($replica))) < 0.001);

$token = token('/compras/ver/' . $pedido . '?aba=itens');

list($status, $corpo, $final) = req($base . '/compras/transferir_item/' . $pedido, array(
    'biostech_csrf' => $token,
    'destino'       => $replica,
    'produto_id'    => $p2['id'],
));

checar('transferir item sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('origem com 1 item', (int) valor("SELECT count(*) FROM {$schema}.biostech_compras_itens WHERE pedido_numero = ?", array($pedido)) === 1);
checar('destino com 1 item', (int) valor("SELECT count(*) FROM {$schema}.biostech_compras_itens WHERE pedido_numero = ?", array($replica)) === 1);
checar('total da origem recalculado (10,00)', abs((float) valor("SELECT total FROM {$schema}.biostech_compras_pedidos WHERE numero = ?", array($pedido)) - 9.0) < 0.001);

list($status, $corpo, $final) = req($base . '/compras/transferir_item/' . $replica, array(
    'biostech_csrf' => $token,
    'destino'       => $pedido,
    'produto_id'    => $p2['id'],
));

checar('devolver item transferido', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('origem restaurada com 2 itens', (int) valor("SELECT count(*) FROM {$schema}.biostech_compras_itens WHERE pedido_numero = ?", array($pedido)) === 2);
checar('destino vazio', (int) valor("SELECT count(*) FROM {$schema}.biostech_compras_itens WHERE pedido_numero = ?", array($replica)) === 0);

// ---------------------------------------------------------------------
// 6. Cancelar / reabrir / excluir
// ---------------------------------------------------------------------

$token = token('/compras/ver/' . $pedido);

req($base . '/compras/cancelar/' . $pedido, array('biostech_csrf' => $token, 'motivo' => 'TESTE AUTOMATICO'));
checar('pedido cancelado', valor("SELECT situacao FROM {$schema}.biostech_compras_pedidos WHERE numero = ?", array($pedido)) === 'cancelado');

req($base . '/compras/reabrir/' . $pedido, array('biostech_csrf' => $token));
checar('pedido reaberto', valor("SELECT situacao FROM {$schema}.biostech_compras_pedidos WHERE numero = ?", array($pedido)) === 'aberto');

req($base . '/compras/excluir/' . $replica, array('biostech_csrf' => $token));
checar('replica excluida', (int) valor("SELECT count(*) FROM {$schema}.biostech_compras_pedidos WHERE numero = ?", array($replica)) === 0);

req($base . '/compras/excluir/' . $pedido, array('biostech_csrf' => $token));
checar('pedido excluido', (int) valor("SELECT count(*) FROM {$schema}.biostech_compras_pedidos WHERE numero = ?", array($pedido)) === 0);

// ---------------------------------------------------------------------
// 7. Solicitacao interna -> cotacao -> pedido
// ---------------------------------------------------------------------

$token = token('/compras/solicitacao');
$numero_sc = (int) valor("SELECT COALESCE(max(numero), 0) FROM {$schema}.biostech_compras_solicitacoes");

list($status, $corpo, $final) = req($base . '/compras/solicitacao', array(
    'biostech_csrf'         => $token,
    'data'                  => date('Y-m-d'),
    'hora'                  => date('H:i'),
    'local_entrega'         => 'TESTE AUTOMATICO',
    'responsavel_recebimento' => 'TESTE',
    'fone_responsavel'      => '0000',
    'observacao'            => 'TESTE AUTOMATICO COMPRAS',
    'produto_id'            => array($p1['id'], $p2['id']),
    'quantidade'            => array('2', '3'),
    'data_utilizacao'       => array('', ''),
    'periodo'               => array('', ''),
    'repor_estoque'         => array('N', 'N'),
    'selecionado'           => array('S', 'S'),
));

$erro = erro_pagina($corpo);
checar('criar solicitacao sem erro', $erro === NULL, (string) $erro);

$solicitacao = (int) valor("SELECT max(numero) FROM {$schema}.biostech_compras_solicitacoes");
checar('solicitacao criada', $solicitacao > $numero_sc, (string) $solicitacao);

$criados['solicitacoes'][] = $solicitacao;

checar('solicitacao aberta', valor("SELECT situacao FROM {$schema}.biostech_compras_solicitacoes WHERE numero = ?", array($solicitacao)) === 'aberta');
checar('solicitacao com 2 itens', (int) valor("SELECT count(*) FROM {$schema}.biostech_compras_solicitacao_itens WHERE solicitacao_numero = ?", array($solicitacao)) === 2);
checar('itens marcados', (int) valor("SELECT count(*) FROM {$schema}.biostech_compras_solicitacao_itens WHERE solicitacao_numero = ? AND selecionado", array($solicitacao)) === 2);

paginar('/compras/solicitacao/' . $solicitacao, array('Solicitacao interna ' . $solicitacao, 'Itens solicitados'));
paginar('/compras/imprimir_solicitacao/' . $solicitacao, array('Solicitacao interna ' . $solicitacao));

$token = token('/compras/solicitacao/' . $solicitacao);
$cotacao_antes = (int) valor("SELECT COALESCE(max(codigo), 0) FROM {$schema}.biostech_compras_cotacoes");

list($status, $corpo, $final) = req($base . '/compras/importar_solicitacao/' . $solicitacao, array('biostech_csrf' => $token));
checar('importar solicitacao sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));

$cotacao = (int) valor("SELECT COALESCE(max(codigo), 0) FROM {$schema}.biostech_compras_cotacoes");
checar('cotacao criada', $cotacao > $cotacao_antes, (string) $cotacao);
$criados['cotacoes'][] = $cotacao;

checar('cotacao com 2 itens', (int) valor("SELECT count(*) FROM {$schema}.biostech_compras_cotacao_itens WHERE cotacao_codigo = ?", array($cotacao)) === 2);
checar('solicitacao vinculada a cotacao', (int) valor("SELECT cotacao_codigo FROM {$schema}.biostech_compras_solicitacoes WHERE numero = ?", array($solicitacao)) === $cotacao);

paginar('/compras/cotacao/' . $cotacao, array('Cotacao ' . $cotacao, 'Fornecedor 1'));
paginar('/compras/imprimir_cotacao/' . $cotacao, array('Comparativo de propostas'));

$token = token('/compras/solicitacao/' . $solicitacao);

list($status, $corpo, $final) = req($base . '/compras/solicitacao_gerar_pedido/' . $solicitacao, array(
    'biostech_csrf'        => $token,
    'somente_selecionados' => 'S',
));

checar('gerar pedido da solicitacao sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));

$pedido_sc = (int) valor("SELECT pedido_numero FROM {$schema}.biostech_compras_solicitacoes WHERE numero = ?", array($solicitacao));
checar('pedido gerado pela solicitacao', $pedido_sc > 0, (string) $pedido_sc);

if ($pedido_sc > 0)
{
    $criados['pedidos'][] = $pedido_sc;
    checar('pedido da solicitacao com 2 itens', (int) valor("SELECT count(*) FROM {$schema}.biostech_compras_itens WHERE pedido_numero = ?", array($pedido_sc)) === 2);
    checar('solicitacao fechada', valor("SELECT situacao FROM {$schema}.biostech_compras_solicitacoes WHERE numero = ?", array($solicitacao)) === 'fechada');
    checar('itens marcados como exportados', (int) valor("SELECT count(*) FROM {$schema}.biostech_compras_solicitacao_itens WHERE solicitacao_numero = ? AND exportado", array($solicitacao)) === 2);
}

// ---------------------------------------------------------------------
// 8. Prazos de fornecedor
// ---------------------------------------------------------------------

$fornecedor_prazo = (int) $fornecedor['codigo'];
$codigo_prazo = (int) valor("SELECT COALESCE(max(codigo), 0) + 1 FROM {$schema}.biostech_fornecedor_prazos WHERE fornecedor_codigo = ?", array($fornecedor_prazo));

$token = token('/compras/prazos/' . $fornecedor_prazo);

list($status, $corpo) = req($base . '/compras/prazo_salvar', array(
    'biostech_csrf'    => $token,
    'fornecedor_codigo'=> $fornecedor_prazo,
    'codigo'           => $codigo_prazo,
    'forma_pagamento'  => 'TESTE AUTOMATICO',
    'prazo'            => '30 dias',
));

checar('salvar prazo sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('prazo gravado', (int) valor("SELECT count(*) FROM {$schema}.biostech_fornecedor_prazos WHERE fornecedor_codigo = ? AND codigo = ?", array($fornecedor_prazo, $codigo_prazo)) === 1);

list($status, $corpo) = req($base . '/compras/prazo_excluir/' . $fornecedor_prazo . '/' . $codigo_prazo, array('biostech_csrf' => $token));
checar('excluir prazo sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('prazo removido', (int) valor("SELECT count(*) FROM {$schema}.biostech_fornecedor_prazos WHERE fornecedor_codigo = ? AND codigo = ?", array($fornecedor_prazo, $codigo_prazo)) === 0);

// ---------------------------------------------------------------------
// Limpeza
// ---------------------------------------------------------------------

echo "\nLimpando dados de teste...\n";

try
{
    foreach ($criados['pedidos'] as $n)
    {
        db("DELETE FROM {$schema}.biostech_contas_pagar WHERE origem = 'compra' AND origem_id = ?", array((string) $n));
        db("DELETE FROM {$schema}.biostech_estoque_movimentos WHERE origem IN ('compra', 'estorno') AND (origem_id = ? OR documento = ?)", array((string) $n, $nf));
        db("DELETE FROM {$schema}.biostech_compras_log WHERE pedido_numero = ?", array($n));
        db("DELETE FROM {$schema}.biostech_compras_pedidos WHERE numero = ?", array($n));
    }

    foreach ($criados['cotacoes'] as $c)
    {
        db("DELETE FROM {$schema}.biostech_compras_cotacoes WHERE codigo = ?", array($c));
    }

    foreach ($criados['solicitacoes'] as $s)
    {
        db("DELETE FROM {$schema}.biostech_compras_log WHERE solicitacao_numero = ?", array($s));
        db("DELETE FROM {$schema}.biostech_compras_solicitacoes WHERE numero = ?", array($s));
    }

    // Restaura produtos e saldos usados no teste.
    foreach ($snapshot as $pid => $dados)
    {
        $p = $dados['produto'];

        db(
            "UPDATE {$schema}.biostech_produtos
                SET valor_compra = ?, valor_compra_desconto = ?,
                    ultimo_pedido_compra = ?, data_ultima_entrada = ?,
                    ultima_nf_entrada = ?, percentual_lucro = ?,
                    valor_venda = ?, updated_at = ?
              WHERE id = ?",
            array(
                $p['valor_compra'], $p['valor_compra_desconto'],
                $p['ultimo_pedido_compra'], $p['data_ultima_entrada'],
                $p['ultima_nf_entrada'], $p['percentual_lucro'],
                $p['valor_venda'], $p['updated_at'], $pid,
            )
        );

        if ( ! empty($dados['saldo']))
        {
            db(
                "UPDATE {$schema}.biostech_estoque_saldos
                    SET quantidade = ?, quantidade_consumida = ?
                  WHERE produto_id = ?",
                array($dados['saldo']['quantidade'], $dados['saldo']['quantidade_consumida'], $pid)
            );
        }
    }

    echo "Dados de teste removidos e produtos/saldos restaurados.\n";
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

echo "TODOS OS TESTES DE COMPRAS PASSARAM\n";
exit(0);
