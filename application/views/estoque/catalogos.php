<?php
$rotulos = array(
    'grupos'      => 'Grupos',
    'subgrupos'   => 'Subgrupos',
    'posicoes'    => 'Posicoes',
    'estantes'    => 'Estantes',
    'prateleiras' => 'Prateleiras',
    'finalidades' => 'Finalidades',
);

$tipo_singular = array(
    'grupos'      => 'grupo',
    'subgrupos'   => 'subgrupo',
    'posicoes'    => 'posicao',
    'estantes'    => 'estante',
    'prateleiras' => 'prateleira',
    'finalidades' => 'finalidade',
);

$singular = $tipo_singular[$tipo];
?>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Catalogos do estoque</h3>
        <a class="botao secundario pequeno" href="<?= site_url('estoque') ?>">&larr; Saldos</a>
    </div>

    <div class="abas abas-catalogo">
        <?php foreach ($tipos as $t): ?>
            <a class="aba <?= $t === $tipo ? 'ativa' : '' ?>" href="<?= site_url('estoque/catalogos?tipo=' . $t) ?>">
                <?= html_escape($rotulos[$t]) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="painel-corpo">
        <h4 class="secao-titulo" id="titulo-form">Novo <?= html_escape($singular) ?></h4>

        <?= form_open('estoque/catalogo_salvar', array('id' => 'form-catalogo')) ?>
            <input type="hidden" name="tipo" value="<?= html_escape($singular) ?>">

            <div class="form-grid">
                <?php if ($tipo === 'subgrupos'): ?>
                    <div class="campo">
                        <label for="cat_grupo">Grupo *</label>
                        <select name="grupo_codigo" id="cat_grupo">
                            <option value="">Selecione</option>
                            <?php foreach ($grupos as $g): ?>
                                <option value="<?= html_escape($g['codigo']) ?>"><?= html_escape($g['codigo'] . ' - ' . $g['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if ($tipo === 'prateleiras'): ?>
                    <div class="campo">
                        <label for="cat_estante">Estante *</label>
                        <select name="estante_codigo" id="cat_estante">
                            <option value="">Selecione</option>
                            <?php foreach ($estantes as $e): ?>
                                <option value="<?= (int) $e['codigo'] ?>"><?= html_escape($e['codigo'] . ' - ' . $e['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="campo">
                    <label for="cat_codigo">Codigo <?= in_array($tipo, array('posicoes', 'estantes', 'prateleiras', 'finalidades'), TRUE) ? '(vazio = proximo)' : '*' ?></label>
                    <input type="text" name="codigo" id="cat_codigo" maxlength="3">
                </div>

                <div class="campo">
                    <label for="cat_nome">Nome *</label>
                    <input type="text" name="nome" id="cat_nome" maxlength="70">
                </div>

                <?php if ($tipo === 'grupos'): ?>
                    <div class="campo">
                        <label for="cat_percentual">Percentual de venda (%)</label>
                        <input type="text" name="percentual_venda" id="cat_percentual" placeholder="0,00">
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao" id="botao-salvar">Salvar <?= html_escape($singular) ?></button>
                <button type="button" class="botao secundario" id="botao-limpar">Limpar</button>
            </div>
        <?= form_close() ?>
    </div>
</div>

<div class="painel">
    <div class="painel-cabecalho">
        <h3><?= html_escape($rotulos[$tipo]) ?> cadastrados</h3>

        <?php if ($tipo === 'subgrupos'): ?>
            <form method="get" action="<?= site_url('estoque/catalogos') ?>" class="filtros">
                <input type="hidden" name="tipo" value="subgrupos">
                <select name="grupo">
                    <option value="">Todos os grupos</option>
                    <?php foreach ($grupos as $g): ?>
                        <option value="<?= html_escape($g['codigo']) ?>" <?= $g['codigo'] === $grupo ? 'selected' : '' ?>>
                            <?= html_escape($g['codigo'] . ' - ' . $g['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="botao secundario">Filtrar</button>
                <?php if ($grupo !== ''): ?>
                    <a class="botao secundario" href="<?= site_url('estoque/catalogos?tipo=subgrupos') ?>">Limpar</a>
                <?php endif; ?>
            </form>
        <?php endif; ?>

        <span class="badge info"><?= numero_br(count($registros)) ?> registro(s)</span>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <?php if ($tipo === 'subgrupos'): ?>
                        <th style="width:180px">Grupo</th>
                    <?php endif; ?>
                    <?php if ($tipo === 'prateleiras'): ?>
                        <th style="width:180px">Estante</th>
                    <?php endif; ?>
                    <th style="width:110px">Codigo</th>
                    <th>Nome</th>
                    <?php if ($tipo === 'grupos'): ?>
                        <th class="num" style="width:110px">% venda</th>
                        <th class="num" style="width:90px">Produtos</th>
                    <?php elseif (in_array($tipo, array('subgrupos', 'estantes', 'prateleiras'), TRUE)): ?>
                        <th class="num" style="width:90px">Produtos</th>
                    <?php endif; ?>
                    <th class="centro" style="width:180px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="7" class="vazio">Nenhum registro cadastrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <?php
                        if ($tipo === 'subgrupos')
                        {
                            $codigo = $r['codigo'];
                            $chave_grupo = $r['grupo_codigo'];
                            $chave_estante = '';
                        }
                        elseif ($tipo === 'prateleiras')
                        {
                            $codigo = $r['codigo'];
                            $chave_grupo = '';
                            $chave_estante = $r['estante_codigo'];
                        }
                        else
                        {
                            $codigo = $r['codigo'];
                            $chave_grupo = '';
                            $chave_estante = '';
                        }
                        ?>
                        <tr>
                            <?php if ($tipo === 'subgrupos'): ?>
                                <td><?= html_escape($r['grupo_codigo'] . ' - ' . $r['grupo_nome']) ?></td>
                            <?php endif; ?>
                            <?php if ($tipo === 'prateleiras'): ?>
                                <td><?= html_escape($r['estante_codigo'] . ' - ' . $r['estante_nome']) ?></td>
                            <?php endif; ?>
                            <td><?= html_escape($codigo) ?></td>
                            <td><?= html_escape($r['nome']) ?></td>
                            <?php if ($tipo === 'grupos'): ?>
                                <td class="num"><?= $r['percentual_venda'] !== NULL ? numero_br($r['percentual_venda'], 2) : '-' ?></td>
                                <td class="num"><?= numero_br($r['produtos']) ?></td>
                            <?php elseif (in_array($tipo, array('subgrupos', 'estantes', 'prateleiras'), TRUE)): ?>
                                <td class="num"><?= numero_br($r['produtos']) ?></td>
                            <?php endif; ?>
                            <td class="centro">
                                <button type="button" class="botao secundario pequeno botao-editar"
                                        data-codigo="<?= html_escape($codigo) ?>"
                                        data-nome="<?= html_escape($r['nome']) ?>"
                                        data-percentual="<?= $r['percentual_venda'] !== NULL ? html_escape($r['percentual_venda']) : '' ?>"
                                        data-grupo="<?= html_escape($chave_grupo) ?>"
                                        data-estante="<?= html_escape($chave_estante) ?>">Editar</button>

                                <?= form_open('estoque/catalogo_excluir', array('class' => 'inline', 'onsubmit' => "return confirm('Excluir este cadastro?');")) ?>
                                    <input type="hidden" name="tipo" value="<?= html_escape($singular) ?>">
                                    <input type="hidden" name="codigo" value="<?= html_escape($codigo) ?>">
                                    <?php if ($tipo === 'subgrupos'): ?>
                                        <input type="hidden" name="grupo_codigo" value="<?= html_escape($chave_grupo) ?>">
                                    <?php endif; ?>
                                    <?php if ($tipo === 'prateleiras'): ?>
                                        <input type="hidden" name="estante_codigo" value="<?= html_escape($chave_estante) ?>">
                                    <?php endif; ?>
                                    <button type="submit" class="botao perigo pequeno">Excluir</button>
                                <?= form_close() ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    'use strict';

    var form = document.getElementById('form-catalogo');
    var titulo = document.getElementById('titulo-form');
    var botaoSalvar = document.getElementById('botao-salvar');
    var campoCodigo = document.getElementById('cat_codigo');
    var campoNome = document.getElementById('cat_nome');
    var campoPercentual = document.getElementById('cat_percentual');
    var campoGrupo = document.getElementById('cat_grupo');
    var campoEstante = document.getElementById('cat_estante');
    var singular = <?= json_encode($singular) ?>;

    document.querySelectorAll('.botao-editar').forEach(function (botao) {
        botao.addEventListener('click', function () {
            if (campoCodigo) { campoCodigo.value = this.getAttribute('data-codigo') || ''; }
            if (campoNome) { campoNome.value = this.getAttribute('data-nome') || ''; }
            if (campoPercentual) { campoPercentual.value = this.getAttribute('data-percentual') || ''; }
            if (campoGrupo) { campoGrupo.value = this.getAttribute('data-grupo') || ''; }
            if (campoEstante) { campoEstante.value = this.getAttribute('data-estante') || ''; }

            if (titulo) { titulo.textContent = 'Editar ' + singular; }
            if (botaoSalvar) { botaoSalvar.textContent = 'Salvar alteracoes'; }
            if (campoNome) { campoNome.focus(); }

            if (form) { form.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });
    });

    var botaoLimpar = document.getElementById('botao-limpar');

    if (botaoLimpar) {
        botaoLimpar.addEventListener('click', function () {
            if (form) { form.reset(); }
            if (titulo) { titulo.textContent = 'Novo ' + singular; }
            if (botaoSalvar) { botaoSalvar.textContent = 'Salvar ' + singular; }
        });
    }
})();
</script>
