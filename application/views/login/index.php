<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar · <?= APP_NOME ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="tela-login">
    <div class="login-card">
        <div class="login-marca">
            <div class="logo">BS</div>
            <div>
                <h1><?= APP_NOME ?></h1>
                <small>Sistema de gestao</small>
            </div>
        </div>

        <p class="subtitulo">Acesse com seu usuario e senha do BIOSTECH.</p>

        <?php if ( ! empty($erro)): ?>
            <div class="alert alert-danger"><?= html_escape($erro) ?></div>
        <?php endif; ?>

        <?= form_open('login/entrar') ?>

            <label for="login">Usuario</label>
            <input type="text" name="login" id="login" value="<?= html_escape($login) ?>"
                   autocomplete="username" autofocus maxlength="30">

            <label for="senha">Senha</label>
            <input type="password" name="senha" id="senha" autocomplete="current-password" maxlength="64">

            <button type="submit" class="botao">Entrar</button>

        <?= form_close() ?>

        <div class="login-rodape">
            <?= APP_NOME ?> <?= APP_VERSAO ?> · conversao do Access 2003 para web
        </div>
    </div>
</div>
</body>
</html>
