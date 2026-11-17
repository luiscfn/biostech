<?php
$edicao = ! empty($registro['id']);

$valor = function ($campo, $default = NULL) use ($registro, $edicao) {
    if ( ! $edicao)
    {
        return $default;
    }

    return isset($registro[$campo]) ? $registro[$campo] : $default;
};
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3><?= $edicao ? 'Editar cheque ' . html_escape($registro['numero']) : 'Novo cheque' ?></h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/cheques') ?>">Voltar</a>
        </div>
    </div>

    <div class="painel-corpo">
        <?= form_open($edicao ? 'financeiro/cheque_editar/' . $registro['id'] : 'financeiro/cheque_novo') ?>

            <div class="form-grid">
                <div class="campo terco">
                    <label for="numero">Numero</label>
                    <input type="text" name="numero" id="numero" maxlength="20"
                           value="<?= html_escape($valor('numero', '')) ?>">
                </div>

                <div class="campo terco">
                    <label for="valor">Valor *</label>
                    <input type="text" name="valor" id="valor" inputmode="decimal" required
                           value="<?= $edicao ? number_format((float) $registro['valor'], 2, ',', '') : '' ?>">
                </div>

                <div class="campo terco">
                    <label for="situacao">Situacao</label>
                    <select name="situacao" id="situacao">
                        <option value="1" <?= $edicao && (int) $registro['situacao'] === 1 ? 'selected' : '' ?>>Emitido</option>
                        <option value="2" <?= $edicao && (int) $registro['situacao'] === 2 ? 'selected' : '' ?>>Compensado</option>
                        <option value="3" <?= $edicao && (int) $registro['situacao'] === 3 ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                </div>

                <div class="campo full">
                    <label for="favorecido">Favorecido</label>
                    <input type="text" name="favorecido" id="favorecido" maxlength="120"
                           value="<?= html_escape($valor('favorecido', '')) ?>">
                </div>

                <div class="campo terco">
                    <label for="banco_codigo">Banco</label>
                    <select name="banco_codigo" id="banco_codigo">
                        <option value="">-</option>
                        <?php foreach ($bancos as $b): ?>
                            <option value="<?= html_escape($b['codigo']) ?>"
                                <?= $edicao && (string) $registro['banco_codigo'] === (string) $b['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($b['codigo'] . ' - ' . $b['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo terco">
                    <label for="agencia">Agencia</label>
                    <input type="text" name="agencia" id="agencia" maxlength="25"
                           value="<?= html_escape($valor('agencia', '')) ?>">
                </div>

                <div class="campo terco">
                    <label for="conta">Conta</label>
                    <input type="text" name="conta" id="conta" maxlength="25"
                           value="<?= html_escape($valor('conta', '')) ?>">
                </div>

                <div class="campo terco">
                    <label for="data_emissao">Emissao</label>
                    <input type="date" name="data_emissao" id="data_emissao"
                           value="<?= html_escape($valor('data_emissao', date('Y-m-d'))) ?>">
                </div>

                <div class="campo terco">
                    <label for="data_vencimento">Vencimento</label>
                    <input type="date" name="data_vencimento" id="data_vencimento"
                           value="<?= html_escape($valor('data_vencimento', '')) ?>">
                </div>

                <div class="campo terco">
                    <label for="data_compensacao">Compensacao</label>
                    <input type="date" name="data_compensacao" id="data_compensacao"
                           value="<?= html_escape($valor('data_compensacao', '')) ?>">
                </div>

                <div class="campo full">
                    <label for="observacao">Observacao</label>
                    <textarea name="observacao" id="observacao" rows="3"><?= html_escape($valor('observacao', '')) ?></textarea>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao"><?= $edicao ? 'Salvar alteracoes' : 'Gravar cheque' ?></button>
                <a class="botao secundario" href="<?= site_url('financeiro/cheques') ?>">Cancelar</a>
            </div>

        <?= form_close() ?>
    </div>
</div>
