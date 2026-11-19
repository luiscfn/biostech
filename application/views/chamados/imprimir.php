<?php
$total_pecas = 0;
foreach ($pecas as $p) { $total_pecas += (float) $p['valor_total']; }

$total_servicos = 0;
foreach ($servicos as $s) { if ($s['tipo'] === 'avulso') { $total_servicos += (float) $s['valor_total']; } }

$total_geral = $total_pecas + $total_servicos
             + (float) $registro['valor_mao_obra'] + (float) $registro['material_fixacao']
             + (float) $registro['valor_imposto'] + (float) $registro['acrescimo_custo']
             - (float) $registro['valor_desconto'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title><?= html_escape($registro['categoria']) ?> <?= html_escape($registro['numero']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #222; margin: 24px; }
        h1 { font-size: 18px; margin: 0; }
        h2 { font-size: 14px; margin: 18px 0 6px; border-bottom: 1px solid #999; padding-bottom: 3px; }
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
        <div class="numero"><?= html_escape($registro['categoria']) ?> <?= html_escape($registro['numero']) ?></div>
        <div>Abertura: <?= data_br($registro['data_abertura']) ?> <?= $registro['hora_abertura'] ? html_escape(substr((string) $registro['hora_abertura'], 0, 5)) : '' ?></div>
        <div>Status: <?= html_escape($registro['status_nome']) ?></div>
        <div>Situacao: <?= html_escape($registro['situacao_nome']) ?></div>
    </div>
</div>

<h2>Cliente</h2>
<div class="grade">
    <div class="campo" style="grid-column: span 2;">
        <div class="rotulo">Nome</div>
        <?= html_escape($registro['cliente_nome']) ?>
    </div>
    <div class="campo">
        <div class="rotulo">CPF / CNPJ</div>
        <?= html_escape($registro['cpf_cnpj'] ? $registro['cpf_cnpj'] : '-') ?>
    </div>
    <div class="campo">
        <div class="rotulo">Telefone</div>
        <?= html_escape($registro['celular'] ? $registro['celular'] : ($registro['telefone'] ? $registro['telefone'] : '-')) ?>
    </div>
</div>

<h2>Equipamento</h2>
<div class="grade">
    <div class="campo">
        <div class="rotulo">Numero de serie</div>
        <?= html_escape( ! empty($equipamento['equipamento_serie']) ? $equipamento['equipamento_serie'] : '-') ?>
    </div>
    <div class="campo">
        <div class="rotulo">Patrimonio</div>
        <?= html_escape( ! empty($equipamento['patrimonio']) ? $equipamento['patrimonio'] : '-') ?>
    </div>
    <div class="campo">
        <div class="rotulo">Garantia</div>
        <?= ! empty($equipamento['tipo_garantia_codigo']) && isset($dominios['tipo_garantia'][(int) $equipamento['tipo_garantia_codigo']])
            ? html_escape($dominios['tipo_garantia'][(int) $equipamento['tipo_garantia_codigo']])
            : '-' ?>
    </div>
    <div class="campo">
        <div class="rotulo">Nota fiscal</div>
        <?= html_escape( ! empty($equipamento['nota_fiscal_chegada']) ? $equipamento['nota_fiscal_chegada'] : '-') ?>
    </div>
</div>

<h2>Defeitos e servicos</h2>
<div class="rotulo">Defeito relatado</div>
<div class="texto"><?= html_escape($registro['defeito_relatado'] ? $registro['defeito_relatado'] : '-') ?></div>

<div class="rotulo" style="margin-top:8px">Defeito constatado</div>
<div class="texto"><?= html_escape($registro['defeito_constatado'] ? $registro['defeito_constatado'] : '-') ?></div>

<div class="rotulo" style="margin-top:8px">Servico executado</div>
<div class="texto"><?= html_escape($registro['servico_executado'] ? $registro['servico_executado'] : '-') ?></div>

<h2>Intervencoes</h2>
<table>
    <thead>
        <tr><th style="width:80px">Data</th><th style="width:55px">Hora</th><th style="width:150px">Tecnico</th><th>Descricao</th></tr>
    </thead>
    <tbody>
        <?php if (empty($intervencoes)): ?>
            <tr><td colspan="4">Nenhuma intervencao registrada.</td></tr>
        <?php else: ?>
            <?php foreach ($intervencoes as $i): ?>
                <tr>
                    <td><?= data_br($i['data_movimento']) ?></td>
                    <td><?= html_escape(substr((string) $i['hora_movimento'], 0, 5)) ?></td>
                    <td><?= html_escape($i['tecnico_nome'] ? $i['tecnico_nome'] : '-') ?></td>
                    <td class="texto"><?= html_escape($i['descricao']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<h2>Pecas</h2>
<table>
    <thead>
        <tr><th style="width:50px">Item</th><th>Produto</th><th style="width:50px">Qtd</th><th style="width:90px">Unitario</th><th style="width:90px">Total</th></tr>
    </thead>
    <tbody>
        <?php if (empty($pecas)): ?>
            <tr><td colspan="5">Nenhuma peca aplicada.</td></tr>
        <?php else: ?>
            <?php foreach ($pecas as $p): ?>
                <tr>
                    <td><?= (int) $p['numero_item'] ?></td>
                    <td><?= html_escape($p['descricao_produto']) ?></td>
                    <td class="direita"><?= (int) $p['quantidade'] ?></td>
                    <td class="direita"><?= moeda_br($p['valor_unitario']) ?></td>
                    <td class="direita"><?= moeda_br($p['valor_total']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<h2>Servicos</h2>
<table>
    <thead>
        <tr><th style="width:80px">Tipo</th><th>Descricao</th><th style="width:50px">Qtd</th><th style="width:90px">Unitario</th><th style="width:90px">Total</th></tr>
    </thead>
    <tbody>
        <?php if (empty($servicos)): ?>
            <tr><td colspan="5">Nenhum servico registrado.</td></tr>
        <?php else: ?>
            <?php foreach ($servicos as $s): ?>
                <tr>
                    <td><?= html_escape($s['tipo']) ?></td>
                    <td class="texto"><?= html_escape($s['descricao']) ?></td>
                    <td class="direita"><?= (int) $s['quantidade'] ?></td>
                    <td class="direita"><?= moeda_br($s['valor_unitario']) ?></td>
                    <td class="direita"><?= moeda_br($s['valor_total']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<h2>Totais</h2>
<table>
    <tbody>
        <tr><td style="width:70%">Pecas</td><td class="direita"><?= moeda_br($total_pecas) ?></td></tr>
        <tr><td>Servicos avulsos</td><td class="direita"><?= moeda_br($total_servicos) ?></td></tr>
        <tr><td>Mao de obra</td><td class="direita"><?= moeda_br($registro['valor_mao_obra']) ?></td></tr>
        <tr><td>Material de fixacao</td><td class="direita"><?= moeda_br($registro['material_fixacao']) ?></td></tr>
        <tr><td>Imposto</td><td class="direita"><?= moeda_br($registro['valor_imposto']) ?></td></tr>
        <tr><td>Acrescimo de custo</td><td class="direita"><?= moeda_br($registro['acrescimo_custo']) ?></td></tr>
        <tr><td>Desconto</td><td class="direita">- <?= moeda_br($registro['valor_desconto']) ?></td></tr>
        <tr><td class="total">TOTAL</td><td class="direita total"><?= moeda_br($total_geral) ?></td></tr>
    </tbody>
</table>

<div class="assinaturas">
    <div>Tecnico responsavel</div>
    <div>Cliente / recebedor</div>
</div>

</body>
</html>
