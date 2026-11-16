<div class="painel">
    <div class="painel-cabecalho">
        <h3>Clientes</h3>

        <div class="filtros">
            <form method="get" action="<?= site_url('clientes') ?>" class="filtros">
                <input type="text" name="busca" value="<?= html_escape($busca) ?>" placeholder="Nome, documento, cidade, e-mail...">

                <select name="ordem">
                    <option value="nome"    <?= $ordem === 'nome' ? 'selected' : '' ?>>Ordenar por nome</option>
                    <option value="cidade"  <?= $ordem === 'cidade' ? 'selected' : '' ?>>Ordenar por cidade</option>
                    <option value="recente" <?= $ordem === 'recente' ? 'selected' : '' ?>>Mais recentes</option>
                </select>

                <button type="submit" class="botao secundario">Pesquisar</button>

                <?php if ($busca !== ''): ?>
                    <a class="botao secundario" href="<?= site_url('clientes') ?>">Limpar</a>
                <?php endif; ?>
            </form>

            <a class="botao" href="<?= site_url('clientes/novo') ?>">+ Novo cliente</a>
        </div>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:80px">Codigo</th>
                    <th>Nome</th>
                    <th>Fantasia</th>
                    <th>CPF / CNPJ</th>
                    <th>Cidade / UF</th>
                    <th>Contato</th>
                    <th style="width:90px">Situacao</th>
                    <th class="centro" style="width:90px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="8" class="vazio">Nenhum cliente encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td class="num"><?= (int) $r['id'] ?></td>
                            <td>
                                <a href="<?= site_url('clientes/ver/' . $r['id']) ?>"><strong><?= html_escape($r['nome']) ?></strong></a>
                            </td>
                            <td><?= html_escape($r['fantasia'] ? $r['fantasia'] : '-') ?></td>
                            <td><?= html_escape($r['cpf_cnpj'] ? $r['cpf_cnpj'] : '-') ?></td>
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
                                <a class="botao secundario pequeno" href="<?= site_url('clientes/ver/' . $r['id']) ?>">Ver</a>
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
