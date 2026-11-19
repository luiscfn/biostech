<div class="painel">
    <div class="painel-cabecalho">
        <h3>Fornecedores</h3>

        <form method="get" action="<?= site_url('fornecedores') ?>" class="filtros filtros-largos">
            <input type="text" name="busca" value="<?= html_escape($busca) ?>" placeholder="Nome, fantasia, CNPJ ou cidade...">

            <select name="ordem">
                <option value="nome"    <?= $ordem === 'nome' ? 'selected' : '' ?>>Ordenar por nome</option>
                <option value="cidade"  <?= $ordem === 'cidade' ? 'selected' : '' ?>>Ordenar por cidade</option>
                <option value="recente" <?= $ordem === 'recente' ? 'selected' : '' ?>>Mais recentes</option>
            </select>

            <button type="submit" class="botao">Pesquisar</button>

            <?php if ($busca !== ''): ?>
                <a class="botao secundario" href="<?= site_url('fornecedores') ?>">Limpar</a>
            <?php endif; ?>
        </form>

        <a class="botao" href="<?= site_url('fornecedores/novo') ?>">+ Novo fornecedor</a>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:80px">Codigo</th>
                    <th>Razao social</th>
                    <th style="width:160px">Cidade / UF</th>
                    <th style="width:130px">Fone</th>
                    <th style="width:150px">Contato</th>
                    <th style="width:140px">Situacao</th>
                    <th class="centro" style="width:90px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="7" class="vazio">Nenhum fornecedor encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td class="num"><?= (int) $r['codigo'] ?></td>
                            <td>
                                <a href="<?= site_url('fornecedores/editar/' . (int) $r['codigo']) ?>"><strong><?= html_escape($r['razao_social']) ?></strong></a>
                                <?php if ($r['nome_fantasia']): ?>
                                    <div class="ajuda"><?= html_escape($r['nome_fantasia']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= html_escape($r['cidade'] ? $r['cidade'] : '-') ?><?= $r['uf'] ? ' / ' . html_escape($r['uf']) : '' ?></td>
                            <td><?= html_escape($r['fone'] ? $r['fone'] : '-') ?></td>
                            <td><?= html_escape($r['contato'] ? $r['contato'] : '-') ?></td>
                            <td>
                                <?php if (bd_bool($r['ativo'])): ?>
                                    <span class="badge ok">Ativo</span>
                                <?php else: ?>
                                    <span class="badge off">Inativo</span>
                                <?php endif; ?>

                                <?php if ( ! empty($r['cliente_id'])): ?>
                                    <a class="badge info" style="text-decoration:none"
                                       href="<?= site_url('clientes/ver/' . (int) $r['cliente_id']) ?>"
                                       title="Abrir o cliente vinculado">Tambem cliente</a>
                                <?php endif; ?>
                            </td>
                            <td class="centro">
                                <a class="botao pequeno" href="<?= site_url('fornecedores/editar/' . (int) $r['codigo']) ?>">Abrir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $this->load->view('layouts/paginacao', array(
        'pag'   => $pag,
        'base'  => site_url('fornecedores'),
        'busca' => $busca,
        'ordem' => $ordem,
    )); ?>
</div>
