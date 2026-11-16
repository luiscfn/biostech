<div class="painel">
    <div class="painel-cabecalho">
        <h3>Saida de estoque</h3>
        <a class="botao secundario pequeno" href="<?= site_url('estoque') ?>">&larr; Saldos</a>
    </div>

    <div class="painel-corpo">
        <?= form_open('estoque/saida') ?>

            <div class="form-grid">
                <div class="campo">
                    <label for="setor_id">Setor requisitante</label>
                    <select name="setor_id" id="setor_id">
                        <option value="">(nao informado)</option>
                        <?php foreach ($setores as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"><?= html_escape($s['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="funcionario_id">Responsavel / funcionario</label>
                    <select name="funcionario_id" id="funcionario_id">
                        <option value="">(nao informado)</option>
                        <?php foreach ($funcionarios as $f): ?>
                            <option value="<?= (int) $f['id'] ?>"><?= html_escape($f['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="documento">Documento / requisicao</label>
                    <input type="text" name="documento" id="documento" maxlength="30" placeholder="Requisicao, pedido...">
                </div>

                <div class="campo">
                    <label for="motivo">Motivo</label>
                    <input type="text" name="motivo" id="motivo" maxlength="80" placeholder="Consumo, manutencao, perda...">
                </div>

                <div class="campo full">
                    <label for="observacao">Observacoes</label>
                    <textarea name="observacao" id="observacao"></textarea>
                </div>
            </div>

            <hr class="divisor">

            <h4 class="secao-titulo">Itens da saida</h4>

            <div class="tabela-wrap">
                <table class="tabela tabela-itens" data-itens data-grupo="saida" data-template="linha-saida" data-total="total-saida">
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
                            <td colspan="6" class="num"><strong>Custo total da saida</strong></td>
                            <td class="num"><strong>R$ <span id="total-saida">0,00</span></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="ajuda">O saldo nunca pode ficar negativo. A saida consome o saldo e soma no total consumido do produto.</div>

            <div class="form-acoes">
                <button type="button" class="botao secundario" data-adicionar-item="saida">+ Adicionar item</button>
                <button type="submit" class="botao">Registrar saida</button>
                <a class="botao secundario" href="<?= site_url('estoque') ?>">Cancelar</a>
            </div>

        <?= form_close() ?>
    </div>
</div>

<template id="linha-saida">
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
            <td><input type="text" name="custo[]" class="custo" value="" readonly tabindex="-1" style="text-align:right"></td>
            <td class="num total-linha">0,00</td>
            <td class="centro"><button type="button" class="botao perigo pequeno remover-item" title="Remover item">&times;</button></td>
        </tr>
    </tbody></table>
</template>

<script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
<script src="<?= base_url('assets/js/estoque.js') ?>"></script>
