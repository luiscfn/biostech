<?php
$edicao = ! empty($registro);

$campo = function ($nome, $default = '') use ($registro, $edicao) {
    if ( ! $edicao)
    {
        return $default;
    }

    return isset($registro[$nome]) ? $registro[$nome] : $default;
};
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>Permissoes de Acesso</h3>

        <?php if ( ! $pode_editar): ?>
            <div class="acoes-ficha">
                <a class="botao pequeno" href="<?= site_url('tabelas_basicas') ?>">Retornar as Tabelas Basicas</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="painel-corpo">
        <p class="ajuda" style="margin-top:0">
            Modulos e programas herdados de <strong>PERMISSOES_MODULO_01</strong> e
            <strong>PERMISSOES_MODULO_02</strong>. O controle efetivo de acesso do
            sistema web continua sendo feito pelos perfis de usuario
            (admin, gestor e operador).
        </p>

        <?php if ($pode_editar): ?>
            <?= form_open('tabelas_basicas/modulo_salvar') ?>
                <input type="hidden" name="editar" value="<?= $edicao ? (int) $registro['codigo'] : 0 ?>">

                <div class="form-grid">
                    <div class="campo terco">
                        <label for="codigo">Codigo *</label>
                        <input type="number" name="codigo" id="codigo" min="1" step="1" required
                               value="<?= html_escape($edicao ? $registro['codigo'] : $proximo_codigo) ?>"
                               <?= $edicao ? 'readonly' : '' ?>>
                    </div>

                    <div class="campo terco">
                        <label for="nome">Modulo *</label>
                        <input type="text" name="nome" id="nome" maxlength="80" required
                               value="<?= html_escape($campo('nome')) ?>">
                    </div>

                    <div class="campo terco">
                        <label for="ordem">Ordem teste</label>
                        <input type="number" name="ordem" id="ordem" min="0" step="1"
                               value="<?= html_escape($campo('ordem')) ?>">
                    </div>

                    <div class="campo terco">
                        <label class="check" style="margin-top:26px">
                            <input type="checkbox" name="ativo" value="1"
                                <?= ( ! $edicao || bd_bool($registro['ativo'])) ? 'checked' : '' ?>>
                            Ativo
                        </label>
                    </div>
                </div>

                <div class="form-acoes">
                    <button type="submit" class="botao"><?= $edicao ? 'Gravar alteracoes' : 'Incluir modulo' ?></button>
                    <?php if ($edicao): ?>
                        <a class="botao" href="<?= site_url('tabelas_basicas/permissoes') ?>">Cancelar</a>
                        <a class="botao" href="<?= site_url('tabelas_basicas/permissoes') ?>">Novo</a>
                    <?php endif; ?>
                    <a class="botao" href="<?= site_url('tabelas_basicas') ?>">Retornar as Tabelas Basicas</a>
                </div>
            <?= form_close() ?>
        <?php else: ?>
            <div class="alert alert-warning">Consulta somente leitura. Inclusao e alteracao exigem perfil admin ou gestor.</div>
        <?php endif; ?>
    </div>
</div>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Modulos cadastrados (<?= numero_br($pag['total']) ?>)</h3>

        <form method="get" action="<?= site_url('tabelas_basicas/permissoes') ?>" class="acoes-ficha">
            <input type="text" name="busca" placeholder="Informe o Nome ou Codigo a Pesquisar"
                   value="<?= html_escape($busca) ?>" style="width:360px;max-width:100%;padding:6px 10px;border:1px solid var(--borda);border-radius:8px">
            <button type="submit" class="botao pequeno">Pesquisar</button>
            <?php if ($busca !== ''): ?>
                <a class="botao secundario pequeno" href="<?= site_url('tabelas_basicas/permissoes') ?>">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:90px">Codigo</th>
                    <th>Modulo</th>
                    <th class="num" style="width:110px">Programas</th>
                    <th style="width:100px">Situacao</th>
                    <th class="centro" style="width:250px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($modulos)): ?>
                    <tr><td colspan="5" class="vazio">Nenhum modulo encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($modulos as $m): ?>
                        <?php $qtd_programas = (int) $m['qtd_programas']; ?>
                        <tr>
                            <td><strong><?= html_escape($m['codigo']) ?></strong></td>
                            <td><?= html_escape($m['nome']) ?></td>
                            <td class="num"><?= numero_br($m['qtd_programas']) ?></td>
                            <td>
                                <?php if (bd_bool($m['ativo'])): ?>
                                    <span class="badge ok">Ativo</span>
                                <?php else: ?>
                                    <span class="badge off">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="centro">
                                <a class="botao secundario pequeno"
                                   href="<?= site_url('tabelas_basicas/programas/' . (int) $m['codigo']) ?>">Programas</a>

                                <?php if ($pode_editar): ?>
                                    <a class="botao pequeno"
                                       href="<?= site_url('tabelas_basicas/permissoes?editar=' . (int) $m['codigo']) ?>">Editar</a>

                                    <?= form_open('tabelas_basicas/modulo_excluir/' . (int) $m['codigo'], array('style' => 'display:inline')) ?>
                                        <?php if ($qtd_programas > 0): ?>
                                            <button type="submit" class="botao perigo pequeno" disabled
                                                title="Nao e possivel excluir: existem <?= numero_br($qtd_programas) ?> programa(s) vinculados.">Excluir</button>
                                        <?php else: ?>
                                            <button type="submit" class="botao perigo pequeno"
                                                onclick="return confirm('Excluir este modulo?')">Excluir</button>
                                        <?php endif; ?>
                                    <?= form_close() ?>
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
        'base'  => site_url('tabelas_basicas/permissoes'),
        'busca' => $busca,
    )); ?>
</div>
