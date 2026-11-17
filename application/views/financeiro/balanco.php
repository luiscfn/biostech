<?php
$pode_editar = in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE);

$tem_filtro = $filtros['de'] !== '' || $filtros['ate'] !== '';
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>Balanco por periodo</h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('financeiro') ?>">Painel</a>
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/relatorios?tipo=balanco') ?>">Relatorio</a>
        </div>
    </div>

    <div class="painel-corpo">
        <form method="get" action="<?= site_url('financeiro/balanco') ?>" class="filtros">
            <input type="date" name="de" value="<?= html_escape($filtros['de']) ?>" title="Periodo de">
            <input type="date" name="ate" value="<?= html_escape($filtros['ate']) ?>" title="Periodo ate">

            <button type="submit" class="botao secundario">Pesquisar</button>

            <?php if ($tem_filtro): ?>
                <a class="botao secundario" href="<?= site_url('financeiro/balanco') ?>">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($pode_editar): ?>
        <div class="painel-corpo">
            <?= form_open('financeiro/balanco_gerar') ?>
                <div class="form-grid">
                    <div class="campo terco">
                        <label for="inicio">Inicio do periodo *</label>
                        <input type="date" name="inicio" id="inicio" value="<?= date('Y-m-01') ?>" required>
                    </div>

                    <div class="campo terco">
                        <label for="fim">Fim do periodo *</label>
                        <input type="date" name="fim" id="fim" value="<?= date('Y-m-t') ?>" required>
                    </div>

                    <div class="campo terco">
                        <label>&nbsp;</label>
                        <button type="submit" class="botao">Gerar balanco do periodo</button>
                    </div>
                </div>
            <?= form_close() ?>
        </div>
    <?php endif; ?>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:105px">Periodo</th>
                    <th style="width:80px">Mes/Ano</th>
                    <th class="num">Bruto faturado</th>
                    <th class="num">ISS faturado</th>
                    <th class="num">Liquido faturado</th>
                    <th class="num">Bruto a faturar</th>
                    <th class="num">Pago no periodo</th>
                    <th class="num">A pagar</th>
                    <th class="centro" style="width:90px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="9" class="vazio">Nenhum balanco encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td>
                                <?= data_br($r['data_inicio']) ?>
                                <div class="ajuda">ate <?= data_br($r['data_fim']) ?></div>
                            </td>
                            <td><?= html_escape($r['mes_ano'] ? $r['mes_ano'] : '-') ?></td>
                            <td class="num"><?= moeda_br($r['bruto_faturado']) ?></td>
                            <td class="num"><?= moeda_br($r['iss_faturado']) ?></td>
                            <td class="num"><strong><?= moeda_br($r['liquido_faturado']) ?></strong></td>
                            <td class="num"><?= moeda_br($r['bruto_faturar']) ?></td>
                            <td class="num"><?= moeda_br($r['bruto_pago']) ?></td>
                            <td class="num"><?= moeda_br($r['bruto_pagar']) ?></td>
                            <td class="centro">
                                <?php if ($pode_editar): ?>
                                    <?= form_open('financeiro/balanco_excluir/' . $r['id'], array('style' => 'display:inline')) ?>
                                        <button type="submit" class="botao perigo pequeno">Excluir</button>
                                    <?= form_close() ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $this->load->view('layouts/paginacao', array(
        'pag'   => $pag,
        'base'  => site_url('financeiro/balanco'),
        'busca' => '',
        'extra' => array(
            'de'  => $filtros['de'],
            'ate' => $filtros['ate'],
        ),
    )); ?>
</div>
