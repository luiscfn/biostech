<div class="painel">
    <div class="painel-cabecalho">
        <h3>Entrada de estoque</h3>
        <a class="botao secundario pequeno" href="<?= site_url('estoque') ?>">&larr; Saldos</a>
    </div>

    <div class="painel-corpo">
        <?= form_open('estoque/entrada') ?>

            <div class="form-grid">
                <div class="campo full">
                    <label for="fornecedor_busca">Fornecedor</label>
                    <div class="autocomplete" data-url="<?= site_url('estoque/buscar_fornecedores') ?>">
                        <input type="text" id="fornecedor_busca" class="autocomplete-busca" placeholder="Digite a razao social, nome fantasia ou CNPJ..." autocomplete="off">
                        <input type="hidden" name="fornecedor_id" id="fornecedor_id">
                        <div class="autocomplete-lista"></div>
                    </div>
                    <div class="ajuda">Opcional para devolucao ou ajuste de entrada.</div>
                </div>

                <div class="campo">
                    <label for="documento">Documento / nota fiscal</label>
                    <input type="text" name="documento" id="documento" maxlength="30" placeholder="NF, recibo, devolucao...">
                </div>

                <div class="campo">
                    <label for="motivo">Motivo</label>
                    <input type="text" name="motivo" id="motivo" maxlength="80" placeholder="Compra, devolucao, bonificacao...">
                </div>

                <div class="campo full">
                    <label for="observacao">Observacoes</label>
                    <textarea name="observacao" id="observacao"></textarea>
                </div>
            </div>

            <hr class="divisor">

            <h4 class="secao-titulo">Itens da entrada</h4>

            <div class="tabela-wrap">
                <table class="tabela tabela-itens" data-itens data-template="linha-entrada" data-total="total-entrada">
                    <thead>
                        <tr>
                            <th class="num" style="width:42px">#</th>
                            <th>Produto</th>
                            <th class="centro" style="width:70px">Unid.</th>
                            <th class="num" style="width:85px">Saldo atual</th>
                            <th class="num" style="width:85px">Qtd</th>
                            <th class="num" style="width:110px">Custo unit.</th>
                            <th class="num" style="width:105px">Subtotal</th>
                            <th class="centro" style="width:55px"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6" class="num"><strong>Total da entrada</strong></td>
                            <td class="num"><strong><span id="total-entrada">0,00</span></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="form-acoes">
                <button type="button" class="botao secundario" data-adicionar-item="entrada">+ Adicionar item</button>
                <button type="submit" class="botao">Registrar entrada</button>
                <a class="botao secundario" href="<?= site_url('estoque') ?>">Cancelar</a>
            </div>

        <?= form_close() ?>
    </div>
</div>

<template id="linha-entrada">
    <table><tbody>
        <tr class="linha-item">
            <td class="num numero-item">1</td>
            <td>
                <div class="autocomplete"
                     data-url="<?= site_url('estoque/buscar_produtos') ?>"
                     data-custo=".custo"
                     data-estoque=".saldo"
                     data-unidade=".unidade">
                    <input type="text" class="autocomplete-busca" placeholder="Codigo ou descricao do produto..." autocomplete="off">
                    <input type="hidden" name="produto_id[]">
                    <div class="autocomplete-lista"></div>
                </div>
            </td>
            <td><input type="text" class="unidade" value="" readonly tabindex="-1"></td>
            <td><input type="text" class="saldo" value="" readonly tabindex="-1" style="text-align:right"></td>
            <td><input type="number" name="quantidade[]" class="qtd" value="1" min="1" step="1" style="text-align:right"></td>
            <td><input type="text" name="custo[]" class="custo" value="" placeholder="0,00" inputmode="decimal" style="text-align:right"></td>
            <td class="num total-linha">0,00</td>
            <td class="centro"><button type="button" class="botao perigo pequeno remover-item" title="Remover item">&times;</button></td>
        </tr>
    </tbody></table>
</template>

<script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
<script src="<?= base_url('assets/js/estoque.js') ?>"></script>
