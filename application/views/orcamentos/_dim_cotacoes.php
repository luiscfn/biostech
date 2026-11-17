<div class="painel-corpo">
    <?php if (empty($cotacoes)): ?>
        <div class="vazio-estado">
            Nenhuma cotacao de fornecedor registrada no legado para este dimensionamento.
        </div>
    <?php else: ?>
        <div class="tabela-wrap">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th style="width:170px">Fornecedor</th>
                        <th style="width:100px">Data</th>
                        <th class="num" style="width:120px">Preco unit.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cotacoes as $cotacao): ?>
                        <tr>
                            <td>
                                <?= html_escape($cotacao['produto_descricao'] ? $cotacao['produto_descricao'] : ($cotacao['descricao_produto'] ? $cotacao['descricao_produto'] : '-')) ?>
                            </td>
                            <td>
                                <?php if ( ! empty($cotacao['fornecedor_codigo'])): ?>
                                    <a href="<?= site_url('estoque/fornecedor/' . $cotacao['fornecedor_codigo']) ?>"><?= (int) $cotacao['fornecedor_codigo'] ?></a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= data_br($cotacao['data']) ?></td>
                            <td class="num"><?= moeda_br($cotacao['preco_unitario']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
