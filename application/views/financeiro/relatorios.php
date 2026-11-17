<?php
$tipos = array(
    'cp'      => 'Contas a pagar',
    'cr'      => 'Contas a receber',
    'fluxo'   => 'Fluxo de caixa',
    'posicao' => 'Posicao bancaria',
    'balanco' => 'Balanco',
);
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>Relatorios do financeiro</h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('financeiro') ?>">Painel</a>
            <button type="button" class="botao secundario pequeno" onclick="window.print()">Imprimir</button>
        </div>
    </div>

    <div class="painel-corpo">
        <form method="get" action="<?= site_url('financeiro/relatorios') ?>" class="filtros">
            <select name="tipo">
                <?php foreach ($tipos as $valor => $rotulo): ?>
                    <option value="<?= $valor ?>" <?= $tipo === $valor ? 'selected' : '' ?>><?= html_escape($rotulo) ?></option>
                <?php endforeach; ?>
            </select>

            <input type="date" name="de" value="<?= html_escape($de) ?>" title="De">
            <input type="date" name="ate" value="<?= html_escape($ate) ?>" title="Ate">

            <?php if ($tipo === 'cp' || $tipo === 'cr'): ?>
                <select name="situacao">
                    <option value="">Situacao</option>
                    <option value="aberta"    <?= $situacao === 'aberta' ? 'selected' : '' ?>>Aberta</option>
                    <option value="vencido"   <?= $situacao === 'vencido' ? 'selected' : '' ?>>Aberta e vencida</option>
                    <option value="baixada"   <?= $situacao === 'baixada' ? 'selected' : '' ?>>Baixada</option>
                    <option value="cancelada" <?= $situacao === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                </select>
            <?php endif; ?>

            <button type="submit" class="botao secundario">Gerar</button>
        </form>
    </div>

    <div class="painel-cabecalho">
        <h3>
            <?= html_escape($tipos[$tipo]) ?>
            <span class="tag"><?= data_br($de) ?> a <?= data_br($ate) ?></span>
        </h3>

        <div class="acoes-ficha">
            <?php if ($tipo === 'cp' || $tipo === 'cr' || $tipo === 'fluxo'): ?>
                <span class="badge <?= $total < 0 ? 'erro' : 'ok' ?>">
                    Total: <?= moeda_br($total) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="tabela-wrap">
        <?php if ($tipo === 'posicao'): ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Banco</th>
                        <th>Agencia</th>
                        <th>Conta</th>
                        <th>Titular</th>
                        <th class="num">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($posicao)): ?>
                        <tr><td colspan="5" class="vazio">Nenhuma conta bancaria cadastrada.</td></tr>
                    <?php else: ?>
                        <?php $soma_posicao = 0.0; ?>
                        <?php foreach ($posicao as $p): ?>
                            <?php $soma_posicao += (float) $p['saldo']; ?>
                            <tr>
                                <td><?= html_escape($p['banco_nome'] ? $p['banco_nome'] : $p['banco_codigo']) ?></td>
                                <td><?= html_escape($p['agencia'] ? $p['agencia'] : '-') ?></td>
                                <td><?= html_escape($p['conta'] ? $p['conta'] : '-') ?></td>
                                <td><?= html_escape($p['titular'] ? $p['titular'] : '-') ?></td>
                                <td class="num"><strong><?= moeda_br($p['saldo']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="num">Total</th>
                        <th class="num"><?= moeda_br(isset($soma_posicao) ? $soma_posicao : 0) ?></th>
                    </tr>
                </tfoot>
            </table>

        <?php elseif ($tipo === 'fluxo'): ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th style="width:110px">Data</th>
                        <th>Descricao</th>
                        <th class="centro" style="width:90px">Tipo</th>
                        <th class="num" style="width:120px">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registros)): ?>
                        <tr><td colspan="4" class="vazio">Nenhum movimento no periodo.</td></tr>
                    <?php else: ?>
                        <?php $saldo_fluxo = 0.0; ?>
                        <?php foreach ($registros as $r): ?>
                            <?php
                            $entrada = $r['tipo'] === 'C';
                            $saldo_fluxo += $entrada ? (float) $r['valor'] : -(float) $r['valor'];
                            ?>
                            <tr>
                                <td><?= data_br($r['data']) ?></td>
                                <td><?= html_escape($r['descricao']) ?></td>
                                <td class="centro">
                                    <?php if ($entrada): ?>
                                        <span class="badge ok">Entrada</span>
                                    <?php else: ?>
                                        <span class="badge erro">Saida</span>
                                    <?php endif; ?>
                                </td>
                                <td class="num">
                                    <?= $entrada ? '' : '-' ?><?= moeda_br($r['valor']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="num">Saldo do periodo</th>
                        <th class="num"><?= moeda_br($total) ?></th>
                    </tr>
                </tfoot>
            </table>

        <?php elseif ($tipo === 'balanco'): ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th style="width:130px">Periodo</th>
                        <th style="width:80px">Mes/Ano</th>
                        <th class="num">Bruto faturado</th>
                        <th class="num">ISS</th>
                        <th class="num">Liquido faturado</th>
                        <th class="num">Pago</th>
                        <th class="num">A pagar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registros)): ?>
                        <tr><td colspan="7" class="vazio">Nenhum balanco no periodo.</td></tr>
                    <?php else: ?>
                        <?php foreach ($registros as $r): ?>
                            <tr>
                                <td><?= data_br($r['data_inicio']) ?> a <?= data_br($r['data_fim']) ?></td>
                                <td><?= html_escape($r['mes_ano'] ? $r['mes_ano'] : '-') ?></td>
                                <td class="num"><?= moeda_br($r['bruto_faturado']) ?></td>
                                <td class="num"><?= moeda_br($r['iss_faturado']) ?></td>
                                <td class="num"><strong><?= moeda_br($r['liquido_faturado']) ?></strong></td>
                                <td class="num"><?= moeda_br($r['bruto_pago']) ?></td>
                                <td class="num"><?= moeda_br($r['bruto_pagar']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($tipo === 'cr'): ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th style="width:95px">Titulo</th>
                        <th style="width:90px">Emissao</th>
                        <th>Cliente</th>
                        <th style="width:110px">Documento</th>
                        <th style="width:100px">Vencimento</th>
                        <th class="num" style="width:110px">Valor</th>
                        <th class="num" style="width:110px">Liquido</th>
                        <th class="centro" style="width:100px">Situacao</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registros)): ?>
                        <tr><td colspan="8" class="vazio">Nenhum titulo no periodo.</td></tr>
                    <?php else: ?>
                        <?php foreach ($registros as $r): ?>
                            <tr>
                                <td>
                                    <a href="<?= site_url('financeiro/conta_receber_ver/' . $r['codigo']) ?>">
                                        <?= (int) $r['codigo'] ?>/<?= (int) $r['parcela'] ?>
                                    </a>
                                </td>
                                <td><?= data_br($r['data_emissao']) ?></td>
                                <td><?= html_escape($r['cliente_nome'] ? $r['cliente_nome'] : 'Sem cliente') ?></td>
                                <td><?= html_escape($r['numero_documento'] ? $r['numero_documento'] : '-') ?></td>
                                <td><?= data_br($r['data_vencimento']) ?></td>
                                <td class="num"><?= moeda_br($r['valor']) ?></td>
                                <td class="num"><strong><?= moeda_br($r['vliq']) ?></strong></td>
                                <td class="centro">
                                    <?php if (bd_bool($r['cancelado'])): ?>
                                        <span class="badge off">Cancelada</span>
                                    <?php elseif (bd_bool($r['arquivada'])): ?>
                                        <span class="badge info">Arquivada</span>
                                    <?php else: ?>
                                        <span class="badge alerta">Aberta</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="num">Total</th>
                        <th class="num"><?= moeda_br($total) ?></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>

        <?php else: ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th style="width:95px">Titulo</th>
                        <th style="width:90px">Emissao</th>
                        <th>Fornecedor</th>
                        <th style="width:110px">Documento</th>
                        <th style="width:100px">Vencimento</th>
                        <th>Centro de custo</th>
                        <th class="num" style="width:110px">Valor</th>
                        <th class="centro" style="width:100px">Situacao</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registros)): ?>
                        <tr><td colspan="8" class="vazio">Nenhum titulo no periodo.</td></tr>
                    <?php else: ?>
                        <?php foreach ($registros as $r): ?>
                            <tr>
                                <td>
                                    <a href="<?= site_url('financeiro/conta_pagar_ver/' . $r['id']) ?>">
                                        <?= (int) $r['numero'] ?>/<?= (int) $r['parcela'] ?>
                                    </a>
                                </td>
                                <td><?= data_br($r['emissao']) ?></td>
                                <td><?= html_escape($r['fornecedor_nome'] ? $r['fornecedor_nome'] : 'Sem fornecedor') ?></td>
                                <td><?= html_escape($r['documento'] ? $r['documento'] : '-') ?></td>
                                <td><?= data_br($r['vencimento']) ?></td>
                                <td><?= html_escape($r['centro_custo_nome'] ? $r['centro_custo_nome'] : '-') ?></td>
                                <td class="num"><?= moeda_br($r['valor']) ?></td>
                                <td class="centro">
                                    <?php if ((int) $r['situacao'] === 2): ?>
                                        <span class="badge ok">Baixada</span>
                                    <?php elseif ((int) $r['situacao'] === 3 || bd_bool($r['cancelado'])): ?>
                                        <span class="badge off">Cancelada</span>
                                    <?php else: ?>
                                        <span class="badge alerta">Aberta</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="6" class="num">Total</th>
                        <th class="num"><?= moeda_br($total) ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </div>
</div>
