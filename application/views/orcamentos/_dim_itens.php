<?php
$total_compra = 0.0;
$total_venda  = 0.0;
foreach ($itens as $item)
{
    $total_compra += (float) $item['total_compra'];
    $total_venda  += (float) $item['total_venda'];
}
?>
<div class="painel-corpo">
    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th class="num" style="width:40px">#</th>
                    <th>Produto</th>
                    <th class="centro" style="width:55px">Unid.</th>
                    <th class="num" style="width:70px">Qtd</th>
                    <th class="num" style="width:95px">Valor compra</th>
                    <th class="num" style="width:95px">Valor gasto</th>
                    <th class="num" style="width:100px">Total compra</th>
                    <th class="num" style="width:95px">Valor venda</th>
                    <th class="num" style="width:100px">Total venda</th>
                    <th class="centro" style="width:60px">Grupo</th>
                    <th class="centro" style="width:70px">Subgrupo</th>
                    <?php if ($pode_editar): ?>
                        <th class="centro" style="width:145px">Acoes</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($itens)): ?>
                    <tr><td colspan="<?= $pode_editar ? 12 : 11 ?>" class="vazio">Nenhum item no dimensionamento.</td></tr>
                <?php else: ?>
                    <?php foreach ($itens as $item): ?>
                        <?php $form_id = 'dim-item-' . (int) $item['id']; ?>
                        <tr class="linha-item">
                            <td class="num"><?= (int) $item['numero_item'] ?></td>
                            <td>
                                <?php if ( ! empty($item['produto_id'])): ?>
                                    <a href="<?= site_url('produtos/ver/' . $item['produto_id']) ?>"><strong><?= html_escape($item['descricao_produto']) ?></strong></a>
                                    <div class="ajuda">codigo <?= html_escape($item['produto_codigo'] ? $item['produto_codigo'] : (string) $item['produto_id']) ?></div>
                                <?php else: ?>
                                    <strong><?= html_escape($item['descricao_produto'] ? $item['descricao_produto'] : $item['descricao']) ?></strong>
                                <?php endif; ?>
                                <?php if ( ! empty($item['descricao']) && $item['descricao_produto']): ?>
                                    <div class="ajuda"><?= html_escape($item['descricao']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="centro"><?= html_escape($item['produto_unidade'] ? $item['produto_unidade'] : '-') ?></td>
                            <td class="num">
                                <?php if ($pode_editar): ?>
                                    <input type="number" name="quantidade" form="<?= $form_id ?>" min="1" step="1"
                                           value="<?= (int) $item['quantidade'] ?>" style="width:65px;text-align:right">
                                <?php else: ?>
                                    <?= numero_br($item['quantidade']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="num">
                                <?php if ($pode_editar): ?>
                                    <input type="text" name="valor_compra" form="<?= $form_id ?>" inputmode="decimal"
                                           value="<?= number_format((float) $item['valor_compra'], 2, ',', '.') ?>"
                                           style="width:90px;text-align:right">
                                <?php else: ?>
                                    <?= moeda_br($item['valor_compra']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="num"><?= moeda_br($item['valor_gasto']) ?></td>
                            <td class="num"><?= moeda_br($item['total_compra']) ?></td>
                            <td class="num">
                                <?php if ($pode_editar): ?>
                                    <input type="text" name="valor_venda" form="<?= $form_id ?>" inputmode="decimal"
                                           value="<?= number_format((float) $item['valor_venda'], 2, ',', '.') ?>"
                                           style="width:90px;text-align:right">
                                <?php else: ?>
                                    <?= moeda_br($item['valor_venda']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="num"><strong><?= moeda_br($item['total_venda']) ?></strong></td>
                            <td class="centro"><?= html_escape($item['grupo'] ? $item['grupo'] : '-') ?></td>
                            <td class="centro"><?= html_escape($item['subgrupo'] ? $item['subgrupo'] : '-') ?></td>
                            <?php if ($pode_editar): ?>
                                <td class="centro">
                                    <?= form_open('orcamentos/dim_atualizar_item/' . $registro['numero'] . '/' . $item['id'],
                                        array('id' => $form_id)) ?>
                                        <button type="submit" class="botao secundario pequeno">Salvar</button>
                                    <?= form_close() ?>

                                    <?= form_open('orcamentos/dim_excluir_item/' . $registro['numero'] . '/' . $item['id'],
                                        array('style' => 'margin-top:4px', 'onsubmit' => "return confirm('Excluir o item?')")) ?>
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
                    <td colspan="6" class="num"><strong>Totais</strong></td>
                    <td class="num"><strong><?= moeda_br($total_compra) ?></strong></td>
                    <td></td>
                    <td class="num"><strong><?= moeda_br($total_venda) ?></strong></td>
                    <td colspan="<?= $pode_editar ? 3 : 2 ?>"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if ($pode_editar): ?>
        <hr class="divisor">
        <h4 class="secao-titulo">Incluir item</h4>

        <?= form_open('orcamentos/dim_adicionar_item/' . $registro['numero']) ?>
            <div class="form-grid">
                <div class="campo metade">
                    <label for="dim_produto_busca">Produto</label>
                    <div class="autocomplete" data-url="<?= site_url('orcamentos/buscar_produtos' . ( ! empty($registro['cliente_id']) ? '?cliente=' . (int) $registro['cliente_id'] : '')) ?>"
                         data-custo="#dim_valor_compra" data-preco="#dim_valor_venda"
                         data-unidade="#dim_unidade" data-descricao="descricao_produto">
                        <input type="text" id="dim_produto_busca" class="autocomplete-busca"
                               placeholder="Digite o codigo ou a descricao..." autocomplete="off">
                        <input type="hidden" name="produto_id" id="dim_produto_id">
                        <div class="autocomplete-lista"></div>
                    </div>
                </div>

                <div class="campo metade">
                    <label for="descricao_produto">Descricao</label>
                    <input type="text" name="descricao_produto" id="descricao_produto" maxlength="100">
                </div>

                <div class="campo terco">
                    <label for="dim_unidade">Unidade</label>
                    <input type="text" id="dim_unidade" readonly tabindex="-1">
                </div>

                <div class="campo terco">
                    <label for="dim_quantidade">Quantidade</label>
                    <input type="number" name="quantidade" id="dim_quantidade" min="1" step="1" value="1">
                </div>

                <div class="campo terco">
                    <label for="dim_valor_compra">Valor de compra</label>
                    <input type="text" name="valor_compra" id="dim_valor_compra" inputmode="decimal" placeholder="0,00">
                </div>

                <div class="campo terco">
                    <label for="dim_valor_venda">Valor de venda</label>
                    <input type="text" name="valor_venda" id="dim_valor_venda" inputmode="decimal" placeholder="0,00">
                </div>

                <div class="campo terco">
                    <label for="dim_valor_gasto">Valor gasto</label>
                    <input type="text" name="valor_gasto" id="dim_valor_gasto" inputmode="decimal" placeholder="0,00">
                </div>

                <div class="campo terco">
                    <label for="dim_grupo">Grupo</label>
                    <input type="text" name="grupo" id="dim_grupo" maxlength="3">
                </div>
                <div class="campo terco">
                    <label for="dim_subgrupo">Subgrupo</label>
                    <input type="text" name="subgrupo" id="dim_subgrupo" maxlength="3">
                </div>
                <div class="campo terco">
                    <label for="dim_pg">PG</label>
                    <input type="text" name="pg" id="dim_pg" maxlength="3">
                </div>
                <div class="campo terco">
                    <label for="dim_psg">PSG</label>
                    <input type="text" name="psg" id="dim_psg" maxlength="3">
                </div>

                <div class="campo terco" style="align-self:end">
                    <button type="submit" class="botao">Incluir item</button>
                </div>
            </div>
        <?= form_close() ?>
    <?php endif; ?>
</div>
