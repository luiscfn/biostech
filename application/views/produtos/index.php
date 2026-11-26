<?php
$tem_filtro = $busca !== '' || $grupo !== '' || $subgrupo !== '' || $situacao !== '';
?>
<style>
.filtros.filtros-produtos { align-items:flex-end; gap:12px; }
.filtros-produtos .filtro-campo { display:flex; flex-direction:column; gap:5px; }
.filtros-produtos .filtro-campo > label { font-size:12px; color:#2a3a55; font-weight:600; white-space:nowrap; }
.filtros-produtos .filtro-busca input[type=text] { width:360px; min-width:360px; }
.filtros-produtos .filtro-campo select { width:190px; min-width:190px; font-size:12px; }
.filtros-produtos .filtro-situacao select { width:110px; min-width:110px; }
.filtros-produtos .filtro-acoes { display:flex; gap:8px; align-items:center; }
</style>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>Catalogo de produtos</h3>
        <div>
            <a class="botao pequeno" href="<?= site_url('produtos/novo') ?>">+ Novo produto</a>
        </div>
    </div>

    <div class="painel-corpo">
        <div class="secao-titulo destaque">Filtros</div>

        <form method="get" action="<?= site_url('produtos') ?>" class="filtros filtros-produtos" id="filtro-produtos">
            <div class="filtro-campo filtro-busca">
                <label for="filtro-busca">Produto (nome ou código):</label>
                <input type="text" id="filtro-busca" name="busca" value="<?= html_escape($busca) ?>" placeholder="Nome ou código do produto...">
            </div>

            <div class="filtro-campo">
                <label for="filtro-grupo">Grupo:</label>
                <select name="grupo" id="filtro-grupo">
                    <option value="">Todos</option>
                    <?php foreach ($grupos as $g): ?>
                        <option value="<?= html_escape($g['codigo']) ?>" <?= (string) $g['codigo'] === $grupo ? 'selected' : '' ?>>
                            <?= html_escape($g['codigo'] . ' - ' . $g['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filtro-campo">
                <label for="filtro-subgrupo">Subgrupo:</label>
                <select name="subgrupo" id="filtro-subgrupo" <?= $grupo === '' ? 'disabled title="Selecione um grupo"' : '' ?>>
                    <option value="">Todos</option>
                    <?php foreach ($subgrupos as $sg): ?>
                        <option value="<?= html_escape($sg['codigo']) ?>" <?= (string) $sg['codigo'] === $subgrupo ? 'selected' : '' ?>>
                            <?= html_escape($sg['codigo'] . ' - ' . $sg['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filtro-campo filtro-situacao">
                <label for="filtro-situacao">Situação:</label>
                <select name="situacao" id="filtro-situacao">
                    <option value="ativos" <?= $situacao === 'ativos' ? 'selected' : '' ?>>Ativos</option>
                    <option value="inativos" <?= $situacao === 'inativos' ? 'selected' : '' ?>>Inativos</option>
                    <option value="" <?= $situacao === '' ? 'selected' : '' ?>>Todos</option>
                </select>
            </div>

            <div class="filtro-acoes">
                <button type="submit" class="botao secundario">Pesquisar</button>

                <?php if ($tem_filtro): ?>
                    <a class="botao secundario" href="<?= site_url('produtos') ?>">Limpar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:80px">Codigo</th>
                    <th style="min-width:420px">Descricao</th>
                    <th style="width:130px">Grupo</th>
                    <th style="width:130px">Subgrupo</th>
                    <th class="num" style="width:100px">Compra</th>
                    <th class="num" style="width:90px">% Lucro</th>
                    <th class="num" style="width:100px">Venda</th>
                    <th class="num" style="width:60px">Estoque</th>
                    <th style="width:90px">Situacao</th>
                    <th class="centro" style="width:180px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="10" class="vazio">Nenhum produto encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <?php
                        $ativo        = bd_bool($r['ativo']);
                        $grupo_nome   = ! empty($r['grupo_nome']) ? $r['grupo_nome'] : $r['grupo'];
                        $subgrupo_nome = ! empty($r['subgrupo_nome']) ? $r['subgrupo_nome'] : $r['subgrupo'];
                        $acao         = $ativo ? 'Inativar' : 'Reativar';
                        ?>
                        <tr>
                            <td class="num"><?= (int) $r['id'] ?></td>
                            <td style="font-size:12px">
                                <a href="<?= site_url('produtos/ver/' . $r['id']) ?>"><strong><?= html_escape($r['descricao']) ?></strong></a>
                                <?php if ( ! empty($r['marca'])): ?>
                                    <div style="font-size:11px;color:#6b7785"><?= html_escape($r['marca']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:11px"><?= html_escape($grupo_nome ? $grupo_nome : '-') ?></td>
                            <td style="font-size:11px"><?= html_escape($subgrupo_nome ? $subgrupo_nome : '-') ?></td>
                            <td class="num"><?= moeda_br($r['valor_compra']) ?></td>
                            <td class="num"><?= $r['perc_lucro'] !== NULL ? numero_br($r['perc_lucro'], 2) . '%' : '-' ?></td>
                            <td class="num"><?= moeda_br($r['valor_venda']) ?></td>
                            <td class="num"><?= numero_br($r['saldo']) ?></td>
                            <td>
                                <?php if ($ativo): ?>
                                    <span class="badge ok">Ativo</span>
                                <?php else: ?>
                                    <span class="badge off">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="centro">
                                <a class="botao secundario pequeno" href="<?= site_url('produtos/ver/' . $r['id']) ?>">Ver</a>
                                <?= form_open('produtos/alternar_ativo/' . (int) $r['id'], array(
                                    'class'    => 'inline',
                                    'onsubmit' => "return confirm('" . $acao . " o produto " . (int) $r['id'] . "?');",
                                )) ?>
                                    <input type="hidden" name="busca" value="<?= html_escape($busca) ?>">
                                    <input type="hidden" name="grupo" value="<?= html_escape($grupo) ?>">
                                    <input type="hidden" name="subgrupo" value="<?= html_escape($subgrupo) ?>">
                                    <input type="hidden" name="situacao" value="<?= html_escape($situacao) ?>">
                                    <input type="hidden" name="pagina" value="<?= (int) $pag['pagina'] ?>">
                                    <button type="submit" class="botao <?= $ativo ? 'secundario' : '' ?> pequeno"><?= $acao ?></button>
                                <?= form_close() ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $this->load->view('layouts/paginacao', array(
        'pag'   => $pag,
        'base'  => site_url('produtos'),
        'busca' => $busca,
        'extra' => array(
            'grupo'    => $grupo,
            'subgrupo' => $subgrupo,
            'situacao' => $situacao,
        ),
    )); ?>
</div>

<script>
(function () {
    var grupo = document.getElementById('filtro-grupo');

    if ( ! grupo) { return; }

    grupo.addEventListener('change', function () {
        var subgrupo = document.getElementById('filtro-subgrupo');

        if (subgrupo) { subgrupo.value = ''; }

        this.form.submit();
    });
})();
</script>
