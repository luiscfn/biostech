<?php
$tem_filtro = $filtros['busca'] !== '' || $filtros['data_de'] !== ''
           || $filtros['data_ate'] !== '' || $filtros['com_orcamento'] !== ''
           || $filtros['com_os'] !== '';
?>
<div class="cartoes cartoes-estoque">
    <div class="cartao">
        <div class="rotulo">Dimensionamentos</div>
        <div class="valor"><?= numero_br($stats['total']) ?></div>
        <div class="detalhe"><?= numero_br($stats['sem_orcamento']) ?> ainda sem orcamento</div>
    </div>

    <div class="cartao verde">
        <div class="rotulo">Com orcamento</div>
        <div class="valor"><?= numero_br($stats['com_orcamento']) ?></div>
        <div class="detalhe">ja geraram proposta comercial</div>
    </div>

    <div class="cartao amarelo">
        <div class="rotulo">Com OS</div>
        <div class="valor"><?= numero_br($stats['com_os']) ?></div>
        <div class="detalhe">vinculados a chamados</div>
    </div>
</div>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Dimensionamentos</h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('orcamentos') ?>">Orcamentos</a>
            <?php if (in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE)): ?>
                <a class="botao pequeno" href="<?= site_url('orcamentos/dimensionamento_novo') ?>">+ Novo dimensionamento</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="painel-corpo">
        <form method="get" action="<?= site_url('orcamentos/dimensionamentos') ?>" class="filtros">
            <input type="text" name="busca" value="<?= html_escape($filtros['busca']) ?>"
                   placeholder="Numero, cliente, contato, local ou OS...">

            <input type="date" name="data_de" value="<?= html_escape($filtros['data_de']) ?>" title="Data inicial">
            <input type="date" name="data_ate" value="<?= html_escape($filtros['data_ate']) ?>" title="Data final">

            <select name="com_orcamento">
                <option value="">Orcamento</option>
                <option value="S" <?= $filtros['com_orcamento'] === 'S' ? 'selected' : '' ?>>Com orcamento</option>
                <option value="N" <?= $filtros['com_orcamento'] === 'N' ? 'selected' : '' ?>>Sem orcamento</option>
            </select>

            <select name="com_os">
                <option value="">OS</option>
                <option value="S" <?= $filtros['com_os'] === 'S' ? 'selected' : '' ?>>Com OS</option>
                <option value="N" <?= $filtros['com_os'] === 'N' ? 'selected' : '' ?>>Sem OS</option>
            </select>

            <button type="submit" class="botao secundario">Pesquisar</button>

            <?php if ($tem_filtro): ?>
                <a class="botao secundario" href="<?= site_url('orcamentos/dimensionamentos') ?>">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:75px">Numero</th>
                    <th style="width:95px">Data</th>
                    <th>Cliente / local</th>
                    <th style="width:150px">Contato</th>
                    <th style="width:150px">Tecnico</th>
                    <th class="num" style="width:55px">Itens</th>
                    <th class="num" style="width:110px">Total venda</th>
                    <th class="centro" style="width:100px">Orcamento</th>
                    <th class="centro" style="width:100px">OS</th>
                    <th class="centro" style="width:80px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="10" class="vazio">Nenhum dimensionamento encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><a href="<?= site_url('orcamentos/dimensionamento/' . $r['numero']) ?>"><strong><?= (int) $r['numero'] ?></strong></a></td>
                            <td><?= data_br($r['data']) ?></td>
                            <td>
                                <?= html_escape($r['cliente_nome'] ? $r['cliente_nome'] : 'Sem cliente') ?>
                                <?php if ( ! empty($r['local_instalacao'])): ?>
                                    <div class="ajuda"><?= html_escape($r['local_instalacao']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= html_escape($r['contato'] ? $r['contato'] : '-') ?>
                                <?php if ( ! empty($r['tipo_contato'])): ?>
                                    <div class="ajuda"><?= html_escape($r['tipo_contato']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= html_escape($r['tecnico_nome'] ? $r['tecnico_nome'] : '-') ?></td>
                            <td class="num"><?= numero_br($r['qtd_itens']) ?></td>
                            <td class="num"><strong><?= moeda_br($r['total_venda']) ?></strong></td>
                            <td class="centro">
                                <?php if ( ! empty($r['orcamento_numero'])): ?>
                                    <a href="<?= site_url('orcamentos/ver/' . $r['orcamento_numero']) ?>"><?= (int) $r['orcamento_numero'] ?></a>
                                <?php else: ?>
                                    <span class="badge alerta">pendente</span>
                                <?php endif; ?>
                            </td>
                            <td class="centro">
                                <?php if ( ! empty($r['chamado_numero'])): ?>
                                    <a href="<?= site_url('chamados/ver/' . $r['chamado_numero']) ?>"><?= html_escape($r['chamado_numero']) ?></a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="centro">
                                <a class="botao secundario pequeno" href="<?= site_url('orcamentos/dimensionamento/' . $r['numero']) ?>">Abrir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $this->load->view('layouts/paginacao', array(
        'pag'   => $pag,
        'base'  => site_url('orcamentos/dimensionamentos'),
        'busca' => $filtros['busca'],
        'extra' => array(
            'data_de'       => $filtros['data_de'],
            'data_ate'      => $filtros['data_ate'],
            'com_orcamento' => $filtros['com_orcamento'],
            'com_os'        => $filtros['com_os'],
        ),
    )); ?>
</div>
