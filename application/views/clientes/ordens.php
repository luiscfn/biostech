<?php
$consulta = ! empty($consulta);
$na_ficha = ( ! $consulta && ! empty($registro));

$base  = $na_ficha
    ? site_url('clientes/ordens/' . (int) $registro['id'])
    : site_url('clientes/ordens');

$extra = ( ! $na_ficha && ! empty($registro))
    ? array('cliente' => (int) $registro['id'])
    : array();

$limpar = $na_ficha
    ? site_url('clientes/ordens/' . (int) $registro['id'])
    : site_url('clientes/ordens');
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3><?= $na_ficha ? 'Ordens de servico' : 'Ordens de servico do cliente' ?></h3>

        <div class="acoes-ficha">
            <?php if ( ! empty($registro)): ?>
                <span class="badge info"><?= html_escape($registro['nome']) ?></span>
                <a class="botao secundario pequeno" href="<?= site_url('clientes/ver/' . (int) $registro['id']) ?>">Voltar ao cliente</a>
            <?php endif; ?>
        </div>
    </div>

    <?php $this->load->view('clientes/_abas', array('registro' => $registro, 'aba' => 'ordens')); ?>

    <div class="painel-corpo">
        <form method="get" action="<?= $base ?>" class="filtros filtros-largos">
            <?php if (empty($registro)): ?>
                <div class="autocomplete" data-url="<?= site_url('clientes/buscar_clientes') ?>" style="flex:4 1 320px; min-width:300px">
                    <input type="text" class="autocomplete-busca" placeholder="Cliente: nome, fantasia ou CNPJ..." autocomplete="off" style="max-width:none">
                    <input type="hidden" name="cliente" value="">
                    <div class="autocomplete-lista"></div>
                </div>
            <?php elseif ($consulta): ?>
                <input type="hidden" name="cliente" value="<?= (int) $registro['id'] ?>">
            <?php endif; ?>

            <input type="text" name="busca" value="<?= html_escape($busca) ?>"
                   placeholder="Numero, serie do equipamento ou defeito..."
                   <?php if (empty($registro)): ?>style="flex:1 1 180px; max-width:260px; min-width:140px"<?php endif; ?>>

            <button type="submit" class="botao">Pesquisar</button>

            <?php if ($busca !== '' || $extra !== array()): ?>
                <a class="botao secundario" href="<?= $limpar ?>">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($registro)): ?>
        <div class="tabela-wrap">
            <table class="tabela">
                <tbody>
                    <tr><td class="vazio">Selecione o cliente acima para consultar as ordens de servico.</td></tr>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="tabela-wrap">
            <table class="tabela">
                <thead>
                    <tr>
                        <th style="width:104px">Numero</th>
                        <th style="width:88px">Abertura</th>
                        <th style="width:92px">Atendimento</th>
                        <th style="width:92px">Fechamento</th>
                        <th class="centro" style="width:92px">Tipo</th>
                        <th>Equipamento</th>
                        <th class="compacta" style="width:96px">Status</th>
                        <th class="compacta" style="width:120px">Situacao atual</th>
                        <th class="num" style="width:100px">Total</th>
                        <th class="centro" style="width:84px">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registros)): ?>
                        <tr><td colspan="10" class="vazio">Nenhuma ordem de servico encontrada para este cliente.</td></tr>
                    <?php else: ?>
                        <?php foreach ($registros as $r): ?>
                            <?php
                            $total = (float) $r['valor_material'] + (float) $r['valor_servico']
                                   + (float) $r['valor_mao_obra'] - (float) $r['valor_desconto'];
                            ?>
                            <tr>
                                <td>
                                    <a href="<?= site_url('chamados/ver/' . $r['numero']) ?>"><strong><?= html_escape($r['numero']) ?></strong></a>
                                </td>
                                <td><?= data_br($r['data_abertura']) ?></td>
                                <td><?= data_br($r['atendimento_data']) ?></td>
                                <td><?= data_br($r['data_fechamento']) ?></td>
                                <td class="centro">
                                    <span class="badge <?= $r['categoria'] === 'Chamado' ? 'info' : 'alerta' ?>">
                                        <?= html_escape($r['categoria']) ?>
                                    </span>
                                </td>
                                <td><?= html_escape($r['equipamento_serie'] ? $r['equipamento_serie'] : '-') ?></td>
                                <td class="compacta"><?= html_escape($r['status_nome'] ? $r['status_nome'] : '-') ?></td>
                                <td class="compacta"><?= html_escape($r['situacao_nome'] ? $r['situacao_nome'] : '-') ?></td>
                                <td class="num"><?= moeda_br($total) ?></td>
                                <td class="centro">
                                    <a class="botao pequeno" href="<?= site_url('chamados/ver/' . $r['numero']) ?>">Abrir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php $this->load->view('layouts/paginacao', array(
            'pag'   => $pag,
            'base'  => $base,
            'busca' => $busca,
            'extra' => $extra,
        )); ?>
    <?php endif; ?>
</div>

<script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
