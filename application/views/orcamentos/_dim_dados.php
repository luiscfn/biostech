<?php
$flags = array(
    'rebocado'      => 'Rebocado',
    'cerca_arame'   => 'Cerca de arame',
    'telhado'       => 'Telhado',
    'arvore'        => 'Arvore',
    'cinta_cimento' => 'Cinta de cimento',
    'pega_ladrao'   => 'Pega ladrao',
);
?>
<div class="painel-corpo">
    <div class="ficha-grade">
        <div class="dado">
            <div class="rotulo">Local de instalacao</div>
            <?= html_escape($registro['local_instalacao'] ? $registro['local_instalacao'] : '-') ?>
        </div>
        <div class="dado">
            <div class="rotulo">Setor</div>
            <?= html_escape($registro['setor'] ? $registro['setor'] : '-') ?>
        </div>
        <div class="dado">
            <div class="rotulo">Tipo de contato</div>
            <?= html_escape($registro['tipo_contato'] ? $registro['tipo_contato'] : '-') ?>
        </div>
        <div class="dado">
            <div class="rotulo">Solicitante</div>
            <?= html_escape($registro['solicitante'] ? $registro['solicitante'] : '-') ?>
        </div>
        <div class="dado">
            <div class="rotulo">E-mail</div>
            <?= html_escape($registro['email'] ? $registro['email'] : '-') ?>
        </div>
        <div class="dado">
            <div class="rotulo">Dias previstos</div>
            <?= $registro['qtd_dias'] !== NULL ? (int) $registro['qtd_dias'] : '-' ?>
        </div>
        <div class="dado">
            <div class="rotulo">Tecnicos</div>
            <?= $registro['qtd_tecnicos'] !== NULL ? (int) $registro['qtd_tecnicos'] : '-' ?>
        </div>
        <div class="dado">
            <div class="rotulo">Comerciais / nao comerciais</div>
            <?= numero_br($registro['qtd_comerciais'], 2) ?> / <?= numero_br($registro['qtd_nao_comerciais'], 2) ?>
        </div>
        <div class="dado">
            <div class="rotulo">Cadastro</div>
            <?= html_escape($registro['usuario_cadastro'] ? $registro['usuario_cadastro'] : '-') ?>
            <div class="ajuda"><?= data_hora_br($registro['data_cadastro']) ?></div>
        </div>
        <div class="dado">
            <div class="rotulo">Ultima alteracao</div>
            <?= html_escape($registro['usuario_alteracao'] ? $registro['usuario_alteracao'] : '-') ?>
            <?php if ( ! empty($registro['data_alteracao'])): ?>
                <div class="ajuda"><?= data_hora_br($registro['data_alteracao']) ?></div>
            <?php endif; ?>
        </div>
        <div class="dado">
            <div class="rotulo">Fechamento</div>
            <?= html_escape($registro['usuario_fechamento'] ? $registro['usuario_fechamento'] : '-') ?>
            <?php if ( ! empty($registro['data_fechamento'])): ?>
                <div class="ajuda"><?= data_hora_br($registro['data_fechamento']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <hr class="divisor">

    <h4 class="secao-titulo">Medidas e caracteristicas</h4>

    <div class="ficha-grade">
        <div class="dado">
            <div class="rotulo">Altura</div>
            <?= $registro['altura'] !== NULL ? numero_br($registro['altura'], 2) . ' m' : '-' ?>
        </div>
        <div class="dado">
            <div class="rotulo">Comprimento do muro</div>
            <?= $registro['comprimento_muro'] !== NULL ? numero_br($registro['comprimento_muro'], 2) . ' m' : '-' ?>
        </div>
        <div class="dado">
            <div class="rotulo">Frente / fundo</div>
            <?= $registro['frente'] !== NULL ? numero_br($registro['frente'], 2) : '-' ?>
            /
            <?= $registro['fundo'] !== NULL ? numero_br($registro['fundo'], 2) : '-' ?>
        </div>
        <div class="dado">
            <div class="rotulo">Lado direito / esquerdo</div>
            <?= $registro['lado_direito'] !== NULL ? numero_br($registro['lado_direito'], 2) : '-' ?>
            /
            <?= $registro['lado_esquerdo'] !== NULL ? numero_br($registro['lado_esquerdo'], 2) : '-' ?>
        </div>
    </div>

    <div style="margin-top:10px">
        <?php foreach ($flags as $campo => $rotulo): ?>
            <span class="badge <?= bd_bool($registro[$campo]) ? 'info' : 'off' ?>"><?= html_escape($rotulo) ?>: <?= bd_bool($registro[$campo]) ? 'sim' : 'nao' ?></span>
        <?php endforeach; ?>
    </div>

    <hr class="divisor">

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

    <hr class="divisor">

    <div class="dado">
        <div class="rotulo">Observacoes</div>
        <div class="texto-longo"><?= nl2br(html_escape($registro['observacao'] ? $registro['observacao'] : '-')) ?></div>
    </div>

    <?php if ($pode_editar): ?>
        <hr class="divisor">
        <h4 class="secao-titulo">Acoes</h4>

        <div class="campo">
            <label>Excluir o dimensionamento</label>
            <div class="ajuda">
                <?php if ( ! empty($registro['orcamento_numero'])): ?>
                    O dimensionamento ja gerou o orcamento <?= (int) $registro['orcamento_numero'] ?> e nao pode ser excluido.
                <?php else: ?>
                    Remove itens, servicos, materiais, cotacoes e o historico. A operacao nao pode ser desfeita.
                <?php endif; ?>
            </div>

            <?php if (empty($registro['orcamento_numero'])): ?>
                <div class="form-acoes" style="justify-content:flex-start;margin-top:8px">
                    <?= form_open('orcamentos/dimensionamento_excluir/' . $registro['numero'],
                        array('onsubmit' => "return confirm('Excluir definitivamente o dimensionamento " . (int) $registro['numero'] . "?')")) ?>
                        <button type="submit" class="botao perigo pequeno">Excluir definitivamente</button>
                    <?= form_close() ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
