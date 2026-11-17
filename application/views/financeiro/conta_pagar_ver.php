<?php
$situacao = (int) $registro['situacao'];
$cancelada = $situacao === 3 || bd_bool($registro['cancelado']);
$pode_editar = in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE);
$total_baixado = 0.0;

foreach ($baixas as $b)
{
    $total_baixado += (float) $b['valor_pago'];
}
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>
            Conta a pagar <?= (int) $registro['numero'] ?>/<?= (int) $registro['parcela'] ?>
            <?php if ($cancelada): ?>
                <span class="badge off">Cancelada</span>
            <?php elseif ($situacao === 2): ?>
                <span class="badge ok">Baixada</span>
            <?php else: ?>
                <span class="badge alerta">Aberta</span>
            <?php endif; ?>
        </h3>

        <div class="acoes-ficha">
            <?php if ($situacao === 1 && $pode_editar): ?>
                <a class="botao secundario pequeno" href="<?= site_url('financeiro/conta_pagar_editar/' . $registro['id']) ?>">Editar</a>
            <?php endif; ?>

            <?php if ($situacao === 1): ?>
                <a class="botao pequeno" href="<?= site_url('financeiro/conta_pagar_baixar/' . $registro['id']) ?>">Baixar</a>
            <?php endif; ?>

            <a class="botao secundario pequeno" href="<?= site_url('financeiro/conta_pagar_comprovante/' . $registro['id']) ?>" target="_blank">Comprovante</a>
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/contas_pagar') ?>">Voltar</a>
        </div>
    </div>

    <div class="painel-corpo">
        <div class="detalhes">
            <div class="detalhe">
                <div class="rotulo">Fornecedor</div>
                <div class="valor">
                    <?= html_escape($registro['fornecedor_nome'] ? $registro['fornecedor_nome'] : 'Sem fornecedor') ?>
                    <?php if ( ! empty($registro['fornecedor_cnpj'])): ?>
                        <div class="ajuda">CNPJ <?= html_escape($registro['fornecedor_cnpj']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Valor do titulo</div>
                <div class="valor"><strong><?= moeda_br($registro['valor']) ?></strong></div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Emissao / Lancamento</div>
                <div class="valor">
                    <?= data_br($registro['emissao']) ?>
                    <div class="ajuda">lancamento <?= data_br($registro['lancamento']) ?></div>
                </div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Vencimento</div>
                <div class="valor"><?= data_br($registro['vencimento']) ?></div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Documento</div>
                <div class="valor"><?= html_escape($registro['documento'] ? $registro['documento'] : '-') ?></div>
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
                <div class="valor">
                    <?= (int) $registro['parcela'] ?> de <?= (int) $registro['total_parcelas'] ?>
                    <?php if ($registro['posicao'] !== NULL): ?>
                        <div class="ajuda">posicao <?= (int) $registro['posicao'] ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Origem</div>
                <div class="valor">
                    <?= html_escape($registro['origem']) ?>
                    <?php if ($registro['origem_id']): ?>
                        <div class="ajuda"><?= html_escape($registro['origem_id']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Cadastro</div>
                <div class="valor">
                    <?= html_escape($registro['usuario_cadastro'] ? $registro['usuario_cadastro'] : '-') ?>
                    <div class="ajuda"><?= data_hora_br($registro['data_cadastro']) ?></div>
                </div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Baixa</div>
                <div class="valor">
                    <?= html_escape($registro['usuario_baixa'] ? $registro['usuario_baixa'] : '-') ?>
                    <div class="ajuda">
                        <?= data_br($registro['data_baixa']) ?>
                        <?= $registro['valor_baixado'] !== NULL ? ' | ' . moeda_br($registro['valor_baixado']) : '' ?>
                    </div>
                </div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Retorno de caixa</div>
                <div class="valor">
                    <?= bd_bool($registro['ret_caixa']) ? 'Sim' : 'Nao' ?>
                    <?php if ( ! empty($registro['data_caixa'])): ?>
                        <div class="ajuda"><?= data_br($registro['data_caixa']) ?></div>
                    <?php endif; ?>
                </div>
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
                            <th style="width:110px">Pagamento</th>
                            <th class="num">Multa</th>
                            <th class="num">Juros</th>
                            <th class="num">Desconto</th>
                            <th class="num">Valor atualizado</th>
                            <th class="num">Valor pago</th>
                            <th>Conta</th>
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
                                <td class="num"><strong><?= moeda_br($b['valor_pago']) ?></strong></td>
                                <td><?= html_escape($b['conta_nome'] ? $b['conta_nome'] : '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="num">Total pago</th>
                            <th class="num"><?= moeda_br($total_baixado) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>

        <?php if ( ! empty($log)): ?>
            <hr class="divisor">
            <h4 class="secao-titulo">Historico de alteracoes (<?= count($log) ?>)</h4>

            <div class="tabela-wrap">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th style="width:140px">Data</th>
                            <th style="width:110px">Usuario</th>
                            <th style="width:60px">Tipo</th>
                            <th>Alteracoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($log as $l): ?>
                            <tr>
                                <td><?= data_hora_br($l['data']) ?></td>
                                <td><?= html_escape($l['usuario'] ? $l['usuario'] : '-') ?></td>
                                <td><?= html_escape($l['tipo_mov'] ? $l['tipo_mov'] : '-') ?></td>
                                <td>
                                    <?php
                                    $dados = $l['dados'];

                                    if (is_string($dados))
                                    {
                                        $dados = json_decode($dados, TRUE);
                                    }

                                    if (empty($dados) || ! is_array($dados))
                                    {
                                        echo '-';
                                    }
                                    else
                                    {
                                        echo '<ul class="lista-eventos">';

                                        foreach ($dados as $campo => $par)
                                        {
                                            $antes  = is_array($par) && array_key_exists(0, $par) ? $par[0] : '';
                                            $depois = is_array($par) && array_key_exists(1, $par) ? $par[1] : '';

                                            if (is_array($antes) || is_array($depois))
                                            {
                                                $antes  = json_encode($antes, JSON_UNESCAPED_UNICODE);
                                                $depois = json_encode($depois, JSON_UNESCAPED_UNICODE);
                                            }

                                            echo '<li><span class="quem">' . html_escape($campo) . ':</span> '
                                                . html_escape((string) $antes) . ' -> '
                                                . html_escape((string) $depois) . '</li>';
                                        }

                                        echo '</ul>';
                                    }
                                    ?>
                                </td>
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
                <?php if ($situacao === 2): ?>
                    <div class="campo" style="grid-column:span 6">
                        <?= form_open('financeiro/conta_pagar_estornar/' . $registro['id']) ?>
                            <label>Estornar a baixa</label>
                            <div class="ajuda">Remove a baixa e devolve o titulo para Aberta.</div>
                            <div class="form-acoes" style="justify-content:flex-start;margin-top:8px">
                                <button type="submit" class="botao pequeno">Estornar baixa</button>
                            </div>
                        <?= form_close() ?>
                    </div>
                <?php endif; ?>

                <?php if ($situacao === 1): ?>
                    <div class="campo" style="grid-column:span 6">
                        <?= form_open('financeiro/conta_pagar_cancelar/' . $registro['id']) ?>
                            <label for="motivo_cancelamento">Cancelar o titulo</label>
                            <input type="text" name="motivo" id="motivo_cancelamento" maxlength="200" placeholder="Motivo do cancelamento">
                            <div class="ajuda">O titulo cancelado nao pode ser baixado.</div>
                            <div class="form-acoes" style="justify-content:flex-start;margin-top:8px">
                                <button type="submit" class="botao perigo pequeno">Cancelar titulo</button>
                            </div>
                        <?= form_close() ?>
                    </div>
                <?php endif; ?>

                <?php if ($situacao !== 2): ?>
                    <div class="campo" style="grid-column:span 6">
                        <?= form_open('financeiro/conta_pagar_excluir/' . $registro['id']) ?>
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
