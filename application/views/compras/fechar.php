<?php
$total    = (float) $registro['total'];
$desconto = (float) (isset($registro['desconto']) ? $registro['desconto'] : 0);
$n_itens  = count($itens);
$bruto    = 0.0;

foreach ($itens as $i) {
    $bruto += (float) $i['valor_total'];
}

$bruto = round($bruto, 2);
$modal = ! empty($modal);
$pf    = $modal ? 'fechar-' : '';
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>Fechar pedido de compra <?= (int) $registro['numero'] ?></h3>

        <?php if ($modal): ?>
            <div class="acoes-ficha">
                <button type="button" class="botao secundario pequeno" data-fechar-modal>Fechar</button>
            </div>
        <?php else: ?>
            <div class="acoes-ficha">
                <a class="botao secundario pequeno" href="<?= site_url('compras/ver/' . $registro['numero']) ?>">Ver ficha</a>
                <a class="botao secundario pequeno" href="<?= site_url('compras') ?>">Voltar</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="painel-corpo">
        <div class="detalhes detalhes-fechamento">
            <div class="detalhe detalhe-fornecedor">
                <div class="rotulo">Fornecedor</div>
                <div class="valor"><?= html_escape($registro['fornecedor_nome'] ? $registro['fornecedor_nome'] : '-') ?></div>
            </div>
            <div class="detalhe detalhe-data">
                <div class="rotulo">Data do pedido</div>
                <div class="valor"><?= data_br($registro['data']) ?></div>
            </div>
            <div class="detalhe detalhe-itens detalhe-centro">
                <div class="rotulo">Itens</div>
                <div class="valor"><?= numero_br($n_itens) ?></div>
            </div>
            <div class="detalhe detalhe-bruto detalhe-centro">
                <div class="rotulo">Valor bruto</div>
                <div class="valor"><?= moeda_br($bruto) ?></div>
            </div>
            <div class="detalhe detalhe-desconto detalhe-centro">
                <div class="rotulo">Desconto</div>
                <div class="valor"><?= moeda_br($desconto) ?></div>
            </div>
            <div class="detalhe detalhe-total detalhe-centro">
                <div class="rotulo">Total</div>
                <div class="valor"><strong><?= moeda_br($total) ?></strong></div>
            </div>
        </div>

        <hr class="divisor">

        <?= form_open('compras/fechar/' . $registro['numero'], array(
            'class' => 'form-fechamento',
            'data-pedido-numero' => (int) $registro['numero'],
        )) ?>

            <input type="hidden" id="<?= $pf ?>valor-total-fechamento" data-valor-total-fechamento
                   data-valor="<?= number_format($total, 2, '.', '') ?>" value="">
            <input type="hidden" name="fornecedor_estoque" value="<?= (int) $registro['fornecedor_codigo'] ?>">

            <div class="form-grid">
                <div class="campo quarto">
                    <label for="<?= $pf ?>forma_recebimento">Forma de pagamento</label>
                    <select name="forma_recebimento" id="<?= $pf ?>forma_recebimento">
                        <option value="">-</option>
                        <?php foreach ($dominios['forma_recebimento'] as $d): ?>
                            <option value="<?= (int) $d['codigo'] ?>"
                                <?= (int) $registro['forma_recebimento'] === (int) $d['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($d['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo quarto">
                    <label for="<?= $pf ?>condicao_pagamento">Condicao de pagamento</label>
                    <select name="condicao_pagamento" id="<?= $pf ?>condicao_pagamento">
                        <option value="">-</option>
                        <?php foreach ($dominios['condicao_pagamento'] as $d): ?>
                            <option value="<?= (int) $d['codigo'] ?>"
                                <?= (int) $registro['condicao_pagamento'] === (int) $d['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($d['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo quarto">
                    <label for="<?= $pf ?>num_nf">Numero da nota fiscal</label>
                    <input type="text" name="num_nf" id="<?= $pf ?>num_nf" maxlength="30"
                           value="<?= html_escape($registro['num_nf']) ?>">
                </div>

                <div class="campo quarto">
                    <label for="<?= $pf ?>data_nf">Data da nota fiscal</label>
                    <input type="date" name="data_nf" id="<?= $pf ?>data_nf" value="<?= html_escape($registro['data_nf']) ?>">
                </div>

            </div>

            <hr class="divisor">

            <div class="fechamento-colunas">
                <div class="fechamento-dados">
                    <h4 class="secao-titulo">Dados base para gerar parcelas</h4>

                    <div class="form-grid">
                        <div class="campo full">
                            <label for="<?= $pf ?>data_vencimento">Vencimento Base</label>
                            <input type="date" name="data_vencimento" id="<?= $pf ?>data_vencimento"
                                   value="<?= html_escape($registro['data_vencimento'] ? $registro['data_vencimento'] : date('Y-m-d')) ?>" required>
                            <div class="ajuda">Base para calcular o vencimento de cada parcela.</div>
                        </div>

                        <div class="campo full">
                            <label for="<?= $pf ?>quantidade_parcelas">Quantidade de parcelas</label>
                            <input type="number" id="<?= $pf ?>quantidade_parcelas" data-quantidade-parcelas
                                   min="1" step="1" placeholder="Informe a quantidade">
                            <div class="ajuda">Ao informar, as parcelas e os valores sao gerados automaticamente.</div>
                        </div>

                        <div class="campo full">
                            <label class="check">
                                <input type="checkbox" name="gerar_contas" value="S" checked>
                                Gerar contas a pagar com as parcelas ao lado
                            </label>
                        </div>
                    </div>
                </div>

                <div class="fechamento-parcelas">
                    <h4 class="secao-titulo">Parcelas</h4>

                    <div class="parcelas-bloco">
                        <div class="tabela-wrap tabela-wrap-parcelas">
                            <table class="tabela tabela-itens tabela-parcelas"
                                   data-parcelas data-template="linha-parcela" data-grupo="parcelas" data-iniciais="1">
                                <thead>
                                    <tr>
                                        <th class="num" style="width:34px">#</th>
                                        <th class="num" style="width:140px">Valor da Parcela</th>
                                        <th class="num" style="width:90px">Prazo (dias)</th>
                                        <th style="width:160px">Data Vencimento</th>
                                        <th class="centro" style="width:40px"></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" class="num"><strong>Total</strong></td>
                                        <td class="num total-parcelas"><strong>0,00</strong></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="form-acoes parcelas-acoes">
                            <button type="button" class="botao secundario" data-adicionar-item="parcelas">Adicionar Parcela</button>
                            <button type="button" class="botao" data-botao-fechar>Fechar pedido</button>

                            <?php if ($modal): ?>
                                <button type="button" class="botao secundario" data-fechar-modal>Cancelar Fechamento</button>
                            <?php else: ?>
                                <a class="botao secundario" href="<?= site_url('compras/ver/' . $registro['numero']) ?>">Cancelar Fechamento</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ( ! empty($prazos)): ?>
                <hr class="divisor">
                <h4 class="secao-titulo">Prazos cadastrados para o fornecedor</h4>
                <div class="tabela-wrap">
                    <table class="tabela">
                        <thead>
                            <tr><th style="width:70px">Codigo</th><th>Forma de pagamento</th><th>Prazo</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prazos as $p): ?>
                                <tr>
                                    <td><?= (int) $p['codigo'] ?></td>
                                    <td><?= html_escape($p['forma_pagamento'] ? $p['forma_pagamento'] : '-') ?></td>
                                    <td><?= html_escape($p['prazo'] ? $p['prazo'] : '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="modal-confirmacao" data-confirma-fechamento role="dialog" aria-modal="true">
                <div class="modal-confirmacao-caixa">
                    <h4>Confirma&ccedil;&atilde;o</h4>
                    <p data-confirma-mensagem></p>
                    <div class="modal-confirmacao-acoes">
                        <button type="button" class="botao" data-confirma-sim>Sim</button>
                        <button type="button" class="botao secundario" data-confirma-nao>N&atilde;o</button>
                    </div>
                </div>
            </div>

            <div class="modal-confirmacao" data-aviso-fechamento role="dialog" aria-modal="true">
                <div class="modal-confirmacao-caixa">
                    <h4>Fechamento</h4>
                    <p data-aviso-mensagem></p>
                    <div class="modal-confirmacao-acoes">
                        <button type="button" class="botao" data-aviso-ok>Ok</button>
                    </div>
                </div>
            </div>

        <?= form_close() ?>
    </div>
</div>

<template id="linha-parcela">
    <table><tbody>
        <tr class="linha-item">
            <td class="num numero-item">1</td>
            <td class="num valor-parcela">0,00</td>
            <td><input type="number" name="parcela_dias[]" class="dias" value="0" min="0" step="1" style="width:78px"></td>
            <td><input type="date" name="parcela_vencimento[]" style="width:150px"></td>
            <td class="centro"><button type="button" class="botao perigo pequeno remover-item" title="Remover parcela">&times;</button></td>
        </tr>
    </tbody></table>
</template>

<?php if ( ! $modal): ?>
    <script src="<?= base_url('assets/js/compras.js?v=8') ?>"></script>
<?php endif; ?>
