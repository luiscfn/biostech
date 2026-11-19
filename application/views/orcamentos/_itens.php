<?php
$material = 0.0;
foreach ($itens as $item) { $material += (float) $item['valor_total']; }
?>
<div class="painel-corpo">
    <?php if ($pode_editar): ?>
        <div class="bloco-inclusao">
            <h4 class="secao-titulo destaque">Incluir item</h4>

            <?= form_open('orcamentos/adicionar_item/' . $registro['numero'], array('id' => 'form-item-novo')) ?>

                <div class="form-grid">
                    <div class="campo terco">
                        <label for="item_produto_busca">Produto</label>
                        <div class="autocomplete" data-url="<?= site_url('orcamentos/buscar_produtos' . ( ! empty($registro['cliente_id']) ? '?cliente=' . (int) $registro['cliente_id'] : '')) ?>"
                             data-preco="#item_valor_unitario" data-descricao="descricao_produto"
                             data-unidade="#item_unidade">
                            <input type="text" id="item_produto_busca" class="autocomplete-busca"
                                   placeholder="Digite o codigo ou a descricao..." autocomplete="off">
                            <input type="hidden" name="produto_id" id="item_produto_id">
                            <div class="autocomplete-lista"></div>
                        </div>
                        <div class="ajuda">Deixe em branco para incluir um item avulso (informe a descricao).</div>
                    </div>

                    <div class="campo" style="grid-column:span 8">
                        <label for="descricao_produto">Descricao do item</label>
                        <input type="text" name="descricao_produto" id="descricao_produto" maxlength="100">
                    </div>

                    <div class="campo" style="grid-column:span 2">
                        <label for="item_unidade">Unidade</label>
                        <input type="text" id="item_unidade" readonly tabindex="-1" value="">
                    </div>

                    <div class="campo" style="grid-column:span 2">
                        <label for="item_quantidade">Quantidade</label>
                        <input type="number" name="quantidade" id="item_quantidade" min="1" step="1" value="1">
                    </div>

                    <div class="campo" style="grid-column:span 3">
                        <label for="item_valor_unitario">Valor unitario</label>
                        <input type="text" name="valor_unitario" id="item_valor_unitario" inputmode="decimal" placeholder="0,00">
                    </div>

                    <div class="campo" style="grid-column:span 3">
                        <label for="item_valor_total">Total</label>
                        <input type="text" name="valor_total" id="item_valor_total" inputmode="decimal" placeholder="calculado">
                        <div class="ajuda">Em branco: quantidade x valor unitario.</div>
                    </div>

                    <div class="campo" style="grid-column:span 2">
                        <label>&nbsp;</label>
                        <button type="submit" class="botao" style="width:100%">Incluir item</button>
                    </div>
                </div>

            <?= form_close() ?>
        </div>
    <?php endif; ?>

    <div class="tabela-wrap tabela-rolagem">
        <table class="tabela">
            <thead>
                <tr>
                    <th class="num" style="width:55px">Item</th>
                    <th style="width:90px">Codigo</th>
                    <th>Descricao do produto</th>
                    <th class="num" style="width:90px">Qtde</th>
                    <th class="num" style="width:110px">Valor unit.</th>
                    <th class="num" style="width:115px">Valor total</th>
                    <?php if ($pode_editar): ?>
                        <th class="centro" style="width:175px">Acoes</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($itens)): ?>
                    <tr><td colspan="<?= $pode_editar ? 7 : 6 ?>" class="vazio">Nenhum item no orcamento.</td></tr>
                <?php else: ?>
                    <?php foreach ($itens as $item): ?>
                        <?php $form_id = 'item-' . (int) $item['id']; ?>
                        <tr class="linha-item">
                            <td class="num"><?= (int) $item['numero_item'] ?></td>
                            <td>
                                <?php if ( ! empty($item['produto_id'])): ?>
                                    <a href="<?= site_url('produtos/ver/' . $item['produto_id']) ?>"><?= html_escape($item['produto_codigo'] ? $item['produto_codigo'] : (string) $item['produto_id']) ?></a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($pode_editar): ?>
                                    <input type="text" name="descricao" form="<?= $form_id ?>" maxlength="100"
                                           value="<?= html_escape($item['descricao'] ? $item['descricao'] : $item['descricao_produto']) ?>" style="width:100%">
                                <?php else: ?>
                                    <?= html_escape($item['descricao_produto'] ? $item['descricao_produto'] : ($item['descricao'] ? $item['descricao'] : '-')) ?>
                                <?php endif; ?>
                            </td>
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
                            <?php if ($pode_editar): ?>
                                <td class="centro">
                                    <div class="acoes-tabela">
                                        <?= form_open('orcamentos/atualizar_item/' . $registro['numero'] . '/' . $item['id'],
                                            array('id' => $form_id)) ?>
                                            <button type="submit" class="botao secundario pequeno">Salvar</button>
                                        <?= form_close() ?>

                                        <?= form_open('orcamentos/excluir_item/' . $registro['numero'] . '/' . $item['id'],
                                            array('onsubmit' => "return confirm('Excluir o item " . (int) $item['numero_item'] . "?')")) ?>
                                            <button type="submit" class="botao perigo pequeno">Excluir</button>
                                        <?= form_close() ?>
                                    </div>
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
                    <?php if ($pode_editar): ?><td></td><?php endif; ?>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if ($pode_editar): ?>
        <hr class="divisor">
        <h4 class="secao-titulo">Percentual em massa</h4>

        <div class="percentual-massa">
            <div class="campo">
                <?= form_open('orcamentos/aplicar_percentual/' . $registro['numero']) ?>
                    <input type="hidden" name="tipo" value="desconto">
                    <label for="desc_itens">Desconto nos itens (%)</label>
                    <div class="campo-linha">
                        <input type="text" name="percentual" id="desc_itens" inputmode="decimal" placeholder="0,00">
                        <button type="submit" class="botao secundario pequeno">Aplicar desconto</button>
                        <button type="submit" class="botao secundario pequeno"
                                formaction="<?= site_url('orcamentos/restaurar_percentual/' . $registro['numero'] . '/desconto') ?>"
                                title="Restaura o valor original antes do desconto">Restaurar</button>
                    </div>
                    <div class="ajuda">Aplicado sobre o valor original de cada item.</div>
                <?= form_close() ?>
            </div>

            <div class="campo">
                <?= form_open('orcamentos/aplicar_percentual/' . $registro['numero']) ?>
                    <input type="hidden" name="tipo" value="acrescimo">
                    <label for="acr_itens">Acrescimo nos itens (%)</label>
                    <div class="campo-linha">
                        <input type="text" name="percentual" id="acr_itens" inputmode="decimal" placeholder="0,00">
                        <button type="submit" class="botao secundario pequeno">Aplicar acrescimo</button>
                        <button type="submit" class="botao secundario pequeno"
                                formaction="<?= site_url('orcamentos/restaurar_percentual/' . $registro['numero'] . '/acrescimo') ?>"
                                title="Restaura o valor original antes do acrescimo">Restaurar</button>
                    </div>
                    <div class="ajuda">Aplicado sobre o valor original de cada item.</div>
                <?= form_close() ?>
            </div>

            <div class="campo originais">
                <?= form_open('orcamentos/restaurar_valores/' . $registro['numero']) ?>
                    <div class="campo-linha" style="flex-wrap:nowrap">
                        <label>Restaurar valores originais</label>
                        <button type="submit" class="botao secundario pequeno">Restaurar</button>
                    </div>
                    <div class="ajuda">
                        Volta cada item ao valor unitario/total original e zera os percentuais em massa.
                    </div>
                <?= form_close() ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    // Mostra 6 itens por vez, com o rodape "Total do material" sempre visivel.
    var VISIVEIS = 6;
    var wrap = document.querySelector('.tabela-rolagem');
    if ( ! wrap) { return; }

    var linhas = wrap.querySelectorAll('tbody tr');
    if (linhas.length <= VISIVEIS) { return; }

    function ajustar() {
        wrap.style.maxHeight = '';

        var topo   = wrap.getBoundingClientRect().top;
        var fim    = linhas[VISIVEIS - 1].getBoundingClientRect().bottom;
        var rodape = wrap.querySelector('tfoot').getBoundingClientRect().height;

        var alvo = Math.ceil(fim - topo + rodape + 1);
        wrap.style.maxHeight = alvo + 'px';

        // A barra de rolagem horizontal reduz a altura util: compensa para a
        // ultima linha nao ficar sob o rodape.
        var celula = wrap.querySelector('tfoot td');
        var sobra  = linhas[VISIVEIS - 1].getBoundingClientRect().bottom - celula.getBoundingClientRect().top;

        if (sobra > 0) {
            wrap.style.maxHeight = (alvo + Math.ceil(sobra) + 1) + 'px';
        }
    }

    ajustar();
    window.addEventListener('load', ajustar);
})();
</script>
