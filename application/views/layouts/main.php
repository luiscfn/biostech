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
            <span class="item">Chamados / OS <span class="tag">em breve</span></span>
            <span class="item">Orcamentos <span class="tag">em breve</span></span>
            <span class="item">Estoque <span class="tag">em breve</span></span>
            <span class="item">Compras <span class="tag">em breve</span></span>
            <span class="item">Financeiro <span class="tag">em breve</span></span>

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
