<?php
$rotulo_perfil = array(
    PERFIL_ADMIN    => 'Administrador',
    PERFIL_GESTOR   => 'Gestor',
    PERFIL_OPERADOR => 'Operador',
);
$classe_perfil = array(
    PERFIL_ADMIN    => 'info',
    PERFIL_GESTOR   => 'alerta',
    PERFIL_OPERADOR => 'off',
);
?>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Usuarios do sistema</h3>

        <div class="filtros">
            <form method="get" action="<?= site_url('usuarios') ?>" class="filtros">
                <input type="text" name="busca" value="<?= html_escape($busca) ?>" placeholder="Login ou nome...">
                <button type="submit" class="botao secundario">Pesquisar</button>
                <?php if ($busca !== ''): ?>
                    <a class="botao secundario" href="<?= site_url('usuarios') ?>">Limpar</a>
                <?php endif; ?>
            </form>

            <?php if ($usuario['perfil'] === PERFIL_ADMIN): ?>
                <a class="botao" href="<?= site_url('usuarios/novo') ?>">+ Novo usuario</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th>Login</th>
                    <th>Nome</th>
                    <th>Setor</th>
                    <th>Perfil</th>
                    <th class="centro">Almox.</th>
                    <th>Situacao</th>
                    <th>Ultimo acesso</th>
                    <th class="centro" style="width:230px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="8" class="vazio">Nenhum usuario encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><strong><?= html_escape($r['login']) ?></strong></td>
                            <td><?= html_escape($r['nome']) ?></td>
                            <td><?= html_escape($r['setor_nome'] ? $r['setor_nome'] : '-') ?></td>
                            <td>
                                <span class="badge <?= $classe_perfil[$r['perfil']] ?>">
                                    <?= $rotulo_perfil[$r['perfil']] ?>
                                </span>
                            </td>
                            <td class="centro">
                                <?= bd_bool($r['acesso_almoxarifado']) ? '<span class="badge ok">Sim</span>' : '<span class="badge off">Nao</span>' ?>
                            </td>
                            <td>
                                <?php if (bd_bool($r['ativo'])): ?>
                                    <span class="badge ok">Ativo</span>
                                <?php else: ?>
                                    <span class="badge erro">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td><?= data_hora_br($r['ultimo_acesso']) ?></td>
                            <td class="centro">
                                <?php if ($usuario['perfil'] === PERFIL_ADMIN): ?>
                                    <a class="botao secundario pequeno" href="<?= site_url('usuarios/editar/' . $r['id']) ?>">Editar</a>
                                    <a class="botao secundario pequeno" href="<?= site_url('usuarios/senha/' . $r['id']) ?>">Senha</a>

                                    <?= form_open('usuarios/alternar/' . $r['id'], array('style' => 'display:inline')) ?>
                                        <button type="submit" class="botao <?= bd_bool($r['ativo']) ? 'perigo' : '' ?> pequeno">
                                            <?= bd_bool($r['ativo']) ? 'Desativar' : 'Ativar' ?>
                                        </button>
                                    <?= form_close() ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $this->load->view('layouts/paginacao', array('pag' => $pag, 'base' => site_url('usuarios'), 'busca' => $busca)); ?>
</div>
