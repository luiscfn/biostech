<?php
$total_pago = 0.0;

foreach ($baixas as $b)
{
    $total_pago += (float) $b['valor_pago'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Comprovante de pagamento <?= (int) $registro['numero'] ?>/<?= (int) $registro['parcela'] ?></title>
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
        <div class="numero">Conta a pagar <?= (int) $registro['numero'] ?>/<?= (int) $registro['parcela'] ?></div>
        <div>Emissao: <?= data_br($registro['emissao']) ?></div>
        <div>Vencimento: <?= data_br($registro['vencimento']) ?></div>
        <div>
            <?php if ((int) $registro['situacao'] === 2): ?>
                Baixada
            <?php elseif ((int) $registro['situacao'] === 3 || bd_bool($registro['cancelado'])): ?>
                Cancelada
            <?php else: ?>
                Aberta
            <?php endif; ?>
        </div>
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
        <div class="rotulo">Documento</div>
        <?= html_escape($registro['documento'] ? $registro['documento'] : '-') ?>
    </div>
    <div class="campo" style="grid-column: span 2;">
        <div class="rotulo">Centro de custo</div>
        <?= html_escape($registro['centro_custo_nome'] ? $registro['centro_custo_nome'] : '-') ?>
    </div>
    <div class="campo">
        <div class="rotulo">Banco / Agencia / Conta</div>
        <?= html_escape($registro['banco'] ? $registro['banco'] : '-') ?>
        <?= $registro['agencia'] ? ' / ' . html_escape($registro['agencia']) : '' ?>
        <?= $registro['conta'] ? ' / ' . html_escape($registro['conta']) : '' ?>
    </div>
    <div class="campo">
        <div class="rotulo">Origem</div>
        <?= html_escape($registro['origem'] ? $registro['origem'] : '-') ?>
    </div>
</div>

<h2>Historico</h2>
<div class="texto"><?= html_escape($registro['historico'] ? $registro['historico'] : '-') ?></div>

<h2>Pagamentos</h2>
<table>
    <thead>
        <tr>
            <th>Data</th>
            <th class="direita">Multa</th>
            <th class="direita">Juros</th>
            <th class="direita">Desconto</th>
            <th class="direita">Valor atualizado</th>
            <th class="direita">Valor pago</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($baixas)): ?>
            <tr><td colspan="6">Nenhum pagamento registrado.</td></tr>
        <?php else: ?>
            <?php foreach ($baixas as $b): ?>
                <tr>
                    <td><?= data_br($b['data_pagamento']) ?></td>
                    <td class="direita"><?= moeda_br($b['multa']) ?></td>
                    <td class="direita"><?= moeda_br($b['juros']) ?></td>
                    <td class="direita"><?= moeda_br($b['desconto']) ?></td>
                    <td class="direita"><?= moeda_br($b['valor_atualizado']) ?></td>
                    <td class="direita"><?= moeda_br($b['valor_pago']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<table style="margin-top:12px;width:auto;margin-left:auto">
    <tr>
        <th>Valor do titulo</th>
        <td class="direita"><?= moeda_br($registro['valor']) ?></td>
    </tr>
    <tr>
        <th>Total pago</th>
        <td class="direita total"><?= moeda_br($total_pago) ?></td>
    </tr>
</table>

<div class="assinaturas">
    <div>Responsavel pelo pagamento</div>
    <div>Fornecedor / Recebedor</div>
</div>

</body>
</html>
