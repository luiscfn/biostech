<?php
$fechado = $registro['situacao'] === 'fechado';
$soma    = 0.0;

foreach ($parcelas as $p)
{
    $soma += (float) $p['valor'];
}
?>
<div class="painel-corpo">
    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th class="num" style="width:60px">Parcela</th>
                    <th class="num" style="width:110px">Prazo (dias)</th>
                    <th style="width:160px">Vencimento</th>
                    <th class="num" style="width:140px">Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($parcelas)): ?>
                    <tr><td colspan="4" class="vazio">Nenhuma parcela gerada. As parcelas sao criadas no fechamento do pedido.</td></tr>
                <?php else: ?>
                    <?php foreach ($parcelas as $p): ?>
                        <tr>
                            <td class="num"><?= (int) $p['numero_par'] ?></td>
                            <td class="num"><?= (int) $p['dias'] ?></td>
                            <td><?= data_br($p['vencimento']) ?></td>
                            <td class="num"><?= moeda_br($p['valor']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if ( ! empty($parcelas)): ?>
                <tfoot>
                    <tr>
                        <td colspan="3" class="num"><strong>Total das parcelas</strong></td>
                        <td class="num"><strong><?= moeda_br($soma) ?></strong></td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>

    <?php if ($fechado && $pode_editar): ?>
        <hr class="divisor">
        <h4 class="secao-titulo">Cancelar o fechamento</h4>

        <div class="alert alert-warning">
            O cancelamento estorna os movimentos de estoque gerados pelo fechamento e cancela as contas a pagar.
            O pedido volta para a situacao aberto.
        </div>

        <?= form_open('compras/cancelar_fechamento/' . $registro['numero']) ?>
            <div class="form-grid">
                <div class="campo full">
                    <label for="motivo_fechamento">Motivo *</label>
                    <input type="text" name="motivo" id="motivo_fechamento" maxlength="200" required>
                </div>
            </div>
            <div class="form-acoes">
                <button type="submit" class="botao perigo">Cancelar fechamento</button>
            </div>
        <?= form_close() ?>
    <?php endif; ?>
</div>
