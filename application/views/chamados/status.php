<div class="painel">
    <div class="painel-cabecalho">
        <h3>Mudar status - <?= html_escape($registro['numero']) ?></h3>
        <a class="botao secundario pequeno" href="<?= site_url('chamados/ver/' . $registro['numero']) ?>">Voltar</a>
    </div>

    <div class="painel-corpo">
        <div class="detalhes">
            <div class="detalhe">
                <div class="rotulo">Cliente</div>
                <div class="valor"><?= html_escape($registro['cliente_nome']) ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Categoria</div>
                <div class="valor"><?= html_escape($registro['categoria']) ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Status atual</div>
                <div class="valor"><span class="badge info"><?= html_escape($registro['status_nome']) ?></span></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Situacao atual</div>
                <div class="valor"><span class="badge alerta"><?= html_escape($registro['situacao_nome']) ?></span></div>
            </div>
        </div>

        <hr class="divisor">

        <?= form_open('chamados/status/' . $registro['numero']) ?>
            <div class="form-grid">
                <div class="campo">
                    <label for="status_codigo">Novo status *</label>
                    <select name="status_codigo" id="status_codigo" required>
                        <option value="">Selecione</option>
                        <?php foreach ($status_opcoes as $s): ?>
                            <option value="<?= (int) $s['codigo'] ?>" <?= (int) $s['codigo'] === (int) $registro['status_codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($s['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="situacao_codigo">Situacao atual *</label>
                    <select name="situacao_codigo" id="situacao_codigo" required>
                        <option value="">Selecione</option>
                        <?php foreach ($situacoes as $s): ?>
                            <option value="<?= (int) $s['codigo'] ?>" <?= (int) $s['codigo'] === (int) $registro['situacao_codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($s['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="tecnico_atendimento_id">Tecnico do atendimento</label>
                    <select name="tecnico_atendimento_id" id="tecnico_atendimento_id">
                        <option value="">(nao alterar)</option>
                        <?php foreach ($tecnicos as $t): ?>
                            <option value="<?= (int) $t['id'] ?>" <?= ! empty($atendimento['tecnico_atendimento_id']) && (int) $atendimento['tecnico_atendimento_id'] === (int) $t['id'] ? 'selected' : '' ?>>
                                <?= html_escape($t['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="atendimento_hora_fim">Hora final do atendimento</label>
                    <input type="time" name="atendimento_hora_fim" id="atendimento_hora_fim"
                           value="<?= ! empty($atendimento['atendimento_hora_fim']) ? html_escape(substr((string) $atendimento['atendimento_hora_fim'], 0, 5)) : '' ?>">
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao">Confirmar mudanca</button>
                <a class="botao secundario" href="<?= site_url('chamados/ver/' . $registro['numero']) ?>">Cancelar</a>
            </div>
        <?= form_close() ?>
    </div>
</div>
