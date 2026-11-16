<?php
$editando = ! empty($registro);

function _chamado_valor($registro, $campo, $padrao = '')
{
    if ( ! empty($registro) && array_key_exists($campo, $registro))
    {
        return (string) $registro[$campo];
    }

    $v = set_value($campo);
    return $v === '' ? (string) $padrao : $v;
}

$categoria = _chamado_valor($registro, 'categoria', 'Chamado');
$cliente_id = _chamado_valor($registro, 'cliente_id');
$cliente_nome = $editando ? (string) $registro['cliente_nome'] : '';
$data_abertura = _chamado_valor($registro, 'data_abertura', date('Y-m-d'));
$hora_abertura = _chamado_valor($registro, 'hora_abertura', date('H:i'));
$cancelar = $editando ? site_url('chamados/ver/' . $registro['numero']) : site_url('chamados');
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3><?= $editando ? 'Editar ' . html_escape($registro['categoria']) . ' ' . html_escape($registro['numero']) : 'Novo chamado / OS' ?></h3>
        <?php if ($editando): ?>
            <span class="badge info"><?= html_escape($registro['status_nome']) ?></span>
        <?php endif; ?>
    </div>

    <div class="painel-corpo">
        <?= form_open($editando ? 'chamados/editar/' . $registro['numero'] : 'chamados/novo') ?>

            <div class="form-grid">
                <div class="campo">
                    <label for="categoria">Categoria *</label>
                    <select name="categoria" id="categoria">
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= html_escape($c) ?>" <?= $c === $categoria ? 'selected' : '' ?>><?= html_escape($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= form_error('categoria') ?>
                </div>

                <div class="campo full">
                    <label for="cliente_busca">Cliente *</label>
                    <div class="autocomplete" data-url="<?= site_url('chamados/buscar_clientes') ?>">
                        <input type="text" id="cliente_busca" class="autocomplete-busca"
                               value="<?= html_escape($cliente_nome) ?>"
                               placeholder="Digite o nome ou CPF/CNPJ do cliente..."
                               autocomplete="off">
                        <input type="hidden" name="cliente_id" id="cliente_id" value="<?= html_escape($cliente_id) ?>">
                        <div class="autocomplete-lista"></div>
                    </div>
                    <?= form_error('cliente_id') ?>
                    <div class="ajuda">Selecione o cliente na lista sugerida.</div>
                </div>

                <div class="campo terco">
                    <label for="numero_par">Numero par (chamado/OS)</label>
                    <input type="text" name="numero_par" id="numero_par" value="<?= html_escape(_chamado_valor($registro, 'numero_par')) ?>" maxlength="16">
                </div>

                <div class="campo terco">
                    <label for="data_abertura">Data de abertura *</label>
                    <input type="date" name="data_abertura" id="data_abertura" value="<?= html_escape($data_abertura) ?>">
                    <?= form_error('data_abertura') ?>
                </div>

                <div class="campo terco">
                    <label for="hora_abertura">Hora de abertura</label>
                    <input type="time" name="hora_abertura" id="hora_abertura" value="<?= html_escape(substr($hora_abertura, 0, 5)) ?>">
                </div>

                <div class="campo">
                    <label for="equipamento_serie">Numero de serie do equipamento</label>
                    <input type="text" name="equipamento_serie" id="equipamento_serie" value="<?= html_escape(_chamado_valor($registro, 'equipamento_serie')) ?>" maxlength="30">
                </div>

                <div class="campo">
                    <label for="tipo_garantia_codigo">Tipo de garantia</label>
                    <select name="tipo_garantia_codigo" id="tipo_garantia_codigo">
                        <option value="">(nao informado)</option>
                        <?php foreach ($dominios['tipo_garantia'] as $cod => $nome): ?>
                            <option value="<?= (int) $cod ?>" <?= (string) $cod === _chamado_valor($registro, 'tipo_garantia_codigo') ? 'selected' : '' ?>>
                                <?= html_escape($nome) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="patrimonio">Patrimonio</label>
                    <input type="text" name="patrimonio" id="patrimonio" value="<?= html_escape(_chamado_valor($registro, 'patrimonio')) ?>" maxlength="25">
                </div>

                <div class="campo">
                    <label for="nota_fiscal_chegada">Nota fiscal de chegada</label>
                    <input type="text" name="nota_fiscal_chegada" id="nota_fiscal_chegada" value="<?= html_escape(_chamado_valor($registro, 'nota_fiscal_chegada')) ?>" maxlength="20">
                </div>

                <div class="campo">
                    <label for="prioridade_codigo">Prioridade</label>
                    <select name="prioridade_codigo" id="prioridade_codigo">
                        <option value="">(nao informada)</option>
                        <?php foreach ($dominios['prioridade'] as $cod => $nome): ?>
                            <option value="<?= (int) $cod ?>" <?= (string) $cod === _chamado_valor($registro, 'prioridade_codigo') ? 'selected' : '' ?>>
                                <?= html_escape($nome) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="segmento_codigo">Segmento</label>
                    <select name="segmento_codigo" id="segmento_codigo">
                        <option value="">(nao informado)</option>
                        <?php foreach ($dominios['segmento'] as $cod => $nome): ?>
                            <option value="<?= (int) $cod ?>" <?= (string) $cod === _chamado_valor($registro, 'segmento_codigo') ? 'selected' : '' ?>>
                                <?= html_escape($nome) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="solicitante">Solicitante</label>
                    <input type="text" name="solicitante" id="solicitante" value="<?= html_escape(_chamado_valor($registro, 'solicitante')) ?>" maxlength="50">
                </div>

                <div class="campo">
                    <label for="contato">Contato</label>
                    <input type="text" name="contato" id="contato" value="<?= html_escape(_chamado_valor($registro, 'contato')) ?>" maxlength="50">
                </div>

                <div class="campo full">
                    <label for="local_atendimento">Local de atendimento</label>
                    <input type="text" name="local_atendimento" id="local_atendimento" value="<?= html_escape(_chamado_valor($registro, 'local_atendimento')) ?>" maxlength="50">
                </div>

                <div class="campo full">
                    <label for="defeito_relatado">Defeito relatado</label>
                    <textarea name="defeito_relatado" id="defeito_relatado"><?= html_escape(_chamado_valor($registro, 'defeito_relatado')) ?></textarea>
                </div>

                <div class="campo full">
                    <label for="observacao">Observacoes</label>
                    <textarea name="observacao" id="observacao"><?= html_escape(_chamado_valor($registro, 'observacao')) ?></textarea>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao"><?= $editando ? 'Salvar alteracoes' : 'Abrir chamado / OS' ?></button>
                <a class="botao secundario" href="<?= $cancelar ?>">Cancelar</a>
            </div>

        <?= form_close() ?>
    </div>
</div>

<script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
