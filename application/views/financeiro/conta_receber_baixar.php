<?php
$valor = (float) $registro['vliq'] > 0 ? (float) $registro['vliq'] : (float) $registro['valor'];
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>Receber conta a receber <?= (int) $registro['codigo'] ?>/<?= (int) $registro['parcela'] ?></h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/conta_receber_ver/' . $registro['codigo']) ?>">Voltar</a>
        </div>
    </div>

    <div class="painel-corpo">
        <div class="detalhes" style="margin-bottom:18px">
            <div class="detalhe">
                <div class="rotulo">Cliente</div>
                <div class="valor"><?= html_escape($registro['cliente_nome'] ? $registro['cliente_nome'] : '-') ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Vencimento</div>
                <div class="valor"><?= data_br($registro['data_vencimento']) ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Valor liquido</div>
                <div class="valor"><strong><?= moeda_br($valor) ?></strong></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Documento</div>
                <div class="valor"><?= html_escape($registro['numero_documento'] ? $registro['numero_documento'] : '-') ?></div>
            </div>
        </div>

        <?= form_open('financeiro/conta_receber_baixar/' . $registro['codigo'], array('id' => 'form-baixa-cr')) ?>

            <div class="form-grid">
                <div class="campo terco">
                    <label for="data_pagamento">Data do recebimento *</label>
                    <input type="date" name="data_pagamento" id="data_pagamento" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="campo terco">
                    <label for="multa">Multa</label>
                    <input type="text" name="multa" id="multa" inputmode="decimal" value="0,00" data-calculo="baixa-cr">
                </div>

                <div class="campo terco">
                    <label for="juros">Juros</label>
                    <input type="text" name="juros" id="juros" inputmode="decimal" value="0,00" data-calculo="baixa-cr">
                </div>

                <div class="campo terco">
                    <label for="desconto">Desconto</label>
                    <input type="text" name="desconto" id="desconto" inputmode="decimal" value="0,00" data-calculo="baixa-cr">
                </div>

                <div class="campo terco">
                    <label for="valor_recebido">Valor recebido *</label>
                    <input type="text" name="valor_recebido" id="valor_recebido" inputmode="decimal"
                           value="<?= number_format($valor, 2, ',', '') ?>" required
                           data-valor-base="<?= number_format($valor, 2, '.', '') ?>">
                </div>

                <div class="campo terco">
                    <label class="check">
                        <input type="checkbox" name="gerar_movimento" value="1"
                            <?= ! empty($registro['banco']) && ! empty($registro['conta']) ? 'checked' : '' ?>>
                        Gerar movimento bancario (credito)
                    </label>
                </div>

                <div class="campo terco">
                    <label class="check">
                        <input type="checkbox" name="gerar_caixa" value="1" <?= bd_bool($registro['ret_caixa']) ? 'checked' : '' ?>>
                        Lancar entrada no caixa
                    </label>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao">Confirmar recebimento</button>
                <a class="botao secundario" href="<?= site_url('financeiro/conta_receber_ver/' . $registro['codigo']) ?>">Cancelar</a>
            </div>

        <?= form_close() ?>
    </div>
</div>

<script src="<?= base_url('assets/js/financeiro.js') ?>"></script>
