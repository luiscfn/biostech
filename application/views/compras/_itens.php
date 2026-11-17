<?php
$aberto = $registro['situacao'] === 'aberto';
$bruto  = 0.0;
?>
<div class="painel-corpo">
    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:70px">Codigo</th>
                    <th>Produto</th>
                    <th class="centro" style="width:60px">Unid.</th>
                    <th class="num" style="width:80px">Qtd</th>
                    <th class="num" style="width:110px">Valor unit.</th>
                    <th class="num" style="width:110px">Novo valor</th>
                    <th class="num" style="width:80px">Desc. %</th>
                    <th class="num" style="width:105px">Desc. R$</th>
                    <th class="num" style="width:115px">Total</th>
                    <th class="centro" style="width:75px">Atualiza</th>
                    <th>Obs.</th>
                    <?php if ($aberto && $pode_editar): ?>
                        <th class="centro" style="width:170px">Acoes</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($itens)): ?>
                    <tr><td colspan="12" class="vazio">Nenhum item no pedido.</td></tr>
                <?php else: ?>
                    <?php foreach ($itens as $item): ?>
                        <?php $bruto += (float) $item['valor_total']; ?>
                        <tr>
                            <td><?= html_escape($item['produto_codigo'] ? $item['produto_codigo'] : (string) $item['produto_id']) ?></td>
                            <td>
                                <a href="<?= site_url('produtos/ver/' . $item['produto_id']) ?>"><strong><?= html_escape($item['descricao']) ?></strong></a>
                                <div class="ajuda">Saldo atual: <?= numero_br($item['saldo']) ?></div>
                            </td>
                            <td class="centro"><?= html_escape($item['unidade']) ?></td>
                            <td class="num"><?= numero_br($item['quantidade'], 3) ?></td>
                            <td class="num"><?= moeda_br($item['valor_unitario']) ?></td>
                            <td class="num"><?= $item['novo_valor_unitario'] !== NULL ? moeda_br($item['novo_valor_unitario']) : '-' ?></td>
                            <td class="num"><?= $item['desconto_percentual'] !== NULL ? numero_br($item['desconto_percentual'], 2) : '-' ?></td>
                            <td class="num"><?= moeda_br($item['desconto_item']) ?></td>
                            <td class="num"><strong><?= moeda_br($item['valor_total']) ?></strong></td>
                            <td class="centro">
                                <?= bd_bool($item['atualiza_preco_compra']) ? '<span class="badge info">Sim</span>' : '<span class="badge off">Nao</span>' ?>
                            </td>
                            <td><?= html_escape($item['observacao'] ? $item['observacao'] : '-') ?></td>
                            <?php if ($aberto && $pode_editar): ?>
                                <td class="centro">
                                    <?= form_open('compras/transferir_item/' . $registro['numero'], array('style' => 'display:flex;gap:4px;justify-content:center')) ?>
                                        <input type="hidden" name="produto_id" value="<?= (int) $item['produto_id'] ?>">
                                        <input type="number" name="destino" min="1" step="1" placeholder="Pedido"
                                               style="width:90px" title="Numero do pedido de destino" required>
                                        <button type="submit" class="botao secundario pequeno" title="Transferir item">Transferir</button>
                                    <?= form_close() ?>

                                    <?= form_open('compras/excluir_item/' . $registro['numero'] . '/' . $item['produto_id'], array('style' => 'margin-top:4px')) ?>
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
                    <td colspan="8" class="num"><strong>Total bruto</strong></td>
                    <td class="num"><strong><?= moeda_br($bruto) ?></strong></td>
                    <td colspan="<?= $aberto && $pode_editar ? 3 : 2 ?>"></td>
                </tr>
                <tr>
                    <td colspan="8" class="num">Desconto do pedido</td>
                    <td class="num"><?= moeda_br($registro['desconto']) ?></td>
                    <td colspan="<?= $aberto && $pode_editar ? 3 : 2 ?>"></td>
                </tr>
                <tr>
                    <td colspan="8" class="num"><strong>Total do pedido</strong></td>
                    <td class="num"><strong><?= moeda_br($registro['total']) ?></strong></td>
                    <td colspan="<?= $aberto && $pode_editar ? 3 : 2 ?>"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if ($aberto && $pode_editar): ?>
        <div class="form-acoes">
            <a class="botao" href="<?= site_url('compras/editar/' . $registro['numero']) ?>">Editar itens</a>
        </div>
    <?php endif; ?>
</div>
