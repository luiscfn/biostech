<?php
/**
 * BIOSTECH - teste funcional do modulo Orcamentos
 *
 * Cobre o fluxo comercial (cabecalho, itens, desconto/acrescimo em massa,
 * servicos, contatos, status, impressao e geracao de chamado), o fluxo de
 * dimensionamento (levantamento, itens, servicos e geracao do orcamento) e
 * os endpoints de autocomplete. Ao final remove tudo o que foi criado.
 *
 * Uso:
 *   php scripts/test/orcamentos.php [url-base] [login] [senha]
 */

$base  = isset($argv[1]) ? rtrim($argv[1], '/') : 'http://127.0.0.1:8090';
$login = isset($argv[2]) ? $argv[2] : 'DIMENSIONAMENTO';
$senha = isset($argv[3]) ? $argv[3] : 'dimensionamento';

$jar    = tempnam(sys_get_temp_dir(), 'biostech_orc_');
$falhas = 0;

// ---------------------------------------------------------------------
// Banco
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
    $pdo->exec("SET datestyle TO 'ISO'");
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

function dinheiro($sql, $params = array())
{
    return round((float) valor($sql, $params), 2);
}

// PDO_PGSQL devolve booleanos como bool PHP; versoes antigas devolviam 't'/'f'.
function booleano($sql, $params = array())
{
    return in_array(valor($sql, $params), array(TRUE, 't', 'true', '1', 1), TRUE);
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

// ---------------------------------------------------------------------
// Dados base
// ---------------------------------------------------------------------

$cliente = db(
    "SELECT id, nome FROM {$schema}.biostech_clientes
      WHERE ativo ORDER BY id LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

checar('cliente de teste encontrado', ! empty($cliente));

$produto = db(
    "SELECT id, descricao, valor_venda FROM {$schema}.biostech_produtos
      WHERE ativo AND id > 0 AND valor_venda > 0
      ORDER BY id LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

checar('produto de teste encontrado', ! empty($produto));

// ---------------------------------------------------------------------
// 1. Lista
// ---------------------------------------------------------------------

list($status, $corpo) = req($base . '/orcamentos');

checar('GET /orcamentos => 200', $status === 200, 'status ' . $status);
checar('lista sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('lista contem o filtro de situacao', strpos($corpo, 'name="situacao"') !== FALSE);
checar('lista contem o botao de novo orcamento', strpos($corpo, 'Novo orcamento') !== FALSE);

// ---------------------------------------------------------------------
// 2. Criar orcamento comercial
// ---------------------------------------------------------------------

$atencao = 'TESTE ORC ' . date('His');

$token = token('/orcamentos/novo');

list($status, $corpo) = req($base . '/orcamentos/novo', array(
    'biostech_csrf'       => $token,
    'data'                => date('Y-m-d'),
    'cliente_id'          => (int) $cliente['id'],
    'atencao'             => $atencao,
    'observacao'          => 'TESTE AUTOMATICO ORCAMENTOS',
    'condicao_pagamento'  => '1',
    'entrega'             => '10',
    'garantia'            => '90',
    'validade'            => '30',
    'validade_pecas'      => '180',
    'assinatura'          => 'TESTE',
    'valor_mao_obra'      => '',
    'valor_imposto'       => '',
    'valor_desconto'      => '',
    'material_fixacao'    => '',
    'acrescimo_custo'     => '',
    'valor_maximo_cliente' => '',
    'valor_custos_indiretos' => '',
    'percentual_desconto'    => '',
    'percentual_imposto'     => '',
    'percentual_mao_obra'    => '',
    'percentual_servico'     => '',
    'percentual_nota_fiscal' => '',
    'percentual_mat_fixacao' => '',
    'percentual_acrescimo_custo' => '',
    'desconto_percentual'    => '',
    'acrescimo_percentual'   => '',
));

$erro = erro_pagina($corpo);
checar('criar orcamento sem erro', $erro === NULL, (string) $erro);

$n = (int) valor(
    "SELECT numero FROM {$schema}.biostech_orcamentos
      WHERE atencao = ? AND usuario_cadastro = ?
      ORDER BY numero DESC LIMIT 1",
    array(mb_substr($atencao, 0, 70), $login)
);

checar('orcamento criado', $n > 0, 'numero ' . $n);
checar('situacao inicial ABERTO', (int) valor("SELECT situacao FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($n)) === 1);
checar('resultado inicial NAO ENVIADO', (int) valor("SELECT resultado FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($n)) === 3);
checar('validade gravada', (int) valor("SELECT validade FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($n)) === 30);
checar('entrega gravada', (int) valor("SELECT entrega FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($n)) === 10);

// ---------------------------------------------------------------------
// 3. Incluir item
// ---------------------------------------------------------------------

list($status, $corpo) = req($base . '/orcamentos/ver/' . $n . '?aba=itens');
checar('ficha do orcamento => 200', $status === 200, 'status ' . $status);
checar('ficha sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('ficha contem incluir item', strpos($corpo, 'Incluir item') !== FALSE);

$token = token('/orcamentos/ver/' . $n . '?aba=itens');

list($status, $corpo) = req($base . '/orcamentos/adicionar_item/' . $n, array(
    'biostech_csrf'    => $token,
    'produto_id'       => (int) $produto['id'],
    'descricao_produto' => '',
    'descricao'        => '',
    'quantidade'       => '2',
    'valor_unitario'   => '10,00',
    'valor_total'      => '',
));

checar('incluir item sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('um item no orcamento', (int) valor("SELECT count(*) FROM {$schema}.biostech_orcamento_itens WHERE orcamento_numero = ?", array($n)) === 1);
checar('total do item = 20,00', dinheiro("SELECT valor_total FROM {$schema}.biostech_orcamento_itens WHERE orcamento_numero = ?", array($n)) === 20.00);
checar('valor original do item = 10,00', dinheiro("SELECT valor_unitario_original FROM {$schema}.biostech_orcamento_itens WHERE orcamento_numero = ?", array($n)) === 10.00);
checar('material do orcamento = 20,00', dinheiro("SELECT valor_material FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($n)) === 20.00);

$itemId = (int) valor("SELECT id FROM {$schema}.biostech_orcamento_itens WHERE orcamento_numero = ?", array($n));

// ---------------------------------------------------------------------
// 4. Alterar item
// ---------------------------------------------------------------------

$token = token('/orcamentos/ver/' . $n . '?aba=itens');

list($status, $corpo) = req($base . '/orcamentos/atualizar_item/' . $n . '/' . $itemId, array(
    'biostech_csrf'  => $token,
    'descricao'      => 'item de teste',
    'quantidade'     => '3',
    'valor_unitario' => '12,50',
    'valor_total'    => '',
));

checar('alterar item sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('quantidade = 3', (int) valor("SELECT quantidade FROM {$schema}.biostech_orcamento_itens WHERE id = ?", array($itemId)) === 3);
checar('total do item = 37,50', dinheiro("SELECT valor_total FROM {$schema}.biostech_orcamento_itens WHERE id = ?", array($itemId)) === 37.50);
checar('material do orcamento = 37,50', dinheiro("SELECT valor_material FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($n)) === 37.50);
checar('descricao do item gravada', valor("SELECT descricao FROM {$schema}.biostech_orcamento_itens WHERE id = ?", array($itemId)) === 'item de teste');

// ---------------------------------------------------------------------
// 5. Desconto em massa e restauracao
// ---------------------------------------------------------------------

$token = token('/orcamentos/ver/' . $n . '?aba=itens');

list($status, $corpo) = req($base . '/orcamentos/aplicar_percentual/' . $n, array(
    'biostech_csrf' => $token,
    'tipo'          => 'desconto',
    'percentual'    => '10',
));

checar('desconto em massa sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('item com 10% de desconto = 33,75', dinheiro("SELECT valor_total FROM {$schema}.biostech_orcamento_itens WHERE id = ?", array($itemId)) === 33.75);
checar('percentual de desconto gravado', dinheiro("SELECT percentual_desconto_item FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($n)) === 10.00);
checar('original preservado em 37,50', dinheiro("SELECT valor_total_original FROM {$schema}.biostech_orcamento_itens WHERE id = ?", array($itemId)) === 37.50);

$token = token('/orcamentos/ver/' . $n . '?aba=itens');

list($status, $corpo) = req($base . '/orcamentos/restaurar_valores/' . $n, array('biostech_csrf' => $token));

checar('restaurar valores sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('item restaurado em 37,50', dinheiro("SELECT valor_total FROM {$schema}.biostech_orcamento_itens WHERE id = ?", array($itemId)) === 37.50);
checar('percentual de desconto limpo', valor("SELECT percentual_desconto_item FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($n)) === NULL);

// ---------------------------------------------------------------------
// 6. Servico a executar
// ---------------------------------------------------------------------

$token = token('/orcamentos/ver/' . $n . '?aba=servicos');

list($status, $corpo) = req($base . '/orcamentos/adicionar_servico/' . $n, array(
    'biostech_csrf' => $token,
    'descricao'     => 'Servico de teste',
    'concluido'     => 'S',
));

checar('incluir servico sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('servico gravado', (int) valor("SELECT count(*) FROM {$schema}.biostech_orcamento_servicos WHERE orcamento_numero = ?", array($n)) === 1);
checar('servico concluido', booleano("SELECT concluido FROM {$schema}.biostech_orcamento_servicos WHERE orcamento_numero = ?", array($n)));

// ---------------------------------------------------------------------
// 7. Servico com valor
// ---------------------------------------------------------------------

$token = token('/orcamentos/ver/' . $n . '?aba=servicos_valores');

list($status, $corpo) = req($base . '/orcamentos/adicionar_servico_valor/' . $n, array(
    'biostech_csrf'  => $token,
    'descricao'      => 'Instalacao de teste',
    'quantidade'     => '2',
    'valor_unitario' => '50,00',
    'valor_total'    => '',
));

checar('incluir servico com valor sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('servico com valor = 100,00', dinheiro("SELECT valor_servico FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($n)) === 100.00);
checar('total do orcamento = 137,50', dinheiro("SELECT valor_total FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($n)) === 137.50);

// ---------------------------------------------------------------------
// 8. Contato / follow-up
// ---------------------------------------------------------------------

$token = token('/orcamentos/ver/' . $n . '?aba=contatos');

list($status, $corpo) = req($base . '/orcamentos/adicionar_contato/' . $n, array(
    'biostech_csrf' => $token,
    'data_mov'      => date('Y-m-d'),
    'hora_mov'      => '10:00',
    'assunto'       => 'Ligacao de teste',
    'funcionario_id' => '',
    'contato'       => 'Fulano de teste',
    'descricao'     => 'Contato gerado pelo teste automatico.',
    'data_proximo_contato' => '',
    'hora_proximo_contato' => '',
));

checar('incluir contato sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('contato gravado', (int) valor("SELECT count(*) FROM {$schema}.biostech_orcamento_contatos WHERE orcamento_numero = ?", array($n)) === 1);

// ---------------------------------------------------------------------
// 9. Historico
// ---------------------------------------------------------------------

list($status, $corpo) = req($base . '/orcamentos/ver/' . $n . '?aba=log');

checar('historico => 200', $status === 200, 'status ' . $status);
checar('historico sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('historico com eventos', (int) valor("SELECT count(*) FROM {$schema}.biostech_orcamento_log WHERE orcamento_numero = ?", array($n)) > 0);

// ---------------------------------------------------------------------
// 10. Status (concluido + aprovado)
// ---------------------------------------------------------------------

$token = token('/orcamentos/status/' . $n);

list($status, $corpo) = req($base . '/orcamentos/status/' . $n, array(
    'biostech_csrf'  => $token,
    'situacao'       => '4',
    'resultado'      => '1',
    'data_envio'     => date('Y-m-d'),
    'data_aprovacao' => '',
    'motivo_cancelamento' => '',
));

checar('alterar status sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('situacao = 4', (int) valor("SELECT situacao FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($n)) === 4);
checar('resultado = 1', (int) valor("SELECT resultado FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($n)) === 1);
checar('aprovado = true', booleano("SELECT aprovado FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($n)));
checar('data de aprovacao preenchida', valor("SELECT data_aprovacao FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($n)) !== NULL);
checar('data do resultado preenchida', valor("SELECT data_resultado FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($n)) !== NULL);

// ---------------------------------------------------------------------
// 11. Impressao
// ---------------------------------------------------------------------

list($status, $corpo) = req($base . '/orcamentos/imprimir/' . $n);

checar('impressao => 200', $status === 200, 'status ' . $status);
checar('impressao sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('impressao contem o numero', strpos($corpo, 'Orcamento ' . $n) !== FALSE);
checar('impressao contem o total', strpos($corpo, '137,50') !== FALSE);

// ---------------------------------------------------------------------
// 12. Gerar chamado
// ---------------------------------------------------------------------

$token = token('/orcamentos/ver/' . $n);

list($status, $corpo) = req($base . '/orcamentos/gerar_chamado/' . $n, array('biostech_csrf' => $token));

checar('gerar chamado sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));

$chamado = valor("SELECT chamado_numero FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($n));

checar('orcamento vinculado ao chamado', ! empty($chamado), 'chamado ' . $chamado);
checar('chamado existe', (int) valor("SELECT count(*) FROM {$schema}.biostech_chamados WHERE numero = ?", array($chamado)) === 1);
checar('chamado aponta o orcamento', (int) valor("SELECT orcamento_numero FROM {$schema}.biostech_chamados WHERE numero = ?", array($chamado)) === $n);
checar('pecas copiadas', (int) valor("SELECT count(*) FROM {$schema}.biostech_chamado_pecas WHERE chamado_numero = ?", array($chamado)) === 1);
checar('servicos copiados', (int) valor("SELECT count(*) FROM {$schema}.biostech_chamado_servicos WHERE chamado_numero = ?", array($chamado)) === 2);
checar('equipamento do chamado criado', (int) valor("SELECT count(*) FROM {$schema}.biostech_chamado_equipamento WHERE chamado_numero = ?", array($chamado)) === 1);

// ---------------------------------------------------------------------
// 13. Dimensionamento
// ---------------------------------------------------------------------

$token = token('/orcamentos/dimensionamento_novo');

list($status, $corpo) = req($base . '/orcamentos/dimensionamento_novo', array(
    'biostech_csrf'   => $token,
    'data'            => date('Y-m-d'),
    'chamado_numero'  => '',
    'cliente_id'      => (int) $cliente['id'],
    'contato'         => 'Contato teste',
    'tecnico_id'      => '',
    'local_instalacao' => 'Rua do teste, 100',
    'tipo_contato'    => 'Telefone',
    'setor'           => 'TI',
    'email'           => 'teste@example.com',
    'qtd_tecnicos'    => '2',
    'qtd_comerciais'  => '1',
    'qtd_nao_comerciais' => '0',
    'solicitante'     => 'Solicitante teste',
    'qtd_dias'        => '5',
    'que_funciona'    => 'Funciona parcialmente',
    'quer_fazer'      => 'Instalar cerca',
    'dificuldades'    => 'Muro alto',
    'material_precisa' => 'Cabo, central',
    'providenciar'    => 'Nada',
    'observacao'      => 'TESTE AUTOMATICO DIMENSIONAMENTO',
    'altura'          => '2,50',
    'comprimento_muro' => '40,00',
    'lado_direito'    => '10,00',
    'lado_esquerdo'   => '10,00',
    'frente'          => '8,00',
    'fundo'           => '12,00',
    'rebocado'        => 'S',
    'cerca_arame'     => 'S',
    'telhado'         => '',
    'arvore'          => 'S',
    'cinta_cimento'   => '',
    'pega_ladrao'     => '',
    'percentual_mat_fixacao' => '5',
    'percentual_mao_obra'    => '20',
    'percentual_nota_fiscal' => '10',
    'percentual_acrescimo_custo' => '0',
    'segunda'         => 'S', 'segunda_inicio' => '8',  'segunda_fim' => '17',
    'terca'           => 'S', 'terca_inicio'   => '8',  'terca_fim'   => '17',
    'quarta'          => '',  'quarta_inicio'  => '',   'quarta_fim'  => '',
    'quinta'          => '',  'quinta_inicio'  => '',   'quinta_fim'  => '',
    'sexta'           => '',  'sexta_inicio'   => '',   'sexta_fim'   => '',
    'sabado'          => '',  'sabado_inicio'  => '',   'sabado_fim'  => '',
    'domingo'         => '',  'domingo_inicio' => '',   'domingo_fim' => '',
));

checar('criar dimensionamento sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));

$d = (int) valor(
    "SELECT numero FROM {$schema}.biostech_orcamentos_dimensionamento
      WHERE cliente_id = ? AND usuario_cadastro = ?
      ORDER BY numero DESC LIMIT 1",
    array((int) $cliente['id'], $login)
);

checar('dimensionamento criado', $d > 0, 'numero ' . $d);
checar('levantamento gravado', valor("SELECT que_funciona FROM {$schema}.biostech_orcamentos_dimensionamento WHERE numero = ?", array($d)) === 'Funciona parcialmente');
checar('medida gravada', round((float) valor("SELECT altura FROM {$schema}.biostech_orcamentos_dimensionamento WHERE numero = ?", array($d)), 2) === 2.50);
checar('flag gravada', booleano("SELECT rebocado FROM {$schema}.biostech_orcamentos_dimensionamento WHERE numero = ?", array($d)));
checar('cronograma gravado', round((float) valor("SELECT segunda_fim FROM {$schema}.biostech_orcamentos_dimensionamento WHERE numero = ?", array($d)), 2) === 17.00);

// item do dimensionamento
$token = token('/orcamentos/dimensionamento/' . $d . '?aba=itens');

list($status, $corpo) = req($base . '/orcamentos/dim_adicionar_item/' . $d, array(
    'biostech_csrf'    => $token,
    'produto_id'       => (int) $produto['id'],
    'descricao_produto' => '',
    'descricao'        => '',
    'quantidade'       => '2',
    'valor_compra'     => '10,00',
    'valor_gasto'      => '5,00',
    'valor_venda'      => '25,00',
    'grupo'            => '001',
    'subgrupo'         => '002',
    'pg'               => '',
    'psg'              => '',
));

checar('incluir item do dimensionamento sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('total de compra = 20,00', dinheiro("SELECT total_compra FROM {$schema}.biostech_orcamento_dimensionamento_itens WHERE dimensionamento_numero = ?", array($d)) === 20.00);
checar('total de venda = 50,00', dinheiro("SELECT total_venda FROM {$schema}.biostech_orcamento_dimensionamento_itens WHERE dimensionamento_numero = ?", array($d)) === 50.00);

// servico do dimensionamento
$token = token('/orcamentos/dimensionamento/' . $d . '?aba=servicos');

list($status, $corpo) = req($base . '/orcamentos/dim_adicionar_servico/' . $d, array(
    'biostech_csrf' => $token,
    'descricao'     => 'Servico do dimensionamento',
    'concluido'     => '',
));

checar('incluir servico do dimensionamento sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('servico do dimensionamento gravado', (int) valor("SELECT count(*) FROM {$schema}.biostech_orcamento_dimensionamento_servicos WHERE dimensionamento_numero = ?", array($d)) === 1);

// ---------------------------------------------------------------------
// 14. Dimensionamento -> orcamento
// ---------------------------------------------------------------------

$token = token('/orcamentos/dimensionamento/' . $d);

list($status, $corpo) = req($base . '/orcamentos/dimensionamento_gerar_orcamento/' . $d, array('biostech_csrf' => $token));

checar('gerar orcamento sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));

$b = (int) valor("SELECT orcamento_numero FROM {$schema}.biostech_orcamentos_dimensionamento WHERE numero = ?", array($d));

checar('orcamento gerado', $b > 0, 'orcamento ' . $b);
checar('orcamento aponta o dimensionamento', (int) valor("SELECT dimensionamento_numero FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($b)) === $d);
checar('item copiado', (int) valor("SELECT count(*) FROM {$schema}.biostech_orcamento_itens WHERE orcamento_numero = ?", array($b)) === 1);
checar('valor de venda copiado', dinheiro("SELECT valor_unitario FROM {$schema}.biostech_orcamento_itens WHERE orcamento_numero = ?", array($b)) === 25.00);
checar('total do orcamento gerado = 50,00', dinheiro("SELECT valor_material FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($b)) === 50.00);
checar('situacao do orcamento gerado', (int) valor("SELECT situacao FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($b)) === 1);
checar('resultado do orcamento gerado', (int) valor("SELECT resultado FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($b)) === 3);
checar('servico copiado para o orcamento', (int) valor("SELECT count(*) FROM {$schema}.biostech_orcamento_servicos WHERE orcamento_numero = ?", array($b)) === 1);

// segunda geracao deve ser bloqueada
$token = token('/orcamentos/dimensionamento/' . $d);
req($base . '/orcamentos/dimensionamento_gerar_orcamento/' . $d, array('biostech_csrf' => $token));

checar('nao gera orcamento duplicado', (int) valor("SELECT count(*) FROM {$schema}.biostech_orcamentos WHERE dimensionamento_numero = ?", array($d)) === 1);

// ---------------------------------------------------------------------
// 15. Autocomplete (JSON)
// ---------------------------------------------------------------------

$parte = mb_substr($cliente['nome'], 0, 3);
list($status, $corpo) = req($base . '/orcamentos/buscar_clientes?termo=' . rawurlencode($parte));

$json = json_decode($corpo, TRUE);
checar('autocomplete de clientes => 200', $status === 200, 'status ' . $status);
checar('autocomplete de clientes e JSON', is_array($json), substr($corpo, 0, 80));
checar('autocomplete de clientes retorna itens', is_array($json) && count($json) > 0);
checar('autocomplete de clientes tem cpf_cnpj', is_array($json) && array_key_exists('cpf_cnpj', $json[0]));

$parte = mb_substr($produto['descricao'], 0, 4);
list($status, $corpo) = req($base . '/orcamentos/buscar_produtos?termo=' . rawurlencode($parte));

$json = json_decode($corpo, TRUE);
checar('autocomplete de produtos => 200', $status === 200, 'status ' . $status);
checar('autocomplete de produtos e JSON', is_array($json), substr($corpo, 0, 80));

// ---------------------------------------------------------------------
// 16. Exclusao de item e de orcamento (endpoint)
// ---------------------------------------------------------------------

$token = token('/orcamentos/ver/' . $b . '?aba=itens');
$itemB = (int) valor("SELECT id FROM {$schema}.biostech_orcamento_itens WHERE orcamento_numero = ?", array($b));

list($status, $corpo) = req($base . '/orcamentos/excluir_item/' . $b . '/' . $itemB, array('biostech_csrf' => $token));

checar('excluir item sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('item excluido', (int) valor("SELECT count(*) FROM {$schema}.biostech_orcamento_itens WHERE id = ?", array($itemB)) === 0);

$token = token('/orcamentos/ver/' . $b);

list($status, $corpo) = req($base . '/orcamentos/excluir/' . $b, array('biostech_csrf' => $token));

checar('excluir orcamento sem erro', erro_pagina($corpo) === NULL, (string) erro_pagina($corpo));
checar('orcamento excluido', (int) valor("SELECT count(*) FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($b)) === 0);
checar('historico do orcamento excluido preservado', (int) valor("SELECT count(*) FROM {$schema}.biostech_orcamento_log WHERE orcamento_numero = ?", array($b)) >= 2);

// ---------------------------------------------------------------------
// Limpeza
// ---------------------------------------------------------------------

echo "\nLimpando dados de teste...\n";

try
{
    db("DELETE FROM {$schema}.biostech_orcamento_log WHERE orcamento_numero = ?", array($n));

    // O orcamento aponta para o chamado; precisa sair antes.
    db("DELETE FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($n));

    if ($chamado)
    {
        db("DELETE FROM {$schema}.biostech_chamado_eventos WHERE chamado_numero = ?", array($chamado));
        db("DELETE FROM {$schema}.biostech_chamado_pecas WHERE chamado_numero = ?", array($chamado));
        db("DELETE FROM {$schema}.biostech_chamado_servicos WHERE chamado_numero = ?", array($chamado));
        db("DELETE FROM {$schema}.biostech_chamado_atendimento WHERE chamado_numero = ?", array($chamado));
        db("DELETE FROM {$schema}.biostech_chamado_equipamento WHERE chamado_numero = ?", array($chamado));
        db("DELETE FROM {$schema}.biostech_chamados WHERE numero = ?", array($chamado));
    }

    db("DELETE FROM {$schema}.biostech_orcamento_dimensionamento_log WHERE dimensionamento_numero = ?", array($d));
    db("DELETE FROM {$schema}.biostech_orcamentos_dimensionamento WHERE numero = ?", array($d));

    // O orcamento $b ja foi excluido pelo endpoint; o log dele nao tem FK.
    db("DELETE FROM {$schema}.biostech_orcamento_log WHERE orcamento_numero = ?", array($b));

    if ($b > 0)
    {
        db("DELETE FROM {$schema}.biostech_orcamentos WHERE numero = ?", array($b));
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

echo "TODOS OS TESTES DE ORCAMENTOS PASSARAM\n";
exit(0);
