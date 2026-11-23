<?php
$url_base  = site_url('orcamentos/dimensionamento/' . $registro['numero']);
$subgrupo  = $filtros['subgrupo'];
$grupo     = $filtros['grupo'];

$nomes_subgrupos = array();

foreach ($subgrupos as $sg)
{
    $nomes_subgrupos[ltrim($sg['grupo_codigo'], '0') . '|' . ltrim($sg['codigo'], '0')] = $sg['nome'];
}

$produtos_map = array();

foreach ($produtos as $p)
{
    $produtos_map[(string) $p['id']] = array(
        'unidade' => $p['unidade'] ? $p['unidade'] : '-',
        'marca'   => $p['marca'] ? $p['marca'] : '-',
        'codigo'  => $p['codigo'] ? $p['codigo'] : (string) $p['id'],
        'saldo'   => numero_br($p['saldo'], 2),
    );
}

$total_classificacao = 0.0;

foreach ($itens_classificacao as $item)
{
    $total_classificacao += (float) $item['total_venda'];
}
?>
<div class="painel-corpo">
    <h4 class="secao-titulo destaque titulo-grande"><?= html_escape($classificacao['rotulo']) ?></h4>

    <?php if ($pode_editar): ?>
        <div class="bloco-inclusao">
            <h4 class="secao-titulo">Incluir produto</h4>

            <?= form_open('orcamentos/dim_adicionar_item_classificacao/' . $registro['numero'], array('id' => 'form-class-produto')) ?>
                <input type="hidden" name="aba" value="<?= html_escape($aba) ?>">
                <input type="hidden" name="grupo" value="<?= html_escape($grupo) ?>">
                <input type="hidden" name="subgrupo" value="<?= html_escape($subgrupo) ?>">

                <div class="form-grid">
                    <div class="campo quarto">
                        <label for="class-subgrupo">Subgrupo</label>
                        <select name="subgrupo_select" id="class-subgrupo">
                            <option value="">Selecione o subgrupo</option>
                            <?php foreach ($subgrupos as $sg): ?>
                                <?php $codigo_sg = ltrim($sg['codigo'], '0') ?>
                                <option value="<?= html_escape($codigo_sg) ?>" <?= $codigo_sg === $subgrupo ? 'selected' : '' ?>>
                                    <?= html_escape($sg['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="campo oitavo">
                        <label for="class-produto">Produto</label>
                        <select name="produto_id" id="class-produto" <?= $subgrupo === '' ? 'disabled' : '' ?>>
                            <option value=""><?= $subgrupo === '' ? 'Selecione o subgrupo' : 'Selecione o produto' ?></option>
                            <?php foreach ($produtos as $p): ?>
                                <option value="<?= (int) $p['id'] ?>"><?= html_escape($p['descricao']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="campo curto">
                        <label>Unidade</label>
                        <input type="text" id="class-unidade" value="-" readonly>
                    </div>
                    <div class="campo curto">
                        <label for="class-cor">Cor</label>
                        <input type="text" name="cor" id="class-cor" maxlength="30" placeholder="Informe a cor">
                    </div>
                    <div class="campo quarto">
                        <label>Marca</label>
                        <input type="text" id="class-marca" value="-" readonly>
                    </div>
                    <div class="campo curto">
                        <label>Referência</label>
                        <input type="text" id="class-referencia" value="-" readonly>
                    </div>
                    <div class="campo micro">
                        <label>ESTOQUE</label>
                        <input type="text" id="class-estoque" value="0,00" readonly>
                    </div>
                    <div class="campo micro">
                        <label for="class-quantidade">Quantidade</label>
                        <input type="number" name="quantidade" id="class-quantidade" min="1" step="1" value="1" required>
                    </div>
                    <div class="campo quarto" style="align-self:end">
                        <button type="submit" class="botao" <?= $subgrupo === '' ? 'disabled' : '' ?>>Incluir produto</button>
                    </div>
                </div>
            <?= form_close() ?>
        </div>
    <?php endif; ?>

    <div class="tabela-wrap tabela-rolagem classificacao-rolagem <?= $aba !== 'canaleta' ? 'rolagem-quatro' : '' ?>">
        <table class="tabela tabela-classificacao">
            <thead>
                <tr>
                    <th style="width:140px">Subgrupo</th>
                    <th>Produto</th>
                    <th style="width:110px">Marca</th>
                    <th class="centro" style="width:75px">UND</th>
                    <th class="num" style="width:85px">VALOR</th>
                    <th class="num" style="width:70px">QTDE</th>
                    <th class="num" style="width:90px">TOTAL</th>
                    <?php if ($pode_editar): ?>
                        <th class="centro" style="width:140px">Ações</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($itens_classificacao)): ?>
                    <tr><td colspan="<?= $pode_editar ? 8 : 7 ?>" class="vazio">Nenhum produto incluído nesta classificação.</td></tr>
                <?php else: ?>
                    <?php foreach ($itens_classificacao as $item): ?>
                        <?php
                        $form_id   = 'dim-class-item-' . (int) $item['id'];
                        $chave_sg  = ltrim((string) $item['grupo_efetivo'], '0') . '|' . ltrim((string) $item['subgrupo_efetivo'], '0');
                        $nome_sg   = isset($nomes_subgrupos[$chave_sg]) ? $nomes_subgrupos[$chave_sg] : '';
                        ?>
                        <tr>
                            <td><?= $nome_sg !== '' ? html_escape($nome_sg) : '-' ?></td>
                            <td><strong><?= html_escape($item['descricao_produto'] ? $item['descricao_produto'] : $item['descricao']) ?></strong></td>
                            <td><?= html_escape($item['produto_marca'] ? $item['produto_marca'] : '-') ?></td>
                            <td class="centro"><?= html_escape($item['produto_unidade'] ? $item['produto_unidade'] : '-') ?></td>
                            <td class="num"><?= moeda_br($item['valor_venda']) ?></td>
                            <td class="num">
                                <?php if ($pode_editar): ?>
                                    <input type="number" name="quantidade" form="<?= $form_id ?>" min="1" step="1"
                                           class="qtd-orcamento" value="<?= (int) $item['quantidade'] ?>">
                                <?php else: ?>
                                    <?= numero_br($item['quantidade']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="num"><strong><?= moeda_br($item['total_venda']) ?></strong></td>
                            <?php if ($pode_editar): ?>
                                <td class="centro acoes-cell">
                                    <div class="acoes-linha">
                                        <?= form_open('orcamentos/dim_atualizar_item/' . $registro['numero'] . '/' . $item['id'],
                                            array('id' => $form_id)) ?>
                                            <input type="hidden" name="aba" value="<?= html_escape($aba) ?>">
                                            <input type="hidden" name="grupo" value="<?= html_escape($grupo) ?>">
                                            <input type="hidden" name="subgrupo" value="<?= html_escape($subgrupo) ?>">
                                            <input type="hidden" name="valor_compra" value="<?= number_format((float) $item['valor_compra'], 2, ',', '.') ?>">
                                            <input type="hidden" name="valor_venda" value="<?= number_format((float) $item['valor_venda'], 2, ',', '.') ?>">
                                            <button type="submit" class="botao secundario pequeno">Salvar</button>
                                        <?= form_close() ?>

                                        <?= form_open('orcamentos/dim_excluir_item/' . $registro['numero'] . '/' . $item['id'],
                                            array('onsubmit' => "return confirm('Excluir o produto?')")) ?>
                                            <input type="hidden" name="aba" value="<?= html_escape($aba) ?>">
                                            <input type="hidden" name="grupo" value="<?= html_escape($grupo) ?>">
                                            <input type="hidden" name="subgrupo" value="<?= html_escape($subgrupo) ?>">
                                            <button type="submit" class="botao perigo pequeno">Excluir</button>
                                        <?= form_close() ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="num"><strong>Total</strong></td>
                    <td class="num"><strong><?= moeda_br($total_classificacao) ?></strong></td>
                    <?php if ($pode_editar): ?><td></td><?php endif; ?>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>
(function () {
    var selSubgrupo = document.getElementById('class-subgrupo');
    var selProduto  = document.getElementById('class-produto');
    var produtos    = <?= json_encode($produtos_map, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    if (selSubgrupo) {
        selSubgrupo.addEventListener('change', function () {
            var url = '<?= $url_base ?>?aba=<?= rawurlencode($aba) ?>';

            <?php if ($aba === 'outros'): ?>
                url += '&grupo=<?= rawurlencode($grupo) ?>';
            <?php endif; ?>

            if (this.value) {
                url += '&subgrupo=' + encodeURIComponent(this.value);
            }

            window.location.href = url;
        });
    }

    function preencherDetalhes() {
        var produto = produtos[selProduto.value];

        document.getElementById('class-unidade').value    = produto ? produto.unidade : '-';
        document.getElementById('class-marca').value      = produto ? produto.marca : '-';
        document.getElementById('class-referencia').value = produto ? produto.codigo : '-';
        document.getElementById('class-estoque').value    = produto ? produto.saldo : '0,00';
    }

    if (selProduto) {
        selProduto.addEventListener('change', preencherDetalhes);
        preencherDetalhes();
    }
})();
</script>
