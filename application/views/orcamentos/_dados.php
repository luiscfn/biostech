<?php
$tem_os = ! empty($registro['chamado_numero']);
?>
<div class="painel-corpo">
    <div class="ficha-grade">
        <div class="dado">
            <div class="rotulo">Atencao</div>
            <?= html_escape($registro['atencao'] ? $registro['atencao'] : '-') ?>
        </div>
        <div class="dado">
            <div class="rotulo">Equipamento / serie</div>
            <?= html_escape($registro['equipamento_serie'] ? $registro['equipamento_serie'] : '-') ?>
        </div>
        <div class="dado">
            <div class="rotulo">Nota fiscal de chegada</div>
            <?= html_escape($registro['nota_fiscal_chegada'] ? $registro['nota_fiscal_chegada'] : '-') ?>
        </div>
        <div class="dado">
            <div class="rotulo">Finalidade</div>
            <?= $registro['finalidade'] !== NULL ? (int) $registro['finalidade'] : '-' ?>
        </div>
        <div class="dado">
            <div class="rotulo">Condicao de pagamento</div>
            <?= $registro['condicao_pagamento'] !== NULL ? (int) $registro['condicao_pagamento'] : '-' ?>
        </div>
        <div class="dado">
            <div class="rotulo">Entrega</div>
            <?= $registro['entrega'] !== NULL ? (int) $registro['entrega'] . ' dia(s)' : '-' ?>
        </div>
        <div class="dado">
            <div class="rotulo">Garantia</div>
            <?= $registro['garantia'] !== NULL ? (int) $registro['garantia'] . ' dia(s)' : '-' ?>
        </div>
        <div class="dado">
            <div class="rotulo">Validade da proposta</div>
            <?= $registro['validade'] !== NULL ? (int) $registro['validade'] . ' dia(s)' : '-' ?>
        </div>
        <div class="dado">
            <div class="rotulo">Validade das pecas</div>
            <?= $registro['validade_pecas'] !== NULL ? (int) $registro['validade_pecas'] . ' dia(s)' : '-' ?>
        </div>
        <div class="dado">
            <div class="rotulo">Assinatura</div>
            <?= html_escape($registro['assinatura'] ? $registro['assinatura'] : '-') ?>
        </div>
        <div class="dado">
            <div class="rotulo">Data de envio</div>
            <?= data_br($registro['data_envio']) ?>
        </div>
        <div class="dado">
            <div class="rotulo">Data de aprovacao</div>
            <?= data_br($registro['data_aprovacao']) ?>
            <?php if ( ! empty($registro['data_resultado'])): ?>
                <div class="ajuda">resultado em <?= data_br($registro['data_resultado']) ?></div>
            <?php endif; ?>
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

    <h4 class="secao-titulo">Composicao de valores</h4>

    <div class="ficha-grade">
        <div class="dado">
            <div class="rotulo">Material</div>
            <?= moeda_br($registro['valor_material']) ?>
        </div>
        <div class="dado">
            <div class="rotulo">Mao de obra</div>
            <?= moeda_br($registro['valor_mao_obra']) ?>
            <?php if ($registro['percentual_mao_obra'] !== NULL): ?>
                <div class="ajuda"><?= numero_br($registro['percentual_mao_obra'], 2) ?>%</div>
            <?php endif; ?>
        </div>
        <div class="dado">
            <div class="rotulo">Material de fixacao</div>
            <?= moeda_br($registro['material_fixacao']) ?>
            <?php if ($registro['percentual_mat_fixacao'] !== NULL): ?>
                <div class="ajuda"><?= numero_br($registro['percentual_mat_fixacao'], 2) ?>%</div>
            <?php endif; ?>
        </div>
        <div class="dado">
            <div class="rotulo">Acrescimo de custo</div>
            <?= moeda_br($registro['acrescimo_custo']) ?>
            <?php if ($registro['percentual_acrescimo_custo'] !== NULL): ?>
                <div class="ajuda"><?= numero_br($registro['percentual_acrescimo_custo'], 2) ?>%</div>
            <?php endif; ?>
        </div>
        <div class="dado">
            <div class="rotulo">Imposto / nota fiscal</div>
            <?= moeda_br($registro['valor_imposto']) ?>
            <?php if ($registro['percentual_nota_fiscal'] !== NULL): ?>
                <div class="ajuda"><?= numero_br($registro['percentual_nota_fiscal'], 2) ?>%</div>
            <?php endif; ?>
        </div>
        <div class="dado">
            <div class="rotulo">Servicos com valor</div>
            <?= moeda_br($registro['valor_servico']) ?>
            <?php if ($registro['percentual_servico'] !== NULL): ?>
                <div class="ajuda"><?= numero_br($registro['percentual_servico'], 2) ?>%</div>
            <?php endif; ?>
        </div>
        <div class="dado">
            <div class="rotulo">Desconto</div>
            <?= moeda_br($registro['valor_desconto']) ?>
            <?php if ($registro['percentual_desconto'] !== NULL): ?>
                <div class="ajuda"><?= numero_br($registro['percentual_desconto'], 2) ?>%</div>
            <?php endif; ?>
        </div>
        <div class="dado">
            <div class="rotulo">Custos indiretos</div>
            <?= moeda_br($registro['valor_custos_indiretos']) ?>
        </div>
        <div class="dado">
            <div class="rotulo">Valor maximo do cliente</div>
            <?= moeda_br($registro['valor_maximo_cliente']) ?>
        </div>
        <div class="dado">
            <div class="rotulo">Total</div>
            <strong><?= moeda_br($registro['valor_total']) ?></strong>
        </div>
    </div>

    <hr class="divisor">

    <div class="dado">
        <div class="rotulo">Observacoes</div>
        <div class="texto-longo"><?= nl2br(html_escape($registro['observacao'] ? $registro['observacao'] : '-')) ?></div>
    </div>

    <?php if ( ! empty($registro['motivo_cancelamento'])): ?>
        <div class="alert alert-warning" style="margin-top:16px">
            Motivo do cancelamento: <?= html_escape($registro['motivo_cancelamento']) ?>
        </div>
    <?php endif; ?>

    <?php if ($pode_editar): ?>
        <hr class="divisor">
        <h4 class="secao-titulo">Acoes</h4>

        <div class="form-grid">
            <div class="campo" style="grid-column:span 6">
                <label>Excluir o orcamento</label>
                <div class="ajuda">
                    <?php if ($tem_os): ?>
                        O orcamento esta vinculado ao chamado <?= html_escape($registro['chamado_numero']) ?>.
                        Remova o vinculo antes de excluir.
                    <?php else: ?>
                        A exclusao remove itens, servicos, contatos e o log do orcamento. A operacao nao pode ser desfeita.
                    <?php endif; ?>
                </div>

                <?php if ( ! $tem_os): ?>
                    <div class="form-acoes" style="justify-content:flex-start;margin-top:8px">
                        <?= form_open('orcamentos/excluir/' . $registro['numero']) ?>
                            <button type="submit" class="botao perigo pequeno"
                                    onclick="return confirm('Excluir definitivamente o orcamento <?= (int) $registro['numero'] ?>?')">
                                Excluir definitivamente
                            </button>
                        <?= form_close() ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="campo" style="grid-column:span 6">
                <label>Gerar chamado</label>
                <div class="ajuda">
                    Requisitos: sem OS vinculada, situacao Concluido ou Encerrado, resultado Aprovado,
                    data de envio e data de aprovacao preenchidas e cliente definido.
                </div>
                <div class="form-acoes" style="justify-content:flex-start;margin-top:8px">
                    <a class="botao secundario pequeno" href="<?= site_url('orcamentos/status/' . $registro['numero']) ?>">Alterar status / datas</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
