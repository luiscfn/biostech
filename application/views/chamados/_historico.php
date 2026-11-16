<div class="painel-corpo">
    <h4 class="secao-titulo">Eventos</h4>

    <?php if (empty($eventos)): ?>
        <p class="vazio-estado">Nenhum evento registrado.</p>
    <?php else: ?>
        <ul class="lista-eventos">
            <?php foreach ($eventos as $e): ?>
                <li>
                    <span class="quando"><?= data_hora_br($e['created_at']) ?></span>
                    <span class="quem"><?= html_escape($e['usuario'] ? $e['usuario'] : '-') ?></span>
                    <span class="badge info"><?= html_escape($e['tipo']) ?></span>
                    <?= html_escape($e['descricao'] ? $e['descricao'] : '') ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <hr class="divisor">

    <h4 class="secao-titulo">Alteracoes campo a campo</h4>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:150px">Data</th>
                    <th style="width:110px">Usuario</th>
                    <th style="width:180px">Campo</th>
                    <th>Valor anterior</th>
                    <th>Valor novo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($log)): ?>
                    <tr><td colspan="5" class="vazio">Nenhuma alteracao registrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($log as $l): ?>
                        <tr>
                            <td><?= data_hora_br($l['created_at']) ?></td>
                            <td><?= html_escape($l['usuario'] ? $l['usuario'] : '-') ?></td>
                            <td><code><?= html_escape($l['campo']) ?></code></td>
                            <td><?= html_escape($l['valor_anterior'] !== NULL ? $l['valor_anterior'] : '-') ?></td>
                            <td><?= html_escape($l['valor_novo'] !== NULL ? $l['valor_novo'] : '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
