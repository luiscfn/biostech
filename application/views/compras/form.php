<?php
$edicao = ! empty($registro['numero']);
$hora   = $edicao && $registro['hora'] ? substr((string) $registro['hora'], 0, 5) : date('H:i');
$data   = $edicao ? $registro['data'] : date('Y-m-d');
$bruto  = 0.0;

foreach ($itens as $item)
{
    $bruto += (float) $item['valor_total'];
}

$desconto = $edicao ? (float) $registro['desconto'] : 0.0;
$perc     = $edicao && $registro['desconto_percentual'] !== NULL ? (float) $registro['desconto_percentual'] : NULL;
$total    = $edicao ? (float) $registro['total'] : $bruto;
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>
            <?= $edicao ? 'Editar pedido de compra ' . (int) $registro['numero'] : 'Novo pedido de compra' ?>
            <?php if ($edicao): ?>
                <span class="badge alerta">Aberto</span>
            <?php endif; ?>
        </h3>

        <div class="acoes-ficha">
            <?php if ($edicao): ?>
                <a class="botao secundario pequeno" href="<?= site_url('compras/ver/' . $registro['numero']) ?>">Ver ficha</a>
            <?php endif; ?>
            <a class="botao secundario pequeno" href="<?= site_url('compras') ?>">Voltar</a>
        </div>
    </div>

    <div class="painel-corpo">
        <?= form_open($edicao ? 'compras/editar/' . $registro['numero'] : 'compras/novo', array('id' => 'form-pedido')) ?>

            <div class="form-grid">
                <div class="campo terco">
                    <label for="data">Data do pedido *</label>
                    <input type="date" name="data" id="data" value="<?= html_escape($data) ?>" required>
                </div>

                <div class="campo terco">
                    <label for="hora">Hora</label>
                    <input type="time" name="hora" id="hora" value="<?= html_escape($hora) ?>">
                </div>

                <div class="campo terco">
                    <label for="numero_proposta">Numero da proposta</label>
                    <input type="text" name="numero_proposta" id="numero_proposta" maxlength="30"
                           value="<?= $edicao ? html_escape($registro['numero_proposta']) : '' ?>">
                </div>

                <div class="campo full">
                    <label for="fornecedor_busca">Fornecedor *</label>
                    <div class="autocomplete" data-url="<?= site_url('compras/buscar_fornecedores') ?>">
                        <input type="text" id="fornecedor_busca" class="autocomplete-busca"
                               placeholder="Digite a razao social, nome fantasia ou CNPJ..."
                               value="<?= $edicao ? html_escape($registro['fornecedor_nome']) : '' ?>" autocomplete="off">
                        <input type="hidden" name="fornecedor_codigo" id="fornecedor_codigo"
                               value="<?= $edicao ? (int) $registro['fornecedor_codigo'] : '' ?>">
                        <div class="autocomplete-lista"></div>
                    </div>
                    <?php if ($edicao && ! empty($registro['fornecedor_codigo'])): ?>
                        <div class="ajuda">
                            <?= html_escape($registro['fornecedor_codigo'] . ' - ' . $registro['fornecedor_nome']) ?>
                            <?= $registro['fornecedor_cnpj'] ? ' | CNPJ ' . html_escape($registro['fornecedor_cnpj']) : '' ?>
                            <?= $registro['fornecedor_fone'] ? ' | Fone ' . html_escape($registro['fornecedor_fone']) : '' ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="campo">
                    <label for="setor_id">Setor solicitante</label>
                    <select name="setor_id" id="setor_id">
                        <option value="">-</option>
                        <?php foreach ($setores as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"
                                <?= $edicao && (int) $registro['setor_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                                <?= html_escape($s['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="funcionario_id">Funcionario</label>
                    <select name="funcionario_id" id="funcionario_id">
                        <option value="">-</option>
                        <?php foreach ($funcionarios as $f): ?>
                            <option value="<?= (int) $f['id'] ?>"
                                <?= $edicao && (int) $registro['funcionario_id'] === (int) $f['id'] ? 'selected' : '' ?>>
                                <?= html_escape($f['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="condicao_pagamento">Condicao de pagamento</label>
                    <select name="condicao_pagamento" id="condicao_pagamento">
                        <option value="">-</option>
                        <?php foreach ($dominios['condicao_pagamento'] as $d): ?>
                            <option value="<?= (int) $d['codigo'] ?>"
                                <?= $edicao && (int) $registro['condicao_pagamento'] === (int) $d['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($d['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="prazo_entrega">Prazo de entrega (dias)</label>
                    <input type="number" name="prazo_entrega" id="prazo_entrega" min="0" step="1"
                           value="<?= $edicao && $registro['prazo_entrega'] !== NULL ? (int) $registro['prazo_entrega'] : '' ?>">
                </div>

                <div class="campo">
                    <label for="forma_recebimento">Forma de pagamento</label>
                    <select name="forma_recebimento" id="forma_recebimento">
                        <option value="">-</option>
                        <?php foreach ($dominios['forma_recebimento'] as $d): ?>
                            <option value="<?= (int) $d['codigo'] ?>"
                                <?= $edicao && (int) $registro['forma_recebimento'] === (int) $d['codigo'] ? 'selected' : '' ?>>
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
                                <?= $edicao && (int) $registro['documento_recebimento'] === (int) $d['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($d['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="centro_custo">Centro de custo</label>
                    <input type="number" name="centro_custo" id="centro_custo" min="0" step="1"
                           value="<?= $edicao && $registro['centro_custo'] !== NULL ? (int) $registro['centro_custo'] : '' ?>">
                </div>

                <div class="campo">
                    <label for="data_vencimento">1o vencimento</label>
                    <input type="date" name="data_vencimento" id="data_vencimento"
                           value="<?= $edicao ? html_escape($registro['data_vencimento']) : '' ?>">
                </div>

                <div class="campo full">
                    <label for="local_entrega">Local de entrega</label>
                    <input type="text" name="local_entrega" id="local_entrega" maxlength="200"
                           value="<?= $edicao ? html_escape($registro['local_entrega']) : '' ?>">
                </div>

                <div class="campo terco">
                    <label for="responsavel_recebimento">Responsavel pelo recebimento</label>
                    <input type="text" name="responsavel_recebimento" id="responsavel_recebimento" maxlength="80"
                           value="<?= $edicao ? html_escape($registro['responsavel_recebimento']) : '' ?>">
                </div>

                <div class="campo terco">
                    <label for="fone_responsavel">Fone do responsavel</label>
                    <input type="text" name="fone_responsavel" id="fone_responsavel" maxlength="20"
                           value="<?= $edicao ? html_escape($registro['fone_responsavel']) : '' ?>">
                </div>

                <div class="campo terco">
                    <label for="solicitacao_numero">Solicitacao interna</label>
                    <input type="number" name="solicitacao_numero" id="solicitacao_numero" min="0" step="1"
                           value="<?= $edicao && $registro['solicitacao_numero'] !== NULL ? (int) $registro['solicitacao_numero'] : '' ?>">
                </div>

                <div class="campo full">
                    <label for="observacao">Observacoes</label>
                    <textarea name="observacao" id="observacao"><?= $edicao ? html_escape($registro['observacao']) : '' ?></textarea>
                </div>
            </div>

            <hr class="divisor">

            <h4 class="secao-titulo">Itens do pedido</h4>

            <div class="tabela-wrap">
                <table class="tabela tabela-itens" id="tabela-pedido"
                       data-compra data-template="linha-pedido" data-grupo="pedido">
                    <thead>
                        <tr>
                            <th class="num" style="width:36px">#</th>
                            <th style="min-width:230px">Produto</th>
                            <th class="centro" style="width:58px">Unid.</th>
                            <th class="num" style="width:70px">Saldo</th>
                            <th class="num" style="width:80px">Qtd</th>
                            <th class="num" style="width:100px">Valor unit.</th>
                            <th class="num" style="width:100px">Novo valor</th>
                            <th class="num" style="width:75px">Desc. %</th>
                            <th class="num" style="width:95px">Desc. R$</th>
                            <th class="num" style="width:105px">Total</th>
                            <th class="centro" style="width:60px" title="Atualiza o preco de compra do produto">Atualiza</th>
                            <th style="width:130px">Obs.</th>
                            <th class="centro" style="width:44px"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="9" class="num"><strong>Total bruto</strong></td>
                            <td class="num"><strong>R$ <span id="pedido-bruto"><?= number_format($bruto, 2, ',', '.') ?></span></strong></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="form-grid" style="margin-top:14px">
                <div class="campo terco">
                    <label for="desconto_percentual">Desconto (%)</label>
                    <input type="text" name="desconto_pedido_percentual" id="desconto_percentual" inputmode="decimal"
                           value="<?= $perc !== NULL ? number_format($perc, 2, ',', '.') : '' ?>" placeholder="0,00">
                </div>

                <div class="campo terco">
                    <label for="desconto">Desconto (R$)</label>
                    <input type="text" name="desconto" id="desconto" inputmode="decimal"
                           value="<?= number_format($desconto, 2, ',', '.') ?>" placeholder="0,00">
                </div>

                <div class="campo terco">
                    <label>Total do pedido</label>
                    <div class="saldo-grande" id="pedido-total"><?= moeda_br($total) ?></div>
                    <div class="ajuda">Bruto menos o desconto do cabecalho.</div>
                </div>

                <div class="campo full">
                    <button type="button" class="botao secundario" data-adicionar-item="pedido">+ Adicionar item</button>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao"><?= $edicao ? 'Salvar alteracoes' : 'Criar pedido' ?></button>
                <a class="botao secundario" href="<?= site_url($edicao ? 'compras/ver/' . $registro['numero'] : 'compras') ?>">Cancelar</a>
            </div>

        <?= form_close() ?>
    </div>
</div>

<template id="linha-pedido">
    <table><tbody>
        <tr class="linha-item">
            <td class="num numero-item">1</td>
            <td>
                <div class="autocomplete"
                     data-url="<?= site_url('compras/buscar_produtos') ?>"
                     data-custo=".valor-unit"
                     data-estoque=".saldo"
                     data-unidade=".unidade">
                    <input type="text" class="autocomplete-busca" placeholder="Codigo ou descricao..." autocomplete="off">
                    <input type="hidden" name="produto_id[]">
                    <div class="autocomplete-lista"></div>
                </div>
            </td>
            <td><input type="text" class="unidade" value="" readonly tabindex="-1"></td>
            <td><input type="text" class="saldo" value="" readonly tabindex="-1" style="text-align:right"></td>
            <td><input type="number" name="quantidade[]" class="qtd" value="1" min="0.001" step="0.001" style="text-align:right"></td>
            <td><input type="text" name="valor_unitario[]" class="valor-unit" inputmode="decimal" style="text-align:right"></td>
            <td><input type="text" name="novo_valor_unitario[]" class="novo-valor" inputmode="decimal" style="text-align:right"></td>
            <td><input type="text" name="desconto_percentual[]" class="desc-perc" inputmode="decimal" style="text-align:right"></td>
            <td><input type="text" name="desconto_item[]" class="desc-item" inputmode="decimal" style="text-align:right"></td>
            <td class="num total-linha">0,00</td>
            <td class="centro"><input type="checkbox" name="atualiza_preco_compra[]" value="S"></td>
            <td><input type="text" name="observacao_item[]" maxlength="80"></td>
            <td class="centro"><button type="button" class="botao perigo pequeno remover-item" title="Remover item">&times;</button></td>
        </tr>
    </tbody></table>
</template>

<script>
window.BIOSTECH_ITENS = <?= json_encode(array_map(function ($i) {
    return array(
        'produto_id'            => (int) $i['produto_id'],
        'descricao'             => $i['descricao'],
        'unidade'               => $i['unidade'],
        'saldo'                 => (float) $i['saldo'],
        'quantidade'            => (float) $i['quantidade'],
        'valor_unitario'        => (float) $i['valor_unitario'],
        'novo_valor_unitario'   => $i['novo_valor_unitario'] !== NULL ? (float) $i['novo_valor_unitario'] : NULL,
        'desconto_percentual'   => $i['desconto_percentual'] !== NULL ? (float) $i['desconto_percentual'] : NULL,
        'desconto_item'         => (float) $i['desconto_item'],
        'valor_total'           => (float) $i['valor_total'],
        'observacao'            => $i['observacao'],
        'atualiza_preco_compra' => bd_bool($i['atualiza_preco_compra']),
    );
}, $itens), JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
<script src="<?= base_url('assets/js/compras.js') ?>"></script>
