<?php
$material = 0.0;
foreach ($itens as $item) { $material += (float) $item['valor_total']; }
?>
<div class="painel-corpo">
    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th class="num" style="width:40px">#</th>
                    <th>Produto</th>
                    <th class="centro" style="width:55px">Unid.</th>
                    <th class="num" style="width:75px">Qtd</th>
                    <th class="num" style="width:105px">Valor unit.</th>
                    <th class="num" style="width:105px">Total</th>
                    <th style="width:170px">Descricao</th>
                    <th class="num" style="width:105px">Unit. original</th>
                    <th class="num" style="width:105px">Total original</th>
                    <?php if ($pode_editar): ?>
                        <th class="centro" style="width:150px">Acoes</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($itens)): ?>
                    <tr><td colspan="<?= $pode_editar ? 10 : 9 ?>" class="vazio">Nenhum item no orcamento.</td></tr>
                <?php else: ?>
                    <?php foreach ($itens as $item): ?>
                        <?php $form_id = 'item-' . (int) $item['id']; ?>
                        <tr class="linha-item">
                            <td class="num"><?= (int) $item['numero_item'] ?></td>
                            <td>
                                <?php if ( ! empty($item['produto_id'])): ?>
                                    <a href="<?= site_url('produtos/ver/' . $item['produto_id']) ?>"><strong><?= html_escape($item['descricao_produto']) ?></strong></a>
                                    <div class="ajuda">codigo <?= html_escape($item['produto_codigo'] ? $item['produto_codigo'] : (string) $item['produto_id']) ?></div>
                                <?php else: ?>
                                    <strong><?= html_escape($item['descricao_produto'] ? $item['descricao_produto'] : $item['descricao']) ?></strong>
                                    <div class="ajuda">item sem cadastro</div>
                                <?php endif; ?>
                            </td>
                            <td class="centro"><?= html_escape($item['produto_unidade'] ? $item['produto_unidade'] : '-') ?></td>
                            <td class="num">
                                <?php if ($pode_editar): ?>
                                    <input type="number" name="quantidade" form="<?= $form_id ?>" min="1" step="1"
                                           value="<?= (int) $item['quantidade'] ?>" style="width:70px;text-align:right">
                                <?php else: ?>
                                    <?= numero_br($item['quantidade']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="num">
                                <?php if ($pode_editar): ?>
                                    <input type="text" name="valor_unitario" form="<?= $form_id ?>" inputmode="decimal"
                                           class="valor-unit" value="<?= number_format((float) $item['valor_unitario'], 2, ',', '.') ?>"
                                           style="width:100px;text-align:right">
                                <?php else: ?>
                                    <?= moeda_br($item['valor_unitario']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="num">
                                <?php if ($pode_editar): ?>
                                    <input type="text" name="valor_total" form="<?= $form_id ?>" inputmode="decimal"
                                           class="valor-total" value="<?= number_format((float) $item['valor_total'], 2, ',', '.') ?>"
                                           style="width:100px;text-align:right">
                                <?php else: ?>
                                    <strong><?= moeda_br($item['valor_total']) ?></strong>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($pode_editar): ?>
                                    <input type="text" name="descricao" form="<?= $form_id ?>" maxlength="100"
                                           value="<?= html_escape($item['descricao']) ?>">
                                <?php else: ?>
                                    <?= html_escape($item['descricao'] ? $item['descricao'] : '-') ?>
                                <?php endif; ?>
                            </td>
                            <td class="num"><?= moeda_br($item['valor_unitario_original']) ?></td>
                            <td class="num"><?= moeda_br($item['valor_total_original']) ?></td>
                            <?php if ($pode_editar): ?>
                                <td class="centro">
                                    <?= form_open('orcamentos/atualizar_item/' . $registro['numero'] . '/' . $item['id'],
                                        array('id' => $form_id)) ?>
                                        <button type="submit" class="botao secundario pequeno">Salvar</button>
                                    <?= form_close() ?>

                                    <?= form_open('orcamentos/excluir_item/' . $registro['numero'] . '/' . $item['id'],
                                        array('style' => 'margin-top:4px', 'onsubmit' => "return confirm('Excluir o item " . (int) $item['numero_item'] . "?')")) ?>
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
                    <td colspan="5" class="num"><strong>Total do material</strong></td>
                    <td class="num"><strong><?= moeda_br($material) ?></strong></td>
                    <td colspan="<?= $pode_editar ? 4 : 3 ?>"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if ($pode_editar): ?>
        <hr class="divisor">
        <h4 class="secao-titulo">Incluir item</h4>

        <?= form_open('orcamentos/adicionar_item/' . $registro['numero'], array('id' => 'form-item-novo')) ?>

            <div class="form-grid">
                <div class="campo metade">
                    <label for="item_produto_busca">Produto</label>
                    <div class="autocomplete" data-url="<?= site_url('orcamentos/buscar_produtos') ?>"
                         data-preco="#item_valor_unitario" data-descricao="descricao_produto"
                         data-unidade="#item_unidade">
                        <input type="text" id="item_produto_busca" class="autocomplete-busca"
                               placeholder="Digite o codigo ou a descricao..." autocomplete="off">
                        <input type="hidden" name="produto_id" id="item_produto_id">
                        <div class="autocomplete-lista"></div>
                    </div>
                    <div class="ajuda">Deixe em branco para incluir um item avulso (informe a descricao).</div>
                </div>

                <div class="campo metade">
                    <label for="descricao_produto">Descricao do item</label>
                    <input type="text" name="descricao_produto" id="descricao_produto" maxlength="100">
                </div>

                <div class="campo terco">
                    <label for="item_unidade">Unidade</label>
                    <input type="text" id="item_unidade" readonly tabindex="-1" value="">
                </div>

                <div class="campo terco">
                    <label for="item_valor_unitario">Valor unitario</label>
                    <input type="text" name="valor_unitario" id="item_valor_unitario" inputmode="decimal" placeholder="0,00">
                </div>

                <div class="campo terco">
                    <label for="item_quantidade">Quantidade</label>
                    <input type="number" name="quantidade" id="item_quantidade" min="1" step="1" value="1">
                </div>

                <div class="campo terco">
                    <label for="item_valor_total">Total</label>
                    <input type="text" name="valor_total" id="item_valor_total" inputmode="decimal" placeholder="calculado">
                    <div class="ajuda">Em branco: quantidade x valor unitario.</div>
                </div>

                <div class="campo terco">
                    <label for="item_descricao">Complemento</label>
                    <input type="text" name="descricao" id="item_descricao" maxlength="100">
                </div>

                <div class="campo terco" style="align-self:end">
                    <button type="submit" class="botao">Incluir item</button>
                </div>
            </div>

        <?= form_close() ?>

        <hr class="divisor">
        <h4 class="secao-titulo">Percentual em massa</h4>

        <div class="form-grid">
            <div class="campo terco">
                <?= form_open('orcamentos/aplicar_percentual/' . $registro['numero']) ?>
                    <input type="hidden" name="tipo" value="desconto">
                    <label for="desc_itens">Desconto nos itens (%)</label>
                    <input type="text" name="percentual" id="desc_itens" inputmode="decimal" placeholder="0,00">
                    <div class="ajuda">Aplicado sobre o valor original de cada item.</div>
                    <div class="form-acoes" style="justify-content:flex-start;margin-top:8px">
                        <button type="submit" class="botao secundario pequeno">Aplicar desconto</button>
                    </div>
                <?= form_close() ?>
            </div>

            <div class="campo terco">
                <?= form_open('orcamentos/aplicar_percentual/' . $registro['numero']) ?>
                    <input type="hidden" name="tipo" value="acrescimo">
                    <label for="acr_itens">Acrescimo nos itens (%)</label>
                    <input type="text" name="percentual" id="acr_itens" inputmode="decimal" placeholder="0,00">
                    <div class="ajuda">Aplicado sobre o valor original de cada item.</div>
                    <div class="form-acoes" style="justify-content:flex-start;margin-top:8px">
                        <button type="submit" class="botao secundario pequeno">Aplicar acrescimo</button>
                    </div>
                <?= form_close() ?>
            </div>

            <div class="campo terco">
                <?= form_open('orcamentos/restaurar_valores/' . $registro['numero']) ?>
                    <label>Restaurar valores originais</label>
                    <div class="ajuda">
                        Volta cada item ao valor unitario/total original e zera os percentuais em massa.
                    </div>
                    <div class="form-acoes" style="justify-content:flex-start;margin-top:8px">
                        <button type="submit" class="botao secundario pequeno">Restaurar</button>
                    </div>
                <?= form_close() ?>
            </div>
        </div>
    <?php endif; ?>
</div>
