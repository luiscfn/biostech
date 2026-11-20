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
        <div class="dado dado-local">
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
            <div class="rotulo">Tecnico</div>
            <?= html_escape($registro['tecnico_nome'] ? $registro['tecnico_nome'] : '-') ?>
        </div>
        <div class="dado">
            <div class="rotulo">QTDE DE TECNICOS</div>
            <?= $registro['qtd_tecnicos'] !== NULL ? (int) $registro['qtd_tecnicos'] : '-' ?>
        </div>
        <div class="dado dado-dias-necessarios">
            <div class="rotulo">DIAS NECESSÁRIOS PARA O SERVIÇO</div>
            <div class="sub-rotulos-dias">
                <div class="sub-rotulo">COMERCIAIS</div>
                <div class="sub-rotulo">NÃO COMERCIAIS</div>
            </div>
            <div class="valores-dias">
                <div><?= $registro['qtd_comerciais'] !== NULL ? (int) $registro['qtd_comerciais'] : '-' ?></div>
                <div><?= $registro['qtd_nao_comerciais'] !== NULL ? (int) $registro['qtd_nao_comerciais'] : '-' ?></div>
            </div>
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

    <h4 class="secao-titulo destaque titulo-grande">Levantamento</h4>

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

    <hr class="divisor">

    <h4 class="secao-titulo destaque titulo-grande">Medidas e caracteristicas</h4>

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
            <span class="badge badge-caracteristica <?= bd_bool($registro[$campo]) ? 'info' : 'off' ?>"><?= html_escape($rotulo) ?>: <?= bd_bool($registro[$campo]) ? 'sim' : 'nao' ?></span>
        <?php endforeach; ?>
    </div>

    <?php if ($pode_editar): ?>
        <hr class="divisor">
        <h4 class="secao-titulo destaque titulo-grande">Acoes</h4>

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
