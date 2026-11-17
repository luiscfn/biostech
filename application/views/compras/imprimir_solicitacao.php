<?php
$total_itens = 0;

foreach ($itens as $i)
{
    $total_itens++;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Solicitacao interna <?= (int) $registro['numero'] ?></title>
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
        .centro { text-align: center; }
        .assinaturas { margin-top: 50px; display: flex; gap: 40px; }
        .assinaturas div { flex: 1; border-top: 1px solid #333; text-align: center; padding-top: 4px; font-size: 11px; }
        .acoes { margin-bottom: 16px; }
        .acoes button { padding: 8px 16px; cursor: pointer; }
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
        <div class="numero">Solicitacao interna <?= (int) $registro['numero'] ?></div>
        <div>Data: <?= data_br($registro['data']) ?><?= $registro['hora'] ? ' as ' . html_escape(substr((string) $registro['hora'], 0, 5)) : '' ?></div>
        <div>Situacao: <?= html_escape($registro['situacao']) ?></div>
        <?php if ( ! empty($registro['pedido_numero'])): ?>
            <div>Pedido gerado: <?= (int) $registro['pedido_numero'] ?></div>
        <?php endif; ?>
        <?php if ( ! empty($registro['cotacao_codigo'])): ?>
            <div>Cotacao: <?= (int) $registro['cotacao_codigo'] ?></div>
        <?php endif; ?>
    </div>
</div>

<h2>Solicitante</h2>
<div class="grade">
    <div class="campo" style="grid-column: span 2;">
        <div class="rotulo">Setor</div>
        <?= html_escape($registro['setor_nome'] ? $registro['setor_nome'] : '-') ?>
    </div>
    <div class="campo" style="grid-column: span 2;">
        <div class="rotulo">Funcionario</div>
        <?= html_escape($registro['funcionario_nome'] ? $registro['funcionario_nome'] : '-') ?>
    </div>
    <div class="campo" style="grid-column: span 2;">
        <div class="rotulo">Local de entrega</div>
        <?= html_escape($registro['local_entrega'] ? $registro['local_entrega'] : '-') ?>
    </div>
    <div class="campo">
        <div class="rotulo">Responsavel pelo recebimento</div>
        <?= html_escape($registro['responsavel_recebimento'] ? $registro['responsavel_recebimento'] : '-') ?>
    </div>
    <div class="campo">
        <div class="rotulo">Telefone</div>
        <?= html_escape($registro['fone_responsavel'] ? $registro['fone_responsavel'] : '-') ?>
    </div>
</div>

<h2>Itens solicitados (<?= $total_itens ?>)</h2>
<table>
    <thead>
        <tr>
            <th style="width:35px">#</th>
            <th style="width:70px">Codigo</th>
            <th>Produto</th>
            <th style="width:50px">Unid.</th>
            <th style="width:65px">Saldo</th>
            <th style="width:65px">Qtd</th>
            <th style="width:90px">Utilizacao</th>
            <th style="width:90px">Periodo</th>
            <th style="width:50px">Repor</th>
            <th style="width:55px">Marcado</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($itens)): ?>
            <tr><td colspan="10">Nenhum item solicitado.</td></tr>
        <?php else: ?>
            <?php $n = 0; foreach ($itens as $i): $n++; ?>
                <tr>
                    <td><?= $n ?></td>
                    <td><?= html_escape($i['produto_codigo'] ? $i['produto_codigo'] : (string) $i['produto_id']) ?></td>
                    <td><?= html_escape($i['descricao']) ?></td>
                    <td><?= html_escape($i['unidade']) ?></td>
                    <td class="direita"><?= numero_br($i['saldo'], 3) ?></td>
                    <td class="direita"><?= numero_br($i['quantidade'], 3) ?></td>
                    <td><?= data_br($i['data_utilizacao']) ?></td>
                    <td><?= html_escape($i['periodo'] ? $i['periodo'] : '-') ?></td>
                    <td class="centro"><?= bd_bool($i['repor_estoque']) ? 'Sim' : 'Nao' ?></td>
                    <td class="centro"><?= bd_bool($i['selecionado']) ? 'Sim' : 'Nao' ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php if ( ! empty($registro['observacao'])): ?>
    <h2>Observacoes</h2>
    <div class="texto"><?= html_escape($registro['observacao']) ?></div>
<?php endif; ?>

<div class="assinaturas">
    <div>Solicitante</div>
    <div>Aprovacao</div>
    <div>Recebedor</div>
</div>

</body>
</html>
