<?php
$total_compra          = 0.0;
$total_compra_desconto = 0.0;

foreach ($itens as $item)
{
    $total_compra += (float) $item['total_compra'];
    $total_compra_desconto += round((int) $item['quantidade'] * (float) $item['ultima_compra'], 2);
}

$lucro_preco_atual   = (float) $total_venda - $total_compra;
$lucro_preco_uc      = (float) $total_venda - $total_compra_desconto;
$percentual_atual    = $total_venda > 0 ? ($lucro_preco_atual / $total_venda) * 100 : 0;
$percentual_desconto = $total_venda > 0 ? ($lucro_preco_uc / $total_venda) * 100 : 0;

// Composicao do total: mesma regra da aba financeira do orçamento (percentual sobre o acumulado anterior)
$composicao_base      = round((float) $total_venda, 2);
$composicao_linhas    = array(
    array('rotulo' => 'Mao de obra',            'perc' => 'percentual_mao_obra',        'valor' => 'mao_obra'),
    array('rotulo' => 'Material de fixacao',    'perc' => 'percentual_mat_fixacao',     'valor' => 'material_fixacao'),
    array('rotulo' => 'Acrescimo de custo',     'perc' => 'percentual_acrescimo_custo', 'valor' => 'acrescimo_custo'),
    array('rotulo' => 'Imposto da nota fiscal', 'perc' => 'percentual_nota_fiscal',     'valor' => 'nota_fiscal'),
);
$composicao_acumulado = $composicao_base;
$composicao_encargos  = 0.0;
$composicao           = array();

foreach ($composicao_linhas as $linha)
{
    $percentual = (isset($registro[$linha['perc']]) && $registro[$linha['perc']] !== NULL)
        ? (float) $registro[$linha['perc']] : NULL;
    $valor = $percentual !== NULL
        ? round($percentual * $composicao_acumulado / 100, 2)
        : (float) $registro[$linha['valor']];

    $composicao_acumulado = round($composicao_acumulado + $valor, 2);
    $composicao_encargos  = round($composicao_encargos + $valor, 2);
    $composicao[] = array(
        'rotulo'     => $linha['rotulo'],
        'percentual' => $percentual,
        'valor'      => $valor,
        'acumulado'  => $composicao_acumulado,
    );
}
$composicao_sem_desconto  = $composicao_acumulado;
$lucro_liquido            = $composicao[0]['valor'] + $lucro_preco_atual;
$percentual_lucro_liquido = $composicao_sem_desconto > 0
    ? ($lucro_liquido / $composicao_sem_desconto) * 100 : 0;
?>
<div class="painel-corpo">
    <div class="tabela-wrap tabela-rolagem tabela-wrap-levantamento" id="wrap-levantamento-itens">
        <table class="tabela tabela-dimen-itens">
            <thead>
                <tr>
                    <th class="num" style="width:40px">Item</th>
                    <th class="centro" style="width:55px">Unidade</th>
                    <th class="centro" style="width:65px">Qtde</th>
                    <th class="produto" style="width:30%">Produto</th>
                    <th class="num" style="width:95px">V.P.U.C</th>
                    <th class="num" style="width:95px">Compra</th>
                    <th class="num" style="width:110px">Total compra</th>
                    <th class="num" style="width:95px">Venda</th>
                    <th class="num" style="width:110px">Total venda</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($itens)): ?>
                    <tr><td colspan="9" class="vazio">Nenhum item no dimensionamento.</td></tr>
                <?php else: ?>
                    <?php foreach ($itens as $item): ?>
                        <tr class="linha-item">
                            <td class="num"><?= (int) $item['numero_item'] ?></td>
                            <td class="centro"><?= html_escape($item['produto_unidade'] ? $item['produto_unidade'] : '-') ?></td>
                            <td class="centro"><?= numero_br($item['quantidade']) ?></td>
                            <td class="produto">
                                <strong><?= html_escape($item['descricao_produto'] ? $item['descricao_produto'] : $item['descricao']) ?></strong>
                            </td>
                            <td class="num"><?= moeda_br($item['ultima_compra']) ?></td>
                            <td class="num"><?= moeda_br($item['valor_compra']) ?></td>
                            <td class="num"><?= moeda_br($item['total_compra']) ?></td>
                            <td class="num"><?= moeda_br($item['valor_venda']) ?></td>
                            <td class="num"><strong><?= moeda_br($item['total_venda']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6"><strong>Total Geral</strong></td>
                    <td class="num"><strong><?= moeda_br($total_compra) ?></strong></td>
                    <td class="num"></td>
                    <td class="num"><strong><?= moeda_br($total_venda) ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="composicao-grade">
    <div class="composicao-bloco">
    <div class="secao-titulo-linha">
        <h4 class="secao-titulo destaque titulo-grande">Composição do total</h4>
        <?php if ($pode_editar): ?>
            <button type="submit" form="form-dim-composicao" class="botao pequeno">Salvar composição</button>
        <?php endif; ?>
    </div>

    <?= form_open('orcamentos/salvar_composicao_dimensionamento/' . $registro['numero'], array('id' => 'form-dim-composicao')) ?>
    <div id="composicao-dim-levantamento"
         data-material="<?= number_format($composicao_base, 2, '.', '') ?>"
         data-total-compra="<?= number_format($total_compra, 2, '.', '') ?>">
        <table class="tabela fin-tabela composicao-total">
            <thead>
                <tr>
                    <th style="width:170px">Item</th>
                    <th class="num" style="width:85px">Percentual</th>
                    <th class="num" style="width:100px">Valor</th>
                    <th class="num" style="width:100px">Acumulado</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Valor das peças (itens)</td>
                    <td class="num">-</td>
                    <td class="num"><?= moeda_br($composicao_base) ?></td>
                    <td class="num fin-destaque" id="dim-acum-material"><?= moeda_br($composicao_base) ?></td>
                </tr>
                <?php foreach ($composicao as $i => $linha): ?>
                    <?php
                    $campo_perc  = $composicao_linhas[$i]['perc'];
                    $campo_valor = $composicao_linhas[$i]['valor'];
                    $percentual  = $registro[$campo_perc] !== NULL ? (float) $registro[$campo_perc] : NULL;
                    ?>
                    <tr>
                        <td><?= html_escape($linha['rotulo']) ?></td>
                        <td class="num">
                            <input type="text" class="fin-campo dim-perc"
                                   name="<?= $campo_perc ?>" id="dim-perc-<?= $campo_valor ?>"
                                   inputmode="decimal"
                                   value="<?= $percentual !== NULL ? numero_br($percentual, 2) : '' ?>"
                                   <?= $pode_editar ? '' : 'disabled' ?>>
                        </td>
                        <td class="num">
                            <input type="text" class="fin-campo dim-valor"
                                   name="<?= $campo_valor ?>" id="dim-valor-<?= $campo_valor ?>"
                                   inputmode="decimal"
                                   value="<?= moeda_br($registro[$campo_valor]) ?>"
                                   <?= $pode_editar ? '' : 'disabled' ?>>
                            <input type="hidden" name="modo_<?= $campo_valor ?>"
                                   id="dim-modo-<?= $campo_valor ?>"
                                   value="<?= $percentual !== NULL ? 'percentual' : 'valor' ?>">
                        </td>
                        <td class="num fin-destaque" id="dim-acum-<?= $campo_valor ?>">
                            <?= moeda_br($linha['acumulado']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2"><strong>Total M.O + ADM + CUSTO + N.F.</strong></td>
                    <td class="num"><strong id="dim-total-encargos"><?= moeda_br($composicao_encargos) ?></strong></td>
                    <td class="num"><strong id="dim-total-sem-desconto"><?= moeda_br($composicao_sem_desconto) ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?= form_close() ?>
    </div>

    <div class="resumo-grade">
    <div class="resumo-bloco">
        <h4 class="secao-titulo destaque titulo-grande">Resumo Geral</h4>

        <table class="tabela resumo-geral-tabela">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th class="num">Valor</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Mão de Obra</td><td class="num" id="resumo-mo"><?= moeda_br($composicao[0]['valor']) ?></td></tr>
                <tr><td>Mat. Fixação/Taxa Administração</td><td class="num" id="resumo-mf"><?= moeda_br($composicao[1]['valor']) ?></td></tr>
                <tr><td>Acréscimo de Custos</td><td class="num" id="resumo-ac"><?= moeda_br($composicao[2]['valor']) ?></td></tr>
                <tr><td>Nota Fiscal</td><td class="num" id="resumo-nf"><?= moeda_br($composicao[3]['valor']) ?></td></tr>
                <tr><td><strong>Total MO + MF + AC + NF</strong></td><td class="num" id="resumo-encargos"><strong><?= moeda_br($composicao_encargos) ?></strong></td></tr>
                <tr><td>Valor Venda das Peças</td><td class="num" id="resumo-venda-pecas"><?= moeda_br($total_venda) ?></td></tr>
                <tr><td><strong>Total Geral Orçamento</strong></td><td class="num" id="resumo-geral"><strong><?= moeda_br($composicao_sem_desconto) ?></strong></td></tr>
            </tbody>
        </table>
    </div>

    <div class="resumo-total-bloco">
        <h4 class="secao-titulo destaque titulo-grande">Resumo do Total Geral</h4>

        <table class="tabela resumo-total-tabela">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th class="num">Valor</th>
                    <th class="num">Perc.</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Lucro Preço Atual:</td>
                    <td class="num"><strong><?= moeda_br($lucro_preco_atual) ?></strong></td>
                    <td class="num percentual"><?= numero_br($percentual_atual, 2) ?>%</td>
                </tr>
                <tr>
                    <td>Lucro Líquido (MO + Lucro Preço Atual)</td>
                    <td class="num" id="resumo-lucro-liquido"><strong><?= moeda_br($lucro_liquido) ?></strong></td>
                    <td class="num percentual" id="resumo-lucro-liquido-perc"><?= numero_br($percentual_lucro_liquido, 2) ?>%</td>
                </tr>
                <tr>
                    <td>Lucro Preço U.C.:</td>
                    <td class="num"><strong><?= moeda_br($lucro_preco_uc) ?></strong></td>
                    <td class="num percentual"><?= numero_br($percentual_desconto, 2) ?>%</td>
                </tr>
                <tr>
                    <td>Total Compra com Desconto:</td>
                    <td class="num"><strong><?= moeda_br($total_compra_desconto) ?></strong></td>
                    <td class="num"></td>
                </tr>
            </tbody>
        </table>
    </div>
    </div>
    </div>

</div>

<script>
(function () {
    var wrap = document.getElementById('wrap-levantamento-itens');
    if (!wrap) { return; }

    var cabecalho = wrap.querySelector('thead');
    var rodape = wrap.querySelector('tfoot');
    var linhas = wrap.querySelectorAll('tbody tr.linha-item');

    if (linhas.length <= 4) {
        wrap.style.maxHeight = 'none';
        return;
    }

    var altura = cabecalho ? cabecalho.offsetHeight : 0;
    altura += rodape ? rodape.offsetHeight : 0;
    for (var i = 0; i < 4; i++) {
        altura += linhas[i].offsetHeight;
    }
    wrap.style.maxHeight = altura + 'px';
})();
</script>
