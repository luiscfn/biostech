<?php
$valor = (float) $registro['valor'];
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>Baixar conta a pagar <?= (int) $registro['numero'] ?>/<?= (int) $registro['parcela'] ?></h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/conta_pagar_ver/' . $registro['id']) ?>">Voltar</a>
        </div>
    </div>

    <div class="painel-corpo">
        <div class="detalhes" style="margin-bottom:18px">
            <div class="detalhe">
                <div class="rotulo">Fornecedor</div>
                <div class="valor"><?= html_escape($registro['fornecedor_nome'] ? $registro['fornecedor_nome'] : '-') ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Vencimento</div>
                <div class="valor"><?= data_br($registro['vencimento']) ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Valor do titulo</div>
                <div class="valor"><strong><?= moeda_br($valor) ?></strong></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Documento</div>
                <div class="valor"><?= html_escape($registro['documento'] ? $registro['documento'] : '-') ?></div>
            </div>
        </div>

        <?= form_open('financeiro/conta_pagar_baixar/' . $registro['id'], array('id' => 'form-baixa-cp')) ?>

            <div class="form-grid">
                <div class="campo terco">
                    <label for="data_pagamento">Data do pagamento *</label>
                    <input type="date" name="data_pagamento" id="data_pagamento" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="campo terco">
                    <label for="multa">Multa</label>
                    <input type="text" name="multa" id="multa" inputmode="decimal" value="0,00" data-calculo="baixa">
                </div>

                <div class="campo terco">
                    <label for="juros">Juros</label>
                    <input type="text" name="juros" id="juros" inputmode="decimal" value="0,00" data-calculo="baixa">
                </div>

                <div class="campo terco">
                    <label for="desconto">Desconto</label>
                    <input type="text" name="desconto" id="desconto" inputmode="decimal" value="0,00" data-calculo="baixa">
                </div>

                <div class="campo terco">
                    <label for="valor_pago">Valor pago *</label>
                    <input type="text" name="valor_pago" id="valor_pago" inputmode="decimal"
                           value="<?= number_format($valor, 2, ',', '') ?>" required
                           data-valor-base="<?= number_format($valor, 2, '.', '') ?>">
                </div>

                <div class="campo terco">
                    <label for="conta_codigo">Conta bancaria</label>
                    <select name="conta_codigo" id="conta_codigo">
                        <option value="">-</option>
                        <?php foreach ($contas as $c): ?>
                            <option value="<?= (int) $c['id'] ?>">
                                <?= html_escape($c['banco_codigo'] . ' | ' . $c['agencia'] . ' | ' . $c['conta']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo terco">
                    <label for="saldo_caixa">Saldo em caixa</label>
                    <input type="text" name="saldo_caixa" id="saldo_caixa" inputmode="decimal">
                </div>

                <div class="campo terco">
                    <label for="saldo_conta">Saldo na conta</label>
                    <input type="text" name="saldo_conta" id="saldo_conta" inputmode="decimal">
                </div>

                <div class="campo terco">
                    <label class="check">
                        <input type="checkbox" name="gerar_movimento" value="1"
                            <?= ! empty($registro['banco']) && ! empty($registro['conta']) ? 'checked' : '' ?>>
                        Gerar movimento bancario (debito)
                    </label>
                </div>

                <div class="campo terco">
                    <label class="check">
                        <input type="checkbox" name="gerar_caixa" value="1" <?= bd_bool($registro['ret_caixa']) ? 'checked' : '' ?>>
                        Lancar saida no caixa
                    </label>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao">Confirmar baixa</button>
                <a class="botao secundario" href="<?= site_url('financeiro/conta_pagar_ver/' . $registro['id']) ?>">Cancelar</a>
            </div>

        <?= form_close() ?>
    </div>
</div>

<script src="<?= base_url('assets/js/financeiro.js') ?>"></script>
