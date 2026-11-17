<?php
$melhor_total = 0.0;

foreach ($itens as $i)
{
    $preco = 0.0;

    if ($i['fornecedor_escolhido'] !== NULL)
    {
        foreach (array(1, 2, 3) as $k)
        {
            if ((int) $i['fornecedor_' . $k] === (int) $i['fornecedor_escolhido'])
            {
                $preco = (float) $i['preco_' . $k];
                break;
            }
        }
    }

    $melhor_total += (float) $i['quantidade'] * $preco;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Cotacao de precos <?= (int) $registro['codigo'] ?></title>
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
        .escolhido { background: #e8f6ed; font-weight: bold; }
        .total { font-size: 14px; font-weight: bold; }
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
        <div class="numero">Cotacao de precos <?= (int) $registro['codigo'] ?></div>
        <div>Data: <?= data_br($registro['data_cotacao']) ?></div>
        <?php if ( ! empty($registro['num_os'])): ?>
            <div>OS: <?= html_escape($registro['num_os']) ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="grade">
    <div class="campo" style="grid-column: span 2;">
        <div class="rotulo">Solicitante</div>
        <?= html_escape($registro['solicitante_nome'] ? $registro['solicitante_nome'] : '-') ?>
    </div>
    <div class="campo" style="grid-column: span 2;">
        <div class="rotulo">Responsavel pela cotacao</div>
        <?= html_escape($registro['funcionario_nome'] ? $registro['funcionario_nome'] : '-') ?>
    </div>
    <div class="campo">
        <div class="rotulo">Solicitacao interna</div>
        <?= $registro['solicitacao_numero'] !== NULL ? (int) $registro['solicitacao_numero'] : '-' ?>
    </div>
    <div class="campo" style="grid-column: span 3;">
        <div class="rotulo">Observacoes</div>
        <?= html_escape($registro['observacao'] ? $registro['observacao'] : '-') ?>
    </div>
</div>

<h2>Comparativo de propostas</h2>
<table>
    <thead>
        <tr>
            <th style="width:35px">#</th>
            <th style="width:65px">Codigo</th>
            <th>Produto</th>
            <th style="width:45px">Unid.</th>
            <th style="width:55px">Qtd</th>
            <th>Fornecedor 1</th>
            <th style="width:75px">Preco 1</th>
            <th>Fornecedor 2</th>
            <th style="width:75px">Preco 2</th>
            <th>Fornecedor 3</th>
            <th style="width:75px">Preco 3</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($itens)): ?>
            <tr><td colspan="11">Nenhum item cotado.</td></tr>
        <?php else: ?>
            <?php $n = 0; foreach ($itens as $i): $n++; ?>
                <tr>
                    <td><?= $n ?></td>
                    <td><?= html_escape($i['produto_codigo'] ? $i['produto_codigo'] : (string) $i['produto_id']) ?></td>
                    <td>
                        <?= html_escape($i['descricao']) ?>
                        <?php if ( ! empty($i['fornecedor_escolhido_nome'])): ?>
                            <div class="rotulo">escolhido: <?= html_escape($i['fornecedor_escolhido_nome']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= html_escape($i['unidade']) ?></td>
                    <td class="direita"><?= numero_br($i['quantidade'], 3) ?></td>

                    <?php foreach (array(1, 2, 3) as $k): ?>
                        <?php $este = (int) $i['fornecedor_escolhido'] === (int) $i['fornecedor_' . $k] && ! empty($i['fornecedor_' . $k]); ?>
                        <td class="<?= $este ? 'escolhido' : '' ?>">
                            <?= html_escape($i['fornecedor_' . $k . '_nome'] ? $i['fornecedor_' . $k . '_nome'] : '-') ?>
                        </td>
                        <td class="direita <?= $este ? 'escolhido' : '' ?>">
                            <?= $i['preco_' . $k] !== NULL ? moeda_br($i['preco_' . $k]) : '-' ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php if ($melhor_total > 0): ?>
    <h2>Total das propostas escolhidas</h2>
    <table>
        <tbody>
            <tr>
                <td style="width:80%">Soma dos itens com fornecedor escolhido</td>
                <td class="direita total"><?= moeda_br($melhor_total) ?></td>
            </tr>
        </tbody>
    </table>
<?php endif; ?>

<div class="assinaturas">
    <div>Responsavel pela cotacao</div>
    <div>Aprovacao</div>
</div>

</body>
</html>
