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
        <h3>
            Programas do modulo
            <span class="badge info"><?= html_escape($modulo['codigo']) ?></span>
            <?= html_escape($modulo['nome']) ?>
        </h3>

        <div class="acoes-ficha">
            <?php if ( ! $pode_editar): ?>
                <a class="botao pequeno" href="<?= site_url('tabelas_basicas/permissoes') ?>">Retornar aos Modulos</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="painel-corpo">
        <?php if ($pode_editar): ?>
            <?= form_open('tabelas_basicas/programa_salvar/' . (int) $modulo['codigo']) ?>
                <input type="hidden" name="editar" value="<?= $edicao ? (int) $registro['codigo'] : 0 ?>">

                <div class="form-grid">
                    <div class="campo terco">
                        <label for="codigo">Codigo *</label>
                        <input type="number" name="codigo" id="codigo" min="1" step="1" required
                               value="<?= html_escape($edicao ? $registro['codigo'] : $proximo_codigo) ?>"
                               <?= $edicao ? 'readonly' : '' ?>>
                    </div>

                    <div class="campo terco">
                        <label for="nome">Programa *</label>
                        <input type="text" name="nome" id="nome" maxlength="100" required
                               value="<?= html_escape($campo('nome')) ?>">
                    </div>

                    <div class="campo terco">
                        <label for="formulario">Formulario</label>
                        <input type="text" name="formulario" id="formulario" maxlength="80"
                               value="<?= html_escape($campo('formulario')) ?>">
                        <div class="ajuda">Nome do formulario no Access (NOM_FORM).</div>
                    </div>

                    <div class="campo terco">
                        <label for="ordem">Ordem</label>
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
                    <button type="submit" class="botao"><?= $edicao ? 'Gravar alteracoes' : 'Incluir programa' ?></button>
                    <?php if ($edicao): ?>
                        <a class="botao" href="<?= site_url('tabelas_basicas/programas/' . (int) $modulo['codigo']) ?>">Cancelar</a>
                        <a class="botao" href="<?= site_url('tabelas_basicas/programas/' . (int) $modulo['codigo']) ?>">Novo</a>
                    <?php endif; ?>
                    <a class="botao" href="<?= site_url('tabelas_basicas/permissoes') ?>">Retornar aos Modulos</a>
                </div>
            <?= form_close() ?>
        <?php else: ?>
            <div class="alert alert-warning">Consulta somente leitura. Inclusao e alteracao exigem perfil admin ou gestor.</div>
        <?php endif; ?>
    </div>
</div>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Programas cadastrados (<?= numero_br($pag['total']) ?>)</h3>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:90px">Codigo</th>
                    <th>Programa</th>
                    <th style="width:220px">Formulario</th>
                    <th class="num" style="width:80px">Ordem</th>
                    <th style="width:100px">Situacao</th>
                    <?php if ($pode_editar): ?>
                        <th class="centro" style="width:170px">Acoes</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($programas)): ?>
                    <tr>
                        <td colspan="<?= $pode_editar ? 6 : 5 ?>" class="vazio">
                            Nenhum programa cadastrado neste modulo.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($programas as $p): ?>
                        <?php $uso = isset($p['uso']) ? (int) $p['uso'] : 0; ?>
                        <tr>
                            <td><strong><?= html_escape($p['codigo']) ?></strong></td>
                            <td><?= html_escape($p['nome']) ?></td>
                            <td><?= $p['formulario'] !== NULL ? html_escape($p['formulario']) : '-' ?></td>
                            <td class="num"><?= $p['ordem'] !== NULL ? (int) $p['ordem'] : '-' ?></td>
                            <td>
                                <?php if (bd_bool($p['ativo'])): ?>
                                    <span class="badge ok">Ativo</span>
                                <?php else: ?>
                                    <span class="badge off">Inativo</span>
                                <?php endif; ?>
                            </td>

                            <?php if ($pode_editar): ?>
                                <td class="centro">
                                    <a class="botao pequeno"
                                       href="<?= site_url('tabelas_basicas/programas/' . (int) $modulo['codigo'] . '?editar=' . (int) $p['codigo']) ?>">Editar</a>

                                    <?= form_open('tabelas_basicas/programa_excluir/' . (int) $modulo['codigo'] . '/' . (int) $p['codigo'], array('style' => 'display:inline')) ?>
                                        <?php if ($uso > 0): ?>
                                            <button type="submit" class="botao perigo pequeno" disabled
                                                title="Nao e possivel excluir: existem <?= numero_br($uso) ?> registro(s) vinculados.">Excluir</button>
                                        <?php else: ?>
                                            <button type="submit" class="botao perigo pequeno"
                                                onclick="return confirm('Excluir este programa?')">Excluir</button>
                                        <?php endif; ?>
                                    <?= form_close() ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $this->load->view('layouts/paginacao', array(
        'pag'   => $pag,
        'base'  => site_url('tabelas_basicas/programas/' . (int) $modulo['codigo']),
    )); ?>
</div>
