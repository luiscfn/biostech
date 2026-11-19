<?php
$encerrado = bd_bool($registro['encerrado']);
$total_pecas = 0;
foreach ($pecas as $p)
{
    $total_pecas += (float) $p['valor_total'];
}
?>
<div class="painel-corpo">
    <h4 class="secao-titulo">Pecas aplicadas</h4>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:55px" class="num">Item</th>
                    <th>Produto</th>
                    <th class="num" style="width:70px">Qtd</th>
                    <th class="num" style="width:100px">Unitario</th>
                    <th class="num" style="width:100px">Total</th>
                    <th class="centro" style="width:100px">Estoque</th>
                    <th class="centro" style="width:80px">Acao</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pecas)): ?>
                    <tr><td colspan="7" class="vazio">Nenhuma peca cadastrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($pecas as $p): ?>
                        <tr>
                            <td class="num"><?= (int) $p['numero_item'] ?></td>
                            <td><?= html_escape($p['descricao_produto'] ? $p['descricao_produto'] : '-') ?></td>
                            <td class="num"><?= (int) $p['quantidade'] ?></td>
                            <td class="num"><?= moeda_br($p['valor_unitario']) ?></td>
                            <td class="num"><?= moeda_br($p['valor_total']) ?></td>
                            <td class="centro">
                                <?php if (bd_bool($p['saiu_estoque'])): ?>
                                    <span class="badge ok">baixada</span>
                                <?php else: ?>
                                    <span class="badge alerta">pendente</span>
                                <?php endif; ?>
                            </td>
                            <td class="centro">
                                <?php if ( ! bd_bool($p['saiu_estoque']) && ! $encerrado): ?>
                                    <?= form_open('chamados/excluir_peca/' . $registro['numero'] . '/' . $p['id'], array('class' => 'inline', 'onsubmit' => "return confirm('Excluir esta peca?');")) ?>
                                        <button type="submit" class="botao perigo pequeno">Excluir</button>
                                    <?= form_close() ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="num"><strong>Total de pecas</strong></td>
                    <td class="num"><strong><?= moeda_br($total_pecas) ?></strong></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if ( ! $encerrado): ?>
        <hr class="divisor">

        <h4 class="secao-titulo">Incluir peca</h4>

        <?= form_open('chamados/pecas/' . $registro['numero']) ?>
            <div class="form-grid">
                <div class="campo full">
                    <label for="produto_busca">Produto *</label>
                    <div class="autocomplete" data-url="<?= site_url('chamados/buscar_produtos' . ( ! empty($registro['cliente_id']) ? '?cliente=' . (int) $registro['cliente_id'] : '')) ?>" data-preco="valor_unitario">
                        <input type="text" id="produto_busca" class="autocomplete-busca" placeholder="Digite o codigo ou a descricao do produto..." autocomplete="off">
                        <input type="hidden" name="produto_id" id="produto_id">
                        <div class="autocomplete-lista"></div>
                    </div>
                    <div class="ajuda">Ao escolher o produto, o valor de venda e preenchido automaticamente.</div>
                </div>

                <div class="campo">
                    <label for="quantidade">Quantidade *</label>
                    <input type="number" name="quantidade" id="quantidade" value="1" min="1">
                </div>

                <div class="campo">
                    <label for="valor_unitario">Valor unitario</label>
                    <input type="text" name="valor_unitario" id="valor_unitario" placeholder="0,00">
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao">Incluir peca</button>

                <button type="submit" class="botao secundario" formaction="<?= site_url('chamados/baixar_estoque/' . $registro['numero']) ?>" formmethod="post"
                        onclick="return confirm('Baixar do estoque todas as pecas pendentes?');">
                    Baixar estoque pendente
                </button>
            </div>
        <?= form_close() ?>
    <?php endif; ?>
</div>

<script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
