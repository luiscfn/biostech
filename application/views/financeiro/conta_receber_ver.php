<?php
$cancelada = bd_bool($registro['cancelado']);
$baixada   = ! empty($baixas);
$aberta    = ! $cancelada && ! $baixada;
$pode_editar = in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE);

$total_recebido = 0.0;

foreach ($baixas as $b)
{
    $total_recebido += (float) $b['valor_recebido'];
}
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>
            Conta a receber <?= (int) $registro['codigo'] ?>/<?= (int) $registro['parcela'] ?>
            <?php if ($cancelada): ?>
                <span class="badge off">Cancelada</span>
            <?php elseif ($baixada): ?>
                <span class="badge ok">Baixada</span>
            <?php else: ?>
                <span class="badge alerta">Aberta</span>
            <?php endif; ?>

            <?php if (bd_bool($registro['arquivada'])): ?>
                <span class="badge info">Arquivada</span>
            <?php endif; ?>
        </h3>

        <div class="acoes-ficha">
            <?php if ($aberta && $pode_editar): ?>
                <a class="botao secundario pequeno" href="<?= site_url('financeiro/conta_receber_editar/' . $registro['codigo']) ?>">Editar</a>
            <?php endif; ?>

            <?php if ($aberta): ?>
                <a class="botao pequeno" href="<?= site_url('financeiro/conta_receber_baixar/' . $registro['codigo']) ?>">Receber</a>
            <?php endif; ?>

            <a class="botao secundario pequeno" href="<?= site_url('financeiro/conta_receber_comprovante/' . $registro['codigo']) ?>" target="_blank">Comprovante</a>
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/contas_receber') ?>">Voltar</a>
        </div>
    </div>

    <div class="painel-corpo">
        <div class="detalhes">
            <div class="detalhe">
                <div class="rotulo">Cliente</div>
                <div class="valor">
                    <?= html_escape($registro['cliente_nome'] ? $registro['cliente_nome'] : 'Sem cliente') ?>
                    <?php if ( ! empty($registro['cliente_cnpj'])): ?>
                        <div class="ajuda">CPF/CNPJ <?= html_escape($registro['cliente_cnpj']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Valor / Liquido</div>
                <div class="valor">
                    <strong><?= moeda_br($registro['valor']) ?></strong>
                    <div class="ajuda">liquido <?= moeda_br($registro['vliq']) ?></div>
                </div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Emissao / Vencimento</div>
                <div class="valor">
                    <?= data_br($registro['data_emissao']) ?>
                    <div class="ajuda">vence <?= data_br($registro['data_vencimento']) ?></div>
                </div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Documento / NF</div>
                <div class="valor">
                    <?= html_escape($registro['numero_documento'] ? $registro['numero_documento'] : '-') ?>
                    <?php if ( ! empty($registro['numero_nota_fiscal'])): ?>
                        <div class="ajuda">NF <?= html_escape($registro['numero_nota_fiscal']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detalhe">
                <div class="rotulo">OS / Chamado</div>
                <div class="valor"><?= html_escape($registro['chamado_numero'] ? $registro['chamado_numero'] : '-') ?></div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Centro de custo</div>
                <div class="valor"><?= html_escape($registro['centro_custo_nome'] ? $registro['centro_custo_nome'] : '-') ?></div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Banco / Agencia / Conta</div>
                <div class="valor">
                    <?= html_escape($registro['banco'] ? $registro['banco'] : '-') ?>
                    <?= $registro['agencia'] ? ' | ' . html_escape($registro['agencia']) : '' ?>
                    <?= $registro['conta'] ? ' | ' . html_escape($registro['conta']) : '' ?>
                </div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Parcela</div>
                <div class="valor"><?= (int) $registro['parcela'] ?> de <?= (int) $registro['num_parcelas'] ?></div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Desconto / Despesas</div>
                <div class="valor">
                    <?= moeda_br($registro['desconto_especial']) ?> / <?= moeda_br($registro['vdesp']) ?>
                </div>
            </div>

            <div class="detalhe">
                <div class="rotulo">ISS</div>
                <div class="valor">
                    <?= moeda_br($registro['viss']) ?>
                    <?php if ($registro['aliq_issqn'] !== NULL): ?>
                        <div class="ajuda"><?= number_format((float) $registro['aliq_issqn'], 4, ',', '') ?>%</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Faturado</div>
                <div class="valor"><?= bd_bool($registro['faturado']) ? 'Sim' : 'Nao' ?></div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Cadastro</div>
                <div class="valor">
                    <?= html_escape($registro['usuario_cadastro'] ? $registro['usuario_cadastro'] : '-') ?>
                    <div class="ajuda"><?= data_hora_br($registro['data_cadastro']) ?></div>
                </div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Recebimento</div>
                <div class="valor">
                    <?= html_escape($registro['usuario_baixa'] ? $registro['usuario_baixa'] : '-') ?>
                    <div class="ajuda"><?= data_br($registro['data_baixa']) ?></div>
                </div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Recebimento pelo caixa</div>
                <div class="valor"><?= bd_bool($registro['ret_caixa']) ? 'Sim' : 'Nao' ?></div>
            </div>
        </div>

        <?php if ( ! empty($registro['historico'])): ?>
            <hr class="divisor">
            <div class="detalhe">
                <div class="rotulo">Historico</div>
                <div class="texto-longo"><?= nl2br(html_escape($registro['historico'])) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($cancelada): ?>
            <div class="alert alert-warning" style="margin-top:16px">
                Cancelada por <?= html_escape($registro['usuario_cancelamento'] ? $registro['usuario_cancelamento'] : '-') ?>
                em <?= data_hora_br($registro['data_cancelamento']) ?>.
            </div>
        <?php endif; ?>

        <?php if ( ! empty($baixas)): ?>
            <hr class="divisor">
            <h4 class="secao-titulo">Baixas (<?= count($baixas) ?>)</h4>

            <div class="tabela-wrap">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th style="width:110px">Recebimento</th>
                            <th class="num">Multa</th>
                            <th class="num">Juros</th>
                            <th class="num">Desconto</th>
                            <th class="num">Valor atualizado</th>
                            <th class="num">Valor recebido</th>
                            <th class="centro">Arquivada</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($baixas as $b): ?>
                            <tr>
                                <td><?= data_br($b['data_pagamento']) ?></td>
                                <td class="num"><?= moeda_br($b['multa']) ?></td>
                                <td class="num"><?= moeda_br($b['juros']) ?></td>
                                <td class="num"><?= moeda_br($b['desconto']) ?></td>
                                <td class="num"><?= moeda_br($b['valor_atualizado']) ?></td>
                                <td class="num"><strong><?= moeda_br($b['valor_recebido']) ?></strong></td>
                                <td class="centro"><?= bd_bool($b['arquivada']) ? 'Sim' : 'Nao' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="num">Total recebido</th>
                            <th class="num"><?= moeda_br($total_recebido) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($pode_editar): ?>
            <hr class="divisor">
            <h4 class="secao-titulo">Acoes</h4>

            <div class="form-grid">
                <?php if ($baixada): ?>
                    <div class="campo" style="grid-column:span 6">
                        <?= form_open('financeiro/conta_receber_estornar/' . $registro['codigo']) ?>
                            <label>Estornar o recebimento</label>
                            <div class="ajuda">Remove a baixa e devolve o titulo para Aberta.</div>
                            <div class="form-acoes" style="justify-content:flex-start;margin-top:8px">
                                <button type="submit" class="botao pequeno">Estornar baixa</button>
                            </div>
                        <?= form_close() ?>
                    </div>
                <?php endif; ?>

                <?php if ( ! $cancelada): ?>
                    <div class="campo" style="grid-column:span 6">
                        <?= form_open('financeiro/conta_receber_cancelar/' . $registro['codigo']) ?>
                            <label for="motivo_cancelamento">Cancelar o titulo</label>
                            <input type="text" name="motivo" id="motivo_cancelamento" maxlength="200" placeholder="Motivo do cancelamento">
                            <div class="form-acoes" style="justify-content:flex-start;margin-top:8px">
                                <button type="submit" class="botao perigo pequeno">Cancelar titulo</button>
                            </div>
                        <?= form_close() ?>
                    </div>
                <?php endif; ?>

                <?php if ( ! $baixada): ?>
                    <div class="campo" style="grid-column:span 6">
                        <?= form_open('financeiro/conta_receber_excluir/' . $registro['codigo']) ?>
                            <label>Excluir o titulo</label>
                            <div class="ajuda">Somente titulos sem baixa. A exclusao e definitiva.</div>
                            <div class="form-acoes" style="justify-content:flex-start;margin-top:8px">
                                <button type="submit" class="botao perigo pequeno">Excluir definitivamente</button>
                            </div>
                        <?= form_close() ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
