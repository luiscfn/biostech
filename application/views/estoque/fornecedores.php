<div class="painel">
    <div class="painel-cabecalho">
        <h3>Fornecedores</h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('estoque') ?>">&larr; Saldos</a>
            <a class="botao pequeno" href="<?= site_url('estoque/fornecedor') ?>">+ Novo fornecedor</a>
        </div>
    </div>

    <div class="painel-corpo">
        <form method="get" action="<?= site_url('estoque/fornecedores') ?>" class="filtros">
            <input type="text" name="busca" value="<?= html_escape($busca) ?>" placeholder="Razao social, fantasia, CNPJ ou cidade...">
            <button type="submit" class="botao secundario">Pesquisar</button>
            <?php if ($busca !== ''): ?>
                <a class="botao secundario" href="<?= site_url('estoque/fornecedores') ?>">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:80px">Codigo</th>
                    <th>Razao social</th>
                    <th style="width:150px">CNPJ</th>
                    <th style="width:160px">Cidade / UF</th>
                    <th style="width:120px">Fone</th>
                    <th style="width:110px">Contato</th>
                    <th class="num" style="width:80px">Produtos</th>
                    <th class="centro" style="width:80px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="8" class="vazio">Nenhum fornecedor encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><?= (int) $r['codigo'] ?></td>
                            <td>
                                <a href="<?= site_url('estoque/fornecedor/' . $r['codigo']) ?>"><strong><?= html_escape($r['razao_social']) ?></strong></a>
                                <?php if ($r['nome_fantasia']): ?>
                                    <div class="ajuda"><?= html_escape($r['nome_fantasia']) ?></div>
                                <?php endif; ?>
                                <?php if ( ! bd_bool($r['ativo'])): ?>
                                    <span class="badge off">inativo</span>
                                <?php endif; ?>
                            </td>
                            <td><?= html_escape($r['cnpj'] ? $r['cnpj'] : '-') ?></td>
                            <td>
                                <?= html_escape($r['cidade'] ? $r['cidade'] : '-') ?>
                                <?= $r['uf'] ? ' / ' . html_escape($r['uf']) : '' ?>
                            </td>
                            <td><?= html_escape($r['fone'] ? $r['fone'] : '-') ?></td>
                            <td><?= html_escape($r['contato'] ? $r['contato'] : '-') ?></td>
                            <td class="num"><?= numero_br($r['produtos']) ?></td>
                            <td class="centro">
                                <a class="botao secundario pequeno" href="<?= site_url('estoque/fornecedor/' . $r['codigo']) ?>">Abrir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $this->load->view('layouts/paginacao', array(
        'pag'   => $pag,
        'base'  => site_url('estoque/fornecedores'),
        'busca' => $busca,
    )); ?>
</div>
