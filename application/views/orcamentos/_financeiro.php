<?php
$material = 0.0;
foreach ($itens as $item) { $material += (float) $item['valor_total']; }

$servico = 0.0;
foreach ($servicos_valores as $valor) { $servico += (float) $valor['valor_total']; }

$material = round($material, 2);
$servico  = round($servico, 2);

// Linhas da composicao: cada valor e calculado sobre o acumulado anterior.
$linhas = array(
    array('rotulo' => 'Mao de obra', 'perc' => 'percentual_mao_obra', 'valor' => 'valor_mao_obra'),
    array('rotulo' => 'Material de fixacao', 'perc' => 'percentual_mat_fixacao', 'valor' => 'material_fixacao'),
    array('rotulo' => 'Acrescimo de custo', 'perc' => 'percentual_acrescimo_custo', 'valor' => 'acrescimo_custo'),
    array('rotulo' => 'Imposto da nota fiscal', 'perc' => 'percentual_nota_fiscal', 'valor' => 'valor_imposto'),
);

$acumulado = $material;
$encargos  = 0.0;
$acumulados = array('material' => $material);

foreach ($linhas as $linha)
{
    $valor = (float) $registro[$linha['valor']];
    $encargos += $valor;
    $acumulado = round($acumulado + $valor, 2);
    $acumulados[$linha['valor']] = $acumulado;
}

$sem_desconto = $acumulado;
$desconto     = (float) $registro['valor_desconto'];
$total_geral  = max(0, round($sem_desconto + $servico - $desconto, 2));

$aceito    = (float) $registro['valor_maximo_cliente'];
$indiretos = (float) $registro['valor_custos_indiretos'];
$diluir    = round($aceito - $material - $indiretos, 2);
$perc_diluir = $material > 0 ? round($diluir / $material * 100, 2) : 0.0;

$modo = function ($percentual) {
    return $percentual === NULL || $percentual === '' ? 'valor' : 'percentual';
};
?>
<div class="painel-corpo">
    <?= form_open('orcamentos/salvar_financeiro/' . $registro['numero'], array(
        'id'            => 'form-financeiro',
        'data-material' => number_format($material, 2, '.', ''),
        'data-servico'  => number_format($servico, 2, '.', ''),
    )) ?>
        <div class="fin-grade">
            <div class="fin-bloco fin-composicao">
                <h4 class="secao-titulo destaque">Composicao do total</h4>

                <table class="tabela fin-tabela">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="num" style="width:110px">Percentual</th>
                            <th class="num" style="width:150px">Valor</th>
                            <th class="num" style="width:150px">Acumulado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Valor das pecas (itens)</td>
                            <td class="num">-</td>
                            <td class="num"><?= moeda_br($material) ?></td>
                            <td class="num fin-destaque" id="fin-acum-material"><?= moeda_br($material) ?></td>
                        </tr>

                        <?php foreach ($linhas as $linha): ?>
                            <tr>
                                <td><?= $linha['rotulo'] ?></td>
                                <td class="num">
                                    <input type="text" class="fin-campo fin-perc"
                                           name="<?= $linha['perc'] ?>" id="fin-perc-<?= $linha['valor'] ?>"
                                           data-linha="<?= $linha['valor'] ?>" inputmode="decimal"
                                           value="<?= $registro[$linha['perc']] !== NULL ? numero_br($registro[$linha['perc']], 2) : '' ?>"
                                           <?= $pode_editar ? '' : 'disabled' ?>>
                                </td>
                                <td class="num">
                                    <input type="text" class="fin-campo fin-valor"
                                           name="<?= $linha['valor'] ?>" id="fin-valor-<?= $linha['valor'] ?>"
                                           data-linha="<?= $linha['valor'] ?>" inputmode="decimal"
                                           value="<?= moeda_br($registro[$linha['valor']]) ?>"
                                           <?= $pode_editar ? '' : 'disabled' ?>>
                                    <input type="hidden" name="modo_<?= $linha['valor'] ?>" id="fin-modo-<?= $linha['valor'] ?>"
                                           value="<?= $modo($registro[$linha['perc']]) ?>">
                                </td>
                                <td class="num fin-destaque" id="fin-acum-<?= $linha['valor'] ?>">
                                    <?= moeda_br($acumulados[$linha['valor']]) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="fin-bloco fin-totais">
                <h4 class="secao-titulo destaque">Totais</h4>

                <div class="fin-total">
                    <span>M.O + ADM + CUSTO + N.F.</span>
                    <strong id="fin-total-encargos"><?= moeda_br($encargos) ?></strong>
                </div>
                <div class="fin-total">
                    <span>Total geral sem desconto</span>
                    <strong id="fin-total-sem-desconto"><?= moeda_br($sem_desconto) ?></strong>
                </div>
                <div class="fin-total">
                    <span>Servicos com valor</span>
                    <strong id="fin-total-servicos"><?= moeda_br($servico) ?></strong>
                </div>

                <div class="fin-total fin-desconto">
                    <span>Desconto (sobre o total geral)</span>
                    <div class="fin-desconto-campos">
                        <input type="text" class="fin-campo fin-perc"
                               name="percentual_desconto" id="fin-perc-desconto"
                               data-linha="desconto" inputmode="decimal"
                               value="<?= $registro['percentual_desconto'] !== NULL ? numero_br($registro['percentual_desconto'], 2) : '' ?>"
                               <?= $pode_editar ? '' : 'disabled' ?>>
                        <span class="fin-unidade">%</span>
                        <input type="text" class="fin-campo fin-valor"
                               name="valor_desconto" id="fin-valor-desconto"
                               data-linha="desconto" inputmode="decimal"
                               value="<?= moeda_br($desconto) ?>"
                               <?= $pode_editar ? '' : 'disabled' ?>>
                        <input type="hidden" name="modo_valor_desconto" id="fin-modo-desconto"
                               value="<?= $modo($registro['percentual_desconto']) ?>">
                    </div>
                </div>

                <div class="fin-total fin-total-final">
                    <span>Total geral do orcamento</span>
                    <strong id="fin-total-geral"><?= moeda_br($total_geral) ?></strong>
                </div>
            </div>
        </div>

        <div class="fin-bloco fin-diluicao">
            <h4 class="secao-titulo destaque">Diluicao no material</h4>

            <div class="form-grid">
                <div class="campo quarto">
                    <label for="fin-aceito">Valor aceito pelo cliente</label>
                    <input type="text" name="valor_maximo_cliente" id="fin-aceito" inputmode="decimal"
                           value="<?= moeda_br($aceito) ?>" <?= $pode_editar ? '' : 'disabled' ?>>
                </div>

                <div class="campo quarto">
                    <label for="fin-indiretos">Valor dos custos indiretos</label>
                    <input type="text" name="valor_custos_indiretos" id="fin-indiretos" inputmode="decimal"
                           value="<?= moeda_br($indiretos) ?>" <?= $pode_editar ? '' : 'disabled' ?>>
                </div>

                <div class="campo quarto">
                    <label>Valor a diluir (no material)</label>
                    <div class="fin-caixa" id="fin-diluir"><?= moeda_br($diluir) ?></div>
                </div>

                <div class="campo quarto">
                    <label>% sugerido para diluir</label>
                    <div class="fin-caixa" id="fin-perc-diluir"><?= numero_br($perc_diluir, 2) ?> %</div>
                </div>
            </div>

            <div class="ajuda">
                Valor a diluir = valor aceito pelo cliente - total das pecas (itens) - custos indiretos.
                O percentual sugerido e o valor a diluir em relacao ao total das pecas.
            </div>
        </div>

        <?php if ($pode_editar): ?>
            <div class="form-acoes" style="justify-content:flex-start;margin-top:16px">
                <button type="submit" class="botao">Salvar</button>
            </div>
        <?php endif; ?>
    <?= form_close() ?>
</div>
