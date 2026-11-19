<?php $encerrado = bd_bool($registro['encerrado']); ?>

<div class="painel-corpo">
    <div class="detalhes">
        <div class="detalhe">
            <div class="rotulo">Defeito relatado</div>
            <div class="valor texto-longo"><?= nl2br(html_escape($registro['defeito_relatado'] ? $registro['defeito_relatado'] : '-')) ?></div>
        </div>
        <div class="detalhe">
            <div class="rotulo">Defeito constatado</div>
            <div class="valor texto-longo"><?= nl2br(html_escape($registro['defeito_constatado'] ? $registro['defeito_constatado'] : '-')) ?></div>
        </div>
        <div class="detalhe">
            <div class="rotulo">Servico executado</div>
            <div class="valor texto-longo"><?= nl2br(html_escape($registro['servico_executado'] ? $registro['servico_executado'] : '-')) ?></div>
        </div>
        <div class="detalhe">
            <div class="rotulo">Observacoes</div>
            <div class="valor texto-longo"><?= nl2br(html_escape($registro['observacao'] ? $registro['observacao'] : '-')) ?></div>
        </div>
    </div>

    <hr class="divisor">

    <div class="detalhes">
        <div class="detalhe"><div class="rotulo">Material (pecas)</div><div class="valor"><?= moeda_br($registro['valor_material']) ?></div></div>
        <div class="detalhe"><div class="rotulo">Servicos</div><div class="valor"><?= moeda_br($registro['valor_servico']) ?></div></div>
        <div class="detalhe"><div class="rotulo">Mao de obra</div><div class="valor"><?= moeda_br($registro['valor_mao_obra']) ?></div></div>
        <div class="detalhe"><div class="rotulo">Material de fixacao</div><div class="valor"><?= moeda_br($registro['material_fixacao']) ?></div></div>
        <div class="detalhe"><div class="rotulo">Imposto</div><div class="valor"><?= moeda_br($registro['valor_imposto']) ?></div></div>
        <div class="detalhe"><div class="rotulo">Acrescimo de custo</div><div class="valor"><?= moeda_br($registro['acrescimo_custo']) ?></div></div>
        <div class="detalhe"><div class="rotulo">Desconto</div><div class="valor"><?= moeda_br($registro['valor_desconto']) ?></div></div>
        <div class="detalhe"><div class="rotulo">Valor faturado</div><div class="valor"><?= moeda_br($registro['valor_faturado']) ?></div></div>
        <div class="detalhe"><div class="rotulo">Parcelas</div><div class="valor"><?= (int) $registro['qtd_parcelas'] ?></div></div>
    </div>

    <hr class="divisor">

    <div class="detalhes">
        <div class="detalhe">
            <div class="rotulo">Orcamento</div>
            <div class="valor">
                <?php if ( ! empty($registro['orcamento_numero'])): ?>
                    <a href="<?= site_url('orcamentos/ver/' . (int) $registro['orcamento_numero']) ?>"><?= (int) $registro['orcamento_numero'] ?></a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </div>
        </div>
        <div class="detalhe"><div class="rotulo">Cadastrado por</div><div class="valor"><?= html_escape($registro['usuario_cadastro'] ? $registro['usuario_cadastro'] : '-') ?></div></div>
        <div class="detalhe"><div class="rotulo">Data do cadastro</div><div class="valor"><?= data_hora_br($registro['data_cadastro']) ?></div></div>
        <div class="detalhe"><div class="rotulo">Alterado por</div><div class="valor"><?= html_escape($registro['usuario_alteracao'] ? $registro['usuario_alteracao'] : '-') ?></div></div>
        <div class="detalhe"><div class="rotulo">Data da alteracao</div><div class="valor"><?= data_hora_br($registro['data_alteracao']) ?></div></div>
        <div class="detalhe"><div class="rotulo">Encerrado por</div><div class="valor"><?= html_escape($registro['usuario_encerramento'] ? $registro['usuario_encerramento'] : '-') ?></div></div>
        <div class="detalhe"><div class="rotulo">Data do encerramento</div><div class="valor"><?= data_hora_br($registro['data_encerramento']) ?></div></div>
    </div>

    <hr class="divisor">

    <div class="acoes-ficha">
        <?php if (empty($registro['numero_gerado'])): ?>
            <?= form_open('chamados/gerar/' . $registro['numero'], array('class' => 'inline', 'onsubmit' => "return confirm('Gerar o par Chamado/OS para este registro?');")) ?>
                <button type="submit" class="botao secundario pequeno">Gerar <?= $registro['categoria'] === 'Chamado' ? 'Ordem de Servico' : 'Chamado' ?></button>
            <?= form_close() ?>
        <?php endif; ?>

        <?php if ($encerrado): ?>
            <?= form_open('chamados/reabrir/' . $registro['numero'], array('class' => 'inline', 'onsubmit' => "return confirm('Reabrir este registro?');")) ?>
                <input type="text" name="motivo" placeholder="Motivo da reabertura" maxlength="200" style="min-width:240px">
                <button type="submit" class="botao secundario pequeno">Reabrir</button>
            <?= form_close() ?>

            <?= form_open('chamados/cancelar_encerramento/' . $registro['numero'], array('class' => 'inline', 'onsubmit' => "return confirm('Cancelar o encerramento e as contas a receber geradas?');")) ?>
                <button type="submit" class="botao perigo pequeno">Cancelar encerramento</button>
            <?= form_close() ?>
        <?php endif; ?>
    </div>
</div>
