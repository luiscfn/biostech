<?php
$dias = array(
    'segunda' => 'Segunda',
    'terca'   => 'Terca',
    'quarta'  => 'Quarta',
    'quinta'  => 'Quinta',
    'sexta'   => 'Sexta',
    'sabado'  => 'Sabado',
    'domingo' => 'Domingo',
);
$total = 0.0;
?>
<div class="painel-corpo">
    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th>Dia</th>
                    <th class="centro" style="width:100px">Trabalha</th>
                    <th class="num" style="width:130px">Inicio (h)</th>
                    <th class="num" style="width:130px">Fim (h)</th>
                    <th class="num" style="width:130px">Horas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dias as $chave => $rotulo): ?>
                    <?php
                    $inicio = (float) $registro[$chave . '_inicio'];
                    $fim    = (float) $registro[$chave . '_fim'];
                    $horas  = $fim > $inicio ? $fim - $inicio : 0;
                    $total += $horas;
                    ?>
                    <tr>
                        <td><?= html_escape($rotulo) ?></td>
                        <td class="centro">
                            <?= bd_bool($registro[$chave]) ? '<span class="badge ok">Sim</span>' : '<span class="badge off">Nao</span>' ?>
                        </td>
                        <td class="num"><?= $inicio > 0 ? numero_br($inicio, 2) : '-' ?></td>
                        <td class="num"><?= $fim > 0 ? numero_br($fim, 2) : '-' ?></td>
                        <td class="num"><?= $horas > 0 ? numero_br($horas, 2) : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="num"><strong>Total de horas na semana</strong></td>
                    <td class="num"><strong><?= numero_br($total, 2) ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="ajuda" style="margin-top:8px">
        Dias previstos: <?= $registro['qtd_dias'] !== NULL ? (int) $registro['qtd_dias'] : '-' ?>.
        Os horarios sao informados em horas decimais (ex.: 8,5 = 8h30).
    </div>

    <?php if ($pode_editar): ?>
        <div class="form-acoes">
            <a class="botao secundario pequeno" href="<?= site_url('orcamentos/dimensionamento_editar/' . $registro['numero']) ?>">Editar cronograma</a>
        </div>
    <?php endif; ?>
</div>
