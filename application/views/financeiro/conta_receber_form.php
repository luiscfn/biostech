<?php
$edicao = ! empty($registro['codigo']);
$hoje   = date('Y-m-d');

$valor = function ($campo, $default = NULL) use ($registro, $edicao) {
    if ( ! $edicao)
    {
        return $default;
    }

    return isset($registro[$campo]) ? $registro[$campo] : $default;
};
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>
            <?= $edicao ? 'Editar conta a receber ' . (int) $registro['codigo'] . '/' . (int) $registro['parcela'] : 'Nova conta a receber' ?>
        </h3>

        <div class="acoes-ficha">
            <?php if ($edicao): ?>
                <a class="botao secundario pequeno" href="<?= site_url('financeiro/conta_receber_ver/' . $registro['codigo']) ?>">Ver ficha</a>
            <?php endif; ?>
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/contas_receber') ?>">Voltar</a>
        </div>
    </div>

    <div class="painel-corpo">
        <?= form_open($edicao ? 'financeiro/conta_receber_editar/' . $registro['codigo'] : 'financeiro/conta_receber_novo', array('id' => 'form-cr')) ?>

            <div class="form-grid">
                <div class="campo full">
                    <label for="cliente_busca">Cliente</label>
                    <div class="autocomplete" data-url="<?= site_url('financeiro/buscar_clientes') ?>">
                        <input type="text" id="cliente_busca" class="autocomplete-busca"
                               placeholder="Digite o nome, fantasia ou CPF/CNPJ..."
                               value="<?= $edicao ? html_escape($registro['cliente_nome']) : '' ?>" autocomplete="off">
                        <input type="hidden" name="cliente_id" id="cliente_id"
                               value="<?= $edicao ? (int) $registro['cliente_id'] : '' ?>">
                        <div class="autocomplete-lista"></div>
                    </div>
                    <?php if ($edicao && ! empty($registro['cliente_id'])): ?>
                        <div class="ajuda">
                            <?= html_escape($registro['cliente_id'] . ' - ' . $registro['cliente_nome']) ?>
                            <?= $registro['cliente_cnpj'] ? ' | CPF/CNPJ ' . html_escape($registro['cliente_cnpj']) : '' ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="campo terco">
                    <label for="data_emissao">Emissao</label>
                    <input type="date" name="data_emissao" id="data_emissao" value="<?= html_escape($valor('data_emissao', $hoje)) ?>">
                </div>

                <div class="campo terco">
                    <label for="data_vencimento">1o vencimento *</label>
                    <input type="date" name="data_vencimento" id="data_vencimento" value="<?= html_escape($valor('data_vencimento', $hoje)) ?>" required>
                </div>

                <div class="campo terco">
                    <label for="valor">Valor total *</label>
                    <input type="text" name="valor" id="valor" inputmode="decimal"
                           value="<?= $edicao ? number_format((float) $registro['valor'], 2, ',', '') : '' ?>" required
                           data-calculo="cr">
                </div>

                <?php if ( ! $edicao): ?>
                    <div class="campo terco">
                        <label for="parcelas">Parcelas</label>
                        <input type="number" name="parcelas" id="parcelas" min="1" max="12" step="1" value="1">
                    </div>

                    <div class="campo terco">
                        <label for="intervalo_dias">Intervalo entre parcelas (dias)</label>
                        <input type="number" name="intervalo_dias" id="intervalo_dias" min="1" step="1" value="30">
                    </div>
                <?php endif; ?>

                <div class="campo terco">
                    <label for="chamado_numero">OS / Chamado</label>
                    <input type="text" name="chamado_numero" id="chamado_numero" maxlength="16"
                           value="<?= html_escape($valor('chamado_numero', '')) ?>">
                </div>

                <div class="campo terco">
                    <label for="numero_documento">Numero do documento</label>
                    <input type="text" name="numero_documento" id="numero_documento" maxlength="20"
                           value="<?= html_escape($valor('numero_documento', '')) ?>">
                </div>

                <div class="campo terco">
                    <label for="numero_nota_fiscal">Nota fiscal</label>
                    <input type="text" name="numero_nota_fiscal" id="numero_nota_fiscal" maxlength="30"
                           value="<?= html_escape($valor('numero_nota_fiscal', '')) ?>">
                </div>

                <div class="campo terco">
                    <label for="tipo">Tipo de pagamento</label>
                    <select name="tipo" id="tipo">
                        <option value="">-</option>
                        <?php foreach ($dominios['tipo_pagamento'] as $d): ?>
                            <option value="<?= (int) $d['codigo'] ?>"
                                <?= $edicao && (int) $registro['tipo'] === (int) $d['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($d['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo terco">
                    <label for="forma_recebimento_codigo">Forma de recebimento</label>
                    <select name="forma_recebimento_codigo" id="forma_recebimento_codigo">
                        <option value="">-</option>
                        <?php foreach ($dominios['forma_recebimento'] as $d): ?>
                            <option value="<?= (int) $d['codigo'] ?>"
                                <?= $edicao && (int) $registro['forma_recebimento_codigo'] === (int) $d['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($d['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo terco">
                    <label for="documento_codigo">Documento</label>
                    <select name="documento_codigo" id="documento_codigo">
                        <option value="">-</option>
                        <?php foreach ($dominios['documento'] as $d): ?>
                            <option value="<?= (int) $d['codigo'] ?>"
                                <?= $edicao && (int) $registro['documento_codigo'] === (int) $d['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($d['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo terco">
                    <label for="dias">Dias</label>
                    <input type="number" name="dias" id="dias" min="0" step="1"
                           value="<?= $edicao && $registro['dias'] !== NULL ? (int) $registro['dias'] : '' ?>">
                </div>

                <div class="campo terco">
                    <label for="codigo_centro_custo">Centro de custo</label>
                    <select name="codigo_centro_custo" id="codigo_centro_custo">
                        <option value="">-</option>
                        <?php foreach ($centros as $c): ?>
                            <option value="<?= (int) $c['codigo'] ?>"
                                <?= $edicao && (int) $registro['codigo_centro_custo'] === (int) $c['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($c['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo terco">
                    <label for="desconto_especial">Desconto</label>
                    <input type="text" name="desconto_especial" id="desconto_especial" inputmode="decimal"
                           value="<?= $edicao ? number_format((float) $registro['desconto_especial'], 2, ',', '') : '0,00' ?>"
                           data-calculo="cr">
                </div>

                <div class="campo terco">
                    <label for="vdesp">Despesas</label>
                    <input type="text" name="vdesp" id="vdesp" inputmode="decimal"
                           value="<?= $edicao ? number_format((float) $registro['vdesp'], 2, ',', '') : '0,00' ?>"
                           data-calculo="cr">
                </div>

                <div class="campo terco">
                    <label for="aliq_issqn">Aliquota ISSQN (%)</label>
                    <input type="text" name="aliq_issqn" id="aliq_issqn" inputmode="decimal"
                           value="<?= $edicao && $registro['aliq_issqn'] !== NULL ? number_format((float) $registro['aliq_issqn'], 4, ',', '') : '' ?>"
                           data-calculo="cr">
                </div>

                <div class="campo terco">
                    <label for="viss">ISS</label>
                    <input type="text" name="viss" id="viss" inputmode="decimal"
                           value="<?= $edicao ? number_format((float) $registro['viss'], 2, ',', '') : '0,00' ?>"
                           data-calculo="cr">
                </div>

                <div class="campo terco">
                    <label for="vliq">Valor liquido</label>
                    <input type="text" name="vliq" id="vliq" inputmode="decimal"
                           value="<?= $edicao ? number_format((float) $registro['vliq'], 2, ',', '') : '' ?>">
                </div>

                <div class="campo terco">
                    <label for="banco">Banco</label>
                    <select name="banco" id="banco">
                        <option value="">-</option>
                        <?php foreach ($bancos as $b): ?>
                            <option value="<?= html_escape($b['codigo']) ?>"
                                <?= $edicao && (string) $registro['banco'] === (string) $b['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($b['codigo'] . ' - ' . $b['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo terco">
                    <label for="agencia">Agencia</label>
                    <input type="text" name="agencia" id="agencia" maxlength="25"
                           value="<?= html_escape($valor('agencia', '')) ?>">
                </div>

                <div class="campo terco">
                    <label for="conta">Conta</label>
                    <input type="text" name="conta" id="conta" maxlength="20"
                           value="<?= html_escape($valor('conta', '')) ?>">
                </div>

                <div class="campo terco">
                    <label for="portador_codigo">Portador</label>
                    <input type="number" name="portador_codigo" id="portador_codigo" min="0" step="1"
                           value="<?= $edicao && $registro['portador_codigo'] !== NULL ? (int) $registro['portador_codigo'] : '' ?>">
                </div>

                <div class="campo full">
                    <label for="historico">Historico</label>
                    <textarea name="historico" id="historico" rows="3"><?= html_escape($valor('historico', '')) ?></textarea>
                </div>

                <div class="campo">
                    <label class="check">
                        <input type="checkbox" name="ret_caixa" value="1"
                            <?= $edicao && bd_bool($registro['ret_caixa']) ? 'checked' : '' ?>>
                        Recebimento pelo caixa
                    </label>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao"><?= $edicao ? 'Salvar alteracoes' : 'Criar titulo' ?></button>
                <a class="botao secundario" href="<?= site_url('financeiro/contas_receber') ?>">Cancelar</a>
            </div>

        <?= form_close() ?>
    </div>
</div>

<script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
<script src="<?= base_url('assets/js/financeiro.js') ?>"></script>
