<?php
$editando = ! empty($registro);

$login   = $editando ? $registro['login'] : set_value('login');
$nome    = $editando ? $registro['nome'] : set_value('nome');
$perfil  = $editando ? $registro['perfil'] : set_value('perfil', PERFIL_OPERADOR);
$setor   = $editando ? $registro['setor_id'] : set_value('setor_id');
$almox   = $editando ? bd_bool($registro['acesso_almoxarifado']) : FALSE;
$ativo   = $editando ? bd_bool($registro['ativo']) : TRUE;
?>

<div class="painel">
    <div class="painel-cabecalho">
        <h3><?= $editando ? 'Editar usuario' : 'Novo usuario' ?></h3>
        <?php if ($editando): ?>
            <span class="badge <?= bd_bool($registro['ativo']) ? 'ok' : 'erro' ?>">
                <?= bd_bool($registro['ativo']) ? 'ativo' : 'inativo' ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="painel-corpo">
        <?= form_open($editando ? 'usuarios/editar/' . $registro['id'] : 'usuarios/novo') ?>

            <div class="form-grid">
                <div class="campo">
                    <label for="login">Login *</label>
                    <input type="text" name="login" id="login" value="<?= html_escape($login) ?>" maxlength="30">
                    <?= form_error('login') ?>
                </div>

                <div class="campo">
                    <label for="nome">Nome completo *</label>
                    <input type="text" name="nome" id="nome" value="<?= html_escape($nome) ?>" maxlength="100">
                    <?= form_error('nome') ?>
                </div>

                <?php if ( ! $editando): ?>
                    <div class="campo">
                        <label for="senha">Senha provisoria *</label>
                        <input type="password" name="senha" id="senha" maxlength="64" autocomplete="new-password">
                        <div class="ajuda">Minimo de 6 caracteres. O usuario deve trocar no primeiro acesso.</div>
                        <?= form_error('senha') ?>
                    </div>
                <?php endif; ?>

                <div class="campo">
                    <label for="perfil">Perfil *</label>
                    <select name="perfil" id="perfil">
                        <option value="operador" <?= $perfil === 'operador' ? 'selected' : '' ?>>Operador</option>
                        <option value="gestor"   <?= $perfil === 'gestor' ? 'selected' : '' ?>>Gestor</option>
                        <option value="admin"    <?= $perfil === 'admin' ? 'selected' : '' ?>>Administrador</option>
                    </select>
                    <?= form_error('perfil') ?>
                </div>

                <div class="campo">
                    <label for="setor_id">Setor</label>
                    <select name="setor_id" id="setor_id">
                        <option value="">(sem setor)</option>
                        <?php foreach ($setores as $s): ?>
                            <option value="<?= (int) $s['id'] ?>" <?= ((string) $setor === (string) $s['id']) ? 'selected' : '' ?>>
                                <?= html_escape($s['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= form_error('setor_id') ?>
                </div>

                <div class="campo">
                    <div class="check">
                        <input type="checkbox" name="acesso_almoxarifado" id="acesso_almoxarifado" value="1" <?= $almox ? 'checked' : '' ?>>
                        <label for="acesso_almoxarifado" style="margin:0;text-transform:none;letter-spacing:0;font-size:13px">Acesso ao almoxarifado</label>
                    </div>

                    <div class="check" style="margin-top:10px">
                        <input type="checkbox" name="ativo" id="ativo" value="1" <?= $ativo ? 'checked' : '' ?>>
                        <label for="ativo" style="margin:0;text-transform:none;letter-spacing:0;font-size:13px">Usuario ativo</label>
                    </div>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao"><?= $editando ? 'Salvar alteracoes' : 'Cadastrar usuario' ?></button>
                <a class="botao secundario" href="<?= site_url('usuarios') ?>">Cancelar</a>
            </div>

        <?= form_close() ?>
    </div>
</div>
