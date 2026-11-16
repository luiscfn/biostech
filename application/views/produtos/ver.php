<div class="painel">
    <div class="painel-cabecalho">
        <h3><?= html_escape($registro['descricao']) ?></h3>
        <div>
            <?php if (bd_bool($registro['ativo'])): ?>
                <span class="badge ok">Ativo</span>
            <?php else: ?>
                <span class="badge off">Inativo</span>
            <?php endif; ?>
            <span class="badge info">codigo <?= (int) $registro['id'] ?></span>
        </div>
    </div>

    <div class="painel-corpo">
        <div class="detalhes">
            <div class="detalhe">
                <div class="rotulo">Codigo completo</div>
                <div class="valor"><?= html_escape($registro['codigo'] ? $registro['codigo'] : '-') ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Unidade</div>
                <div class="valor"><?= html_escape($registro['unidade'] ? $registro['unidade'] : '-') ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Marca</div>
                <div class="valor"><?= html_escape($registro['marca'] ? $registro['marca'] : '-') ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Grupo / Subgrupo</div>
                <div class="valor">
                    <?= html_escape($registro['grupo'] ? $registro['grupo'] : '-') ?>
                    <?= $registro['subgrupo'] ? ' / ' . html_escape($registro['subgrupo']) : '' ?>
                </div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Estante / Prateleira</div>
                <div class="valor">
                    <?= $registro['estante'] !== NULL ? (int) $registro['estante'] : '-' ?>
                    /
                    <?= $registro['prateleira'] !== NULL ? (int) $registro['prateleira'] : '-' ?>
                </div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Estoque minimo</div>
                <div class="valor"><?= numero_br($registro['estoque_minimo']) ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Valor de compra</div>
                <div class="valor"><?= moeda_br($registro['valor_compra']) ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Valor de venda</div>
                <div class="valor"><?= moeda_br($registro['valor_venda']) ?></div>
            </div>
        </div>

        <?php if ( ! empty($registro['aplicacao'])): ?>
            <hr style="border:none;border-top:1px solid #e3e8ef;margin:22px 0">
            <div class="detalhe">
                <div class="rotulo">Aplicacao</div>
                <div class="valor" style="white-space:pre-line"><?= html_escape($registro['aplicacao']) ?></div>
            </div>
        <?php endif; ?>

        <div class="form-acoes">
            <a class="botao secundario" href="<?= site_url('produtos') ?>">Voltar</a>
        </div>
    </div>
</div>
