<?php $encerrado = bd_bool($registro['encerrado']); ?>

<div class="painel-corpo">
    <h4 class="secao-titulo">Contatos e follow-up</h4>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:95px">Data</th>
                    <th style="width:70px">Hora</th>
                    <th>Descricao</th>
                    <th style="width:140px">Contato</th>
                    <th style="width:130px">Responsavel</th>
                    <th style="width:150px">Proximo contato</th>
                    <th class="centro" style="width:90px">Concluido</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contatos)): ?>
                    <tr><td colspan="7" class="vazio">Nenhum contato registrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($contatos as $c): ?>
                        <tr>
                            <td><?= data_br($c['data_movimento']) ?></td>
                            <td><?= html_escape(substr((string) $c['hora_movimento'], 0, 5)) ?></td>
                            <td class="texto-longo"><?= nl2br(html_escape($c['descricao'])) ?></td>
                            <td><?= html_escape($c['contato'] ? $c['contato'] : '-') ?></td>
                            <td><?= html_escape($c['responsavel_nome'] ? $c['responsavel_nome'] : '-') ?></td>
                            <td>
                                <?php if ( ! empty($c['proximo_contato_data'])): ?>
                                    <?= data_br($c['proximo_contato_data']) ?>
                                    <?= $c['proximo_contato_hora'] ? ' ' . html_escape(substr((string) $c['proximo_contato_hora'], 0, 5)) : '' ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="centro">
                                <?= bd_bool($c['concluido']) ? '<span class="badge ok">Sim</span>' : '<span class="badge alerta">Nao</span>' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ( ! $encerrado): ?>
        <hr class="divisor">

        <h4 class="secao-titulo">Registrar contato</h4>

        <?= form_open('chamados/contatos/' . $registro['numero']) ?>
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
                    <label for="contato">Pessoa contatada</label>
                    <input type="text" name="contato" id="contato" maxlength="50">
                </div>
                <div class="campo">
                    <label for="proximo_contato_data">Proximo contato - data</label>
                    <input type="date" name="proximo_contato_data" id="proximo_contato_data">
                </div>
                <div class="campo">
                    <label for="proximo_contato_hora">Proximo contato - hora</label>
                    <input type="time" name="proximo_contato_hora" id="proximo_contato_hora">
                </div>
                <div class="campo full">
                    <label for="descricao">Descricao *</label>
                    <textarea name="descricao" id="descricao" required></textarea>
                </div>
                <div class="campo full">
                    <label class="check"><input type="checkbox" name="concluido" value="1"> Contato concluido</label>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao">Registrar contato</button>
            </div>
        <?= form_close() ?>
    <?php endif; ?>
</div>
