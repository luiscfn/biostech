<?php
$total_venda = 0.0;
foreach ($itens as $item)
{
    $total_venda += (float) $item['total_venda'];
}
?>
<div class="painel-corpo">
    <div class="tabela-wrap tabela-rolagem tabela-wrap-itens" id="wrap-itens-dimensionamento">
        <table class="tabela tabela-dimen-itens">
            <thead>
                <tr>
                    <th class="num" style="width:40px">#</th>
                    <th class="centro" style="width:140px">Grupo</th>
                    <th class="centro" style="width:140px">Subgrupo</th>
                    <th class="produto" style="width:40%">Produto</th>
                    <th class="centro" style="width:55px">Unid.</th>
                    <th class="centro" style="width:70px">Qtd</th>
                    <th class="num" style="width:95px">Valor</th>
                    <th class="num" style="width:100px">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($itens)): ?>
                    <tr><td colspan="8" class="vazio">Nenhum item no dimensionamento.</td></tr>
                <?php else: ?>
                    <?php foreach ($itens as $item): ?>
                        <tr class="linha-item">
                            <td class="num"><?= (int) $item['numero_item'] ?></td>
                            <td class="centro"><?= html_escape($item['grupo_descricao'] ? $item['grupo_descricao'] : ($item['grupo_exibicao'] ? $item['grupo_exibicao'] : '-')) ?></td>
                            <td class="centro"><?= html_escape($item['subgrupo_descricao'] ? $item['subgrupo_descricao'] : ($item['subgrupo_exibicao'] ? $item['subgrupo_exibicao'] : '-')) ?></td>
                            <td class="produto">
                                <?php if ( ! empty($item['produto_id'])): ?>
                                    <a href="<?= site_url('produtos/ver/' . $item['produto_id']) ?>"><strong><?= html_escape($item['descricao_produto']) ?></strong></a>
                                <?php else: ?>
                                    <strong><?= html_escape($item['descricao_produto'] ? $item['descricao_produto'] : $item['descricao']) ?></strong>
                                <?php endif; ?>
                            </td>
                            <td class="centro"><?= html_escape($item['produto_unidade'] ? $item['produto_unidade'] : '-') ?></td>
                            <td class="centro">
                                <?php if ($pode_editar): ?>
                                    <input type="number" name="quantidade" min="1" step="1" class="sem-borda"
                                           value="<?= (int) $item['quantidade'] ?>" style="width:65px;text-align:center">
                                <?php else: ?>
                                    <?= numero_br($item['quantidade']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="num">
                                <?php if ($pode_editar): ?>
                                    <input type="text" name="valor_venda" inputmode="decimal" class="sem-borda"
                                           value="<?= number_format((float) $item['valor_venda'], 2, ',', '.') ?>"
                                           style="width:90px;text-align:right">
                                <?php else: ?>
                                    <?= moeda_br($item['valor_venda']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="num"><strong><?= moeda_br($item['total_venda']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="num"><strong>Total Geral</strong></td>
                    <td class="num"></td>
                    <td class="num total-geral-valor"><strong><?= moeda_br($total_venda) ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>
(function () {
    var wrap = document.getElementById('wrap-itens-dimensionamento');
    if (!wrap) { return; }

    var cabecalho = wrap.querySelector('thead');
    var rodape = wrap.querySelector('tfoot');
    var linhas = wrap.querySelectorAll('tbody tr.linha-item');

    if (linhas.length <= 8) {
        wrap.style.maxHeight = 'none';
        return;
    }

    var altura = cabecalho ? cabecalho.offsetHeight : 0;
    altura += rodape ? rodape.offsetHeight : 0;
    for (var i = 0; i < 8; i++) {
        altura += linhas[i].offsetHeight;
    }
    wrap.style.maxHeight = altura + 'px';
})();
</script>
