<?php
$tem_filtro = $filtros['busca'] !== '' || $filtros['grupo'] !== '' || $filtros['subgrupo'] !== ''
           || $filtros['estante'] !== '' || $filtros['situacao'] !== '';
?>

<div class="cartoes-estoque">
    <div class="cartao">
        <div class="rotulo">Produtos</div>
        <div class="valor"><?= numero_br($stats['produtos']) ?></div>
        <div class="detalhe"><?= numero_br($stats['ativos']) ?> ativos</div>
    </div>

    <div class="cartao amarelo">
        <div class="rotulo">Abaixo do minimo</div>
        <div class="valor"><?= numero_br($stats['abaixo_minimo']) ?></div>
        <div class="detalhe">repor no almoxarifado</div>
    </div>

    <div class="cartao vermelho">
        <div class="rotulo">Sem saldo</div>
        <div class="valor"><?= numero_br($stats['zerados']) ?></div>
        <div class="detalhe">saldo zero</div>
    </div>

    <div class="cartao verde">
        <div class="rotulo">Valor em estoque</div>
        <div class="valor"><?= moeda_br($stats['valor_estoque']) ?></div>
        <div class="detalhe">custo x saldo</div>
    </div>

    <div class="cartao">
        <div class="rotulo">Movimentos no mes</div>
        <div class="valor"><?= numero_br($stats['movimentos_mes']) ?></div>
        <div class="detalhe">entradas, saidas e ajustes</div>
    </div>
</div>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Saldos em estoque</h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('estoque/movimentos') ?>">Movimentos</a>
            <a class="botao secundario pequeno" href="<?= site_url('estoque/fornecedores') ?>">Fornecedores</a>
            <a class="botao secundario pequeno" href="<?= site_url('estoque/catalogos') ?>">Catalogos</a>
            <a class="botao secundario pequeno" href="<?= site_url('estoque/ajuste') ?>">Ajuste</a>
            <a class="botao secundario pequeno" href="<?= site_url('estoque/saida') ?>">- Saida</a>
            <a class="botao pequeno" href="<?= site_url('estoque/entrada') ?>">+ Entrada</a>
        </div>
    </div>

    <div class="painel-corpo">
        <form method="get" action="<?= site_url('estoque') ?>" class="filtros">
            <input type="text" name="busca" value="<?= html_escape($filtros['busca']) ?>" placeholder="Codigo, descricao ou marca...">

            <select name="grupo">
                <option value="">Grupo</option>
                <?php foreach ($grupos as $g): ?>
                    <option value="<?= html_escape($g['codigo']) ?>" <?= (string) $g['codigo'] === $filtros['grupo'] ? 'selected' : '' ?>>
                        <?= html_escape($g['codigo'] . ' - ' . $g['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="subgrupo" value="<?= html_escape($filtros['subgrupo']) ?>" placeholder="Subgrupo" style="max-width:90px">

            <select name="estante">
                <option value="">Estante</option>
                <?php foreach ($estantes as $e): ?>
                    <option value="<?= (int) $e['codigo'] ?>" <?= (string) $e['codigo'] === $filtros['estante'] ? 'selected' : '' ?>>
                        <?= html_escape($e['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="situacao">
                <option value="">Situacao</option>
                <option value="ativos"   <?= $filtros['situacao'] === 'ativos' ? 'selected' : '' ?>>Somente ativos</option>
                <option value="inativos" <?= $filtros['situacao'] === 'inativos' ? 'selected' : '' ?>>Inativos</option>
                <option value="abaixo"   <?= $filtros['situacao'] === 'abaixo' ? 'selected' : '' ?>>Abaixo do minimo</option>
                <option value="zerado"   <?= $filtros['situacao'] === 'zerado' ? 'selected' : '' ?>>Saldo zero</option>
                <option value="saldo"    <?= $filtros['situacao'] === 'saldo' ? 'selected' : '' ?>>Com saldo</option>
            </select>

            <select name="ordem">
                <option value="produto" <?= $ordem === 'produto' ? 'selected' : '' ?>>Ordem: produto</option>
                <option value="saldo"   <?= $ordem === 'saldo' ? 'selected' : '' ?>>Ordem: saldo</option>
                <option value="menor"   <?= $ordem === 'menor' ? 'selected' : '' ?>>Menor saldo</option>
                <option value="maior"   <?= $ordem === 'maior' ? 'selected' : '' ?>>Maior saldo</option>
                <option value="valor"   <?= $ordem === 'valor' ? 'selected' : '' ?>>Maior valor</option>
                <option value="codigo"  <?= $ordem === 'codigo' ? 'selected' : '' ?>>Codigo</option>
            </select>

            <button type="submit" class="botao secundario">Pesquisar</button>

            <?php if ($tem_filtro || $ordem !== ''): ?>
                <a class="botao secundario" href="<?= site_url('estoque') ?>">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:80px">Codigo</th>
                    <th>Produto</th>
                    <th style="width:150px">Grupo / Subgrupo</th>
                    <th style="width:130px">Local</th>
                    <th class="num" style="width:80px">Saldo</th>
                    <th class="num" style="width:75px">Minimo</th>
                    <th class="num" style="width:105px">Custo</th>
                    <th class="num" style="width:105px">Venda</th>
                    <th class="num" style="width:110px">Total</th>
                    <th class="centro" style="width:80px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="10" class="vazio">Nenhum produto encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <?php
                        $saldo    = (int) $r['saldo'];
                        $minimo   = (int) $r['estoque_minimo'];
                        $abaixo   = bd_bool($r['ativo']) && $saldo <= $minimo;
                        $valor    = $saldo * (float) $r['valor_compra'];
                        ?>
                        <tr>
                            <td><?= html_escape($r['codigo'] ? $r['codigo'] : '-') ?></td>
                            <td>
                                <a href="<?= site_url('estoque/ver/' . $r['id']) ?>"><strong><?= html_escape($r['descricao']) ?></strong></a>
                                <?php if ( ! bd_bool($r['ativo'])): ?>
                                    <span class="badge off">inativo</span>
                                <?php endif; ?>
                                <?php if ($r['marca']): ?>
                                    <div class="ajuda"><?= html_escape($r['marca']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= html_escape($r['grupo_nome'] ? $r['grupo_nome'] : '-') ?>
                                <?php if ($r['subgrupo_nome']): ?>
                                    <div class="ajuda"><?= html_escape($r['subgrupo_nome']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= html_escape($r['estante_nome'] ? $r['estante_nome'] : '-') ?>
                                <?php if ($r['prateleira_nome']): ?>
                                    <div class="ajuda"><?= html_escape($r['prateleira_nome']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="num">
                                <?php if ($saldo <= 0): ?>
                                    <span class="badge erro"><?= numero_br($saldo) ?></span>
                                <?php elseif ($abaixo): ?>
                                    <span class="badge alerta"><?= numero_br($saldo) ?></span>
                                <?php else: ?>
                                    <strong><?= numero_br($saldo) ?></strong>
                                <?php endif; ?>
                            </td>
                            <td class="num"><?= numero_br($minimo) ?></td>
                            <td class="num"><?= moeda_br($r['valor_compra']) ?></td>
                            <td class="num"><?= moeda_br($r['valor_venda']) ?></td>
                            <td class="num"><?= moeda_br($valor) ?></td>
                            <td class="centro">
                                <a class="botao secundario pequeno" href="<?= site_url('estoque/ver/' . $r['id']) ?>">Abrir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $this->load->view('layouts/paginacao', array(
        'pag'   => $pag,
        'base'  => site_url('estoque'),
        'busca' => $filtros['busca'],
        'ordem' => $ordem,
        'extra' => array(
            'grupo'    => $filtros['grupo'],
            'subgrupo' => $filtros['subgrupo'],
            'estante'  => $filtros['estante'],
            'situacao' => $filtros['situacao'],
        ),
    )); ?>
</div>
