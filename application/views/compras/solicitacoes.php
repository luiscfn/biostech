<?php
$tem_filtro = $filtros['busca'] !== '' || $filtros['situacao'] !== ''
           || (int) $filtros['setor'] > 0
           || $filtros['data_de'] !== '' || $filtros['data_ate'] !== '';

$rotulos = array(
    'aberta'   => array('Aberta', 'alerta'),
    'fechada'  => array('Fechada', 'ok'),
    'cancelada'=> array('Cancelada', 'off'),
);
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>Solicitacoes internas de compra</h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('compras') ?>">Pedidos</a>
            <?php if (in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE)): ?>
                <a class="botao pequeno" href="<?= site_url('compras/solicitacao') ?>">+ Nova solicitacao</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="painel-corpo">
        <form method="get" action="<?= site_url('compras/solicitacoes') ?>" class="filtros">
            <input type="text" name="busca" value="<?= html_escape($filtros['busca']) ?>"
                   style="width:330px;min-width:330px"
                   placeholder="Numero, solicitante ou local de entrega...">

            <select name="situacao">
                <option value="">Situacao</option>
                <option value="aberta"    <?= $filtros['situacao'] === 'aberta' ? 'selected' : '' ?>>Aberta</option>
                <option value="fechada"   <?= $filtros['situacao'] === 'fechada' ? 'selected' : '' ?>>Fechada</option>
                <option value="cancelada" <?= $filtros['situacao'] === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
            </select>

            <select name="setor">
                <option value="">Setor</option>
                <?php foreach ($setores as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= (int) $s['id'] === (int) $filtros['setor'] ? 'selected' : '' ?>>
                        <?= html_escape($s['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="date" name="data_de" value="<?= html_escape($filtros['data_de']) ?>" title="Data inicial" style="height:37px">
            <input type="date" name="data_ate" value="<?= html_escape($filtros['data_ate']) ?>" title="Data final" style="height:37px">

            <button type="submit" class="botao secundario">Pesquisar</button>

            <?php if ($tem_filtro): ?>
                <a class="botao secundario" href="<?= site_url('compras/solicitacoes') ?>">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="tabela-wrap" style="overflow-y:auto">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:80px">Numero</th>
                    <th style="width:95px">Data</th>
                    <th>Setor</th>
                    <th>Solicitante</th>
                    <th>Local de entrega</th>
                    <th class="centro" style="width:100px">Situacao</th>
                    <th class="num" style="width:60px">Itens</th>
                    <th class="centro" style="width:90px">Pedido</th>
                    <th class="centro" style="width:160px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="9" class="vazio">Nenhuma solicitacao encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <?php
                        $situacao = isset($rotulos[$r['situacao']]) ? $rotulos[$r['situacao']] : array($r['situacao'], 'off');
                        ?>
                        <tr>
                            <td>
                                <a href="<?= site_url('compras/solicitacao/' . $r['numero']) ?>">
                                    <strong><?= (int) $r['numero'] ?></strong>
                                </a>
                            </td>
                            <td><?= data_br($r['data']) ?></td>
                            <td><?= html_escape($r['setor_nome'] ? $r['setor_nome'] : '-') ?></td>
                            <td><?= html_escape($r['funcionario_nome'] ? $r['funcionario_nome'] : '-') ?></td>
                            <td><?= html_escape($r['local_entrega'] ? $r['local_entrega'] : '-') ?></td>
                            <td class="centro"><span class="badge <?= $situacao[1] ?>"><?= html_escape($situacao[0]) ?></span></td>
                            <td class="num"><?= numero_br($r['qtd_itens']) ?></td>
                            <td class="centro">
                                <?php if ( ! empty($r['pedido_numero'])): ?>
                                    <a href="<?= site_url('compras/ver/' . $r['pedido_numero']) ?>"><?= (int) $r['pedido_numero'] ?></a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="centro">
                                <a class="botao secundario pequeno" href="<?= site_url('compras/solicitacao/' . $r['numero']) ?>">Abrir</a>
                                <a class="botao secundario pequeno" href="<?= site_url('compras/imprimir_solicitacao/' . $r['numero']) ?>" target="_blank">Imprimir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $this->load->view('layouts/paginacao', array(
        'pag'   => $pag,
        'base'  => site_url('compras/solicitacoes'),
        'busca' => $filtros['busca'],
        'extra' => array(
            'situacao' => $filtros['situacao'],
            'setor'    => (int) $filtros['setor'] > 0 ? (int) $filtros['setor'] : '',
            'data_de'  => $filtros['data_de'],
            'data_ate' => $filtros['data_ate'],
        ),
    )); ?>
</div>
