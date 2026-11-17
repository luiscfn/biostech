<?php
$edicao = ! empty($registro['id']);
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>
            <?= $edicao ? 'Editar movimento bancario ' . (int) $registro['numero'] : 'Novo movimento bancario' ?>
        </h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/movimentos') ?>">Voltar</a>
        </div>
    </div>

    <div class="painel-corpo">
        <?= form_open($edicao ? 'financeiro/movimento_editar/' . $registro['id'] : 'financeiro/movimento_novo') ?>

            <div class="form-grid">
                <?php if ($edicao): ?>
                    <div class="campo terco">
                        <label>Numero</label>
                        <input type="text" value="<?= (int) $registro['numero'] ?>" disabled>
                    </div>
                <?php endif; ?>

                <div class="campo terco">
                    <label for="data">Data *</label>
                    <input type="date" name="data" id="data" value="<?= $edicao ? html_escape($registro['data']) : date('Y-m-d') ?>" required>
                </div>

                <div class="campo terco">
                    <label for="tipo">Tipo *</label>
                    <select name="tipo" id="tipo" required>
                        <option value="C" <?= $edicao && $registro['tipo'] === 'C' ? 'selected' : '' ?>>Credito (entrada)</option>
                        <option value="D" <?= $edicao && $registro['tipo'] === 'D' ? 'selected' : '' ?>>Debito (saida)</option>
                    </select>
                </div>

                <div class="campo terco">
                    <label for="valor">Valor *</label>
                    <input type="text" name="valor" id="valor" inputmode="decimal" required
                           value="<?= $edicao ? number_format((float) $registro['valor'], 2, ',', '') : '' ?>">
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
                           value="<?= $edicao ? html_escape($registro['agencia']) : '' ?>">
                </div>

                <div class="campo terco">
                    <label for="conta">Conta</label>
                    <input type="text" name="conta" id="conta" maxlength="25"
                           value="<?= $edicao ? html_escape($registro['conta']) : '' ?>">
                </div>

                <div class="campo full">
                    <label for="historico">Historico</label>
                    <textarea name="historico" id="historico" rows="3"><?= $edicao ? html_escape($registro['historico']) : '' ?></textarea>
                </div>

                <div class="campo terco">
                    <label for="conta_pagar_numero">Conta a pagar - numero</label>
                    <input type="number" name="conta_pagar_numero" id="conta_pagar_numero" min="0" step="1"
                           value="<?= $edicao && $registro['conta_pagar_numero'] !== NULL ? (int) $registro['conta_pagar_numero'] : '' ?>">
                </div>

                <div class="campo terco">
                    <label for="conta_pagar_parcela">Conta a pagar - parcela</label>
                    <input type="number" name="conta_pagar_parcela" id="conta_pagar_parcela" min="0" step="1"
                           value="<?= $edicao && $registro['conta_pagar_parcela'] !== NULL ? (int) $registro['conta_pagar_parcela'] : '' ?>">
                </div>

                <div class="campo terco"></div>

                <div class="campo terco">
                    <label for="conta_receber_codigo">Conta a receber - codigo</label>
                    <input type="number" name="conta_receber_codigo" id="conta_receber_codigo" min="0" step="1"
                           value="<?= $edicao && $registro['conta_receber_codigo'] !== NULL ? (int) $registro['conta_receber_codigo'] : '' ?>">
                </div>

                <div class="campo terco">
                    <label for="conta_receber_parcela">Conta a receber - parcela</label>
                    <input type="number" name="conta_receber_parcela" id="conta_receber_parcela" min="0" step="1"
                           value="<?= $edicao && $registro['conta_receber_parcela'] !== NULL ? (int) $registro['conta_receber_parcela'] : '' ?>">
                </div>
            </div>

            <?php if ($edicao): ?>
                <div class="ajuda" style="margin-top:12px">
                    Saldo anterior: <?= moeda_br($registro['saldo_anterior']) ?>
                    | saldo atual: <?= moeda_br($registro['saldo_atual']) ?>
                </div>
            <?php endif; ?>

            <div class="form-acoes">
                <button type="submit" class="botao"><?= $edicao ? 'Salvar alteracoes' : 'Gravar movimento' ?></button>
                <a class="botao secundario" href="<?= site_url('financeiro/movimentos') ?>">Cancelar</a>
            </div>

        <?= form_close() ?>

        <?php if ($edicao): ?>
            <hr class="divisor">
            <h4 class="secao-titulo">Excluir movimento</h4>

            <?= form_open('financeiro/movimento_excluir/' . $registro['id']) ?>
                <div class="ajuda">A exclusao e definitiva e nao recalcula os saldos seguintes.</div>
                <div class="form-acoes" style="justify-content:flex-start;margin-top:8px">
                    <button type="submit" class="botao perigo pequeno">Excluir movimento</button>
                </div>
            <?= form_close() ?>
        <?php endif; ?>
    </div>
</div>
