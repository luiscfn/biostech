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
$conta_numero = ! empty($contas) && ! empty($contas[0]['numero']) ? (int) $contas[0]['numero'] : '';

$formatar_auditoria = function ($nome, $data) {
    $nome = trim((string) $nome);
    $data = trim((string) $data);

    if ($nome === '' && $data === '') { return ''; }

    return $nome . ($data !== '' ? ' - ' . $data : '');
};
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
.pedido-grid label, .item-grid label { display:block; font-size:11px; font-weight:600; color:#2a3a55; text-transform:uppercase; letter-spacing:.3px; margin-bottom:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pedido-grid input, .pedido-grid select, .pedido-grid textarea,
.item-grid input, .item-grid select { width:100%; box-sizing:border-box; border:1px solid #7f90a8; border-radius:3px; padding:4px 6px; font-size:12px; height:26px; }
.pedido-grid input[readonly], .item-grid input[readonly] { background:#eef1f6; }
.pedido-grid textarea { height:26px; min-height:26px; resize:vertical; }
.pedido-grid .uf-campo input { width:55px; }
.pedido-auditoria { display:flex; flex-wrap:wrap; gap:24px; align-items:flex-start; margin-top:6px; }
.pedido-auditoria > div { display:flex; align-items:flex-start; gap:4px; white-space:nowrap; }
.pedido-auditoria .rotulo,
.pedido-auditoria .valor { font-size:12px; color:var(--texto); font-weight:600; }
.pedido-auditoria .valor { font-weight:400; }
.pedido-item-cadastro { border:1px solid #c9d5e4; border-radius:6px; background:#fafcff; padding:12px; margin:10px 0 14px; }
.item-grid { display:grid; grid-template-columns:4fr 0.8fr 0.9fr 0.9fr 1.2fr 0.9fr 0.8fr 0.9fr 1.1fr 1fr; gap:8px 5px; }
.item-grid .campo { grid-column:auto; min-width:0; }
.item-grid .campo-botao { display:flex; flex-direction:column; justify-content:flex-end; }
.item-grid .campo-botao .botao { width:100%; }
.item-grid .campo.i1 > label { text-align:center; }
.item-grid #item-saldo { max-width:100%; }
.item-grid #item-qtd { max-width:100%; }
.item-grid #item-valor-unit { max-width:100%; }
.item-grid #item-novo-valor { max-width:100%; }
.item-grid #item-desc-perc { max-width:100%; }
.item-grid #item-desc-valor { max-width:100%; }
.item-grid #item-saldo, .item-grid #item-qtd, .item-grid #item-valor-unit,
.item-grid #item-novo-valor, .item-grid #item-desc-perc, .item-grid #item-desc-valor { text-align:center; }
.item-total { display:block; box-sizing:border-box; height:26px; line-height:14px; background:#d9f2e6; padding:5px 8px; text-align:center; font-weight:700; border:1px solid #bfe6d4; border-radius:3px; }
.item-linha-acoes { display:flex; justify-content:space-between; align-items:center; margin-top:10px; gap:12px; }
.tabela-itens-rolagem { max-height:264px; overflow-y:auto; }
.titulo-itens-pedido { margin:0 0 6px; }
.divisor { margin:10px 0 14px; }
table.tabela-itens thead th { padding:7px 8px; font-size:11px; }
table.tabela-itens tbody td, table.tabela-itens tfoot td { padding:6px 8px; font-size:12px; }
.tabela-itens input[type=text], .tabela-itens input[type=number] { padding:4px 6px; font-size:12px; border-radius:4px; }
.transf-grid { display:grid; grid-template-columns:190px 1fr; gap:10px 12px; align-items:center; }
.transf-grid label { font-size:12px; color:#2a3a55; font-weight:600; }
.transf-grid input, .transf-grid select { width:100%; box-sizing:border-box; border:1px solid #7f90a8; border-radius:3px; padding:5px 7px; font-size:12px; height:28px; }
.transf-grid input[readonly] { background:#eef1f6; }
</style>
<div class="painel">
    <div class="painel-cabecalho">
        <div class="cabecalho-titulo">
            <h3>Pedido de Compra</h3>
            <div class="dados-pedido">
                <span>N&deg;: <strong><?= $edicao ? (int) $registro['numero'] : '-' ?></strong></span>
                <span>Data: <strong><?= data_br($data) ?></strong></span>
                <span>N&deg; SC: <strong><?= $edicao && ! empty($registro['solicitacao_numero']) ? (int) $registro['solicitacao_numero'] : '' ?></strong></span>
                <span>N&deg; conta a pagar: <strong><?= html_escape($conta_numero) ?></strong></span>
                <?php if ($edicao): ?>
                    <span style="margin-left:24px">Cadastro: <strong><?= html_escape($formatar_auditoria(
                        $registro['usuario_cadastro_nome'] ? $registro['usuario_cadastro_nome'] : $registro['usuario_cadastro'],
                        ! empty($registro['data_cadastro']) ? data_hora_br($registro['data_cadastro']) : ''
                    )) ?></strong></span>
                    <span>Altera&ccedil;&atilde;o: <strong><?= html_escape($formatar_auditoria(
                        $registro['usuario_ultima_alteracao'],
                        ! empty($registro['data_ultima_alteracao']) ? data_hora_br($registro['data_ultima_alteracao']) : ''
                    )) ?></strong></span>
                    <span>Fechamento: <strong><?= html_escape($formatar_auditoria(
                        $registro['usuario_fechamento_nome'] ? $registro['usuario_fechamento_nome'] : $registro['usuario_fechamento'],
                        ! empty($registro['data_fechamento']) ? data_hora_br($registro['data_fechamento']) : ''
                    )) ?></strong></span>
                <?php endif; ?>
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
                         data-cidade="#forn-cidade" data-uf="#forn-uf"
                         data-endereco="#forn-endereco" data-numero="#forn-numero" data-bairro="#forn-bairro">
                        <input type="text" id="fornecedor_busca" class="autocomplete-busca"
                               placeholder="Razao social, nome fantasia ou CNPJ..."
                               value="<?= $edicao ? html_escape($registro['fornecedor_nome']) : '' ?>" autocomplete="off">
                        <input type="hidden" name="fornecedor_codigo" id="fornecedor_codigo"
                               value="<?= $edicao ? (int) $registro['fornecedor_codigo'] : '' ?>">
                        <div class="autocomplete-lista"></div>
                    </div>
                </div>

                <div class="campo p4">
                    <label for="responsavel_recebimento">Recebedor</label>
                    <input type="text" name="responsavel_recebimento" id="responsavel_recebimento" maxlength="80"
                           value="<?= $edicao ? html_escape($registro['responsavel_recebimento']) : '' ?>">
                </div>

                <div class="campo p2">
                    <label for="fone_responsavel">Fone do responsavel</label>
                    <input type="text" name="fone_responsavel" id="fone_responsavel" maxlength="14"
                           value="<?= $edicao ? html_escape($registro['fone_responsavel']) : '' ?>">
                </div>

                <div class="campo p6">
                    <label>Endere&ccedil;o</label>
                    <input type="text" id="forn-endereco" readonly tabindex="-1"
                           value="<?= $edicao ? html_escape($registro['fornecedor_endereco']) : '' ?>">
                </div>

                <div class="campo p1">
                    <label>N&deg;</label>
                    <input type="text" id="forn-numero" readonly tabindex="-1"
                           value="<?= $edicao ? html_escape($registro['fornecedor_numero']) : '' ?>">
                </div>

                <div class="campo p2">
                    <label>Bairro</label>
                    <input type="text" id="forn-bairro" readonly tabindex="-1"
                           value="<?= $edicao ? html_escape($registro['fornecedor_bairro']) : '' ?>">
                </div>

                <div class="campo p2">
                    <label>Cidade</label>
                    <input type="text" id="forn-cidade" readonly tabindex="-1"
                           value="<?= $edicao ? html_escape($registro['fornecedor_cidade']) : '' ?>">
                </div>

                <div class="campo p1 uf-campo">
                    <label>UF</label>
                    <input type="text" id="forn-uf" readonly tabindex="-1"
                           value="<?= $edicao ? html_escape($registro['fornecedor_uf']) : '' ?>">
                </div>

                <div class="campo p4">
                    <label for="local_entrega">Local de entrega</label>
                    <input type="text" name="local_entrega" id="local_entrega" maxlength="200"
                           value="<?= $edicao ? html_escape($registro['local_entrega']) : '' ?>">
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

                <div class="campo p2">
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

                <div class="campo p12">
                    <label for="observacao">Observa&ccedil;&otilde;es</label>
                    <textarea name="observacao" id="observacao"><?= $edicao ? html_escape($registro['observacao']) : '' ?></textarea>
                </div>

                <div class="campo p12" style="display:none">
                    <input type="hidden" name="data" id="data" value="<?= html_escape($data) ?>">
                    <input type="hidden" name="hora" id="hora" value="<?= html_escape($hora) ?>">
                    <input type="hidden" name="tipo" value="<?= $edicao ? html_escape($registro['tipo']) : '' ?>">
                    <input type="hidden" name="data_vencimento" value="<?= $edicao ? html_escape($registro['data_vencimento']) : '' ?>">
                    <input type="hidden" name="solicitacao_numero" value="<?= $edicao && $registro['solicitacao_numero'] !== NULL ? (int) $registro['solicitacao_numero'] : '' ?>">
                    <input type="hidden" name="numero_proposta" value="<?= $edicao ? html_escape($registro['numero_proposta']) : '' ?>">
                </div>
            </div>

            <hr class="divisor">

            <h4 class="secao-titulo titulo-itens-pedido">Itens do pedido</h4>

            <div class="pedido-item-cadastro">
                <div class="item-grid">
                    <div class="campo i3">
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
                    <div class="campo i1"><label>VLR UNIT</label><input type="text" inputmode="decimal" id="item-valor-unit" placeholder="0,00"></div>
                    <div class="campo i1"><label>Novo valor</label><input type="text" inputmode="decimal" id="item-novo-valor" placeholder="0,00"></div>
                    <div class="campo i1"><label>Desc. %</label><input type="text" inputmode="decimal" id="item-desc-perc" placeholder="0,00"></div>
                    <div class="campo i1"><label>Desc. R$</label><input type="text" inputmode="decimal" id="item-desc-valor" placeholder="0,00"></div>
                    <div class="campo i1"><label>Total do item:</label><span class="item-total" id="item-total">0,00</span></div>
                    <div class="campo i1 campo-botao">
                        <label>&nbsp;</label>
                        <button type="button" class="botao pequeno" id="btn-adicionar-item-pedido">Adicionar</button>
                    </div>
                </div>
            </div>

            <div class="tabela-wrap tabela-itens-rolagem">
                <table class="tabela tabela-itens" id="tabela-pedido"
                       data-compra data-template="linha-pedido" data-grupo="pedido">
                    <thead>
                        <tr>
                            <th class="num" style="width:36px">#</th>
                            <th style="min-width:435px">Produto</th>
                            <th class="centro" style="width:58px">Unid.</th>
                            <th class="num" style="width:70px">Saldo</th>
                            <th class="num" style="width:80px">Qtd</th>
                            <th class="num" style="width:100px">Vlr unit</th>
                            <th class="num" style="width:85px">Novo VLR</th>
                            <th class="num" style="width:75px">Desc. %</th>
                            <th class="num" style="width:95px">Desc. R$</th>
                            <th class="num" style="width:105px">Total</th>
                            <th class="centro" style="width:44px"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="9" class="num"><strong>Total bruto</strong></td>
                            <td class="num"><strong><span id="pedido-bruto"><?= number_format($bruto, 2, ',', '.') ?></span></strong></td>
                            <td colspan="1"></td>
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
                    <button type="button" class="botao secundario" id="btn-transfere-item">Transfere item</button>
                    <button type="button" class="botao secundario" id="btn-fechar-pedido">Fechar Pedido</button>
                    <a class="botao secundario" href="<?= site_url('compras/ver/' . $registro['numero'] . '?aba=log') ?>">Mostrar Alteracoes</a>
                    <button type="submit" class="botao pequeno perigo"
                            formaction="<?= site_url('compras/excluir/' . $registro['numero']) ?>"
                            onclick="return confirm('Excluir definitivamente o pedido <?= (int) $registro['numero'] ?>?');">Apaga</button>
                <?php endif; ?>
            </div>

        <?= form_close() ?>
    </div>
</div>

<?php if ($edicao): ?>
    <div class="modal-confirmacao" id="modal-transfere-item" role="dialog" aria-modal="true">
        <div class="modal-confirmacao-caixa" style="width:min(640px, calc(100vw - 32px))">
            <h4>Pedido de Compras</h4>
            <p style="font-weight:700;text-align:center">Transfere Produto Para Outro Pedido de Compra</p>

            <div class="transf-grid">
                <label>Numero da Compra de Origem:</label>
                <input type="text" readonly value="<?= (int) $registro['numero'] ?>">

                <label>Data da Compra de Origem:</label>
                <input type="text" readonly value="<?= data_br($registro['data']) ?>">

                <label>Numero da Compra de Destino:</label>
                <div class="autocomplete" data-url="<?= site_url('compras/buscar_pedidos_transferencia?origem=' . (int) $registro['numero']) ?>">
                    <input type="text" class="autocomplete-busca" id="transf-destino-busca" placeholder="Numero, data ou fornecedor..." autocomplete="off">
                    <input type="hidden" id="transf-destino-num">
                    <div class="autocomplete-lista"></div>
                </div>

                <label>Data da Compra de Destino:</label>
                <input type="text" id="transf-destino-data" readonly tabindex="-1">

                <label>Fornec.:</label>
                <input type="text" id="transf-fornecedor" readonly tabindex="-1">

                <label>Produto:</label>
                <select id="transf-produto">
                    <option value="">Selecione</option>
                    <?php foreach ($itens as $i): ?>
                        <option value="<?= (int) $i['produto_id'] ?>"
                                data-qtd="<?= numero_br($i['quantidade'], 3) ?>"
                                data-preco="<?= moeda_br($i['valor_unitario']) ?>">
                            <?= html_escape($i['descricao']) ?> | Qtd: <?= numero_br($i['quantidade'], 3) ?> | Pre&ccedil;o: <?= moeda_br($i['valor_unitario']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-confirmacao-acoes" style="margin-top:18px">
                <button type="button" class="botao" id="transf-btn-transferir">Transferir Produto</button>
                <button type="button" class="botao" id="transf-btn-copiar">Copiar Produto</button>
                <button type="button" class="botao secundario" id="transf-fechar">Fechar</button>
            </div>
        </div>
    </div>

    <div class="modal-confirmacao" id="modal-confirma-transf" role="dialog" aria-modal="true">
        <div class="modal-confirmacao-caixa">
            <h4>Confirma&ccedil;&atilde;o</h4>
            <p id="transf-confirma-msg"></p>
            <div class="modal-confirmacao-acoes">
                <button type="button" class="botao" id="transf-confirma-sim">Sim</button>
                <button type="button" class="botao secundario" id="transf-confirma-nao">N&atilde;o</button>
            </div>
        </div>
    </div>

    <?= form_open('compras/transferir_item_modal', array('id' => 'form-transfere-item', 'style' => 'display:none')) ?>
        <input type="hidden" name="origem" value="<?= (int) $registro['numero'] ?>">
        <input type="hidden" name="destino" id="transf-form-destino">
        <input type="hidden" name="produto_id" id="transf-form-produto">
        <input type="hidden" name="acao" id="transf-form-acao">
    <?= form_close() ?>

    <div class="modal-confirmacao modal-fechamento" id="modal-fechar-pedido" role="dialog" aria-modal="true"
         aria-label="Fechar pedido de compra">
        <div class="modal-confirmacao-caixa modal-fechamento-caixa">
            <div id="modal-fechar-conteudo"></div>
        </div>
    </div>
<?php endif; ?>

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
            <td><input type="text" inputmode="decimal" name="quantidade[]" class="qtd" value="1" style="text-align:right"></td>
            <td><input type="text" name="valor_unitario[]" class="valor-unit" inputmode="decimal" style="text-align:right"></td>
            <td><input type="text" name="novo_valor_unitario[]" class="novo-valor" inputmode="decimal" style="text-align:right"></td>
            <td><input type="text" name="desconto_percentual[]" class="desc-perc" inputmode="decimal" style="text-align:right"></td>
            <td><input type="text" name="desconto_item[]" class="desc-item" inputmode="decimal" style="text-align:right"></td>
            <td class="num total-linha">0,00</td>
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
<script src="<?= base_url('assets/js/compras.js?v=8') ?>"></script>

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

<?php if ($edicao): ?>
<script>
(function () {
    var modal = document.getElementById('modal-transfere-item');
    var confirma = document.getElementById('modal-confirma-transf');
    var abrir = document.getElementById('btn-transfere-item');
    var fechar = document.getElementById('transf-fechar');
    var destinoBusca = document.getElementById('transf-destino-busca');
    var destinoNum = document.getElementById('transf-destino-num');
    var produto = document.getElementById('transf-produto');
    var acao = '';

    if (!modal || !abrir) { return; }

    abrir.addEventListener('click', function () {
        modal.classList.add('visivel');
        if (destinoBusca) { destinoBusca.focus(); }
    });

    function fecharModal() { modal.classList.remove('visivel'); }

    if (fechar) { fechar.addEventListener('click', fecharModal); }

    if (destinoBusca) {
        var box = destinoBusca.closest('.autocomplete');

        box.addEventListener('autocomplete:selecionar', function (ev) {
            var item = ev.detail || {};
            var data = document.getElementById('transf-destino-data');
            var fornec = document.getElementById('transf-fornecedor');

            if (data) { data.value = item.data || ''; }
            if (fornec) { fornec.value = item.fornecedor || ''; }
        });
    }

    function pedirConfirmacao(tipo) {
        var destino = destinoNum ? destinoNum.value : '';

        if (!destino) {
            window.alert('Selecione a compra de destino.');
            return;
        }

        if (!produto || !produto.value) {
            window.alert('Selecione o produto.');
            return;
        }

        acao = tipo;
        var msg = document.getElementById('transf-confirma-msg');

        if (msg) {
            msg.textContent = tipo === 'copiar'
                ? 'Confirma a cópia do Produto?'
                : 'Confirma a transferência do Produto?';
        }

        confirma.classList.add('visivel');
    }

    var botaoTransferir = document.getElementById('transf-btn-transferir');
    var botaoCopiar = document.getElementById('transf-btn-copiar');

    if (botaoTransferir) { botaoTransferir.addEventListener('click', function () { pedirConfirmacao('transferir'); }); }
    if (botaoCopiar) { botaoCopiar.addEventListener('click', function () { pedirConfirmacao('copiar'); }); }

    var sim = document.getElementById('transf-confirma-sim');
    var nao = document.getElementById('transf-confirma-nao');

    if (nao) { nao.addEventListener('click', function () { confirma.classList.remove('visivel'); }); }

    if (sim) {
        sim.addEventListener('click', function () {
            document.getElementById('transf-form-destino').value = destinoNum.value;
            document.getElementById('transf-form-produto').value = produto.value;
            document.getElementById('transf-form-acao').value = acao;
            document.getElementById('form-transfere-item').submit();
        });
    }
})();
</script>

<script>
(function () {
    var abrir  = document.getElementById('btn-fechar-pedido');
    var modal  = document.getElementById('modal-fechar-pedido');
    var corpo  = document.getElementById('modal-fechar-conteudo');

    if (!abrir || !modal || !corpo) { return; }

    function fechar() {
        modal.classList.remove('visivel');
    }

    function ligarBotoesFechar() {
        var botoes = corpo.querySelectorAll('[data-fechar-modal]');

        for (var i = 0; i < botoes.length; i++) {
            botoes[i].addEventListener('click', fechar);
        }
    }

    abrir.addEventListener('click', function () {
        corpo.innerHTML = '<p style="padding:20px">Carregando...</p>';
        modal.classList.add('visivel');

        fetch('<?= site_url('compras/fechar_modal/' . $registro['numero']) ?>', {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (resposta) {
            if (!resposta.ok) { throw new Error('erro'); }
            return resposta.text();
        })
        .then(function (html) {
            corpo.innerHTML = html;

            if (window.ComprasFechamento) {
                window.ComprasFechamento.iniciar(corpo);
            }

            ligarBotoesFechar();

            var campo = corpo.querySelector('[data-quantidade-parcelas]');
            if (campo) { campo.focus(); }
        })
        .catch(function () {
            corpo.innerHTML = '<p style="padding:20px">Nao foi possivel carregar o fechamento.</p>';
            ligarBotoesFechar();
        });
    });

    modal.addEventListener('click', function (ev) {
        if (ev.target === modal) { fechar(); }
    });

    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && modal.classList.contains('visivel')) { fechar(); }
    });
})();
</script>
<?php endif; ?>
