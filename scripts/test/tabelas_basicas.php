<?php
/**
 * BIOSTECH - teste funcional do modulo Tabelas Basicas
 *
 * Percorre o menu, todas as telas de cadastro e exercita inclusao, alteracao e
 * exclusao dos itens (dominio, setor, parametros, modulos e programas).
 * Ao final remove os dados de teste e restaura os parametros originais.
 *
 * Uso:
 *   php scripts/test/tabelas_basicas.php [url-base] [login] [senha]
 */

$base  = isset($argv[1]) ? rtrim($argv[1], '/') : 'http://127.0.0.1:8090';
$login = isset($argv[2]) ? $argv[2] : 'ANDRIUS';
$senha = isset($argv[3]) ? $argv[3] : '123123';

$jar    = tempnam(sys_get_temp_dir(), 'biostech_tb_');
$falhas = 0;

$codigo_teste = 9999;
$modulo_teste = 9999;
$programa_teste = 1;

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

function postar($url, $dados)
{
    global $base;

    // Qualquer pagina com formulario serve: o token CSRF e da sessao.
    $dados['biostech_csrf'] = token('/tabelas_basicas/categoria/tipos_equipamento');

    return req($base . $url, $dados);
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
// Limpeza inicial
// ---------------------------------------------------------------------

db("DELETE FROM {$schema}.biostech_dominios WHERE tipo = 'tipo_equipamento' AND codigo = ?", array($codigo_teste));
db("DELETE FROM {$schema}.biostech_basico_programas WHERE modulo_codigo = ?", array($modulo_teste));
db("DELETE FROM {$schema}.biostech_basico_modulos WHERE codigo = ?", array($modulo_teste));

$param_original = db("SELECT * FROM {$schema}.biostech_basico_parametros WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
checar('parametros existem', ! empty($param_original));

// ---------------------------------------------------------------------
// Menu e telas
// ---------------------------------------------------------------------

paginar('/tabelas_basicas', array(
    'Setores e Filiais',
    'Tipos de Equipamentos',
    'Status da Ordem de Servico',
    'Status do Equipamento',
    'Condicoes de Pagamento',
    'Status do Orcamento',
    'Locais de Atendimento',
    'Valor Orcamento Nao Aprovado',
    'Finalidade do Orcamento',
    'Natureza das Notas Fiscais',
    'Cargos e Funcoes',
    'Permissoes de Acesso',
    'Tipos de Pagamentos',
    'Retorna ao Menu',
));

paginar('/tabelas_basicas/categoria/setores', array('GERENCIA', 'Setores e Filiais'));
paginar('/tabelas_basicas/categoria/tipos_equipamento', array('MONITOR', 'Qtd. equip.'));
paginar('/tabelas_basicas/categoria/status_os', array('ABERTA', 'Chamados'));
paginar('/tabelas_basicas/categoria/status_equipamento', array('PENDENTE'));
paginar('/tabelas_basicas/categoria/condicoes_pagamento', array('A VISTA'));
paginar('/tabelas_basicas/categoria/situacoes_orcamento', array('ABERTO', 'Orcamentos'));
paginar('/tabelas_basicas/categoria/locais_atendimento', array('COORDENA'));
paginar('/tabelas_basicas/categoria/finalidades', array('MANUTENCAO CORRETIVA'));
paginar('/tabelas_basicas/categoria/naturezas_nota', array('REMESSA'));
paginar('/tabelas_basicas/categoria/funcoes', array('DIRETOR GERAL'));
paginar('/tabelas_basicas/categoria/tipos_pagamento', array('CHEQUE DO DIA'));
paginar('/tabelas_basicas/parametros', array('Texto de rodape do orcamento', 'VALOR_ORC_NAPROV'));
paginar('/tabelas_basicas/permissoes', array('Administrativo', 'Programas'));
paginar('/tabelas_basicas/programas/1', array('Nenhum programa cadastrado'));

// ---------------------------------------------------------------------
// CRUD de dominio (tipos de equipamento)
// ---------------------------------------------------------------------

$nome = 'TESTE TB ' . date('His');

list($status, $corpo) = postar('/tabelas_basicas/salvar/tipos_equipamento', array(
    'editar' => 0,
    'codigo' => $codigo_teste,
    'nome'   => $nome,
    'ordem'  => $codigo_teste,
    'ativo'  => 1,
));

checar('incluir tipo de equipamento', strpos($corpo, 'Item gravado') !== FALSE);

$novo = db(
    "SELECT nome, ativo FROM {$schema}.biostech_dominios
      WHERE tipo = 'tipo_equipamento' AND codigo = ?",
    array($codigo_teste)
)->fetch(PDO::FETCH_ASSOC);

checar('tipo de equipamento gravado no banco', ! empty($novo) && $novo['nome'] === mb_strtoupper($nome));
checar('tipo de equipamento ativo', ! empty($novo) && in_array($novo['ativo'], array('t', TRUE), TRUE));

list($status, $corpo) = postar('/tabelas_basicas/salvar/tipos_equipamento', array(
    'editar' => 0,
    'codigo' => $codigo_teste,
    'nome'   => 'DUPLICADO',
    'ativo'  => 1,
));

checar('codigo duplicado rejeitado', strpos($corpo, 'Ja existe um item com o codigo') !== FALSE);

list($corpo) = paginar('/tabelas_basicas/categoria/tipos_equipamento?editar=' . $codigo_teste, array('readonly'));

list($status, $corpo) = postar('/tabelas_basicas/salvar/tipos_equipamento', array(
    'editar' => $codigo_teste,
    'codigo' => $codigo_teste,
    'nome'   => $nome . ' ALTERADO',
    'ordem'  => $codigo_teste,
    'ativo'  => 1,
));

checar('alterar tipo de equipamento', strpos($corpo, 'Item gravado') !== FALSE);

$alterado = valor(
    "SELECT nome FROM {$schema}.biostech_dominios
      WHERE tipo = 'tipo_equipamento' AND codigo = ?",
    array($codigo_teste)
);

checar('nome alterado no banco', $alterado === mb_strtoupper($nome . ' ALTERADO'));

// Exclusao de item em uso deve ser bloqueada (status_os 1 tem chamados).
$antes = valor("SELECT count(*) FROM {$schema}.biostech_dominios WHERE tipo = 'status_os' AND codigo = 1");

list($status, $corpo) = postar('/tabelas_basicas/excluir/status_os/1', array());

$depois = valor("SELECT count(*) FROM {$schema}.biostech_dominios WHERE tipo = 'status_os' AND codigo = 1");

checar('exclusao de item em uso bloqueada', $antes === $depois && strpos($corpo, 'Nao e possivel excluir') !== FALSE);

// Exclusao por GET deve ser recusada (acao destrutiva exige POST).
req($base . '/tabelas_basicas/excluir/tipos_equipamento/' . $codigo_teste);
$ainda = valor(
    "SELECT count(*) FROM {$schema}.biostech_dominios
      WHERE tipo = 'tipo_equipamento' AND codigo = ?",
    array($codigo_teste)
);
checar('exclusao por GET recusada', (int) $ainda === 1);

// POST sem CSRF deve ser recusado.
list($status, $corpo) = req($base . '/tabelas_basicas/salvar/tipos_equipamento', array(
    'editar' => $codigo_teste,
    'codigo' => $codigo_teste,
    'nome'   => 'SEM CSRF',
    'ativo'  => 1,
));
$sem_csrf = valor(
    "SELECT nome FROM {$schema}.biostech_dominios
      WHERE tipo = 'tipo_equipamento' AND codigo = ?",
    array($codigo_teste)
);
checar('POST sem CSRF recusado', $sem_csrf !== 'SEM CSRF');

list($status, $corpo) = postar('/tabelas_basicas/excluir/tipos_equipamento/' . $codigo_teste, array());

checar('excluir tipo de equipamento', strpos($corpo, 'Registro excluido') !== FALSE);
checar('tipo de equipamento removido',
    (int) valor("SELECT count(*) FROM {$schema}.biostech_dominios
                  WHERE tipo = 'tipo_equipamento' AND codigo = ?", array($codigo_teste)) === 0);

// ---------------------------------------------------------------------
// CRUD de setores
// ---------------------------------------------------------------------

$setor_id = (int) valor("SELECT COALESCE(MAX(id), 0) + 1 FROM {$schema}.biostech_setores");
$setor_nome = 'SETOR TESTE TB';

db("DELETE FROM {$schema}.biostech_setores WHERE id = ?", array($setor_id));

list($status, $corpo) = postar('/tabelas_basicas/salvar/setores', array(
    'editar' => 0,
    'codigo' => $setor_id,
    'nome'   => $setor_nome,
    'ativo'  => 1,
));

checar('incluir setor', strpos($corpo, 'Setor gravado') !== FALSE);
checar('setor gravado', valor("SELECT nome FROM {$schema}.biostech_setores WHERE id = ?", array($setor_id)) === $setor_nome);

list($status, $corpo) = postar('/tabelas_basicas/salvar/setores', array(
    'editar' => $setor_id,
    'codigo' => $setor_id,
    'nome'   => $setor_nome . ' II',
    'ativo'  => 1,
));

checar('alterar setor', strpos($corpo, 'Setor gravado') !== FALSE);
checar('setor alterado',
    valor("SELECT nome FROM {$schema}.biostech_setores WHERE id = ?", array($setor_id)) === $setor_nome . ' II');

list($status, $corpo) = postar('/tabelas_basicas/excluir/setores/' . $setor_id, array());

checar('excluir setor', strpos($corpo, 'Registro excluido') !== FALSE);
checar('setor removido',
    (int) valor("SELECT count(*) FROM {$schema}.biostech_setores WHERE id = ?", array($setor_id)) === 0);

// ---------------------------------------------------------------------
// Parametros do orcamento nao aprovado
// ---------------------------------------------------------------------

list($status, $corpo) = postar('/tabelas_basicas/parametros_salvar', array(
    'valor'               => '99,90',
    'prazo_ret_equip'     => '10',
    'valor_taxa_perm'     => '5,50',
    'prazo_ini_envio_orc' => '24',
    'prazo_fim_envio_orc' => '72',
    'dias_limite'         => '30',
    'texto_rodape'        => 'TEXTO DE TESTE',
));

checar('gravar parametros', strpos($corpo, 'Parametros gravados') !== FALSE);

$p = db("SELECT * FROM {$schema}.biostech_basico_parametros WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

checar('valor dos parametros', abs((float) $p['valor'] - 99.90) < 0.001, (string) $p['valor']);
checar('taxa de permanencia', abs((float) $p['valor_taxa_perm'] - 5.50) < 0.001, (string) $p['valor_taxa_perm']);
checar('dias limite', (int) $p['dias_limite'] === 30);
checar('texto de rodape', $p['texto_rodape'] === 'TEXTO DE TESTE');

// Restaura os parametros originais.
db(
    "UPDATE {$schema}.biostech_basico_parametros
        SET valor = ?, prazo_ret_equip = ?, valor_taxa_perm = ?,
            prazo_ini_envio_orc = ?, prazo_fim_envio_orc = ?, dias_limite = ?,
            texto_rodape = ?, updated_at = now()
      WHERE id = 1",
    array(
        $param_original['valor'], $param_original['prazo_ret_equip'],
        $param_original['valor_taxa_perm'], $param_original['prazo_ini_envio_orc'],
        $param_original['prazo_fim_envio_orc'], $param_original['dias_limite'],
        $param_original['texto_rodape'],
    )
);

checar('parametros restaurados',
    (float) valor("SELECT valor FROM {$schema}.biostech_basico_parametros WHERE id = 1")
        === (float) $param_original['valor']);

// ---------------------------------------------------------------------
// Permissoes: modulos e programas
// ---------------------------------------------------------------------

list($status, $corpo) = postar('/tabelas_basicas/modulo_salvar', array(
    'editar' => 0,
    'codigo' => $modulo_teste,
    'nome'   => 'MODULO TESTE TB',
    'ordem'  => $modulo_teste,
    'ativo'  => 1,
));

checar('incluir modulo', strpos($corpo, 'Modulo gravado') !== FALSE);
checar('modulo gravado',
    valor("SELECT nome FROM {$schema}.biostech_basico_modulos WHERE codigo = ?", array($modulo_teste)) === 'MODULO TESTE TB');

list($status, $corpo) = postar('/tabelas_basicas/programa_salvar/' . $modulo_teste, array(
    'editar'     => 0,
    'codigo'     => $programa_teste,
    'nome'       => 'PROGRAMA TESTE TB',
    'formulario' => 'F_TESTE',
    'ordem'      => 1,
    'ativo'      => 1,
));

checar('incluir programa', strpos($corpo, 'Programa gravado') !== FALSE);
checar('programa gravado',
    valor("SELECT nome FROM {$schema}.biostech_basico_programas
            WHERE modulo_codigo = ? AND codigo = ?", array($modulo_teste, $programa_teste)) === 'PROGRAMA TESTE TB');

list($status, $corpo) = postar('/tabelas_basicas/programa_salvar/' . $modulo_teste, array(
    'editar'     => $programa_teste,
    'codigo'     => $programa_teste,
    'nome'       => 'PROGRAMA TESTE TB II',
    'formulario' => 'F_TESTE2',
    'ordem'      => 2,
    'ativo'      => 1,
));

checar('alterar programa', strpos($corpo, 'Programa gravado') !== FALSE);
checar('programa alterado',
    valor("SELECT nome FROM {$schema}.biostech_basico_programas
            WHERE modulo_codigo = ? AND codigo = ?", array($modulo_teste, $programa_teste)) === 'PROGRAMA TESTE TB II');

list($status, $corpo) = postar('/tabelas_basicas/modulo_excluir/' . $modulo_teste, array());

checar('exclusao de modulo com programa bloqueada',
    strpos($corpo, 'Nao e possivel excluir') !== FALSE
    && (int) valor("SELECT count(*) FROM {$schema}.biostech_basico_modulos WHERE codigo = ?", array($modulo_teste)) === 1);

list($status, $corpo) = postar('/tabelas_basicas/programa_excluir/' . $modulo_teste . '/' . $programa_teste, array());

checar('excluir programa', strpos($corpo, 'Programa excluido') !== FALSE);

list($status, $corpo) = postar('/tabelas_basicas/modulo_excluir/' . $modulo_teste, array());

checar('excluir modulo', strpos($corpo, 'Modulo excluido') !== FALSE);
checar('modulo removido',
    (int) valor("SELECT count(*) FROM {$schema}.biostech_basico_modulos WHERE codigo = ?", array($modulo_teste)) === 0);

// ---------------------------------------------------------------------
// Limpeza final
// ---------------------------------------------------------------------

db("DELETE FROM {$schema}.biostech_dominios WHERE tipo = 'tipo_equipamento' AND codigo = ?", array($codigo_teste));
db("DELETE FROM {$schema}.biostech_setores WHERE id = ?", array($setor_id));
db("DELETE FROM {$schema}.biostech_basico_programas WHERE modulo_codigo = ?", array($modulo_teste));
db("DELETE FROM {$schema}.biostech_basico_modulos WHERE codigo = ?", array($modulo_teste));

@unlink($jar);

echo "\n";
if ($falhas === 0)
{
    echo "== Tabelas Basicas: todas as verificacoes passaram ==\n";
    exit(0);
}

echo "== Tabelas Basicas: $falhas falha(s) ==\n";
exit(1);
