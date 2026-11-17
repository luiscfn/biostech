<?php
$rotulos = array(
    1 => 'Aberto',
    2 => 'Baixado',
    3 => 'Cancelado',
);
?>
<div class="painel-corpo">
    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:80px">Titulo</th>
                    <th class="num" style="width:70px">Parcela</th>
                    <th style="width:150px">Vencimento</th>
                    <th class="num" style="width:130px">Valor</th>
                    <th class="centro" style="width:110px">Situacao</th>
                    <th>Historico</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contas)): ?>
                    <tr><td colspan="6" class="vazio">Nenhuma conta a pagar gerada para este pedido.</td></tr>
                <?php else: ?>
                    <?php foreach ($contas as $c): ?>
                        <tr>
                            <td><?= (int) $c['numero'] ?></td>
                            <td class="num"><?= (int) $c['parcela'] ?> / <?= (int) $c['total_parcelas'] ?></td>
                            <td><?= data_br($c['vencimento']) ?></td>
                            <td class="num"><?= moeda_br($c['valor']) ?></td>
                            <td class="centro">
                                <?php if ((int) $c['situacao'] === 1): ?>
                                    <span class="badge alerta">Aberto</span>
                                <?php elseif ((int) $c['situacao'] === 2): ?>
                                    <span class="badge ok">Baixado</span>
                                <?php else: ?>
                                    <span class="badge off">Cancelado</span>
                                <?php endif; ?>
                            </td>
                            <td><?= html_escape($c['historico'] ? $c['historico'] : '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
