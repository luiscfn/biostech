<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($titulo ? $titulo . ' · ' . APP_NOME : APP_NOME) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/favicon.svg') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="app">

    <aside class="sidebar">
        <div class="marca">
            <div class="logo">BS</div>
            <div>
                <strong><?= APP_NOME ?></strong>
                <span>SISTEMA DE GESTAO</span>
            </div>
        </div>

        <nav>
            <div class="grupo">Principal</div>
            <a class="item <?= $menu_ativo === 'dashboard' ? 'ativo' : '' ?>" href="<?= site_url('dashboard') ?>">Painel</a>

            <div class="grupo">Operacao</div>
            <a class="item <?= $menu_ativo === 'chamados' ? 'ativo' : '' ?>" href="<?= site_url('chamados') ?>">Chamados / OS</a>
            <a class="item <?= $menu_ativo === 'orcamentos' ? 'ativo' : '' ?>" href="<?= site_url('orcamentos') ?>">Orcamentos</a>
            <a class="item <?= $menu_ativo === 'estoque' ? 'ativo' : '' ?>" href="<?= site_url('estoque') ?>">Estoque</a>
            <a class="item <?= $menu_ativo === 'compras' ? 'ativo' : '' ?>" href="<?= site_url('compras') ?>">Compras</a>
            <a class="item <?= $menu_ativo === 'financeiro' ? 'ativo' : '' ?>" href="<?= site_url('financeiro') ?>">Financeiro</a>

            <div class="grupo">Tabelas Basicas</div>
            <a class="item <?= $menu_ativo === 'tabelas_basicas' ? 'ativo' : '' ?>" href="<?= site_url('tabelas_basicas') ?>">Tabelas Basicas</a>

            <?php if ($menu_ativo === 'tabelas_basicas'): ?>
                <div class="submenu">
                    <a class="item sub <?= $menu_sub === 'setores' ? 'ativo' : '' ?>" href="<?= site_url('tabelas_basicas/categoria/setores') ?>">A · Setores e Filiais</a>
                    <a class="item sub <?= $menu_sub === 'tipos_equipamento' ? 'ativo' : '' ?>" href="<?= site_url('tabelas_basicas/categoria/tipos_equipamento') ?>">B · Tipos de Equipamentos</a>
                    <a class="item sub <?= $menu_sub === 'status_os' ? 'ativo' : '' ?>" href="<?= site_url('tabelas_basicas/categoria/status_os') ?>">C · Status da OS</a>
                    <a class="item sub <?= $menu_sub === 'status_equipamento' ? 'ativo' : '' ?>" href="<?= site_url('tabelas_basicas/categoria/status_equipamento') ?>">D · Status do Equipamento</a>
                    <a class="item sub <?= $menu_sub === 'condicoes_pagamento' ? 'ativo' : '' ?>" href="<?= site_url('tabelas_basicas/categoria/condicoes_pagamento') ?>">E · Condicoes de Pagamento</a>
                    <a class="item sub <?= $menu_sub === 'situacoes_orcamento' ? 'ativo' : '' ?>" href="<?= site_url('tabelas_basicas/categoria/situacoes_orcamento') ?>">· Status do Orcamento</a>
                    <a class="item sub <?= $menu_sub === 'locais_atendimento' ? 'ativo' : '' ?>" href="<?= site_url('tabelas_basicas/categoria/locais_atendimento') ?>">· Locais de Atendimento</a>
                    <a class="item sub <?= $menu_sub === 'valor_orcamento_nao_aprovado' ? 'ativo' : '' ?>" href="<?= site_url('tabelas_basicas/parametros') ?>">· Valor Orc. Nao Aprovado</a>
                    <a class="item sub <?= $menu_sub === 'finalidades' ? 'ativo' : '' ?>" href="<?= site_url('tabelas_basicas/categoria/finalidades') ?>">F · Finalidade do Orcamento</a>
                    <a class="item sub <?= $menu_sub === 'naturezas_nota' ? 'ativo' : '' ?>" href="<?= site_url('tabelas_basicas/categoria/naturezas_nota') ?>">G · Natureza das Notas Fiscais</a>
                    <a class="item sub <?= $menu_sub === 'funcoes' ? 'ativo' : '' ?>" href="<?= site_url('tabelas_basicas/categoria/funcoes') ?>">H · Cargos e Funcoes</a>
                    <a class="item sub <?= $menu_sub === 'permissoes' ? 'ativo' : '' ?>" href="<?= site_url('tabelas_basicas/permissoes') ?>">I · Permissoes de Acesso</a>
                    <a class="item sub <?= $menu_sub === 'tipos_pagamento' ? 'ativo' : '' ?>" href="<?= site_url('tabelas_basicas/categoria/tipos_pagamento') ?>">K · Tipos de Pagamentos</a>
                </div>
            <?php endif; ?>

            <div class="grupo">Cadastros</div>
            <a class="item <?= $menu_ativo === 'clientes' ? 'ativo' : '' ?>" href="<?= site_url('clientes') ?>">Clientes</a>
            <a class="item <?= $menu_ativo === 'produtos' ? 'ativo' : '' ?>" href="<?= site_url('produtos') ?>">Produtos</a>
            <a class="item <?= $menu_ativo === 'setores' ? 'ativo' : '' ?>" href="<?= site_url('setores') ?>">Setores</a>

            <?php if (in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE)): ?>
                <a class="item <?= $menu_ativo === 'usuarios' ? 'ativo' : '' ?>" href="<?= site_url('usuarios') ?>">Usuarios</a>
            <?php endif; ?>
        </nav>

        <div class="rodape">
            <span class="nome"><?= html_escape($usuario['nome']) ?></span>
            <span class="perfil"><?= html_escape($usuario['perfil']) ?><?= $usuario['setor'] ? ' · ' . html_escape($usuario['setor']) : '' ?></span>
        </div>
    </aside>

    <div class="conteudo">
        <header class="topbar">
            <h2><?= html_escape($titulo) ?></h2>

            <div class="acoes">
                <div class="usuario-badge">
                    <div class="avatar"><?= html_escape(mb_substr($usuario['nome'], 0, 1)) ?></div>
                    <div>
                        <strong><?= html_escape($usuario['login']) ?></strong><br>
                        <?= html_escape($usuario['perfil']) ?>
                    </div>
                </div>
                <a class="botao secundario pequeno" href="<?= site_url('logout') ?>">Sair</a>
            </div>
        </header>

        <main class="pagina">
            <?php if ( ! empty($flash)): ?>
                <div class="alert alert-<?= html_escape($flash['tipo']) ?>">
                    <?= html_escape($flash['mensagem']) ?>
                </div>
            <?php endif; ?>

            <?= $conteudo ?>
        </main>
    </div>

</div>
</body>
</html>
