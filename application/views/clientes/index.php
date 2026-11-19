<div class="painel">
    <div class="painel-cabecalho">
        <h3>Clientes</h3>

        <form method="get" action="<?= site_url('clientes') ?>" class="filtros filtros-largos">
            <input type="text" name="busca" value="<?= html_escape($busca) ?>" placeholder="Nome, documento, cidade, e-mail...">

            <select name="ordem">
                <option value="nome"    <?= $ordem === 'nome' ? 'selected' : '' ?>>Ordenar por nome</option>
                <option value="cidade"  <?= $ordem === 'cidade' ? 'selected' : '' ?>>Ordenar por cidade</option>
                <option value="recente" <?= $ordem === 'recente' ? 'selected' : '' ?>>Mais recentes</option>
            </select>

            <button type="submit" class="botao">Pesquisar</button>

            <?php if ($busca !== ''): ?>
                <a class="botao secundario" href="<?= site_url('clientes') ?>">Limpar</a>
            <?php endif; ?>
        </form>

        <a class="botao" href="<?= site_url('clientes/novo') ?>">+ Novo cliente</a>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:80px">Codigo</th>
                    <th>Nome</th>
                    <th style="width:190px">Cidade / UF</th>
                    <th style="width:150px">Contato</th>
                    <th style="width:120px">Situacao</th>
                    <th class="centro" style="width:90px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="6" class="vazio">Nenhum cliente encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td class="num"><?= (int) $r['id'] ?></td>
                            <td>
                                <a href="<?= site_url('clientes/ver/' . $r['id']) ?>"><strong><?= html_escape($r['nome']) ?></strong></a>
                                <?php if ( ! empty($r['fornecedor_codigo'])): ?>
                                    <a class="badge info" style="text-decoration:none"
                                       href="<?= site_url('fornecedores/editar/' . (int) $r['fornecedor_codigo']) ?>"
                                       title="Abrir o cadastro deste fornecedor">Tambem fornecedor</a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= html_escape($r['cidade'] ? $r['cidade'] : '-') ?>
                                <?= $r['uf'] ? '/' . html_escape($r['uf']) : '' ?>
                            </td>
                            <td>
                                <?= html_escape($r['celular'] ? $r['celular'] : ($r['telefone'] ? $r['telefone'] : '-')) ?>
                            </td>
                            <td>
                                <?php if (bd_bool($r['ativo'])): ?>
                                    <span class="badge ok">Ativo</span>
                                <?php else: ?>
                                    <span class="badge off">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="centro">
                                <a class="botao pequeno" href="<?= site_url('clientes/ver/' . $r['id']) ?>">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $this->load->view('layouts/paginacao', array(
        'pag'   => $pag,
        'base'  => site_url('clientes'),
        'busca' => $busca,
        'ordem' => $ordem,
    )); ?>
</div>
