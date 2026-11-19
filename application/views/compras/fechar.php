<?php
$total = (float) $registro['total'];
$n_itens = count($itens);
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>Fechar pedido de compra <?= (int) $registro['numero'] ?></h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('compras/ver/' . $registro['numero']) ?>">Ver ficha</a>
            <a class="botao secundario pequeno" href="<?= site_url('compras') ?>">Voltar</a>
        </div>
    </div>

    <div class="painel-corpo">
        <div class="detalhes">
            <div class="detalhe">
                <div class="rotulo">Fornecedor</div>
                <div class="valor"><?= html_escape($registro['fornecedor_nome'] ? $registro['fornecedor_nome'] : '-') ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Data do pedido</div>
                <div class="valor"><?= data_br($registro['data']) ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Itens</div>
                <div class="valor"><?= numero_br($n_itens) ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Total</div>
                <div class="valor"><strong><?= moeda_br($total) ?></strong></div>
            </div>
        </div>

        <hr class="divisor">

        <?= form_open('compras/fechar/' . $registro['numero']) ?>

            <input type="hidden" id="valor-total-fechamento" data-valor="<?= number_format($total, 2, '.', '') ?>" value="">
            <input type="hidden" name="fornecedor_estoque" value="<?= (int) $registro['fornecedor_codigo'] ?>">

            <div class="form-grid">
                <div class="campo">
                    <label for="data_compra">Data da compra *</label>
                    <input type="date" name="data_compra" id="data_compra" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="campo">
                    <label for="num_nf">Numero da nota fiscal</label>
                    <input type="text" name="num_nf" id="num_nf" maxlength="30"
                           value="<?= html_escape($registro['num_nf']) ?>">
                </div>

                <div class="campo">
                    <label for="data_nf">Data da nota fiscal</label>
                    <input type="date" name="data_nf" id="data_nf" value="<?= html_escape($registro['data_nf']) ?>">
                </div>

                <div class="campo">
                    <label for="data_vencimento">1o vencimento *</label>
                    <input type="date" name="data_vencimento" id="data_vencimento"
                           value="<?= html_escape($registro['data_vencimento'] ? $registro['data_vencimento'] : date('Y-m-d')) ?>" required>
                    <div class="ajuda">Base para calcular o vencimento de cada parcela.</div>
                </div>

                <div class="campo">
                    <label for="condicao_pagamento">Condicao de pagamento</label>
                    <select name="condicao_pagamento" id="condicao_pagamento">
                        <option value="">-</option>
                        <?php foreach ($dominios['condicao_pagamento'] as $d): ?>
                            <option value="<?= (int) $d['codigo'] ?>"
                                <?= (int) $registro['condicao_pagamento'] === (int) $d['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($d['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="prazo_entrega">Prazo de entrega (dias)</label>
                    <input type="number" name="prazo_entrega" id="prazo_entrega" min="0" step="1"
                           value="<?= $registro['prazo_entrega'] !== NULL ? (int) $registro['prazo_entrega'] : '' ?>">
                </div>

                <div class="campo">
                    <label for="forma_recebimento">Forma de pagamento</label>
                    <select name="forma_recebimento" id="forma_recebimento">
                        <option value="">-</option>
                        <?php foreach ($dominios['forma_recebimento'] as $d): ?>
                            <option value="<?= (int) $d['codigo'] ?>"
                                <?= (int) $registro['forma_recebimento'] === (int) $d['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($d['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="documento_recebimento">Documento</label>
                    <select name="documento_recebimento" id="documento_recebimento">
                        <option value="">-</option>
                        <?php foreach ($dominios['documento'] as $d): ?>
                            <option value="<?= (int) $d['codigo'] ?>"
                                <?= (int) $registro['documento_recebimento'] === (int) $d['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($d['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="centro_custo">Centro de custo</label>
                    <input type="number" name="centro_custo" id="centro_custo" min="0" step="1"
                           value="<?= $registro['centro_custo'] !== NULL ? (int) $registro['centro_custo'] : '' ?>">
                </div>

                <div class="campo full">
                    <label class="check">
                        <input type="checkbox" name="gerar_contas" value="S" checked>
                        Gerar contas a pagar com as parcelas abaixo
                    </label>
                </div>
            </div>

            <hr class="divisor">

            <h4 class="secao-titulo">Parcelas</h4>

            <div class="tabela-wrap">
                <table class="tabela tabela-itens"
                       data-parcelas data-template="linha-parcela" data-grupo="parcelas" data-iniciais="1">
                    <thead>
                        <tr>
                            <th class="num" style="width:42px">#</th>
                            <th class="num" style="width:120px">Prazo (dias)</th>
                            <th style="width:190px">Vencimento</th>
                            <th class="num">Valor previsto</th>
                            <th class="centro" style="width:50px"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="form-acoes" style="margin-top:10px">
                <button type="button" class="botao secundario" data-adicionar-item="parcelas">+ Adicionar parcela</button>
                <span class="ajuda">Maximo de 12 parcelas. Os prazos e vencimentos nao podem diminuir.</span>
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

            <div class="form-acoes">
                <button type="submit" class="botao">Fechar pedido</button>
                <a class="botao secundario" href="<?= site_url('compras/ver/' . $registro['numero']) ?>">Cancelar</a>
            </div>

        <?= form_close() ?>
    </div>
</div>

<template id="linha-parcela">
    <table><tbody>
        <tr class="linha-item">
            <td class="num numero-item">1</td>
            <td><input type="number" name="parcela_dias[]" class="dias" value="0" min="0" step="1" style="text-align:right"></td>
            <td><input type="date" name="parcela_vencimento[]"></td>
            <td class="num valor-parcela">0,00</td>
            <td class="centro"><button type="button" class="botao perigo pequeno remover-item" title="Remover parcela">&times;</button></td>
        </tr>
    </tbody></table>
</template>

<script src="<?= base_url('assets/js/compras.js') ?>"></script>
