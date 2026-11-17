<?php
$total = 0.0;
foreach ($servicos_valores as $servico) { $total += (float) $servico['valor_total']; }
?>
<div class="painel-corpo">
    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th class="num" style="width:40px">#</th>
                    <th>Servico</th>
                    <th class="num" style="width:80px">Qtd</th>
                    <th class="num" style="width:110px">Valor unit.</th>
                    <th class="num" style="width:110px">Total</th>
                    <?php if ($pode_editar): ?>
                        <th class="centro" style="width:160px">Acoes</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($servicos_valores)): ?>
                    <tr><td colspan="<?= $pode_editar ? 6 : 5 ?>" class="vazio">Nenhum servico com valor cadastrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($servicos_valores as $servico): ?>
                        <?php $form_id = 'servico-valor-' . (int) $servico['id']; ?>
                        <tr class="linha-item">
                            <td class="num"><?= (int) $servico['numero_item'] ?></td>
                            <td>
                                <?php if ($pode_editar): ?>
                                    <input type="text" name="descricao" form="<?= $form_id ?>"
                                           value="<?= html_escape($servico['descricao']) ?>" maxlength="200">
                                <?php else: ?>
                                    <?= html_escape($servico['descricao']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="num">
                                <?php if ($pode_editar): ?>
                                    <input type="number" name="quantidade" form="<?= $form_id ?>" min="1" step="1"
                                           value="<?= (int) $servico['quantidade'] ?>" style="width:70px;text-align:right">
                                <?php else: ?>
                                    <?= numero_br($servico['quantidade']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="num">
                                <?php if ($pode_editar): ?>
                                    <input type="text" name="valor_unitario" form="<?= $form_id ?>" inputmode="decimal"
                                           class="valor-unit" value="<?= number_format((float) $servico['valor_unitario'], 2, ',', '.') ?>"
                                           style="width:100px;text-align:right">
                                <?php else: ?>
                                    <?= moeda_br($servico['valor_unitario']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="num">
                                <?php if ($pode_editar): ?>
                                    <input type="text" name="valor_total" form="<?= $form_id ?>" inputmode="decimal"
                                           class="valor-total" value="<?= number_format((float) $servico['valor_total'], 2, ',', '.') ?>"
                                           style="width:100px;text-align:right">
                                <?php else: ?>
                                    <strong><?= moeda_br($servico['valor_total']) ?></strong>
                                <?php endif; ?>
                            </td>
                            <?php if ($pode_editar): ?>
                                <td class="centro">
                                    <?= form_open('orcamentos/atualizar_servico_valor/' . $registro['numero'] . '/' . $servico['id'],
                                        array('id' => $form_id)) ?>
                                        <button type="submit" class="botao secundario pequeno">Salvar</button>
                                    <?= form_close() ?>

                                    <?= form_open('orcamentos/excluir_servico_valor/' . $registro['numero'] . '/' . $servico['id'],
                                        array('style' => 'margin-top:4px', 'onsubmit' => "return confirm('Excluir o servico?')")) ?>
                                        <button type="submit" class="botao perigo pequeno">Excluir</button>
                                    <?= form_close() ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="num"><strong>Total dos servicos</strong></td>
                    <td class="num"><strong><?= moeda_br($total) ?></strong></td>
                    <?php if ($pode_editar): ?><td></td><?php endif; ?>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if ($pode_editar): ?>
        <hr class="divisor">
        <h4 class="secao-titulo">Incluir servico com valor</h4>
        <p class="ajuda" style="margin-top:-4px">
            Estes servicos entram em VALSERV e sao somados ao total do orcamento.
        </p>

        <?= form_open('orcamentos/adicionar_servico_valor/' . $registro['numero']) ?>
            <div class="form-grid">
                <div class="campo metade">
                    <label for="sv_descricao">Descricao</label>
                    <input type="text" name="descricao" id="sv_descricao" maxlength="200" required>
                </div>
                <div class="campo terco">
                    <label for="sv_quantidade">Quantidade</label>
                    <input type="number" name="quantidade" id="sv_quantidade" min="1" step="1" value="1">
                </div>
                <div class="campo terco">
                    <label for="sv_unitario">Valor unitario</label>
                    <input type="text" name="valor_unitario" id="sv_unitario" inputmode="decimal" placeholder="0,00">
                </div>
                <div class="campo terco">
                    <label for="sv_total">Total</label>
                    <input type="text" name="valor_total" id="sv_total" inputmode="decimal" placeholder="calculado">
                </div>
                <div class="campo terco" style="align-self:end">
                    <button type="submit" class="botao">Incluir servico</button>
                </div>
            </div>
        <?= form_close() ?>
    <?php endif; ?>
</div>
