<div class="painel">
    <div class="painel-cabecalho">
        <h3>Catalogo de produtos</h3>

        <div class="filtros">
            <form method="get" action="<?= site_url('produtos') ?>" class="filtros">
                <input type="text" name="busca" value="<?= html_escape($busca) ?>" placeholder="Descricao, codigo, marca, grupo...">
                <button type="submit" class="botao secundario">Pesquisar</button>
                <?php if ($busca !== ''): ?>
                    <a class="botao secundario" href="<?= site_url('produtos') ?>">Limpar</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:80px">Codigo</th>
                    <th>Descricao</th>
                    <th style="width:70px" class="centro">Un.</th>
                    <th style="width:90px">Grupo</th>
                    <th style="width:120px">Localizacao</th>
                    <th class="num" style="width:90px">Est. min.</th>
                    <th class="num" style="width:110px">Compra</th>
                    <th class="num" style="width:110px">Venda</th>
                    <th style="width:90px">Situacao</th>
                    <th class="centro" style="width:80px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="10" class="vazio">Nenhum produto encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td class="num"><?= (int) $r['id'] ?></td>
                            <td>
                                <a href="<?= site_url('produtos/ver/' . $r['id']) ?>"><strong><?= html_escape($r['descricao']) ?></strong></a>
                                <?php if ( ! empty($r['marca'])): ?>
                                    <div style="font-size:11.5px;color:#6b7785"><?= html_escape($r['marca']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="centro"><?= html_escape($r['unidade'] ? $r['unidade'] : '-') ?></td>
                            <td><?= html_escape($r['grupo'] ? $r['grupo'] : '-') ?></td>
                            <td>
                                <?= $r['estante'] !== NULL ? 'E' . (int) $r['estante'] : '' ?>
                                <?= $r['prateleira'] !== NULL ? ' P' . (int) $r['prateleira'] : '' ?>
                                <?= ($r['estante'] === NULL && $r['prateleira'] === NULL) ? '-' : '' ?>
                            </td>
                            <td class="num"><?= numero_br($r['estoque_minimo']) ?></td>
                            <td class="num"><?= moeda_br($r['valor_compra']) ?></td>
                            <td class="num"><?= moeda_br($r['valor_venda']) ?></td>
                            <td>
                                <?php if (bd_bool($r['ativo'])): ?>
                                    <span class="badge ok">Ativo</span>
                                <?php else: ?>
                                    <span class="badge off">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="centro">
                                <a class="botao secundario pequeno" href="<?= site_url('produtos/ver/' . $r['id']) ?>">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $this->load->view('layouts/paginacao', array('pag' => $pag, 'base' => site_url('produtos'), 'busca' => $busca)); ?>
</div>
