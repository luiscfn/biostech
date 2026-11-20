<?php
$tem_filtro = $filtros['busca'] !== '' || $filtros['cliente'] > 0
           || $filtros['situacao'] !== '' || $filtros['resultado'] !== ''
           || $filtros['data_de'] !== '' || $filtros['data_ate'] !== ''
           || $filtros['com_os'] !== '' || $filtros['com_dim'] !== '';

$rotulo_situacao = array();
foreach ($situacoes as $s) { $rotulo_situacao[(int) $s['codigo']] = $s['nome']; }
$rotulo_resultado = array();
foreach ($resultados as $r) { $rotulo_resultado[(int) $r['codigo']] = $r['nome']; }
?>
<div class="cartoes cartoes-estoque">
    <div class="cartao">
        <div class="rotulo">Orcamentos abertos</div>
        <div class="valor"><?= numero_br($stats['abertos']) ?></div>
        <div class="detalhe"><?= numero_br($stats['total']) ?> orcamento(s) no total</div>
    </div>

    <div class="cartao verde">
        <div class="rotulo">Aprovados</div>
        <div class="valor"><?= numero_br($stats['aprovados']) ?></div>
        <div class="detalhe">resultado Aprovado</div>
    </div>

    <div class="cartao amarelo">
        <div class="rotulo">Em aberto (valor)</div>
        <div class="valor"><?= moeda_br($stats['valor_aberto']) ?></div>
        <div class="detalhe"><?= numero_br($stats['encerrados']) ?> encerrado(s)</div>
    </div>

    <div class="cartao">
        <div class="rotulo">Integracoes</div>
        <div class="valor"><?= numero_br($stats['com_os']) ?> / <?= numero_br($stats['com_dim']) ?></div>
        <div class="detalhe">com OS / com dimensionamento</div>
    </div>
</div>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Orcamentos</h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('orcamentos/dimensionamentos') ?>">Dimensionamentos</a>
            <?php if (in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE)): ?>
                <a class="botao pequeno" href="<?= site_url('orcamentos/novo') ?>">+ Novo orcamento</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="painel-corpo">
        <form method="get" action="<?= site_url('orcamentos') ?>" class="filtros">
            <input type="text" name="busca" value="<?= html_escape($filtros['busca']) ?>"
                   placeholder="Numero, atencao, cliente ou OS...">

            <div class="autocomplete" data-url="<?= site_url('orcamentos/buscar_clientes') ?>" style="min-width:490px">
                <input type="text" class="autocomplete-busca" placeholder="Cliente..."
                       value="<?= $filtros['cliente'] > 0 ? html_escape($filtros['cliente_nome']) : '' ?>"
                       autocomplete="off">
                <input type="hidden" name="cliente" value="<?= $filtros['cliente'] > 0 ? (int) $filtros['cliente'] : '' ?>">
                <div class="autocomplete-lista"></div>
            </div>

            <input type="date" name="data_de" value="<?= html_escape($filtros['data_de']) ?>" title="Data inicial">
            <input type="date" name="data_ate" value="<?= html_escape($filtros['data_ate']) ?>" title="Data final">

            <button type="submit" class="botao secundario">Pesquisar</button>

            <?php if ($tem_filtro): ?>
                <a class="botao secundario" href="<?= site_url('orcamentos') ?>">Limpar</a>
            <?php endif; ?>

            <span class="quebra-filtros"></span>

            <select name="situacao">
                <option value="">Situacao</option>
                <?php foreach ($situacoes as $s): ?>
                    <option value="<?= (int) $s['codigo'] ?>" <?= $filtros['situacao'] === (string) $s['codigo'] ? 'selected' : '' ?>>
                        <?= html_escape($s['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="resultado">
                <option value="">Resultado</option>
                <?php foreach ($resultados as $r): ?>
                    <option value="<?= (int) $r['codigo'] ?>" <?= $filtros['resultado'] === (string) $r['codigo'] ? 'selected' : '' ?>>
                        <?= html_escape($r['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="com_os">
                <option value="">OS</option>
                <option value="S" <?= $filtros['com_os'] === 'S' ? 'selected' : '' ?>>Com OS</option>
                <option value="N" <?= $filtros['com_os'] === 'N' ? 'selected' : '' ?>>Sem OS</option>
            </select>

            <select name="com_dim">
                <option value="">Dimensionamento</option>
                <option value="S" <?= $filtros['com_dim'] === 'S' ? 'selected' : '' ?>>Com dimensionamento</option>
                <option value="N" <?= $filtros['com_dim'] === 'N' ? 'selected' : '' ?>>Sem dimensionamento</option>
            </select>
        </form>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:75px">Numero</th>
                    <th style="width:95px">Data</th>
                    <th>Cliente / atencao</th>
                    <th class="centro" style="width:95px">OS</th>
                    <th class="centro" style="width:85px">Orc dim</th>
                    <th class="num" style="width:110px">Total</th>
                    <th class="centro" style="width:105px">Situacao</th>
                    <th class="centro" style="width:150px">Resultado</th>
                    <th class="centro" style="width:105px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="9" class="vazio">Nenhum orcamento encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><a href="<?= site_url('orcamentos/ver/' . $r['numero']) ?>"><strong><?= (int) $r['numero'] ?></strong></a></td>
                            <td><?= data_br($r['data']) ?></td>
                            <td>
                                <?= html_escape($r['cliente_nome'] ? $r['cliente_nome'] : ($r['atencao'] ? $r['atencao'] : 'Sem cliente')) ?>
                                <?php if ($r['cliente_fantasia']): ?>
                                    <div class="ajuda"><?= html_escape($r['cliente_fantasia']) ?></div>
                                <?php elseif ($r['atencao'] && $r['cliente_nome']): ?>
                                    <div class="ajuda"><?= html_escape($r['atencao']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="centro">
                                <?php if ( ! empty($r['chamado_numero'])): ?>
                                    <a href="<?= site_url('chamados/ver/' . $r['chamado_numero']) ?>"><?= html_escape($r['chamado_numero']) ?></a>
                                <?php elseif ( ! empty($r['chamado_numero_legado'])): ?>
                                    <span title="OS nao existente na base de chamados"><?= html_escape($r['chamado_numero_legado']) ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="centro">
                                <?php if ( ! empty($r['dimensionamento_numero'])): ?>
                                    <a href="<?= site_url('orcamentos/dimensionamento/' . (int) $r['dimensionamento_numero']) ?>"><?= (int) $r['dimensionamento_numero'] ?></a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="num"><strong><?= moeda_br($r['valor_total']) ?></strong></td>
                            <td class="centro">
                                <?php if ($r['situacao'] === NULL): ?>
                                    <span class="badge off">-</span>
                                <?php elseif ((int) $r['situacao'] === 5): ?>
                                    <span class="badge off"><?= html_escape(isset($rotulo_situacao[(int) $r['situacao']]) ? $rotulo_situacao[(int) $r['situacao']] : $r['situacao_nome']) ?></span>
                                <?php elseif ((int) $r['situacao'] === 4): ?>
                                    <span class="badge ok"><?= html_escape(isset($rotulo_situacao[(int) $r['situacao']]) ? $rotulo_situacao[(int) $r['situacao']] : $r['situacao_nome']) ?></span>
                                <?php else: ?>
                                    <span class="badge alerta"><?= html_escape(isset($rotulo_situacao[(int) $r['situacao']]) ? $rotulo_situacao[(int) $r['situacao']] : $r['situacao_nome']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="centro">
                                <?php if ($r['resultado'] === NULL): ?>
                                    <span class="badge off">-</span>
                                <?php elseif ((int) $r['resultado'] === 1): ?>
                                    <span class="badge ok">APROVADO</span>
                                <?php elseif (in_array((int) $r['resultado'], array(2, 6, 7), TRUE)): ?>
                                    <span class="badge off"><?= html_escape(isset($rotulo_resultado[(int) $r['resultado']]) ? $rotulo_resultado[(int) $r['resultado']] : $r['resultado_nome']) ?></span>
                                <?php else: ?>
                                    <span class="badge info"><?= html_escape(isset($rotulo_resultado[(int) $r['resultado']]) ? $rotulo_resultado[(int) $r['resultado']] : $r['resultado_nome']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="centro">
                                <a class="botao secundario pequeno" href="<?= site_url('orcamentos/ver/' . $r['numero']) ?>">Abrir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $this->load->view('layouts/paginacao', array(
        'pag'   => $pag,
        'base'  => site_url('orcamentos'),
        'busca' => $filtros['busca'],
        'extra' => array(
            'cliente'   => $filtros['cliente'] > 0 ? $filtros['cliente'] : '',
            'cliente_nome' => $filtros['cliente_nome'],
            'situacao'  => $filtros['situacao'],
            'resultado' => $filtros['resultado'],
            'data_de'   => $filtros['data_de'],
            'data_ate'  => $filtros['data_ate'],
            'com_os'    => $filtros['com_os'],
            'com_dim'   => $filtros['com_dim'],
        ),
    )); ?>
</div>

<script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
