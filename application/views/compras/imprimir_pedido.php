<?php
$bruto = 0.0;
foreach ($itens as $i) { $bruto += (float) $i['valor_total']; }
$total_parcelas = 0.0;
foreach ($parcelas as $p) { $total_parcelas += (float) $p['valor']; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Pedido de compra <?= (int) $registro['numero'] ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #222; margin: 24px; }
        h1 { font-size: 18px; margin: 0; }
        h2 { font-size: 13px; margin: 16px 0 6px; border-bottom: 1px solid #999; padding-bottom: 3px; }
        .cabecalho { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .cabecalho .empresa { font-size: 11px; color: #555; }
        .numero { font-size: 16px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #bbb; padding: 4px 6px; vertical-align: top; }
        th { background: #eee; text-align: left; }
        .rotulo { color: #555; font-size: 10px; text-transform: uppercase; }
        .grade { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px 16px; margin-top: 8px; }
        .grade .campo { border-bottom: 1px dotted #bbb; padding-bottom: 4px; }
        .texto { white-space: pre-line; }
        .direita { text-align: right; }
        .total { font-size: 14px; font-weight: bold; }
        .assinaturas { margin-top: 50px; display: flex; gap: 40px; }
        .assinaturas div { flex: 1; border-top: 1px solid #333; text-align: center; padding-top: 4px; font-size: 11px; }
        .acoes { margin-bottom: 16px; }
        .acoes button { padding: 8px 16px; cursor: pointer; background: #0b3d6b; color: #fff; border: 1px solid #0b3d6b; border-radius: 8px; font-weight: 600; }
        .acoes button:hover { background: #14558f; }
        @media print {
            .acoes { display: none; }
            body { margin: 8mm; }
        }
    </style>
</head>
<body>

<div class="acoes">
    <button onclick="window.print()">Imprimir</button>
    <button onclick="window.close()">Fechar</button>
</div>

<div class="cabecalho">
    <div>
        <h1><?= APP_NOME ?></h1>
        <div class="empresa">Assistencia tecnica e comercio de equipamentos</div>
    </div>
    <div class="direita">
        <div class="numero">Pedido de compra <?= (int) $registro['numero'] ?></div>
        <div>Data: <?= data_br($registro['data']) ?><?= $registro['hora'] ? ' as ' . html_escape(substr((string) $registro['hora'], 0, 5)) : '' ?></div>
        <div>Situacao: <?= html_escape($registro['situacao']) ?></div>
        <?php if ( ! empty($registro['numero_proposta'])): ?>
            <div>Proposta: <?= html_escape($registro['numero_proposta']) ?></div>
        <?php endif; ?>
    </div>
</div>

<h2>Fornecedor</h2>
<div class="grade">
    <div class="campo" style="grid-column: span 2;">
        <div class="rotulo">Razao social</div>
        <?= html_escape($registro['fornecedor_nome'] ? $registro['fornecedor_nome'] : '-') ?>
    </div>
    <div class="campo">
        <div class="rotulo">CNPJ / CPF</div>
        <?= html_escape($registro['fornecedor_cnpj'] ? $registro['fornecedor_cnpj'] : '-') ?>
    </div>
    <div class="campo">
        <div class="rotulo">Telefone</div>
        <?= html_escape($registro['fornecedor_fone'] ? $registro['fornecedor_fone'] : '-') ?>
    </div>
    <div class="campo" style="grid-column: span 2;">
        <div class="rotulo">Contato</div>
        <?= html_escape($registro['fornecedor_contato'] ? $registro['fornecedor_contato'] : '-') ?>
    </div>
    <div class="campo">
        <div class="rotulo">Condicao de pagamento</div>
        <?= html_escape($registro['condicao_pagamento'] !== NULL ? $registro['condicao_pagamento'] : '-') ?>
    </div>
    <div class="campo">
        <div class="rotulo">Prazo de entrega</div>
        <?= $registro['prazo_entrega'] !== NULL ? (int) $registro['prazo_entrega'] . ' dia(s)' : '-' ?>
    </div>
</div>

<h2>Entrega e solicitacao</h2>
<div class="grade">
    <div class="campo">
        <div class="rotulo">Setor solicitante</div>
        <?= html_escape($registro['setor_nome'] ? $registro['setor_nome'] : '-') ?>
    </div>
    <div class="campo">
        <div class="rotulo">Funcionario</div>
        <?= html_escape($registro['funcionario_nome'] ? $registro['funcionario_nome'] : '-') ?>
    </div>
    <div class="campo" style="grid-column: span 2;">
        <div class="rotulo">Local de entrega</div>
        <?= html_escape($registro['local_entrega'] ? $registro['local_entrega'] : '-') ?>
    </div>
    <div class="campo" style="grid-column: span 2;">
        <div class="rotulo">Responsavel pelo recebimento</div>
        <?= html_escape($registro['responsavel_recebimento'] ? $registro['responsavel_recebimento'] : '-') ?>
        <?= $registro['fone_responsavel'] ? ' - ' . html_escape($registro['fone_responsavel']) : '' ?>
    </div>
    <div class="campo">
        <div class="rotulo">Solicitacao interna</div>
        <?= $registro['solicitacao_numero'] !== NULL ? (int) $registro['solicitacao_numero'] : '-' ?>
    </div>
    <div class="campo">
        <div class="rotulo">Nota fiscal</div>
        <?= html_escape($registro['num_nf'] ? $registro['num_nf'] : '-') ?>
        <?= $registro['data_nf'] ? ' - ' . data_br($registro['data_nf']) : '' ?>
    </div>
</div>

<h2>Itens do pedido</h2>
<table>
    <thead>
        <tr>
            <th style="width:40px">#</th>
            <th style="width:70px">Codigo</th>
            <th>Produto</th>
            <th style="width:50px">Unid.</th>
            <th style="width:60px">Qtd</th>
            <th style="width:80px">Unitario</th>
            <th style="width:80px">Desconto</th>
            <th style="width:85px">Total</th>
            <th style="width:120px">Obs.</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($itens)): ?>
            <tr><td colspan="9">Nenhum item no pedido.</td></tr>
        <?php else: ?>
            <?php $n = 0; foreach ($itens as $i): $n++; ?>
                <tr>
                    <td><?= $n ?></td>
                    <td><?= html_escape($i['produto_codigo'] ? $i['produto_codigo'] : (string) $i['produto_id']) ?></td>
                    <td><?= html_escape($i['descricao']) ?></td>
                    <td><?= html_escape($i['unidade']) ?></td>
                    <td class="direita"><?= numero_br($i['quantidade'], 3) ?></td>
                    <td class="direita"><?= moeda_br($i['valor_unitario']) ?></td>
                    <td class="direita"><?= moeda_br($i['desconto_item']) ?></td>
                    <td class="direita"><?= moeda_br($i['valor_total']) ?></td>
                    <td><?= html_escape($i['observacao'] ? $i['observacao'] : '') ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<h2>Totais</h2>
<table>
    <tbody>
        <tr><td style="width:70%">Total bruto dos itens</td><td class="direita"><?= moeda_br($bruto) ?></td></tr>
        <tr>
            <td>
                Desconto do pedido
                <?= $registro['desconto_percentual'] !== NULL ? ' (' . numero_br($registro['desconto_percentual'], 2) . '%)' : '' ?>
            </td>
            <td class="direita">- <?= moeda_br($registro['desconto']) ?></td>
        </tr>
        <tr><td class="total">TOTAL DO PEDIDO</td><td class="direita total"><?= moeda_br($registro['total']) ?></td></tr>
    </tbody>
</table>

<?php if ( ! empty($parcelas)): ?>
    <h2>Parcelas</h2>
    <table>
        <thead>
            <tr>
                <th style="width:60px">Parcela</th>
                <th style="width:80px">Dias</th>
                <th style="width:110px">Vencimento</th>
                <th class="direita">Valor</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($parcelas as $p): ?>
                <tr>
                    <td><?= (int) $p['numero_par'] ?></td>
                    <td><?= (int) $p['dias'] ?></td>
                    <td><?= data_br($p['vencimento']) ?></td>
                    <td class="direita"><?= moeda_br($p['valor']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="direita"><strong>Total das parcelas</strong></td>
                <td class="direita"><strong><?= moeda_br($total_parcelas) ?></strong></td>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>

<?php if ( ! empty($registro['observacao'])): ?>
    <h2>Observacoes</h2>
    <div class="texto"><?= html_escape($registro['observacao']) ?></div>
<?php endif; ?>

<div class="assinaturas">
    <div>Responsavel pela compra</div>
    <div>Fornecedor</div>
    <div>Recebedor</div>
</div>

</body>
</html>
