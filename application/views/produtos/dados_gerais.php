<div class="painel">
    <div class="painel-cabecalho">
        <h3>Dados gerais do produto</h3>
        <div><span class="badge info"><?= html_escape($registro['descricao']) ?></span></div>
    </div>

    <div class="painel-corpo">
        <div class="detalhes">
            <div class="detalhe">
                <div class="rotulo">Codigo</div>
                <div class="valor"><?= (int) $registro['id'] ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Ultima NF de entrada</div>
                <div class="valor"><?= html_escape($registro['ultima_nf_entrada'] ? $registro['ultima_nf_entrada'] : '-') ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Data ultima entrada</div>
                <div class="valor"><?= data_br($registro['data_ultima_entrada']) ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Ultimo pedido de compra</div>
                <div class="valor"><?= (int) $registro['ultimo_pedido_compra'] ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Valor de compra</div>
                <div class="valor"><?= moeda_br($registro['valor_compra']) ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Valor de venda</div>
                <div class="valor"><?= moeda_br($registro['valor_venda']) ?></div>
            </div>
        </div>

        <hr class="divisor">

        <h4 class="secao-titulo">Notas fiscais de compra</h4>

        <?php if (empty($notas)): ?>
            <p class="vazio">Nenhuma nota fiscal de compra encontrada para este produto.</p>
        <?php else: ?>
            <div class="tabela-wrap">
                <table class="tabela compacta">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Nota fiscal</th>
                            <th style="width:110px">Data NF</th>
                            <th>Fornecedor</th>
                            <th class="num" style="width:90px">Qtde.</th>
                            <th class="num" style="width:100px">Valor unit.</th>
                            <th class="num" style="width:110px">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notas as $n): ?>
                            <tr>
                                <td><a href="<?= site_url('compras/ver/' . (int) $n['numero']) ?>"><?= (int) $n['numero'] ?></a></td>
                                <td><?= html_escape($n['num_nf'] ? $n['num_nf'] : '-') ?></td>
                                <td><?= data_br($n['data_nf']) ?></td>
                                <td><?= html_escape($n['fornecedor'] ? $n['fornecedor'] : '-') ?></td>
                                <td class="num"><?= numero_br($n['quantidade'], 3) ?></td>
                                <td class="num"><?= moeda_br($n['valor_unitario']) ?></td>
                                <td class="num"><?= moeda_br($n['valor_total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="form-acoes">
            <a class="botao secundario" href="<?= site_url('produtos/ver/' . (int) $registro['id']) ?>">Voltar ao produto</a>
        </div>
    </div>
</div>
