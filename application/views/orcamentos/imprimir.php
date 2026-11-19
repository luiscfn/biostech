<?php
$total_itens = 0.0;
foreach ($itens as $i) { $total_itens += (float) $i['valor_total']; }

$total_serv_valores = 0.0;
foreach ($servicos_valores as $s) { $total_serv_valores += (float) $s['valor_total']; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Orcamento <?= (int) $registro['numero'] ?></title>
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
        <div class="numero">Orcamento <?= (int) $registro['numero'] ?></div>
        <div>Data: <?= data_br($registro['data']) ?></div>
        <?php if ( ! empty($registro['validade'])): ?>
            <div>Validade: <?= (int) $registro['validade'] ?> dia(s)</div>
        <?php endif; ?>
        <?php if ( ! empty($registro['situacao_nome'])): ?>
            <div>Situacao: <?= html_escape($registro['situacao_nome']) ?></div>
        <?php endif; ?>
    </div>
</div>

<h2>Cliente</h2>
<div class="grade">
    <div class="campo" style="grid-column: span 2;">
        <div class="rotulo">Nome / razao social</div>
        <?= html_escape($registro['cliente_nome'] ? $registro['cliente_nome'] : ($registro['atencao'] ? $registro['atencao'] : '-')) ?>
        <?php if ( ! empty($registro['cliente_fantasia'])): ?>
            <div><?= html_escape($registro['cliente_fantasia']) ?></div>
        <?php endif; ?>
    </div>
    <div class="campo">
        <div class="rotulo">CPF / CNPJ</div>
        <?= html_escape($registro['cliente_documento'] ? $registro['cliente_documento'] : '-') ?>
    </div>
    <div class="campo">
        <div class="rotulo">Telefone</div>
        <?= html_escape($registro['cliente_telefone'] ? $registro['cliente_telefone'] : '-') ?>
    </div>
    <div class="campo" style="grid-column: span 3;">
        <div class="rotulo">Endereco</div>
        <?= html_escape(trim(
            (string) $registro['cliente_endereco'] . ' ' . (string) $registro['cliente_numero']
            . ' ' . (string) $registro['cliente_complemento']
        )) ?>
        <?= $registro['cliente_bairro'] ? ' - ' . html_escape($registro['cliente_bairro']) : '' ?>
        <?= $registro['cliente_cidade'] ? ' - ' . html_escape($registro['cliente_cidade']) . '/' . html_escape($registro['cliente_uf']) : '' ?>
    </div>
    <div class="campo">
        <div class="rotulo">Atencao</div>
        <?= html_escape($registro['atencao'] ? $registro['atencao'] : '-') ?>
    </div>
</div>

<h2>Condicoes</h2>
<div class="grade">
    <div class="campo">
        <div class="rotulo">Condicao de pagamento</div>
        <?= $registro['condicao_pagamento'] !== NULL ? (int) $registro['condicao_pagamento'] : '-' ?>
    </div>
    <div class="campo">
        <div class="rotulo">Entrega</div>
        <?= $registro['entrega'] !== NULL ? (int) $registro['entrega'] . ' dia(s)' : '-' ?>
    </div>
    <div class="campo">
        <div class="rotulo">Garantia</div>
        <?= $registro['garantia'] !== NULL ? (int) $registro['garantia'] . ' dia(s)' : '-' ?>
    </div>
    <div class="campo">
        <div class="rotulo">Validade das pecas</div>
        <?= $registro['validade_pecas'] !== NULL ? (int) $registro['validade_pecas'] . ' dia(s)' : '-' ?>
    </div>
    <div class="campo">
        <div class="rotulo">Equipamento / serie</div>
        <?= html_escape($registro['equipamento_serie'] ? $registro['equipamento_serie'] : '-') ?>
    </div>
    <div class="campo">
        <div class="rotulo">Nota fiscal de chegada</div>
        <?= html_escape($registro['nota_fiscal_chegada'] ? $registro['nota_fiscal_chegada'] : '-') ?>
    </div>
    <div class="campo" style="grid-column: span 2;">
        <div class="rotulo">Chamado / OS</div>
        <?= html_escape($registro['chamado_numero'] ? $registro['chamado_numero']
            : ( ! empty($registro['chamado_numero_legado']) ? $registro['chamado_numero_legado'] : '-')) ?>
    </div>
</div>

<?php if ( ! empty($itens)): ?>
    <h2>Material</h2>
    <table>
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th>Descricao</th>
                <th style="width:55px">Unid.</th>
                <th class="direita" style="width:65px">Qtd</th>
                <th class="direita" style="width:90px">Valor unit.</th>
                <th class="direita" style="width:95px">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itens as $item): ?>
                <tr>
                    <td><?= (int) $item['numero_item'] ?></td>
                    <td>
                        <?= html_escape($item['descricao_produto'] ? $item['descricao_produto'] : $item['descricao']) ?>
                        <?php if ( ! empty($item['descricao']) && $item['descricao_produto']): ?>
                            <div style="color:#666"><?= html_escape($item['descricao']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= html_escape($item['produto_unidade'] ? $item['produto_unidade'] : '') ?></td>
                    <td class="direita"><?= numero_br($item['quantidade']) ?></td>
                    <td class="direita"><?= moeda_br($item['valor_unitario']) ?></td>
                    <td class="direita"><?= moeda_br($item['valor_total']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="direita"><strong>Total do material</strong></td>
                <td class="direita"><strong><?= moeda_br($total_itens) ?></strong></td>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>

<?php if ( ! empty($servicos)): ?>
    <h2>Servicos a executar</h2>
    <table>
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th>Descricao</th>
                <th style="width:80px">Concluido</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($servicos as $servico): ?>
                <tr>
                    <td><?= (int) $servico['numero_item'] ?></td>
                    <td><?= html_escape($servico['descricao']) ?></td>
                    <td><?= bd_bool($servico['concluido']) ? 'Sim' : 'Nao' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if ( ! empty($servicos_valores)): ?>
    <h2>Servicos com valor</h2>
    <table>
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th>Descricao</th>
                <th class="direita" style="width:65px">Qtd</th>
                <th class="direita" style="width:90px">Valor unit.</th>
                <th class="direita" style="width:95px">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($servicos_valores as $servico): ?>
                <tr>
                    <td><?= (int) $servico['numero_item'] ?></td>
                    <td><?= html_escape($servico['descricao']) ?></td>
                    <td class="direita"><?= numero_br($servico['quantidade']) ?></td>
                    <td class="direita"><?= moeda_br($servico['valor_unitario']) ?></td>
                    <td class="direita"><?= moeda_br($servico['valor_total']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="direita"><strong>Total dos servicos</strong></td>
                <td class="direita"><strong><?= moeda_br($total_serv_valores) ?></strong></td>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>

<h2>Resumo</h2>
<table>
    <tbody>
        <tr><td>Material</td><td class="direita"><?= moeda_br($registro['valor_material']) ?></td></tr>
        <tr><td>Mao de obra</td><td class="direita"><?= moeda_br($registro['valor_mao_obra']) ?></td></tr>
        <tr><td>Material de fixacao</td><td class="direita"><?= moeda_br($registro['material_fixacao']) ?></td></tr>
        <tr><td>Acrescimo de custo</td><td class="direita"><?= moeda_br($registro['acrescimo_custo']) ?></td></tr>
        <tr><td>Imposto / nota fiscal</td><td class="direita"><?= moeda_br($registro['valor_imposto']) ?></td></tr>
        <tr><td>Servicos com valor</td><td class="direita"><?= moeda_br($registro['valor_servico']) ?></td></tr>
        <tr><td>Desconto</td><td class="direita">- <?= moeda_br($registro['valor_desconto']) ?></td></tr>
        <tr>
            <td class="total">Total do orcamento</td>
            <td class="direita total"><?= moeda_br($registro['valor_total']) ?></td>
        </tr>
    </tbody>
</table>

<?php if ( ! empty($registro['observacao'])): ?>
    <h2>Observacoes</h2>
    <div class="texto"><?= nl2br(html_escape($registro['observacao'])) ?></div>
<?php endif; ?>

<div class="assinaturas">
    <div><?= html_escape($registro['assinatura'] ? $registro['assinatura'] : 'BIOSTECH') ?></div>
    <div>Cliente</div>
</div>

</body>
</html>
