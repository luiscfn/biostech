<?php
$aberto    = $registro['situacao'] === 'aberto';
$cancelado = $registro['situacao'] === 'cancelado';
?>
<div class="painel-corpo">
    <div class="ficha-grade">
        <div class="dado">
            <div class="rotulo">Numero da proposta</div>
            <?= html_escape($registro['numero_proposta'] ? $registro['numero_proposta'] : '-') ?>
        </div>
        <div class="dado">
            <div class="rotulo">Solicitacao interna</div>
            <?php if ( ! empty($registro['solicitacao_numero'])): ?>
                <a href="<?= site_url('compras/solicitacao/' . $registro['solicitacao_numero']) ?>"><?= (int) $registro['solicitacao_numero'] ?></a>
            <?php else: ?>
                -
            <?php endif; ?>
        </div>
        <div class="dado">
            <div class="rotulo">Condicao de pagamento</div>
            <?= html_escape($registro['condicao_pagamento'] !== NULL ? $registro['condicao_pagamento'] : '-') ?>
        </div>
        <div class="dado">
            <div class="rotulo">Prazo de entrega</div>
            <?= $registro['prazo_entrega'] !== NULL ? (int) $registro['prazo_entrega'] . ' dia(s)' : '-' ?>
        </div>
        <div class="dado">
            <div class="rotulo">Forma de pagamento</div>
            <?= html_escape($registro['forma_recebimento'] !== NULL ? $registro['forma_recebimento'] : '-') ?>
        </div>
        <div class="dado">
            <div class="rotulo">Documento</div>
            <?= html_escape($registro['documento_recebimento'] !== NULL ? $registro['documento_recebimento'] : '-') ?>
        </div>
        <div class="dado">
            <div class="rotulo">Centro de custo</div>
            <?= $registro['centro_custo'] !== NULL ? (int) $registro['centro_custo'] : '-' ?>
        </div>
        <div class="dado">
            <div class="rotulo">1o vencimento</div>
            <?= data_br($registro['data_vencimento']) ?>
        </div>
        <div class="dado">
            <div class="rotulo">Nota fiscal</div>
            <?= html_escape($registro['num_nf'] ? $registro['num_nf'] : '-') ?>
            <?php if ( ! empty($registro['data_nf'])): ?>
                <div class="ajuda"><?= data_br($registro['data_nf']) ?></div>
            <?php endif; ?>
        </div>
        <div class="dado">
            <div class="rotulo">Local de entrega</div>
            <?= html_escape($registro['local_entrega'] ? $registro['local_entrega'] : '-') ?>
        </div>
        <div class="dado">
            <div class="rotulo">Responsavel pelo recebimento</div>
            <?= html_escape($registro['responsavel_recebimento'] ? $registro['responsavel_recebimento'] : '-') ?>
            <?php if ( ! empty($registro['fone_responsavel'])): ?>
                <div class="ajuda"><?= html_escape($registro['fone_responsavel']) ?></div>
            <?php endif; ?>
        </div>
        <div class="dado">
            <div class="rotulo">Cadastro</div>
            <?= html_escape($registro['usuario_cadastro'] ? $registro['usuario_cadastro'] : '-') ?>
            <div class="ajuda"><?= data_hora_br($registro['data_cadastro']) ?></div>
        </div>
        <div class="dado">
            <div class="rotulo">Fechamento</div>
            <?= html_escape($registro['usuario_fechamento'] ? $registro['usuario_fechamento'] : '-') ?>
            <?php if ( ! empty($registro['data_fechamento'])): ?>
                <div class="ajuda"><?= data_hora_br($registro['data_fechamento']) ?></div>
            <?php endif; ?>
        </div>
        <div class="dado">
            <div class="rotulo">Conta a pagar</div>
            <?= $registro['conta_pagar_numero'] !== NULL ? (int) $registro['conta_pagar_numero'] : '-' ?>
        </div>
    </div>

    <div class="dado" style="margin-top:16px">
        <div class="rotulo">Observacoes</div>
        <div class="texto-longo"><?= nl2br(html_escape($registro['observacao'] ? $registro['observacao'] : '-')) ?></div>
    </div>

    <?php if ($registro['situacao'] === 'cancelado'): ?>
        <div class="alert alert-warning" style="margin-top:16px">
            Cancelado por <?= html_escape($registro['usuario_cancelamento']) ?>
            em <?= data_hora_br($registro['data_cancelamento']) ?>.
            Motivo: <?= html_escape($registro['motivo_cancelamento']) ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty($prazos)): ?>
        <hr class="divisor">
        <h4 class="secao-titulo">Prazos cadastrados para o fornecedor</h4>

        <div class="tabela-wrap">
            <table class="tabela">
                <thead>
                    <tr><th style="width:70px">Codigo</th><th>Forma de pagamento</th><th>Prazo</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($prazos as $p): ?>
                        <tr>
                            <td><?= (int) $p['codigo'] ?></td>
                            <td><?= html_escape($p['forma_pagamento'] ? $p['forma_pagamento'] : '-') ?></td>
                            <td><?= html_escape($p['prazo'] ? $p['prazo'] : '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($pode_editar): ?>
        <hr class="divisor">
        <h4 class="secao-titulo">Acoes</h4>

        <div class="form-grid">
            <?php if ($aberto): ?>
                <div class="campo" style="grid-column:span 6">
                    <?= form_open('compras/cancelar/' . $registro['numero']) ?>
                        <label for="motivo_cancelamento">Cancelar o pedido</label>
                        <input type="text" name="motivo" id="motivo_cancelamento" maxlength="200" placeholder="Motivo do cancelamento" required>
                        <div class="ajuda">O cancelamento mantem os itens e permite reabrir depois.</div>
                        <div class="form-acoes" style="justify-content:flex-start;margin-top:8px">
                            <button type="submit" class="botao perigo pequeno">Cancelar pedido</button>
                        </div>
                    <?= form_close() ?>
                </div>

                <div class="campo" style="grid-column:span 6">
                    <?= form_open('compras/excluir/' . $registro['numero']) ?>
                        <label>Excluir o pedido</label>
                        <div class="ajuda">Somente pedidos abertos podem ser excluidos. A exclusao remove itens e parcelas.</div>
                        <div class="form-acoes" style="justify-content:flex-start;margin-top:8px">
                            <button type="submit" class="botao perigo pequeno">Excluir definitivamente</button>
                        </div>
                    <?= form_close() ?>
                </div>
            <?php elseif ($cancelado): ?>
                <div class="campo" style="grid-column:span 6">
                    <?= form_open('compras/reabrir/' . $registro['numero']) ?>
                        <label>Reabrir o pedido</label>
                        <div class="ajuda">Volta a situacao Aberto para permitir correcoes.</div>
                        <div class="form-acoes" style="justify-content:flex-start;margin-top:8px">
                            <button type="submit" class="botao pequeno">Reabrir pedido</button>
                        </div>
                    <?= form_close() ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
