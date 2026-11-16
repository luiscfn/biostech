<?php
$editando = ! empty($registro);
$ativo    = $editando ? bd_bool($registro['ativo']) : TRUE;
$nome     = $editando ? $registro['nome'] : set_value('nome');
?>

<div class="painel">
    <div class="painel-cabecalho">
        <h3><?= $editando ? 'Editar setor' : 'Novo setor' ?></h3>
        <span class="badge info"><?= $editando ? 'codigo ' . (int) $registro['id'] : 'novo registro' ?></span>
    </div>

    <div class="painel-corpo">
        <?= form_open($editando ? 'setores/editar/' . $registro['id'] : 'setores/novo') ?>

            <div class="form-grid">
                <div class="campo full">
                    <label for="nome">Nome do setor *</label>
                    <input type="text" name="nome" id="nome" value="<?= html_escape($nome) ?>" maxlength="70" autofocus>
                    <?= form_error('nome') ?>
                </div>

                <div class="campo">
                    <div class="check">
                        <input type="checkbox" name="ativo" id="ativo" value="1" <?= $ativo ? 'checked' : '' ?>>
                        <label for="ativo" style="margin:0;text-transform:none;letter-spacing:0;font-size:13px">Setor ativo</label>
                    </div>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao"><?= $editando ? 'Salvar alteracoes' : 'Cadastrar setor' ?></button>
                <a class="botao secundario" href="<?= site_url('setores') ?>">Cancelar</a>
            </div>

        <?= form_close() ?>
    </div>
</div>
