<?php
$total_compra = 0.0;
$total_venda  = 0.0;
foreach ($itens as $item)
{
    $total_compra += (float) $item['total_compra'];
    $total_venda  += (float) $item['total_venda'];
}

$dias = array(
    'segunda' => 'Segunda', 'terca' => 'Terca', 'quarta' => 'Quarta',
    'quinta' => 'Quinta', 'sexta' => 'Sexta', 'sabado' => 'Sabado', 'domingo' => 'Domingo',
);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Dimensionamento <?= (int) $registro['numero'] ?></title>
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
        <div class="numero">Dimensionamento <?= (int) $registro['numero'] ?></div>
        <div>Data: <?= data_br($registro['data']) ?></div>
        <?php if ( ! empty($registro['chamado_numero'])): ?>
            <div>Chamado: <?= html_escape($registro['chamado_numero']) ?></div>
        <?php endif; ?>
    </div>
</div>

<h2>Cliente</h2>
<div class="grade">
    <div class="campo" style="grid-column: span 2;">
        <div class="rotulo">Cliente</div>
        <?= html_escape($registro['cliente_nome'] ? $registro['cliente_nome'] : '-') ?>
    </div>
    <div class="campo">
        <div class="rotulo">CPF / CNPJ</div>
        <?= html_escape($registro['cliente_documento'] ? $registro['cliente_documento'] : '-') ?>
    </div>
    <div class="campo">
        <div class="rotulo">Telefone</div>
        <?= html_escape($registro['cliente_telefone'] ? $registro['cliente_telefone'] : '-') ?>
    </div>
    <div class="campo" style="grid-column: span 2;">
        <div class="rotulo">Local de instalacao</div>
        <?= html_escape($registro['local_instalacao'] ? $registro['local_instalacao'] : '-') ?>
    </div>
    <div class="campo">
        <div class="rotulo">Contato</div>
        <?= html_escape($registro['contato'] ? $registro['contato'] : '-') ?>
    </div>
    <div class="campo">
        <div class="rotulo">Tecnico</div>
        <?= html_escape($registro['tecnico_nome'] ? $registro['tecnico_nome'] : '-') ?>
    </div>
</div>

<h2>Levantamento tecnico</h2>
<table>
    <tbody>
        <tr><td style="width:22%">O que funciona</td><td class="texto"><?= nl2br(html_escape($registro['que_funciona'] ? $registro['que_funciona'] : '-')) ?></td></tr>
        <tr><td>O que quer fazer</td><td class="texto"><?= nl2br(html_escape($registro['quer_fazer'] ? $registro['quer_fazer'] : '-')) ?></td></tr>
        <tr><td>Dificuldades</td><td class="texto"><?= nl2br(html_escape($registro['dificuldades'] ? $registro['dificuldades'] : '-')) ?></td></tr>
        <tr><td>Material necessario</td><td class="texto"><?= nl2br(html_escape($registro['material_precisa'] ? $registro['material_precisa'] : '-')) ?></td></tr>
        <tr><td>Providenciar</td><td class="texto"><?= nl2br(html_escape($registro['providenciar'] ? $registro['providenciar'] : '-')) ?></td></tr>
        <tr><td>Observacoes</td><td class="texto"><?= nl2br(html_escape($registro['observacao'] ? $registro['observacao'] : '-')) ?></td></tr>
    </tbody>
</table>

<h2>Medidas</h2>
<div class="grade">
    <div class="campo"><div class="rotulo">Altura</div><?= $registro['altura'] !== NULL ? numero_br($registro['altura'], 2) . ' m' : '-' ?></div>
    <div class="campo"><div class="rotulo">Comprimento do muro</div><?= $registro['comprimento_muro'] !== NULL ? numero_br($registro['comprimento_muro'], 2) . ' m' : '-' ?></div>
    <div class="campo"><div class="rotulo">Frente</div><?= $registro['frente'] !== NULL ? numero_br($registro['frente'], 2) : '-' ?></div>
    <div class="campo"><div class="rotulo">Fundo</div><?= $registro['fundo'] !== NULL ? numero_br($registro['fundo'], 2) : '-' ?></div>
    <div class="campo"><div class="rotulo">Lado direito</div><?= $registro['lado_direito'] !== NULL ? numero_br($registro['lado_direito'], 2) : '-' ?></div>
    <div class="campo"><div class="rotulo">Lado esquerdo</div><?= $registro['lado_esquerdo'] !== NULL ? numero_br($registro['lado_esquerdo'], 2) : '-' ?></div>
    <div class="campo">
        <div class="rotulo">Caracteristicas</div>
        <?= bd_bool($registro['rebocado']) ? 'Rebocado; ' : '' ?>
        <?= bd_bool($registro['cerca_arame']) ? 'Cerca de arame; ' : '' ?>
        <?= bd_bool($registro['telhado']) ? 'Telhado; ' : '' ?>
        <?= bd_bool($registro['arvore']) ? 'Arvore; ' : '' ?>
        <?= bd_bool($registro['cinta_cimento']) ? 'Cinta de cimento; ' : '' ?>
        <?= bd_bool($registro['pega_ladrao']) ? 'Pega ladrao; ' : '' ?>
        <?= ( ! bd_bool($registro['rebocado']) && ! bd_bool($registro['cerca_arame']) && ! bd_bool($registro['telhado'])
             && ! bd_bool($registro['arvore']) && ! bd_bool($registro['cinta_cimento']) && ! bd_bool($registro['pega_ladrao'])) ? '-' : '' ?>
    </div>
</div>

<h2>Cronograma</h2>
<table>
    <thead>
        <tr><th>Dia</th><th class="direita">Trabalha</th><th class="direita">Inicio</th><th class="direita">Fim</th><th class="direita">Horas</th></tr>
    </thead>
    <tbody>
        <?php foreach ($dias as $chave => $rotulo): ?>
            <?php $inicio = (float) $registro[$chave . '_inicio']; $fim = (float) $registro[$chave . '_fim']; ?>
            <tr>
                <td><?= html_escape($rotulo) ?></td>
                <td class="direita"><?= bd_bool($registro[$chave]) ? 'Sim' : 'Nao' ?></td>
                <td class="direita"><?= $inicio > 0 ? numero_br($inicio, 2) : '-' ?></td>
                <td class="direita"><?= $fim > 0 ? numero_br($fim, 2) : '-' ?></td>
                <td class="direita"><?= ($fim > $inicio) ? numero_br($fim - $inicio, 2) : '-' ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if ( ! empty($itens)): ?>
    <h2>Itens / materiais</h2>
    <table>
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th>Produto</th>
                <th style="width:55px">Unid.</th>
                <th class="direita" style="width:60px">Qtd</th>
                <th class="direita" style="width:90px">Compra</th>
                <th class="direita" style="width:95px">Total compra</th>
                <th class="direita" style="width:90px">Venda</th>
                <th class="direita" style="width:95px">Total venda</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itens as $item): ?>
                <tr>
                    <td><?= (int) $item['numero_item'] ?></td>
                    <td><?= html_escape($item['descricao_produto'] ? $item['descricao_produto'] : $item['descricao']) ?></td>
                    <td><?= html_escape($item['produto_unidade'] ? $item['produto_unidade'] : '') ?></td>
                    <td class="direita"><?= numero_br($item['quantidade']) ?></td>
                    <td class="direita"><?= moeda_br($item['valor_compra']) ?></td>
                    <td class="direita"><?= moeda_br($item['total_compra']) ?></td>
                    <td class="direita"><?= moeda_br($item['valor_venda']) ?></td>
                    <td class="direita"><?= moeda_br($item['total_venda']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="direita"><strong>Totais</strong></td>
                <td class="direita"><strong><?= moeda_br($total_compra) ?></strong></td>
                <td></td>
                <td class="direita"><strong><?= moeda_br($total_venda) ?></strong></td>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>

<?php if ( ! empty($servicos)): ?>
    <h2>Servicos a executar</h2>
    <table>
        <thead>
            <tr><th style="width:40px">#</th><th>Descricao</th><th style="width:80px">Concluido</th></tr>
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

<?php if ( ! empty($materiais)): ?>
    <h2>Materiais previstos</h2>
    <table>
        <thead>
            <tr><th style="width:40px">#</th><th>Material</th><th class="direita" style="width:100px">Quantidade</th></tr>
        </thead>
        <tbody>
            <?php foreach ($materiais as $material): ?>
                <tr>
                    <td><?= (int) $material['numero_item'] ?></td>
                    <td><?= html_escape($material['descricao']) ?></td>
                    <td class="direita"><?= numero_br($material['quantidade'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div class="assinaturas">
    <div>Tecnico responsavel</div>
    <div>Cliente</div>
</div>

</body>
</html>
