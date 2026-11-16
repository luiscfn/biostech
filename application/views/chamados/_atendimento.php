<?php
function _at_valor($atendimento, $campo)
{
    $v = isset($atendimento[$campo]) ? (string) $atendimento[$campo] : '';

    if (in_array($campo, array('previsao_hora', 'atendimento_hora', 'atendimento_hora_fim',
                               'solucao_hora', 'devolucao_hora'), TRUE))
    {
        return substr($v, 0, 5);
    }

    return $v;
}

function _at_check($atendimento, $campo)
{
    return isset($atendimento[$campo]) && bd_bool($atendimento[$campo]);
}

function _at_tecnico($atendimento, $campo, $tecnicos)
{
    $atual = isset($atendimento[$campo]) ? (int) $atendimento[$campo] : 0;
    $html  = '<option value="">(nao informado)</option>';

    foreach ($tecnicos as $t)
    {
        $html .= '<option value="' . (int) $t['id'] . '"' . ($atual === (int) $t['id'] ? ' selected' : '') . '>'
               . html_escape($t['nome']) . '</option>';
    }

    return $html;
}
?>
<div class="painel-corpo">
    <?= form_open('chamados/atendimento/' . $registro['numero']) ?>

        <h4 class="secao-titulo">Datas e tecnicos</h4>

        <div class="form-grid">
            <div class="campo">
                <label for="previsao_data">Previsao - data</label>
                <input type="date" name="previsao_data" id="previsao_data" value="<?= html_escape(_at_valor($atendimento, 'previsao_data')) ?>">
            </div>
            <div class="campo">
                <label for="previsao_hora">Previsao - hora</label>
                <input type="time" name="previsao_hora" id="previsao_hora" value="<?= html_escape(_at_valor($atendimento, 'previsao_hora')) ?>">
            </div>
            <div class="campo">
                <label for="atendimento_data">Atendimento - data</label>
                <input type="date" name="atendimento_data" id="atendimento_data" value="<?= html_escape(_at_valor($atendimento, 'atendimento_data')) ?>">
            </div>
            <div class="campo">
                <label for="atendimento_hora">Atendimento - hora inicial</label>
                <input type="time" name="atendimento_hora" id="atendimento_hora" value="<?= html_escape(_at_valor($atendimento, 'atendimento_hora')) ?>">
            </div>
            <div class="campo">
                <label for="atendimento_hora_fim">Atendimento - hora final</label>
                <input type="time" name="atendimento_hora_fim" id="atendimento_hora_fim" value="<?= html_escape(_at_valor($atendimento, 'atendimento_hora_fim')) ?>">
            </div>
            <div class="campo">
                <label for="solucao_data">Conclusao - data</label>
                <input type="date" name="solucao_data" id="solucao_data" value="<?= html_escape(_at_valor($atendimento, 'solucao_data')) ?>">
            </div>
            <div class="campo">
                <label for="solucao_hora">Conclusao - hora</label>
                <input type="time" name="solucao_hora" id="solucao_hora" value="<?= html_escape(_at_valor($atendimento, 'solucao_hora')) ?>">
            </div>
            <div class="campo">
                <label for="devolucao_data">Devolucao - data</label>
                <input type="date" name="devolucao_data" id="devolucao_data" value="<?= html_escape(_at_valor($atendimento, 'devolucao_data')) ?>">
            </div>
            <div class="campo">
                <label for="devolucao_hora">Devolucao - hora</label>
                <input type="time" name="devolucao_hora" id="devolucao_hora" value="<?= html_escape(_at_valor($atendimento, 'devolucao_hora')) ?>">
            </div>

            <div class="campo">
                <label for="tecnico_abertura_id">Tecnico que abriu</label>
                <select name="tecnico_abertura_id" id="tecnico_abertura_id"><?= _at_tecnico($atendimento, 'tecnico_abertura_id', $tecnicos) ?></select>
            </div>
            <div class="campo">
                <label for="tecnico_atendimento_id">Tecnico do atendimento</label>
                <select name="tecnico_atendimento_id" id="tecnico_atendimento_id"><?= _at_tecnico($atendimento, 'tecnico_atendimento_id', $tecnicos) ?></select>
            </div>
            <div class="campo">
                <label for="tecnico_conclusao_id">Tecnico que concluiu</label>
                <select name="tecnico_conclusao_id" id="tecnico_conclusao_id"><?= _at_tecnico($atendimento, 'tecnico_conclusao_id', $tecnicos) ?></select>
            </div>
            <div class="campo">
                <label for="responsavel_encerramento_id">Responsavel pelo encerramento</label>
                <select name="responsavel_encerramento_id" id="responsavel_encerramento_id"><?= _at_tecnico($atendimento, 'responsavel_encerramento_id', $tecnicos) ?></select>
            </div>
            <div class="campo">
                <label for="prioridade_codigo">Prioridade</label>
                <select name="prioridade_codigo" id="prioridade_codigo">
                    <option value="">(nao informada)</option>
                    <?php foreach ($dominios['prioridade'] as $cod => $nome): ?>
                        <option value="<?= (int) $cod ?>" <?= (string) $cod === _at_valor($atendimento, 'prioridade_codigo') ? 'selected' : '' ?>><?= html_escape($nome) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="segmento_codigo">Segmento</label>
                <select name="segmento_codigo" id="segmento_codigo">
                    <option value="">(nao informado)</option>
                    <?php foreach ($dominios['segmento'] as $cod => $nome): ?>
                        <option value="<?= (int) $cod ?>" <?= (string) $cod === _at_valor($atendimento, 'segmento_codigo') ? 'selected' : '' ?>><?= html_escape($nome) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <h4 class="secao-titulo">Contato e responsaveis</h4>

        <div class="form-grid">
            <div class="campo">
                <label for="solicitante">Solicitante</label>
                <input type="text" name="solicitante" id="solicitante" value="<?= html_escape(_at_valor($atendimento, 'solicitante')) ?>" maxlength="50">
            </div>
            <div class="campo">
                <label for="fone_solicitante">Fone do solicitante</label>
                <input type="text" name="fone_solicitante" id="fone_solicitante" value="<?= html_escape(_at_valor($atendimento, 'fone_solicitante')) ?>" maxlength="30">
            </div>
            <div class="campo">
                <label for="contato">Contato</label>
                <input type="text" name="contato" id="contato" value="<?= html_escape(_at_valor($atendimento, 'contato')) ?>" maxlength="50">
            </div>
            <div class="campo">
                <label for="fone_contato">Fone do contato</label>
                <input type="text" name="fone_contato" id="fone_contato" value="<?= html_escape(_at_valor($atendimento, 'fone_contato')) ?>" maxlength="30">
            </div>
            <div class="campo">
                <label for="local_atendimento">Local de atendimento</label>
                <input type="text" name="local_atendimento" id="local_atendimento" value="<?= html_escape(_at_valor($atendimento, 'local_atendimento')) ?>" maxlength="50">
            </div>
            <div class="campo">
                <label for="responsavel_entrega">Responsavel pela entrega</label>
                <input type="text" name="responsavel_entrega" id="responsavel_entrega" value="<?= html_escape(_at_valor($atendimento, 'responsavel_entrega')) ?>" maxlength="50">
            </div>
            <div class="campo">
                <label for="responsavel_recebimento">Responsavel pelo recebimento</label>
                <select name="responsavel_recebimento" id="responsavel_recebimento"><?= _at_tecnico($atendimento, 'responsavel_recebimento', $tecnicos) ?></select>
            </div>
            <div class="campo">
                <label for="quem_devolveu">Quem devolveu</label>
                <select name="quem_devolveu" id="quem_devolveu"><?= _at_tecnico($atendimento, 'quem_devolveu', $tecnicos) ?></select>
            </div>
            <div class="campo">
                <label for="quem_recebeu">Quem recebeu</label>
                <input type="text" name="quem_recebeu" id="quem_recebeu" value="<?= html_escape(_at_valor($atendimento, 'quem_recebeu')) ?>" maxlength="50">
            </div>
            <div class="campo">
                <label for="autorizada">Autorizada</label>
                <input type="number" name="autorizada" id="autorizada" value="<?= html_escape(_at_valor($atendimento, 'autorizada')) ?>">
            </div>
        </div>

        <h4 class="secao-titulo">Valores do atendimento</h4>

        <div class="form-grid">
            <div class="campo">
                <label for="valor_recolhimento">Valor do recolhimento</label>
                <input type="text" name="valor_recolhimento" id="valor_recolhimento" value="<?= html_escape(_at_valor($atendimento, 'valor_recolhimento')) ?>">
            </div>
            <div class="campo">
                <label for="valor_entrega">Valor da entrega</label>
                <input type="text" name="valor_entrega" id="valor_entrega" value="<?= html_escape(_at_valor($atendimento, 'valor_entrega')) ?>">
            </div>
            <div class="campo full">
                <div class="check-grid">
                    <label class="check"><input type="checkbox" name="pago_recolhimento" value="1" <?= _at_check($atendimento, 'pago_recolhimento') ? 'checked' : '' ?>> Recolhimento pago</label>
                    <label class="check"><input type="checkbox" name="pago_entrega" value="1" <?= _at_check($atendimento, 'pago_entrega') ? 'checked' : '' ?>> Entrega paga</label>
                    <label class="check"><input type="checkbox" name="garantia" value="1" <?= _at_check($atendimento, 'garantia') ? 'checked' : '' ?>> Em garantia</label>
                    <label class="check"><input type="checkbox" name="recolhido" value="1" <?= _at_check($atendimento, 'recolhido') ? 'checked' : '' ?>> Equipamento recolhido</label>
                </div>
            </div>
        </div>

        <div class="form-acoes">
            <button type="submit" class="botao">Salvar atendimento</button>
        </div>

    <?= form_close() ?>
</div>
