<div class="painel-corpo">
    <h4 class="secao-titulo">Valores calculados</h4>

    <div class="ficha-grade">
        <div class="dado">
            <div class="rotulo">Mao de obra</div>
            <?= moeda_br($registro['mao_obra']) ?>
            <?php if ($registro['percentual_mao_obra'] !== NULL): ?>
                <div class="ajuda"><?= numero_br($registro['percentual_mao_obra'], 4) ?>%</div>
            <?php endif; ?>
        </div>
        <div class="dado">
            <div class="rotulo">Material de fixacao</div>
            <?= moeda_br($registro['material_fixacao']) ?>
            <?php if ($registro['percentual_mat_fixacao'] !== NULL): ?>
                <div class="ajuda"><?= numero_br($registro['percentual_mat_fixacao'], 4) ?>%</div>
            <?php endif; ?>
        </div>
        <div class="dado">
            <div class="rotulo">Nota fiscal</div>
            <?= moeda_br($registro['nota_fiscal']) ?>
            <?php if ($registro['percentual_nota_fiscal'] !== NULL): ?>
                <div class="ajuda"><?= numero_br($registro['percentual_nota_fiscal'], 4) ?>%</div>
            <?php endif; ?>
        </div>
        <div class="dado">
            <div class="rotulo">Acrescimo de custo</div>
            <?= moeda_br($registro['acrescimo_custo']) ?>
            <?php if ($registro['percentual_acrescimo_custo'] !== NULL): ?>
                <div class="ajuda"><?= numero_br($registro['percentual_acrescimo_custo'], 4) ?>%</div>
            <?php endif; ?>
        </div>
        <div class="dado">
            <div class="rotulo">Total de venda (itens)</div>
            <strong><?= moeda_br($total_venda) ?></strong>
        </div>
        <div class="dado">
            <div class="rotulo">Orcamento comercial</div>
            <?php if ( ! empty($registro['orcamento_numero'])): ?>
                <a href="<?= site_url('orcamentos/ver/' . $registro['orcamento_numero']) ?>"><?= (int) $registro['orcamento_numero'] ?></a>
                <div class="ajuda"><?= moeda_br($registro['orcamento_valor']) ?></div>
            <?php else: ?>
                -
            <?php endif; ?>
        </div>
    </div>
</div>
