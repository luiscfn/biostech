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
$conta_numero = ! empty($contas) && ! empty($contas[0]['numero']) ? (int) $contas[0]['numero'] : '-';
?>
<style>
.cabecalho-titulo { display:flex; align-items:center; gap:18px; flex-wrap:wrap; }
.dados-pedido { display:flex; gap:16px; flex-wrap:wrap; align-items:center; font-size:13px; color:#2a3a55; }
.dados-pedido strong { color:#123; }
.pedido-grid { display:grid; grid-template-columns:repeat(12, 1fr); gap:10px 12px; }
.pedido-grid .campo { grid-column:span 3; }
.pedido-grid .p1 { grid-column:span 1; }
.pedido-grid .p2 { grid-column:span 2; }
.pedido-grid .p3 { grid-column:span 3; }
.pedido-grid .p4 { grid-column:span 4; }
.pedido-grid .p5 { grid-column:span 5; }
.pedido-grid .p6 { grid-column:span 6; }
.pedido-grid .p12 { grid-column:span 12; }
.pedido-grid label, .item-grid label { display:block; font-size:11px; font-weight:600; color:#2a3a55; text-transform:uppercase; letter-spacing:.3px; margin-bottom:3px; }
.pedido-grid input, .pedido-grid select, .pedido-grid textarea,
.item-grid input, .item-grid select { width:100%; box-sizing:border-box; border:1px solid #7f90a8; border-radius:3px; padding:4px 6px; font-size:12px; height:26px; }
.pedido-grid input[readonly], .item-grid input[readonly] { background:#eef1f6; }
.pedido-grid textarea { height:50px; resize:vertical; }
.pedido-auditoria { display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; margin-top:6px; }
.pedido-auditoria .rotulo { font-size:10px; color:var(--muted); text-transform:uppercase; }
.pedido-auditoria .valor { font-size:12px; }
.pedido-auditoria .ajuda { font-size:11px; color:var(--muted); }
.pedido-item-cadastro { border:1px solid #c9d5e4; border-radius:6px; background:#fafcff; padding:12px; margin:10px 0 14px; }
.item-grid { display:grid; grid-template-columns:repeat(12, 1fr); gap:8px 10px; }
.item-grid .campo { grid-column:span 2; }
.item-grid .i1 { grid-column:span 1; }
.item-grid .i2 { grid-column:span 2; }
.item-grid .i4 { grid-column:span 4; }
.item-total { background:#d9f2e6; padding:5px 8px; text-align:right; font-weight:700; border:1px solid #bfe6d4; border-radius:3px; }
.item-linha-acoes { display:flex; justify-content:space-between; align-items:center; margin-top:10px; gap:12px; }
.tabela-itens-rolagem { max-height:280px; overflow-y:auto; }
</style>
<div class="painel">
    <div class="painel-cabecalho">
        <div class="cabecalho-titulo">
            <h3>Pedido de Compra</h3>
            <div class="dados-pedido">
                <span>N&deg;: <strong><?= $edicao ? (int) $registro['numero'] : '-' ?></strong></span>
                <span>Data: <strong><?= data_br($data) ?></strong></span>
                <span>N&deg; SC: <strong><?= $edicao && ! empty($registro['solicitacao_numero']) ? (int) $registro['solicitacao_numero'] : '-' ?></strong></span>
                <span>N&deg; conta a pagar: <strong><?= html_escape($conta_numero) ?></strong></span>
            </div>
        </div>

        <div class="acoes-ficha">
            <?php if ($edicao): ?>
                <a class="botao secundario pequeno" href="<?= site_url('compras/ver/' . $registro['numero']) ?>">Ver ficha</a>
            <?php endif; ?>
            <a class="botao secundario pequeno" href="<?= site_url('compras') ?>">Voltar</a>
        </div>
    </div>

    <div class="painel-corpo">
        <?= form_open($edicao ? 'compras/editar/' . $registro['numero'] : 'compras/novo', array('id' => 'form-pedido')) ?>

            <div class="pedido-grid">
                <div class="campo p3">
                    <label for="setor_id">Setor</label>
                    <select name="setor_id" id="setor_id">
                        <option value="">-</option>
                        <?php foreach ($setores as $s): ?>
                            <option value="<?= (int) $s['id'] ?>" <?= $edicao && (int) $registro['setor_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                                <?= html_escape($s['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo p3">
                    <label for="funcionario_id">Solicitante</label>
                    <select name="funcionario_id" id="funcionario_id">
                        <option value="">-</option>
                        <?php foreach ($funcionarios as $f): ?>
                            <option value="<?= (int) $f['id'] ?>" <?= $edicao && (int) $registro['funcionario_id'] === (int) $f['id'] ? 'selected' : '' ?>>
                                <?= html_escape($f['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo p2">
                    <label for="num_nf">N&deg; da N.F.</label>
                    <input type="text" name="num_nf" id="num_nf" value="<?= $edicao ? html_escape($registro['num_nf']) : '' ?>">
                </div>

                <div class="campo p2">
                    <label for="data_nf">Data da N.F.</label>
                    <input type="date" name="data_nf" id="data_nf" value="<?= $edicao ? html_escape($registro['data_nf']) : '' ?>">
                </div>

                <div class="campo p2">
                    <label for="prazo_entrega">Prazo entrega (dias)</label>
                    <input type="number" name="prazo_entrega" id="prazo_entrega" min="0" step="1"
                           value="<?= $edicao && $registro['prazo_entrega'] !== NULL ? (int) $registro['prazo_entrega'] : '' ?>">
                </div>

                <div class="campo p6">
                    <label for="fornecedor_busca">Fornecedor</label>
                    <div class="autocomplete" data-url="<?= site_url('compras/buscar_fornecedores') ?>"
                         data-cidade="#forn-cidade" data-uf="#forn-uf" data-cep="#forn-cep"
                         data-endereco="#forn-endereco" data-numero="#forn-numero" data-bairro="#forn-bairro">
                        <input type="text" id="fornecedor_busca" class="autocomplete-busca"
                               placeholder="Razao social, nome fantasia ou CNPJ..."
                               value="<?= $edicao ? html_escape($registro['fornecedor_nome']) : '' ?>" autocomplete="off">
                        <input type="hidden" name="fornecedor_codigo" id="fornecedor_codigo"
                               value="<?= $edicao ? (int) $registro['fornecedor_codigo'] : '' ?>">
                        <div class="autocomplete-lista"></div>
                    </div>
                </div>

                <div class="campo p2">
                    <label>Cidade</label>
                    <input type="text" id="forn-cidade" readonly tabindex="-1">
                </div>

                <div class="campo p1">
                    <label>UF</label>
                    <input type="text" id="forn-uf" readonly tabindex="-1">
                </div>

                <div class="campo p1">
                    <label>CEP</label>
                    <input type="text" id="forn-cep" readonly tabindex="-1">
                </div>

                <div class="campo p4">
                    <label>Endere&ccedil;o</label>
                    <input type="text" id="forn-endereco" readonly tabindex="-1">
                </div>

                <div class="campo p1">
                    <label>N&deg;</label>
                    <input type="text" id="forn-numero" readonly tabindex="-1">
                </div>

                <div class="campo p2">
                    <label>Bairro</label>
                    <input type="text" id="forn-bairro" readonly tabindex="-1">
                </div>

                <div class="campo p5">
                    <label for="local_entrega">Local de entrega</label>
                    <input type="text" name="local_entrega" id="local_entrega" maxlength="200"
                           value="<?= $edicao ? html_escape($registro['local_entrega']) : '' ?>">
                </div>

                <div class="campo p3">
                    <label for="responsavel_recebimento">Recebedor</label>
                    <input type="text" name="responsavel_recebimento" id="responsavel_recebimento" maxlength="80"
                           value="<?= $edicao ? html_escape($registro['responsavel_recebimento']) : '' ?>">
                </div>

                <div class="campo p2">
                    <label for="fone_responsavel">Fone do responsavel</label>
                    <input type="text" name="fone_responsavel" id="fone_responsavel" maxlength="14"
                           value="<?= $edicao ? html_escape($registro['fone_responsavel']) : '' ?>">
                </div>

                <div class="campo p2">
                    <label for="forma_recebimento">Tipo de Pagto</label>
                    <select name="forma_recebimento" id="forma_recebimento">
                        <option value="">-</option>
                        <?php foreach ($dominios['forma_recebimento'] as $d): ?>
                            <option value="<?= (int) $d['codigo'] ?>" <?= $edicao && (int) $registro['forma_recebimento'] === (int) $d['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($d['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo p2">
                    <label for="condicao_pagamento">Condi&ccedil;&atilde;o de pagamento</label>
                    <select name="condicao_pagamento" id="condicao_pagamento">
                        <option value="">-</option>
                        <?php foreach ($dominios['condicao_pagamento'] as $d): ?>
                            <option value="<?= (int) $d['codigo'] ?>" <?= $edicao && (int) $registro['condicao_pagamento'] === (int) $d['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($d['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo p3">
                    <label for="documento_recebimento">Documento</label>
                    <select name="documento_recebimento" id="documento_recebimento">
                        <option value="">-</option>
                        <?php foreach ($dominios['documento'] as $d): ?>
                            <option value="<?= (int) $d['codigo'] ?>" <?= $edicao && (int) $registro['documento_recebimento'] === (int) $d['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($d['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo p2">
                    <label for="centro_custo">Centro de custo</label>
                    <select name="centro_custo" id="centro_custo">
                        <option value="">-</option>
                        <?php foreach ($centros_custo as $cc): ?>
                            <option value="<?= (int) $cc['codigo'] ?>" <?= $edicao && (int) $registro['centro_custo'] === (int) $cc['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($cc['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo p4">
                    <label for="observacao">Observa&ccedil;&otilde;es</label>
                    <textarea name="observacao" id="observacao"><?= $edicao ? html_escape($registro['observacao']) : '' ?></textarea>
                </div>

                <?php if ($edicao): ?>
                    <div class="campo p6">
                        <div class="pedido-auditoria">
                            <div>
                                <div class="rotulo">Cadastro</div>
                                <div class="valor"><?= html_escape($registro['usuario_cadastro_nome'] ? $registro['usuario_cadastro_nome'] : $registro['usuario_cadastro']) ?></div>
                                <div class="ajuda"><?= data_hora_br($registro['data_cadastro']) ?></div>
                            </div>
                            <div>
                                <div class="rotulo">Altera&ccedil;&atilde;o</div>
                                <div class="valor"><?= html_escape($registro['usuario_ultima_alteracao'] ? $registro['usuario_ultima_alteracao'] : '-') ?></div>
                                <div class="ajuda"><?= $registro['data_ultima_alteracao'] ? data_hora_br($registro['data_ultima_alteracao']) : '-' ?></div>
                            </div>
                            <div>
                                <div class="rotulo">Fechamento</div>
                                <div class="valor"><?= html_escape($registro['usuario_fechamento_nome'] ? $registro['usuario_fechamento_nome'] : $registro['usuario_fechamento']) ?></div>
                                <div class="ajuda"><?= $registro['data_fechamento'] ? data_hora_br($registro['data_fechamento']) : '-' ?></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="campo p12">
                    <input type="hidden" name="data" id="data" value="<?= html_escape($data) ?>">
                    <input type="hidden" name="hora" id="hora" value="<?= html_escape($hora) ?>">
                    <input type="hidden" name="tipo" value="<?= $edicao ? html_escape($registro['tipo']) : '' ?>">
                    <input type="hidden" name="data_vencimento" value="<?= $edicao ? html_escape($registro['data_vencimento']) : '' ?>">
                    <input type="hidden" name="solicitacao_numero" value="<?= $edicao && $registro['solicitacao_numero'] !== NULL ? (int) $registro['solicitacao_numero'] : '' ?>">
                    <input type="hidden" name="numero_proposta" value="<?= $edicao ? html_escape($registro['numero_proposta']) : '' ?>">
                </div>
            </div>

            <hr class="divisor">

            <div class="pedido-item-cadastro">
                <div class="item-grid">
                    <div class="campo i4">
                        <label for="item-produto">Produto</label>
                        <div class="autocomplete" data-url="<?= site_url('compras/buscar_produtos') ?>"
                             data-custo="#item-valor-unit" data-estoque="#item-saldo" data-unidade="#item-unidade">
                            <input type="text" class="autocomplete-busca" id="item-produto" placeholder="Codigo ou descricao..." autocomplete="off">
                            <input type="hidden" id="item-produto-id">
                            <div class="autocomplete-lista"></div>
                        </div>
                    </div>
                    <div class="campo i1"><label>Unid.</label><input type="text" id="item-unidade" readonly tabindex="-1"></div>
                    <div class="campo i1"><label>Saldo</label><input type="text" id="item-saldo" readonly tabindex="-1"></div>
                    <div class="campo i1"><label>Qtd</label><input type="text" inputmode="numeric" id="item-qtd" value="1"></div>
                    <div class="campo i2"><label>Valor unit&aacute;rio</label><input type="text" inputmode="decimal" id="item-valor-unit" placeholder="0,00"></div>
                    <div class="campo i1"><label>Novo valor</label><input type="text" inputmode="decimal" id="item-novo-valor" placeholder="0,00"></div>
                    <div class="campo i1"><label>Desc. %</label><input type="text" inputmode="decimal" id="item-desc-perc" placeholder="0,00"></div>
                    <div class="campo i1"><label>Desc. R$</label><input type="text" inputmode="decimal" id="item-desc-valor" placeholder="0,00"></div>
                </div>
                <div class="item-linha-acoes">
                    <div style="font-size:12px">Total do item: <span class="item-total" id="item-total">0,00</span></div>
                    <button type="button" class="botao pequeno" id="btn-adicionar-item-pedido">+ Adicionar item</button>
                </div>
            </div>

            <h4 class="secao-titulo">Itens do pedido</h4>

            <div class="tabela-wrap tabela-itens-rolagem">
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
                            <td class="num"><strong><span id="pedido-bruto"><?= number_format($bruto, 2, ',', '.') ?></span></strong></td>
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
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao"><?= $edicao ? 'Salvar alteracoes' : 'Criar pedido' ?></button>
                <a class="botao secundario" href="<?= site_url($edicao ? 'compras/ver/' . $registro['numero'] : 'compras') ?>">Cancelar</a>

                <?php if ($edicao): ?>
                    <button type="submit" class="botao secundario"
                            formaction="<?= site_url('compras/fechar/' . $registro['numero']) ?>">Fechar Pedido</button>
                    <a class="botao secundario" href="<?= site_url('compras/ver/' . $registro['numero'] . '?aba=log') ?>">Mostrar Alteracoes</a>
                    <button type="submit" class="botao pequeno perigo"
                            formaction="<?= site_url('compras/excluir/' . $registro['numero']) ?>"
                            onclick="return confirm('Excluir definitivamente o pedido <?= (int) $registro['numero'] ?>?');">Apaga</button>
                <?php endif; ?>
            </div>

        <?= form_close() ?>
    </div>
</div>

<template id="linha-pedido">
    <table><tbody>
        <tr class="linha-item">
            <td class="num numero-item">1</td>
            <td>
                <input type="text" class="produto-desc" readonly tabindex="-1">
                <input type="hidden" name="produto_id[]">
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

<script src="<?= base_url('assets/js/autocomplete.js?v=4') ?>"></script>
<script src="<?= base_url('assets/js/compras.js?v=5') ?>"></script>

<script>
(function () {
    var fone = document.getElementById('fone_responsavel');

    if (!fone) { return; }

    function mascaraTelefone(valor) {
        var digitos = valor.replace(/\D/g, '').slice(0, 11);

        if (digitos === '') { return ''; }

        var texto = '(' + digitos.slice(0, 2) + ')';

        if (digitos.length > 2) { texto += ' ' + digitos.slice(2, 7); }
        if (digitos.length > 7) { texto += '-' + digitos.slice(7, 11); }

        return texto;
    }

    fone.value = mascaraTelefone(fone.value);
    fone.addEventListener('input', function () { fone.value = mascaraTelefone(fone.value); });
})();
</script>
