<?php
$encerrado = bd_bool($registro['encerrado']);
$total_previsto = (float) $total_previsto;
$valor_plano = ! empty($plano['valor_total']) ? (float) $plano['valor_total'] : $total_previsto;
$qtd_plano = ! empty($plano['quantidade_parcelas']) ? (int) $plano['quantidade_parcelas'] : 1;
?>
<div class="painel-corpo">
    <div class="cartoes">
        <div class="cartao">
            <div class="rotulo">Pecas</div>
            <div class="valor"><?= moeda_br($registro['valor_material']) ?></div>
        </div>
        <div class="cartao">
            <div class="rotulo">Servicos</div>
            <div class="valor"><?= moeda_br($registro['valor_servico']) ?></div>
        </div>
        <div class="cartao">
            <div class="rotulo">Mao de obra</div>
            <div class="valor"><?= moeda_br($registro['valor_mao_obra']) ?></div>
        </div>
        <div class="cartao verde">
            <div class="rotulo">Total do encerramento</div>
            <div class="valor"><?= moeda_br($total_previsto) ?></div>
        </div>
    </div>

    <h4 class="secao-titulo">Plano de parcelas</h4>

    <?php if ( ! $encerrado): ?>
        <?= form_open('chamados/financeiro/' . $registro['numero']) ?>
            <div class="form-grid">
                <div class="campo">
                    <label for="valor_total">Valor total</label>
                    <input type="number" step="0.01" min="0" name="valor_total" id="valor_total"
                           value="<?= number_format($valor_plano, 2, '.', '') ?>">
                </div>
                <div class="campo">
                    <label for="quantidade_parcelas">Quantidade de parcelas</label>
                    <input type="number" min="1" max="12" name="quantidade_parcelas" id="quantidade_parcelas" value="<?= $qtd_plano ?>">
                </div>
                <div class="campo">
                    <label for="data_emissao">Data de emissao</label>
                    <input type="date" name="data_emissao" id="data_emissao"
                           value="<?= html_escape( ! empty($plano['data_emissao']) ? $plano['data_emissao'] : date('Y-m-d')) ?>">
                </div>
                <div class="campo">
                    <label for="primeiro_vencimento">Primeiro vencimento</label>
                    <input type="date" name="primeiro_vencimento" id="primeiro_vencimento"
                           value="<?= html_escape( ! empty($plano['data_vencimento_base']) ? $plano['data_vencimento_base'] : date('Y-m-d')) ?>">
                </div>
                <div class="campo">
                    <label for="intervalo_dias">Intervalo entre parcelas (dias)</label>
                    <input type="number" min="1" name="intervalo_dias" id="intervalo_dias" value="30">
                </div>
                <div class="campo">
                    <label for="condicao_pagamento_codigo">Condicao de pagamento</label>
                    <select name="condicao_pagamento_codigo" id="condicao_pagamento_codigo">
                        <option value="">(nao informada)</option>
                        <?php foreach ($dominios['condicao_pagamento'] as $cod => $nome): ?>
                            <option value="<?= (int) $cod ?>" <?= (string) $cod === (string) ( ! empty($plano['condicao_pagamento_codigo']) ? $plano['condicao_pagamento_codigo'] : '') ? 'selected' : '' ?>><?= html_escape($nome) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="forma_recebimento_codigo">Forma de recebimento</label>
                    <select name="forma_recebimento_codigo" id="forma_recebimento_codigo">
                        <option value="">(nao informada)</option>
                        <?php foreach ($dominios['forma_recebimento'] as $cod => $nome): ?>
                            <option value="<?= (int) $cod ?>" <?= (string) $cod === (string) ( ! empty($plano['forma_recebimento_codigo']) ? $plano['forma_recebimento_codigo'] : '') ? 'selected' : '' ?>><?= html_escape($nome) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="documento_codigo">Documento</label>
                    <select name="documento_codigo" id="documento_codigo">
                        <option value="">(nao informado)</option>
                        <?php foreach ($dominios['documento'] as $cod => $nome): ?>
                            <option value="<?= (int) $cod ?>" <?= (string) $cod === (string) ( ! empty($plano['documento_codigo']) ? $plano['documento_codigo'] : '') ? 'selected' : '' ?>><?= html_escape($nome) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao">Gerar parcelas</button>
            </div>
        <?= form_close() ?>
    <?php endif; ?>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:70px" class="num">Parcela</th>
                    <th class="num" style="width:120px">Valor</th>
                    <th class="num" style="width:90px">Dias</th>
                    <th>Vencimento</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($parcelas)): ?>
                    <tr><td colspan="4" class="vazio">Nenhuma parcela gerada.</td></tr>
                <?php else: ?>
                    <?php foreach ($parcelas as $p): ?>
                        <tr>
                            <td class="num"><?= (int) $p['numero'] ?></td>
                            <td class="num"><?= moeda_br($p['valor']) ?></td>
                            <td class="num"><?= (int) $p['dias'] ?></td>
                            <td><?= data_br($p['vencimento']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h4 class="secao-titulo">Contas a receber geradas no encerramento</h4>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:100px" class="num">Codigo</th>
                    <th class="num" style="width:70px">Parcela</th>
                    <th style="width:100px">Emissao</th>
                    <th style="width:100px">Vencimento</th>
                    <th class="num" style="width:110px">Valor</th>
                    <th class="num" style="width:110px">Liquido</th>
                    <th class="centro" style="width:90px">Baixas</th>
                    <th class="centro" style="width:100px">Situacao</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contas)): ?>
                    <tr><td colspan="8" class="vazio">Nenhuma conta a receber gerada.</td></tr>
                <?php else: ?>
                    <?php foreach ($contas as $c): ?>
                        <tr>
                            <td class="num"><?= (int) $c['codigo'] ?></td>
                            <td class="num"><?= (int) $c['parcela'] ?></td>
                            <td><?= data_br($c['data_emissao']) ?></td>
                            <td><?= data_br($c['data_vencimento']) ?></td>
                            <td class="num"><?= moeda_br($c['valor']) ?></td>
                            <td class="num"><?= moeda_br($c['vliq']) ?></td>
                            <td class="centro"><?= (int) $c['qtd_baixas'] ?></td>
                            <td class="centro">
                                <?php if (bd_bool($c['cancelado'])): ?>
                                    <span class="badge off">cancelada</span>
                                <?php elseif ((int) $c['qtd_baixas'] > 0): ?>
                                    <span class="badge ok">baixada</span>
                                <?php else: ?>
                                    <span class="badge alerta">aberta</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
