<div class="painel-corpo">
    <div class="ficha-grade">
        <div class="dado" style="grid-column:span 2">
            <div class="rotulo">O que funciona</div>
            <div class="texto-longo"><?= nl2br(html_escape($registro['que_funciona'] ? $registro['que_funciona'] : '-')) ?></div>
        </div>
        <div class="dado" style="grid-column:span 2">
            <div class="rotulo">O que quer fazer</div>
            <div class="texto-longo"><?= nl2br(html_escape($registro['quer_fazer'] ? $registro['quer_fazer'] : '-')) ?></div>
        </div>
        <div class="dado" style="grid-column:span 2">
            <div class="rotulo">Dificuldades</div>
            <div class="texto-longo"><?= nl2br(html_escape($registro['dificuldades'] ? $registro['dificuldades'] : '-')) ?></div>
        </div>
        <div class="dado" style="grid-column:span 2">
            <div class="rotulo">Material necessario</div>
            <div class="texto-longo"><?= nl2br(html_escape($registro['material_precisa'] ? $registro['material_precisa'] : '-')) ?></div>
        </div>
        <div class="dado" style="grid-column:span 2">
            <div class="rotulo">Providenciar</div>
            <div class="texto-longo"><?= nl2br(html_escape($registro['providenciar'] ? $registro['providenciar'] : '-')) ?></div>
        </div>
        <div class="dado" style="grid-column:span 2">
            <div class="rotulo">Observacoes</div>
            <div class="texto-longo"><?= nl2br(html_escape($registro['observacao'] ? $registro['observacao'] : '-')) ?></div>
        </div>
    </div>

    <?php if ($pode_editar): ?>
        <div class="form-acoes">
            <a class="botao secundario pequeno" href="<?= site_url('orcamentos/dimensionamento_editar/' . $registro['numero']) ?>">Editar levantamento</a>
        </div>
    <?php endif; ?>
</div>
