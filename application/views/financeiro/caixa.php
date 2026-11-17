<?php
$tem_filtro = $filtros['data_de'] !== '' || $filtros['data_ate'] !== '' || $filtros['fechado'] !== '';
?>
<div class="cartoes">
    <div class="cartao">
        <div class="rotulo">Saldo atual do caixa</div>
        <div class="valor"><?= moeda_br($saldo) ?></div>
        <div class="detalhe">Ultimo fechamento registrado</div>
    </div>

    <div class="cartao">
        <div class="rotulo">Dias listados</div>
        <div class="valor"><?= numero_br($pag['total']) ?></div>
        <div class="detalhe">Historico de caixa (Access)</div>
    </div>
</div>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Caixa diario</h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('financeiro') ?>">Painel</a>
            <a class="botao pequeno" href="<?= site_url('financeiro/caixa_dia/' . date('Y-m-d')) ?>">Caixa de hoje</a>
        </div>
    </div>

    <div class="painel-corpo">
        <form method="get" action="<?= site_url('financeiro/caixa') ?>" class="filtros">
            <input type="date" name="data_de" value="<?= html_escape($filtros['data_de']) ?>" title="De">
            <input type="date" name="data_ate" value="<?= html_escape($filtros['data_ate']) ?>" title="Ate">

            <select name="fechado">
                <option value="">Situacao</option>
                <option value="S" <?= $filtros['fechado'] === 'S' ? 'selected' : '' ?>>Fechado</option>
                <option value="N" <?= $filtros['fechado'] === 'N' ? 'selected' : '' ?>>Aberto</option>
            </select>

            <button type="submit" class="botao secundario">Pesquisar</button>

            <?php if ($tem_filtro): ?>
                <a class="botao secundario" href="<?= site_url('financeiro/caixa') ?>">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th>Data</th>
                    <th class="num">Abertura</th>
                    <th class="num">Entradas</th>
                    <th class="num">Saidas</th>
                    <th class="num">Fechamento</th>
                    <th class="centro">Situacao</th>
                    <th class="centro" style="width:100px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="7" class="vazio">Nenhum dia de caixa encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><strong><?= data_br($r['data']) ?></strong></td>
                            <td class="num"><?= moeda_br($r['saldo_anterior']) ?></td>
                            <td class="num"><?= moeda_br($r['total_entradas']) ?></td>
                            <td class="num"><?= moeda_br($r['total_saidas']) ?></td>
                            <td class="num"><strong><?= moeda_br($r['saldo_atual']) ?></strong></td>
                            <td class="centro">
                                <?php if (bd_bool($r['fechado'])): ?>
                                    <span class="badge ok">Fechado</span>
                                <?php else: ?>
                                    <span class="badge alerta">Aberto</span>
                                <?php endif; ?>
                            </td>
                            <td class="centro">
                                <a class="botao secundario pequeno" href="<?= site_url('financeiro/caixa_dia/' . $r['data']) ?>">Abrir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $this->load->view('layouts/paginacao', array(
        'pag'   => $pag,
        'base'  => site_url('financeiro/caixa'),
        'busca' => '',
        'extra' => array(
            'data_de'  => $filtros['data_de'],
            'data_ate' => $filtros['data_ate'],
            'fechado'  => $filtros['fechado'],
        ),
    )); ?>
</div>
