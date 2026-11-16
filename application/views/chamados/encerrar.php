<?php
$at = ! empty($atendimento) ? $atendimento : array();

$total_pecas = 0;
foreach ($pecas as $p)
{
    $total_pecas += (float) $p['valor_total'];
}

$total_servicos = 0;
foreach ($servicos as $s)
{
    if ($s['tipo'] === 'avulso')
    {
        $total_servicos += (float) $s['valor_total'];
    }
}

$solucao_data = ! empty($at['solucao_data']) ? $at['solucao_data'] : date('Y-m-d');
$solucao_hora = ! empty($at['solucao_hora']) ? substr((string) $at['solucao_hora'], 0, 5) : date('H:i');
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>Encerrar <?= html_escape($registro['categoria']) ?> <?= html_escape($registro['numero']) ?></h3>
        <a class="botao secundario pequeno" href="<?= site_url('chamados/ver/' . $registro['numero']) ?>">Voltar</a>
    </div>

    <div class="painel-corpo">
        <?php if ((int) $registro['qtd_intervencoes'] === 0): ?>
            <div class="alert alert-warning">
                Este registro nao possui intervencoes. Registre pelo menos uma antes de encerrar.
            </div>
        <?php endif; ?>

        <?php if ((int) $registro['status_codigo'] === 5): ?>
            <div class="alert alert-warning">Este registro ja esta com status ENCERRADA.</div>
        <?php endif; ?>

        <div class="cartoes">
            <div class="cartao">
                <div class="rotulo">Pecas</div>
                <div class="valor"><?= moeda_br($total_pecas) ?></div>
            </div>
            <div class="cartao">
                <div class="rotulo">Servicos avulsos</div>
                <div class="valor"><?= moeda_br($total_servicos) ?></div>
            </div>
            <div class="cartao verde">
                <div class="rotulo">Total previsto</div>
                <div class="valor"><?= moeda_br($total) ?></div>
            </div>
        </div>

        <hr class="divisor">

        <?= form_open('chamados/encerrar/' . $registro['numero'], array('onsubmit' => "return confirm('Confirmar o encerramento deste registro?');")) ?>
            <div class="form-grid">
                <div class="campo">
                    <label for="valor_mao_obra">Mao de obra</label>
                    <input type="number" step="0.01" min="0" name="valor_mao_obra" id="valor_mao_obra" value="<?= number_format((float) $registro['valor_mao_obra'], 2, '.', '') ?>">
                </div>
                <div class="campo">
                    <label for="material_fixacao">Material de fixacao</label>
                    <input type="number" step="0.01" min="0" name="material_fixacao" id="material_fixacao" value="<?= number_format((float) $registro['material_fixacao'], 2, '.', '') ?>">
                </div>
                <div class="campo">
                    <label for="valor_imposto">Imposto</label>
                    <input type="number" step="0.01" min="0" name="valor_imposto" id="valor_imposto" value="<?= number_format((float) $registro['valor_imposto'], 2, '.', '') ?>">
                </div>
                <div class="campo">
                    <label for="acrescimo_custo">Acrescimo de custo</label>
                    <input type="number" step="0.01" min="0" name="acrescimo_custo" id="acrescimo_custo" value="<?= number_format((float) $registro['acrescimo_custo'], 2, '.', '') ?>">
                </div>
                <div class="campo">
                    <label for="valor_desconto">Desconto</label>
                    <input type="number" step="0.01" min="0" name="valor_desconto" id="valor_desconto" value="<?= number_format((float) $registro['valor_desconto'], 2, '.', '') ?>">
                </div>
                <div class="campo">
                    <label for="data_emissao">Data de emissao do titulo</label>
                    <input type="date" name="data_emissao" id="data_emissao" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="campo">
                    <label for="solucao_data">Data da conclusao *</label>
                    <input type="date" name="solucao_data" id="solucao_data" value="<?= html_escape($solucao_data) ?>">
                </div>
                <div class="campo">
                    <label for="solucao_hora">Hora da conclusao *</label>
                    <input type="time" name="solucao_hora" id="solucao_hora" value="<?= html_escape($solucao_hora) ?>">
                </div>
                <div class="campo">
                    <label for="forma_recebimento_codigo">Forma de recebimento</label>
                    <select name="forma_recebimento_codigo" id="forma_recebimento_codigo">
                        <option value="">(nao informada)</option>
                        <?php foreach ($dominios['forma_recebimento'] as $cod => $nome): ?>
                            <option value="<?= (int) $cod ?>" <?= ! empty($plano['forma_recebimento_codigo']) && (int) $plano['forma_recebimento_codigo'] === (int) $cod ? 'selected' : '' ?>>
                                <?= html_escape($nome) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="ajuda">Forma 8 (SEM VALOR A RECEBER) nao gera titulo no Contas a Receber.</div>
                </div>
            </div>

            <h4 class="secao-titulo">Parcelas ja geradas na aba Financeiro</h4>

            <div class="tabela-wrap">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th class="num" style="width:70px">Parcela</th>
                            <th class="num" style="width:120px">Valor</th>
                            <th style="width:110px">Vencimento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parcelas)): ?>
                            <tr>
                                <td colspan="3" class="vazio">
                                    Nenhuma parcela gerada. Se houver valor a receber, gere pelo menos a 1a parcela na aba Financeiro.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($parcelas as $p): ?>
                                <tr>
                                    <td class="num"><?= (int) $p['numero'] ?></td>
                                    <td class="num"><?= moeda_br($p['valor']) ?></td>
                                    <td><?= data_br($p['vencimento']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao">Confirmar encerramento</button>
                <a class="botao secundario" href="<?= site_url('chamados/ver/' . $registro['numero']) ?>">Cancelar</a>
            </div>
        <?= form_close() ?>
    </div>
</div>
