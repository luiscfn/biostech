<?php $encerrado = bd_bool($registro['encerrado']); ?>

<div class="painel-corpo">
    <h4 class="secao-titulo">Intervencoes registradas</h4>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:95px">Data</th>
                    <th style="width:70px">Hora</th>
                    <th style="width:110px">Inicio / Fim</th>
                    <th>Tecnico</th>
                    <th>Descricao</th>
                    <th class="centro" style="width:100px">Resolveu</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($intervencoes)): ?>
                    <tr><td colspan="6" class="vazio">Nenhuma intervencao registrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($intervencoes as $i): ?>
                        <tr>
                            <td><?= data_br($i['data_movimento']) ?></td>
                            <td><?= html_escape(substr((string) $i['hora_movimento'], 0, 5)) ?></td>
                            <td>
                                <?= $i['hora_inicio'] ? html_escape(substr((string) $i['hora_inicio'], 0, 5)) : '-' ?>
                                /
                                <?= $i['hora_fim'] ? html_escape(substr((string) $i['hora_fim'], 0, 5)) : '-' ?>
                            </td>
                            <td><?= html_escape($i['tecnico_nome'] ? $i['tecnico_nome'] : '-') ?></td>
                            <td class="texto-longo"><?= nl2br(html_escape($i['descricao'])) ?></td>
                            <td class="centro">
                                <?php if (bd_bool($i['resolveu'])): ?>
                                    <span class="badge ok">Sim</span>
                                <?php else: ?>
                                    <span class="badge off">Nao</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ( ! $encerrado): ?>
        <hr class="divisor">

        <h4 class="secao-titulo">Nova intervencao</h4>

        <?= form_open('chamados/intervencoes/' . $registro['numero']) ?>
            <div class="form-grid">
                <div class="campo">
                    <label for="data_movimento">Data *</label>
                    <input type="date" name="data_movimento" id="data_movimento" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="campo">
                    <label for="hora_movimento">Hora *</label>
                    <input type="time" name="hora_movimento" id="hora_movimento" value="<?= date('H:i') ?>">
                </div>
                <div class="campo">
                    <label for="hora_inicio">Hora inicial</label>
                    <input type="time" name="hora_inicio" id="hora_inicio">
                </div>
                <div class="campo">
                    <label for="hora_fim">Hora final</label>
                    <input type="time" name="hora_fim" id="hora_fim">
                </div>
                <div class="campo full">
                    <label for="descricao">Descricao *</label>
                    <textarea name="descricao" id="descricao" required></textarea>
                </div>
                <div class="campo full">
                    <label class="check"><input type="checkbox" name="resolveu" value="1"> Esta intervencao resolveu o problema</label>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao">Registrar intervencao</button>
            </div>
        <?= form_close() ?>
    <?php endif; ?>
</div>
