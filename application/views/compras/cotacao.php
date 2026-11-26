<?php
$edicao = ! empty($registro['codigo']);
$data   = $edicao ? $registro['data_cotacao'] : date('Y-m-d');
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>
            <?= $edicao ? 'Cotacao ' . (int) $registro['codigo'] : 'Nova cotacao de precos' ?>
        </h3>

        <div class="acoes-ficha">
            <?php if ($edicao): ?>
                <a class="botao secundario pequeno" href="<?= site_url('compras/imprimir_cotacao/' . $registro['codigo']) ?>" target="_blank">Imprimir</a>
            <?php endif; ?>
            <a class="botao secundario pequeno" href="<?= site_url('compras/cotacoes') ?>">Voltar</a>
        </div>
    </div>

    <div class="painel-corpo">
        <?= form_open($edicao ? 'compras/cotacao/' . $registro['codigo'] : 'compras/cotacao') ?>

            <?php if ($edicao && ! empty($registro['solicitacao_numero'])): ?>
                <input type="hidden" name="solicitacao_numero" value="<?= (int) $registro['solicitacao_numero'] ?>">
                <div class="alert alert-success">
                    Importada da solicitacao interna
                    <a href="<?= site_url('compras/solicitacao/' . $registro['solicitacao_numero']) ?>"><?= (int) $registro['solicitacao_numero'] ?></a>.
                </div>
            <?php endif; ?>

            <div class="form-grid">
                <div class="campo terco">
                    <label for="data_cotacao">Data da cotacao *</label>
                    <input type="date" name="data_cotacao" id="data_cotacao" value="<?= html_escape($data) ?>" required>
                </div>

                <div class="campo terco">
                    <label for="num_os">Numero da OS</label>
                    <input type="text" name="num_os" id="num_os" maxlength="16"
                           value="<?= $edicao ? html_escape($registro['num_os']) : '' ?>">
                </div>

                <div class="campo terco">
                    <label for="solicitante_id">Solicitante</label>
                    <select name="solicitante_id" id="solicitante_id">
                        <option value="">-</option>
                        <?php foreach ($funcionarios as $f): ?>
                            <option value="<?= (int) $f['id'] ?>"
                                <?= $edicao && (int) $registro['solicitante_id'] === (int) $f['id'] ? 'selected' : '' ?>>
                                <?= html_escape($f['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo terco">
                    <label for="funcionario_id">Responsavel pela cotacao *</label>
                    <select name="funcionario_id" id="funcionario_id" required>
                        <option value="">-</option>
                        <?php foreach ($funcionarios as $f): ?>
                            <option value="<?= (int) $f['id'] ?>"
                                <?= $edicao && (int) $registro['funcionario_id'] === (int) $f['id'] ? 'selected' : '' ?>>
                                <?= html_escape($f['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo full">
                    <label for="observacao">Observacoes</label>
                    <textarea name="observacao" id="observacao"><?= $edicao ? html_escape($registro['observacao']) : '' ?></textarea>
                </div>
            </div>

            <hr class="divisor">

            <h4 class="secao-titulo">Itens e propostas (ate 3 fornecedores por item)</h4>

            <div class="tabela-wrap">
                <table class="tabela tabela-itens" data-cotacao data-template="linha-cotacao" data-grupo="cotacao">
                    <thead>
                        <tr>
                            <th class="num" style="width:36px">#</th>
                            <th style="min-width:220px">Produto</th>
                            <th class="num" style="width:80px">Qtd</th>
                            <th style="min-width:190px">Fornecedor 1</th>
                            <th class="num" style="width:95px">Preco 1</th>
                            <th style="min-width:190px">Fornecedor 2</th>
                            <th class="num" style="width:95px">Preco 2</th>
                            <th style="min-width:190px">Fornecedor 3</th>
                            <th class="num" style="width:95px">Preco 3</th>
                            <th style="width:170px">Escolhido</th>
                            <th class="centro" style="width:44px"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="form-acoes">
                <button type="button" class="botao secundario" data-adicionar-item="cotacao">+ Adicionar item</button>
                <button type="submit" class="botao">Salvar cotacao</button>
                <a class="botao secundario" href="<?= site_url('compras/cotacoes') ?>">Cancelar</a>
            </div>

        <?= form_close() ?>
    </div>
</div>

<template id="linha-cotacao">
    <table><tbody>
        <tr class="linha-item">
            <td class="num numero-item">1</td>
            <td>
                <div class="autocomplete" data-url="<?= site_url('compras/buscar_produtos') ?>">
                    <input type="text" class="autocomplete-busca" placeholder="Codigo ou descricao..." autocomplete="off">
                    <input type="hidden" name="produto_id[]">
                    <div class="autocomplete-lista"></div>
                </div>
            </td>
            <td><input type="text" inputmode="decimal" name="quantidade[]" class="qtd" value="1" style="text-align:right"></td>

            <td>
                <div class="autocomplete fornecedor-1" data-url="<?= site_url('compras/buscar_fornecedores') ?>">
                    <input type="text" class="autocomplete-busca" placeholder="Fornecedor..." autocomplete="off">
                    <input type="hidden" name="fornecedor_1[]">
                    <div class="autocomplete-lista"></div>
                </div>
            </td>
            <td><input type="text" name="preco_1[]" inputmode="decimal" style="text-align:right"></td>

            <td>
                <div class="autocomplete fornecedor-2" data-url="<?= site_url('compras/buscar_fornecedores') ?>">
                    <input type="text" class="autocomplete-busca" placeholder="Fornecedor..." autocomplete="off">
                    <input type="hidden" name="fornecedor_2[]">
                    <div class="autocomplete-lista"></div>
                </div>
            </td>
            <td><input type="text" name="preco_2[]" inputmode="decimal" style="text-align:right"></td>

            <td>
                <div class="autocomplete fornecedor-3" data-url="<?= site_url('compras/buscar_fornecedores') ?>">
                    <input type="text" class="autocomplete-busca" placeholder="Fornecedor..." autocomplete="off">
                    <input type="hidden" name="fornecedor_3[]">
                    <div class="autocomplete-lista"></div>
                </div>
            </td>
            <td><input type="text" name="preco_3[]" inputmode="decimal" style="text-align:right"></td>

            <td><select name="fornecedor_escolhido[]" class="fornecedor-escolhido"><option value="">-</option></select></td>

            <td class="centro"><button type="button" class="botao perigo pequeno remover-item" title="Remover item">&times;</button></td>
        </tr>
    </tbody></table>
</template>

<script>
window.BIOSTECH_COTACAO_ITENS = <?= json_encode(array_map(function ($i) {
    return array(
        'produto_id'           => (int) $i['produto_id'],
        'descricao'            => $i['descricao'],
        'quantidade'           => (float) $i['quantidade'],
        'fornecedor_1'         => $i['fornecedor_1'] !== NULL ? (int) $i['fornecedor_1'] : NULL,
        'fornecedor_1_nome'    => $i['fornecedor_1_nome'],
        'preco_1'              => $i['preco_1'] !== NULL ? (float) $i['preco_1'] : NULL,
        'fornecedor_2'         => $i['fornecedor_2'] !== NULL ? (int) $i['fornecedor_2'] : NULL,
        'fornecedor_2_nome'    => $i['fornecedor_2_nome'],
        'preco_2'              => $i['preco_2'] !== NULL ? (float) $i['preco_2'] : NULL,
        'fornecedor_3'         => $i['fornecedor_3'] !== NULL ? (int) $i['fornecedor_3'] : NULL,
        'fornecedor_3_nome'    => $i['fornecedor_3_nome'],
        'preco_3'              => $i['preco_3'] !== NULL ? (float) $i['preco_3'] : NULL,
        'fornecedor_escolhido' => $i['fornecedor_escolhido'] !== NULL ? (int) $i['fornecedor_escolhido'] : NULL,
    );
}, $itens), JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
<script src="<?= base_url('assets/js/compras.js?v=5') ?>"></script>
