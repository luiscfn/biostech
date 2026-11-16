<div class="painel">
    <div class="painel-cabecalho">
        <h3>Alterar senha</h3>
        <span class="badge info"><?= html_escape($registro['login']) ?></span>
    </div>

    <div class="painel-corpo">
        <p style="margin-top:0;color:#6b7785">
            Usuario: <strong><?= html_escape($registro['nome']) ?></strong>
        </p>

        <?= form_open('usuarios/senha/' . $registro['id']) ?>

            <div class="form-grid">
                <div class="campo">
                    <label for="senha">Nova senha *</label>
                    <input type="password" name="senha" id="senha" maxlength="64" autocomplete="new-password">
                    <div class="ajuda">Minimo de 6 caracteres.</div>
                    <?= form_error('senha') ?>
                </div>

                <div class="campo">
                    <label for="senha2">Confirmar nova senha *</label>
                    <input type="password" name="senha2" id="senha2" maxlength="64" autocomplete="new-password">
                    <?= form_error('senha2') ?>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao">Alterar senha</button>
                <a class="botao secundario" href="<?= site_url('usuarios') ?>">Cancelar</a>
            </div>

        <?= form_close() ?>
    </div>
</div>
