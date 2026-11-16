<div class="painel">
    <div class="painel-cabecalho">
        <h3>Setores cadastrados</h3>

        <div class="filtros">
            <form method="get" action="<?= site_url('setores') ?>" class="filtros">
                <input type="text" name="busca" value="<?= html_escape($busca) ?>" placeholder="Pesquisar setor...">
                <button type="submit" class="botao secundario">Pesquisar</button>
                <?php if ($busca !== ''): ?>
                    <a class="botao secundario" href="<?= site_url('setores') ?>">Limpar</a>
                <?php endif; ?>
            </form>

            <?php if (in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE)): ?>
                <a class="botao" href="<?= site_url('setores/novo') ?>">+ Novo setor</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:90px">Codigo</th>
                    <th>Nome</th>
                    <th style="width:110px">Situacao</th>
                    <th style="width:170px" class="centro">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="4" class="vazio">Nenhum setor encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td class="num"><?= (int) $r['id'] ?></td>
                            <td><strong><?= html_escape($r['nome']) ?></strong></td>
                            <td>
                                <?php if (bd_bool($r['ativo'])): ?>
                                    <span class="badge ok">Ativo</span>
                                <?php else: ?>
                                    <span class="badge off">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="centro">
                                <?php if (in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE)): ?>
                                    <a class="botao secundario pequeno" href="<?= site_url('setores/editar/' . $r['id']) ?>">Editar</a>
                                <?php endif; ?>

                                <?php if ($usuario['perfil'] === PERFIL_ADMIN): ?>
                                    <?= form_open('setores/excluir/' . $r['id'], array('style' => 'display:inline', 'onsubmit' => "return confirm('Excluir o setor " . html_escape($r['nome']) . "?');")) ?>
                                        <button type="submit" class="botao perigo pequeno">Excluir</button>
                                    <?= form_close() ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
