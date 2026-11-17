<?php
$tem_filtro = $filtros['busca'] !== '' || $filtros['fornecedor'] > 0
           || $filtros['situacao'] !== '' || $filtros['setor'] > 0
           || $filtros['data_de'] !== '' || $filtros['data_ate'] !== '';
?>

<div class="cartoes cartoes-estoque">
    <div class="cartao">
        <div class="rotulo">Pedidos abertos</div>
        <div class="valor"><?= numero_br($stats['abertos']) ?></div>
        <div class="detalhe"><?= moeda_br($stats['valor_aberto']) ?> em aberto</div>
    </div>

    <div class="cartao verde">
        <div class="rotulo">Fechados no mes</div>
        <div class="valor"><?= moeda_br($stats['valor_mes']) ?></div>
        <div class="detalhe"><?= numero_br($stats['pedidos_mes']) ?> pedido(s) no mes</div>
    </div>

    <div class="cartao amarelo">
        <div class="rotulo">Contas a pagar</div>
        <div class="valor"><?= moeda_br($stats['cp_valor']) ?></div>
        <div class="detalhe"><?= numero_br($stats['cp_abertas']) ?> titulo(s) em aberto</div>
    </div>

    <div class="cartao">
        <div class="rotulo">Solicitacoes abertas</div>
        <div class="valor"><?= numero_br($stats['sc_abertas']) ?></div>
        <div class="detalhe"><?= numero_br($stats['cotacoes_mes']) ?> cotacao(oes) no mes</div>
    </div>
</div>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Pedidos de compra</h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('compras/cotacoes') ?>">Cotacoes</a>
            <a class="botao secundario pequeno" href="<?= site_url('compras/solicitacoes') ?>">Solicitacoes</a>
            <a class="botao secundario pequeno" href="<?= site_url('compras/prazos') ?>">Prazos</a>
            <?php if (in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE)): ?>
                <a class="botao pequeno" href="<?= site_url('compras/novo') ?>">+ Novo pedido</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="painel-corpo">
        <form method="get" action="<?= site_url('compras') ?>" class="filtros">
            <input type="text" name="busca" value="<?= html_escape($filtros['busca']) ?>" placeholder="Numero, fornecedor, NF ou observacao...">

            <select name="situacao">
                <option value="">Situacao</option>
                <option value="aberto"    <?= $filtros['situacao'] === 'aberto' ? 'selected' : '' ?>>Aberto</option>
                <option value="fechado"   <?= $filtros['situacao'] === 'fechado' ? 'selected' : '' ?>>Fechado</option>
                <option value="cancelado" <?= $filtros['situacao'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
            </select>

            <select name="setor">
                <option value="">Setor</option>
                <?php foreach ($setores as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= (int) $s['id'] === (int) $filtros['setor'] ? 'selected' : '' ?>>
                        <?= html_escape($s['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="date" name="data_de" value="<?= html_escape($filtros['data_de']) ?>" title="Data inicial">
            <input type="date" name="data_ate" value="<?= html_escape($filtros['data_ate']) ?>" title="Data final">

            <button type="submit" class="botao secundario">Pesquisar</button>

            <?php if ($tem_filtro): ?>
                <a class="botao secundario" href="<?= site_url('compras') ?>">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:80px">Numero</th>
                    <th style="width:95px">Data</th>
                    <th>Fornecedor</th>
                    <th style="width:150px">Setor</th>
                    <th class="num" style="width:60px">Itens</th>
                    <th class="num" style="width:115px">Total</th>
                    <th class="centro" style="width:100px">Situacao</th>
                    <th class="centro" style="width:150px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="8" class="vazio">Nenhum pedido encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><a href="<?= site_url('compras/ver/' . $r['numero']) ?>"><strong><?= (int) $r['numero'] ?></strong></a></td>
                            <td><?= data_br($r['data']) ?></td>
                            <td>
                                <?= html_escape($r['fornecedor_nome'] ? $r['fornecedor_nome'] : 'Sem fornecedor') ?>
                                <?php if ($r['fornecedor_fantasia']): ?>
                                    <div class="ajuda"><?= html_escape($r['fornecedor_fantasia']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= html_escape($r['setor_nome'] ? $r['setor_nome'] : '-') ?></td>
                            <td class="num"><?= numero_br($r['qtd_itens']) ?></td>
                            <td class="num"><strong><?= moeda_br($r['total']) ?></strong></td>
                            <td class="centro">
                                <?php if ($r['situacao'] === 'aberto'): ?>
                                    <span class="badge alerta">Aberto</span>
                                <?php elseif ($r['situacao'] === 'fechado'): ?>
                                    <span class="badge ok">Fechado</span>
                                <?php else: ?>
                                    <span class="badge off">Cancelado</span>
                                <?php endif; ?>
                            </td>
                            <td class="centro">
                                <a class="botao secundario pequeno" href="<?= site_url('compras/ver/' . $r['numero']) ?>">Abrir</a>
                                <?php if ($r['situacao'] === 'aberto' && in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE)): ?>
                                    <a class="botao pequeno" href="<?= site_url('compras/fechar/' . $r['numero']) ?>">Fechar</a>
                                <?php else: ?>
                                    <a class="botao secundario pequeno" href="<?= site_url('compras/imprimir_pedido/' . $r['numero']) ?>" target="_blank">Imprimir</a>
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
        'base'  => site_url('compras'),
        'busca' => $filtros['busca'],
        'extra' => array(
            'situacao' => $filtros['situacao'],
            'setor'    => $filtros['setor'] > 0 ? $filtros['setor'] : '',
            'data_de'  => $filtros['data_de'],
            'data_ate' => $filtros['data_ate'],
        ),
    )); ?>
</div>
