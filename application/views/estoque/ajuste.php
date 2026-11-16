<?php
$edita_produto = ! empty($produto);
$data_padrao   = date('Y-m-d');
?>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Ajuste de inventario</h3>
        <a class="botao secundario pequeno" href="<?= site_url('estoque') ?>">&larr; Saldos</a>
    </div>

    <div class="painel-corpo">
        <?= form_open('estoque/ajuste') ?>

            <div class="form-grid">
                <div class="campo full">
                    <label for="produto_busca">Produto *</label>
                    <div class="autocomplete"
                         data-url="<?= site_url('estoque/buscar_produtos') ?>"
                         data-estoque="#saldo_atual"
                         data-unidade="#unidade_atual">
                        <input type="text" id="produto_busca" class="autocomplete-busca"
                               value="<?= $edita_produto ? html_escape($produto['descricao']) : '' ?>"
                               placeholder="Digite o codigo ou a descricao do produto..." autocomplete="off">
                        <input type="hidden" name="produto_id" id="produto_id"
                               value="<?= $edita_produto ? (int) $produto['id'] : '' ?>">
                        <div class="autocomplete-lista"></div>
                    </div>
                    <?= form_error('produto_id') ?>
                </div>

                <div class="campo">
                    <label>Saldo atual no sistema</label>
                    <input type="text" id="saldo_atual" readonly tabindex="-1"
                           value="<?= $edita_produto ? (int) $produto['saldo'] : '' ?>">
                </div>

                <div class="campo">
                    <label>Unidade</label>
                    <input type="text" id="unidade_atual" readonly tabindex="-1"
                           value="<?= $edita_produto ? html_escape($produto['unidade']) : '' ?>">
                </div>

                <div class="campo">
                    <label for="data">Data da contagem *</label>
                    <input type="date" name="data" id="data" value="<?= html_escape($data_padrao) ?>">
                </div>

                <div class="campo">
                    <label for="quantidade_contada">Quantidade contada *</label>
                    <input type="number" name="quantidade_contada" id="quantidade_contada" min="0" step="1" value="0">
                    <div class="ajuda">Informe o saldo fisico encontrado. A diferenca sera lancada como ajuste.</div>
                </div>

                <div class="campo full">
                    <label for="motivo">Motivo / observacao</label>
                    <input type="text" name="motivo" id="motivo" maxlength="200" placeholder="Contagem mensal, perda, avaria, correcao...">
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao">Registrar contagem</button>
                <a class="botao secundario" href="<?= site_url('estoque') ?>">Cancelar</a>
            </div>

        <?= form_close() ?>
    </div>
</div>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Ultimos inventarios</h3>
        <a class="botao secundario pequeno" href="<?= site_url('estoque/movimentos?origem=inventario') ?>">Ver movimentos</a>
    </div>

    <?php if (empty($inventarios)): ?>
        <div class="vazio-estado">Nenhum inventario registrado.</div>
    <?php else: ?>
        <div class="tabela-wrap">
            <table class="tabela">
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th style="width:100px">Data</th>
                        <th>Usuario</th>
                        <th class="num" style="width:80px">Itens</th>
                        <th class="num" style="width:120px">Dif. total</th>
                        <th>Observacao</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventarios as $i): ?>
                        <tr>
                            <td><?= (int) $i['id'] ?></td>
                            <td><?= data_br($i['data']) ?></td>
                            <td><?= html_escape($i['usuario']) ?></td>
                            <td class="num"><?= numero_br($i['itens']) ?></td>
                            <td class="num"><?= numero_br($i['diferenca_total']) ?></td>
                            <td><?= html_escape($i['observacao'] ? $i['observacao'] : '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
