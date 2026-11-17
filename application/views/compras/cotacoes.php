<?php
$tem_filtro = $filtros['busca'] !== '' || $filtros['data_de'] !== '' || $filtros['data_ate'] !== '';
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>Cotacoes de precos</h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('compras') ?>">Pedidos</a>
            <a class="botao secundario pequeno" href="<?= site_url('compras/solicitacoes') ?>">Solicitacoes</a>
            <?php if (in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE)): ?>
                <a class="botao pequeno" href="<?= site_url('compras/cotacao') ?>">+ Nova cotacao</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="painel-corpo">
        <form method="get" action="<?= site_url('compras/cotacoes') ?>" class="filtros">
            <input type="text" name="busca" value="<?= html_escape($filtros['busca']) ?>" placeholder="Numero, OS, observacao ou produto...">
            <input type="date" name="data_de" value="<?= html_escape($filtros['data_de']) ?>" title="Data inicial">
            <input type="date" name="data_ate" value="<?= html_escape($filtros['data_ate']) ?>" title="Data final">
            <button type="submit" class="botao secundario">Pesquisar</button>

            <?php if ($tem_filtro): ?>
                <a class="botao secundario" href="<?= site_url('compras/cotacoes') ?>">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:80px">Cotacao</th>
                    <th style="width:100px">Data</th>
                    <th style="width:130px">OS</th>
                    <th>Funcionario</th>
                    <th class="num" style="width:70px">Itens</th>
                    <th>Observacao</th>
                    <th class="centro" style="width:150px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="7" class="vazio">Nenhuma cotacao encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><a href="<?= site_url('compras/cotacao/' . $r['codigo']) ?>"><strong><?= (int) $r['codigo'] ?></strong></a></td>
                            <td><?= data_br($r['data_cotacao']) ?></td>
                            <td><?= html_escape($r['num_os'] ? $r['num_os'] : '-') ?></td>
                            <td><?= html_escape($r['funcionario_nome'] ? $r['funcionario_nome'] : '-') ?></td>
                            <td class="num"><?= numero_br($r['qtd_itens']) ?></td>
                            <td><?= html_escape($r['observacao'] ? $r['observacao'] : '-') ?></td>
                            <td class="centro">
                                <a class="botao secundario pequeno" href="<?= site_url('compras/cotacao/' . $r['codigo']) ?>">Abrir</a>
                                <a class="botao secundario pequeno" href="<?= site_url('compras/imprimir_cotacao/' . $r['codigo']) ?>" target="_blank">Imprimir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $this->load->view('layouts/paginacao', array(
        'pag'   => $pag,
        'base'  => site_url('compras/cotacoes'),
        'busca' => $filtros['busca'],
        'extra' => array(
            'data_de'  => $filtros['data_de'],
            'data_ate' => $filtros['data_ate'],
        ),
    )); ?>
</div>
