<?php
$consulta = ! empty($consulta);
$na_ficha = ( ! $consulta && ! empty($registro));

$base  = $na_ficha
    ? site_url('clientes/orcamentos/' . (int) $registro['id'])
    : site_url('clientes/orcamentos');

$extra = ( ! $na_ficha && ! empty($registro))
    ? array('cliente' => (int) $registro['id'])
    : array();

$limpar = $na_ficha
    ? site_url('clientes/orcamentos/' . (int) $registro['id'])
    : site_url('clientes/orcamentos');

$rotulo_situacao = array();
foreach ($situacoes as $s) { $rotulo_situacao[(int) $s['codigo']] = $s['nome']; }

$rotulo_resultado = array();
foreach ($resultados as $r) { $rotulo_resultado[(int) $r['codigo']] = $r['nome']; }
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3><?= $na_ficha ? 'Orcamentos' : 'Orcamentos do cliente' ?></h3>

        <div class="acoes-ficha">
            <?php if ( ! empty($registro)): ?>
                <span class="badge info"><?= html_escape($registro['nome']) ?></span>
                <a class="botao secundario pequeno" href="<?= site_url('clientes/ver/' . (int) $registro['id']) ?>">Voltar ao cliente</a>
            <?php endif; ?>
        </div>
    </div>

    <?php $this->load->view('clientes/_abas', array('registro' => $registro, 'aba' => 'orcamentos')); ?>

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
                   placeholder="Numero, atencao ou OS..."
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
                    <tr><td class="vazio">Selecione o cliente acima para consultar os orcamentos.</td></tr>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="tabela-wrap">
            <table class="tabela">
                <thead>
                    <tr>
                        <th style="width:75px">Numero</th>
                        <th style="width:95px">Data</th>
                        <th>Atencao</th>
                        <th class="centro" style="width:95px">OS</th>
                        <th class="num" style="width:55px">Itens</th>
                        <th class="num" style="width:110px">Total</th>
                        <th class="centro" style="width:105px">Situacao</th>
                        <th class="centro" style="width:150px">Resultado</th>
                        <th class="centro" style="width:90px">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registros)): ?>
                        <tr><td colspan="9" class="vazio">Nenhum orcamento encontrado para este cliente.</td></tr>
                    <?php else: ?>
                        <?php foreach ($registros as $r): ?>
                            <tr>
                                <td>
                                    <a href="<?= site_url('orcamentos/ver/' . $r['numero']) ?>"><strong><?= (int) $r['numero'] ?></strong></a>
                                </td>
                                <td><?= data_br($r['data']) ?></td>
                                <td><?= html_escape($r['atencao'] ? $r['atencao'] : '-') ?></td>
                                <td class="centro">
                                    <?php if ( ! empty($r['chamado_numero'])): ?>
                                        <a href="<?= site_url('chamados/ver/' . $r['chamado_numero']) ?>"><?= html_escape($r['chamado_numero']) ?></a>
                                    <?php elseif ( ! empty($r['chamado_numero_legado'])): ?>
                                        <span title="OS nao existente na base de chamados"><?= html_escape($r['chamado_numero_legado']) ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="num"><?= numero_br($r['qtd_itens']) ?></td>
                                <td class="num"><strong><?= moeda_br($r['valor_total']) ?></strong></td>
                                <td class="centro">
                                    <?php if ($r['situacao'] === NULL): ?>
                                        <span class="badge off">-</span>
                                    <?php else: ?>
                                        <?php
                                        $rotulo = isset($rotulo_situacao[(int) $r['situacao']])
                                            ? $rotulo_situacao[(int) $r['situacao']]
                                            : $r['situacao_nome'];
                                        $classe = ((int) $r['situacao'] === 5) ? 'off' : (((int) $r['situacao'] === 4) ? 'ok' : 'alerta');
                                        ?>
                                        <span class="badge <?= $classe ?>"><?= html_escape($rotulo) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="centro">
                                    <?php if ($r['resultado'] === NULL): ?>
                                        <span class="badge off">-</span>
                                    <?php else: ?>
                                        <?php
                                        $rotulo = isset($rotulo_resultado[(int) $r['resultado']])
                                            ? $rotulo_resultado[(int) $r['resultado']]
                                            : $r['resultado_nome'];
                                        $classe = ((int) $r['resultado'] === 1)
                                            ? 'ok'
                                            : (in_array((int) $r['resultado'], array(2, 6, 7), TRUE) ? 'off' : 'info');
                                        ?>
                                        <span class="badge <?= $classe ?>"><?= html_escape($rotulo) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="centro">
                                    <a class="botao pequeno" href="<?= site_url('orcamentos/ver/' . $r['numero']) ?>">Abrir</a>
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
