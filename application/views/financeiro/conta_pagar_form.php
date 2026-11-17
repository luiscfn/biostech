<?php
$edicao = ! empty($registro['id']);
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
            <?= $edicao ? 'Editar conta a pagar ' . (int) $registro['numero'] . '/' . (int) $registro['parcela'] : 'Nova conta a pagar' ?>
        </h3>

        <div class="acoes-ficha">
            <?php if ($edicao): ?>
                <a class="botao secundario pequeno" href="<?= site_url('financeiro/conta_pagar_ver/' . $registro['id']) ?>">Ver ficha</a>
            <?php endif; ?>
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/contas_pagar') ?>">Voltar</a>
        </div>
    </div>

    <div class="painel-corpo">
        <?= form_open($edicao ? 'financeiro/conta_pagar_editar/' . $registro['id'] : 'financeiro/conta_pagar_novo', array('id' => 'form-cp')) ?>

            <div class="form-grid">
                <div class="campo full">
                    <label for="fornecedor_busca">Fornecedor</label>
                    <div class="autocomplete" data-url="<?= site_url('financeiro/buscar_fornecedores') ?>">
                        <input type="text" id="fornecedor_busca" class="autocomplete-busca"
                               placeholder="Digite a razao social, nome fantasia ou CNPJ..."
                               value="<?= $edicao ? html_escape($registro['fornecedor_nome']) : '' ?>" autocomplete="off">
                        <input type="hidden" name="fornecedor_codigo" id="fornecedor_codigo"
                               value="<?= $edicao ? (int) $registro['fornecedor_codigo'] : '' ?>">
                        <div class="autocomplete-lista"></div>
                    </div>
                    <?php if ($edicao && ! empty($registro['fornecedor_codigo'])): ?>
                        <div class="ajuda">
                            <?= html_escape($registro['fornecedor_codigo'] . ' - ' . $registro['fornecedor_nome']) ?>
                            <?= $registro['fornecedor_cnpj'] ? ' | CNPJ ' . html_escape($registro['fornecedor_cnpj']) : '' ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="campo terco">
                    <label for="emissao">Emissao</label>
                    <input type="date" name="emissao" id="emissao" value="<?= html_escape($valor('emissao', $hoje)) ?>">
                </div>

                <div class="campo terco">
                    <label for="lancamento">Lancamento</label>
                    <input type="date" name="lancamento" id="lancamento" value="<?= html_escape($valor('lancamento', $hoje)) ?>">
                </div>

                <div class="campo terco">
                    <label for="vencimento">1o vencimento *</label>
                    <input type="date" name="vencimento" id="vencimento" value="<?= html_escape($valor('vencimento', $hoje)) ?>" required>
                </div>

                <div class="campo terco">
                    <label for="valor">Valor total *</label>
                    <input type="text" name="valor" id="valor" inputmode="decimal"
                           value="<?= $edicao ? number_format((float) $registro['valor'], 2, ',', '') : '' ?>" required>
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
                    <label for="forma_recebimento">Forma de pagamento</label>
                    <select name="forma_recebimento" id="forma_recebimento">
                        <option value="">-</option>
                        <?php foreach ($dominios['forma_recebimento'] as $d): ?>
                            <option value="<?= (int) $d['codigo'] ?>"
                                <?= $edicao && (int) $registro['forma_recebimento'] === (int) $d['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($d['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo terco">
                    <label for="documento_recebimento">Documento</label>
                    <select name="documento_recebimento" id="documento_recebimento">
                        <option value="">-</option>
                        <?php foreach ($dominios['documento'] as $d): ?>
                            <option value="<?= (int) $d['codigo'] ?>"
                                <?= $edicao && (int) $registro['documento_recebimento'] === (int) $d['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($d['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo terco">
                    <label for="documento">Numero do documento</label>
                    <input type="text" name="documento" id="documento" maxlength="30"
                           value="<?= html_escape($valor('documento', '')) ?>">
                </div>

                <div class="campo terco">
                    <label for="centro_custo">Centro de custo</label>
                    <select name="centro_custo" id="centro_custo">
                        <option value="">-</option>
                        <?php foreach ($centros as $c): ?>
                            <option value="<?= (int) $c['codigo'] ?>"
                                <?= $edicao && (int) $registro['centro_custo'] === (int) $c['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($c['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo terco">
                    <label for="portador_setor">Setor portador</label>
                    <input type="number" name="portador_setor" id="portador_setor" min="0" step="1"
                           value="<?= $edicao && $registro['portador_setor'] !== NULL ? (int) $registro['portador_setor'] : '' ?>">
                </div>

                <div class="campo terco">
                    <label for="portador">Portador</label>
                    <input type="number" name="portador" id="portador" min="0" step="1"
                           value="<?= $edicao && $registro['portador'] !== NULL ? (int) $registro['portador'] : '' ?>">
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
                    <input type="text" name="conta" id="conta" maxlength="25"
                           value="<?= html_escape($valor('conta', '')) ?>">
                </div>

                <div class="campo full">
                    <label for="historico">Historico</label>
                    <textarea name="historico" id="historico" rows="3"><?= html_escape($valor('historico', '')) ?></textarea>
                </div>

                <div class="campo">
                    <label class="check">
                        <input type="checkbox" name="ret_caixa" value="1"
                            <?= $edicao && bd_bool($registro['ret_caixa']) ? 'checked' : '' ?>>
                        Baixa pelo caixa (retorno de caixa)
                    </label>
                </div>

                <div class="campo">
                    <label for="data_caixa">Data do caixa</label>
                    <input type="date" name="data_caixa" id="data_caixa" value="<?= html_escape($valor('data_caixa', '')) ?>">
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao"><?= $edicao ? 'Salvar alteracoes' : 'Criar titulo' ?></button>
                <a class="botao secundario" href="<?= site_url('financeiro/contas_pagar') ?>">Cancelar</a>
            </div>

        <?= form_close() ?>
    </div>
</div>

<script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
