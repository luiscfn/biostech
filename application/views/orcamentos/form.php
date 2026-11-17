<?php
$edicao = ! empty($registro['numero']);
$data   = $edicao ? $registro['data'] : date('Y-m-d');

$v = function ($campo, $padrao = '') use ($edicao, $registro) {
    return $edicao && isset($registro[$campo]) && $registro[$campo] !== NULL ? $registro[$campo] : $padrao;
};

$dinheiro = function ($campo) use ($v) {
    $valor = $v($campo, NULL);
    return $valor === NULL ? '' : number_format((float) $valor, 2, ',', '.');
};
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>
            <?= $edicao ? 'Editar orcamento ' . (int) $registro['numero'] : 'Novo orcamento' ?>
        </h3>

        <div class="acoes-ficha">
            <?php if ($edicao): ?>
                <a class="botao secundario pequeno" href="<?= site_url('orcamentos/ver/' . $registro['numero']) ?>">Ver ficha</a>
            <?php endif; ?>
            <a class="botao secundario pequeno" href="<?= site_url('orcamentos') ?>">Voltar</a>
        </div>
    </div>

    <div class="painel-corpo">
        <?= form_open($edicao ? 'orcamentos/editar/' . $registro['numero'] : 'orcamentos/novo') ?>

            <h4 class="secao-titulo">Dados do orcamento</h4>

            <div class="form-grid">
                <div class="campo terco">
                    <label for="data">Data *</label>
                    <input type="date" name="data" id="data" value="<?= html_escape($data) ?>" required>
                </div>

                <div class="campo terco">
                    <label for="chamado_numero">Chamado / OS</label>
                    <input type="text" name="chamado_numero" id="chamado_numero" maxlength="16"
                           value="<?= html_escape($v('chamado_numero')) ?>"
                           placeholder="Numero da OS vinculada">
                </div>

                <div class="campo terco">
                    <label for="finalidade">Finalidade</label>
                    <select name="finalidade" id="finalidade">
                        <option value="">-</option>
                        <?php foreach ($finalidades as $f): ?>
                            <option value="<?= (int) $f['codigo'] ?>"
                                <?= (int) $v('finalidade', 0) === (int) $f['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($f['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo full">
                    <label for="cliente_busca">Cliente</label>
                    <div class="autocomplete" data-url="<?= site_url('orcamentos/buscar_clientes') ?>">
                        <input type="text" id="cliente_busca" class="autocomplete-busca"
                               placeholder="Digite o nome, fantasia ou CPF/CNPJ..."
                               value="<?= $edicao ? html_escape($registro['cliente_nome']) : '' ?>" autocomplete="off">
                        <input type="hidden" name="cliente_id" id="cliente_id"
                               value="<?= $edicao ? (int) $registro['cliente_id'] : '' ?>">
                        <div class="autocomplete-lista"></div>
                    </div>
                    <?php if ($edicao && ! empty($registro['cliente_id'])): ?>
                        <div class="ajuda">
                            <?= html_escape($registro['cliente_documento'] ? $registro['cliente_documento'] : '') ?>
                            <?= $registro['cliente_cidade'] ? ' | ' . html_escape($registro['cliente_cidade']) . '/' . html_escape($registro['cliente_uf']) : '' ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="campo">
                    <label for="atencao">Atencao</label>
                    <input type="text" name="atencao" id="atencao" maxlength="70"
                           value="<?= html_escape($v('atencao')) ?>"
                           placeholder="Pessoa de contato / setor">
                </div>

                <div class="campo">
                    <label for="equipamento_serie">Equipamento / serie</label>
                    <input type="text" name="equipamento_serie" id="equipamento_serie" maxlength="30"
                           value="<?= html_escape($v('equipamento_serie')) ?>">
                </div>

                <div class="campo">
                    <label for="nota_fiscal_chegada">Nota fiscal de chegada</label>
                    <input type="text" name="nota_fiscal_chegada" id="nota_fiscal_chegada" maxlength="20"
                           value="<?= html_escape($v('nota_fiscal_chegada')) ?>">
                </div>

                <div class="campo terco">
                    <label for="condicao_pagamento">Condicao de pagamento (codigo)</label>
                    <input type="number" name="condicao_pagamento" id="condicao_pagamento" step="1"
                           value="<?= $v('condicao_pagamento') !== '' ? (int) $v('condicao_pagamento') : '' ?>">
                </div>

                <div class="campo terco">
                    <label for="entrega">Entrega (dias)</label>
                    <input type="number" name="entrega" id="entrega" step="1" min="0"
                           value="<?= $v('entrega') !== '' ? (int) $v('entrega') : '' ?>">
                </div>

                <div class="campo terco">
                    <label for="garantia">Garantia (dias)</label>
                    <input type="number" name="garantia" id="garantia" step="1" min="0"
                           value="<?= $v('garantia') !== '' ? (int) $v('garantia') : '' ?>">
                </div>

                <div class="campo terco">
                    <label for="validade">Validade da proposta (dias)</label>
                    <input type="number" name="validade" id="validade" step="1" min="0"
                           value="<?= $v('validade') !== '' ? (int) $v('validade') : '' ?>">
                </div>

                <div class="campo terco">
                    <label for="validade_pecas">Validade das pecas (dias)</label>
                    <input type="number" name="validade_pecas" id="validade_pecas" step="1" min="0"
                           value="<?= $v('validade_pecas') !== '' ? (int) $v('validade_pecas') : '' ?>">
                </div>

                <div class="campo terco">
                    <label for="assinatura">Assinatura</label>
                    <input type="text" name="assinatura" id="assinatura" maxlength="50"
                           value="<?= html_escape($v('assinatura')) ?>">
                </div>

                <div class="campo full">
                    <label for="observacao">Observacoes</label>
                    <textarea name="observacao" id="observacao" rows="4"><?= html_escape($v('observacao')) ?></textarea>
                </div>
            </div>

            <hr class="divisor">

            <h4 class="secao-titulo">Valores e percentuais</h4>
            <p class="ajuda" style="margin-top:-4px">
                Quando o percentual e informado ele prevalece sobre o valor digitado. Os totais sao recalculados a
                cada alteracao de item, percentual ou valor.
            </p>

            <div class="form-grid">
                <div class="campo terco">
                    <label for="valor_mao_obra">Mao de obra (R$)</label>
                    <input type="text" name="valor_mao_obra" id="valor_mao_obra" inputmode="decimal"
                           value="<?= $dinheiro('valor_mao_obra') ?>" placeholder="0,00">
                </div>
                <div class="campo terco">
                    <label for="percentual_mao_obra">Mao de obra (%)</label>
                    <input type="text" name="percentual_mao_obra" id="percentual_mao_obra" inputmode="decimal"
                           value="<?= $dinheiro('percentual_mao_obra') ?>" placeholder="sobre o material">
                </div>
                <div class="campo terco">
                    <label>Material (itens)</label>
                    <div class="saldo-grande"><?= moeda_br($v('valor_material', 0)) ?></div>
                </div>

                <div class="campo terco">
                    <label for="material_fixacao">Material de fixacao (R$)</label>
                    <input type="text" name="material_fixacao" id="material_fixacao" inputmode="decimal"
                           value="<?= $dinheiro('material_fixacao') ?>" placeholder="0,00">
                </div>
                <div class="campo terco">
                    <label for="percentual_mat_fixacao">Material de fixacao (%)</label>
                    <input type="text" name="percentual_mat_fixacao" id="percentual_mat_fixacao" inputmode="decimal"
                           value="<?= $dinheiro('percentual_mat_fixacao') ?>" placeholder="sobre material + mao de obra">
                </div>
                <div class="campo terco">
                    <label>Servicos com valor</label>
                    <div class="saldo-grande"><?= moeda_br($v('valor_servico', 0)) ?></div>
                </div>

                <div class="campo terco">
                    <label for="acrescimo_custo">Acrescimo de custo (R$)</label>
                    <input type="text" name="acrescimo_custo" id="acrescimo_custo" inputmode="decimal"
                           value="<?= $dinheiro('acrescimo_custo') ?>" placeholder="0,00">
                </div>
                <div class="campo terco">
                    <label for="percentual_acrescimo_custo">Acrescimo de custo (%)</label>
                    <input type="text" name="percentual_acrescimo_custo" id="percentual_acrescimo_custo" inputmode="decimal"
                           value="<?= $dinheiro('percentual_acrescimo_custo') ?>">
                </div>
                <div class="campo terco">
                    <label for="valor_custos_indiretos">Custos indiretos (R$)</label>
                    <input type="text" name="valor_custos_indiretos" id="valor_custos_indiretos" inputmode="decimal"
                           value="<?= $dinheiro('valor_custos_indiretos') ?>" placeholder="0,00">
                </div>

                <div class="campo terco">
                    <label for="valor_imposto">Imposto / nota fiscal (R$)</label>
                    <input type="text" name="valor_imposto" id="valor_imposto" inputmode="decimal"
                           value="<?= $dinheiro('valor_imposto') ?>" placeholder="0,00">
                </div>
                <div class="campo terco">
                    <label for="percentual_nota_fiscal">Imposto / nota fiscal (%)</label>
                    <input type="text" name="percentual_nota_fiscal" id="percentual_nota_fiscal" inputmode="decimal"
                           value="<?= $dinheiro('percentual_nota_fiscal') ?>">
                </div>
                <div class="campo terco">
                    <label for="valor_maximo_cliente">Valor maximo do cliente (R$)</label>
                    <input type="text" name="valor_maximo_cliente" id="valor_maximo_cliente" inputmode="decimal"
                           value="<?= $dinheiro('valor_maximo_cliente') ?>" placeholder="0,00">
                </div>

                <div class="campo terco">
                    <label for="valor_desconto">Desconto (R$)</label>
                    <input type="text" name="valor_desconto" id="valor_desconto" inputmode="decimal"
                           value="<?= $dinheiro('valor_desconto') ?>" placeholder="0,00">
                </div>
                <div class="campo terco">
                    <label for="percentual_desconto">Desconto (%)</label>
                    <input type="text" name="percentual_desconto" id="percentual_desconto" inputmode="decimal"
                           value="<?= $dinheiro('percentual_desconto') ?>">
                </div>
                <div class="campo terco">
                    <label>Total do orcamento</label>
                    <div class="saldo-grande" id="orcamento-total"><?= moeda_br($v('valor_total', 0)) ?></div>
                    <div class="ajuda">Material + servicos - desconto + percentuais.</div>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao"><?= $edicao ? 'Salvar alteracoes' : 'Criar orcamento' ?></button>
                <a class="botao secundario" href="<?= site_url($edicao ? 'orcamentos/ver/' . $registro['numero'] : 'orcamentos') ?>">Cancelar</a>
            </div>

        <?= form_close() ?>
    </div>
</div>

<script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
