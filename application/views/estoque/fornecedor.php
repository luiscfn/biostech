<?php
$editando = ! empty($registro);
$ativo    = $editando ? bd_bool($registro['ativo']) : TRUE;

function _forn_valor($registro, $campo, $padrao = '')
{
    if ( ! empty($registro) && array_key_exists($campo, $registro))
    {
        return (string) $registro[$campo];
    }

    return (string) $padrao;
}
?>

<div class="painel">
    <div class="painel-cabecalho">
        <h3><?= $editando ? 'Editar fornecedor' : 'Novo fornecedor' ?></h3>
        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('estoque/fornecedores') ?>">&larr; Fornecedores</a>
            <?php if ($editando): ?>
                <span class="badge info">codigo <?= (int) $registro['codigo'] ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="painel-corpo">
        <?= form_open('estoque/fornecedor_salvar') ?>
            <input type="hidden" name="codigo" value="<?= $editando ? (int) $registro['codigo'] : '' ?>">

            <div class="form-grid">
                <div class="campo full">
                    <label for="razao_social">Razao social *</label>
                    <input type="text" name="razao_social" id="razao_social" maxlength="100"
                           value="<?= html_escape(_forn_valor($registro, 'razao_social')) ?>" autofocus>
                </div>

                <div class="campo">
                    <label for="nome_fantasia">Nome fantasia</label>
                    <input type="text" name="nome_fantasia" id="nome_fantasia" maxlength="100"
                           value="<?= html_escape(_forn_valor($registro, 'nome_fantasia')) ?>">
                </div>

                <div class="campo">
                    <label for="cnpj">CNPJ</label>
                    <input type="text" name="cnpj" id="cnpj" maxlength="20"
                           value="<?= html_escape(_forn_valor($registro, 'cnpj')) ?>">
                </div>

                <div class="campo">
                    <label for="inscricao_estadual">Inscricao estadual</label>
                    <input type="text" name="inscricao_estadual" id="inscricao_estadual" maxlength="20"
                           value="<?= html_escape(_forn_valor($registro, 'inscricao_estadual')) ?>">
                </div>

                <div class="campo">
                    <label for="contato">Contato</label>
                    <input type="text" name="contato" id="contato" maxlength="25"
                           value="<?= html_escape(_forn_valor($registro, 'contato')) ?>">
                </div>

                <div class="campo">
                    <label for="fone">Fone</label>
                    <input type="text" name="fone" id="fone" maxlength="15"
                           value="<?= html_escape(_forn_valor($registro, 'fone')) ?>">
                </div>

                <div class="campo">
                    <label for="fax">Fax</label>
                    <input type="text" name="fax" id="fax" maxlength="15"
                           value="<?= html_escape(_forn_valor($registro, 'fax')) ?>">
                </div>

                <div class="campo">
                    <label for="email">E-mail</label>
                    <input type="text" name="email" id="email" maxlength="50"
                           value="<?= html_escape(_forn_valor($registro, 'email')) ?>">
                </div>

                <div class="campo">
                    <label for="cep">CEP</label>
                    <input type="text" name="cep" id="cep" maxlength="10"
                           value="<?= html_escape(_forn_valor($registro, 'cep')) ?>">
                </div>

                <div class="campo full">
                    <label for="endereco">Endereco</label>
                    <input type="text" name="endereco" id="endereco" maxlength="60"
                           value="<?= html_escape(_forn_valor($registro, 'endereco')) ?>">
                </div>

                <div class="campo">
                    <label for="numero">Numero</label>
                    <input type="text" name="numero" id="numero" maxlength="10"
                           value="<?= html_escape(_forn_valor($registro, 'numero')) ?>">
                </div>

                <div class="campo">
                    <label for="complemento">Complemento</label>
                    <input type="text" name="complemento" id="complemento" maxlength="50"
                           value="<?= html_escape(_forn_valor($registro, 'complemento')) ?>">
                </div>

                <div class="campo">
                    <label for="bairro">Bairro</label>
                    <input type="text" name="bairro" id="bairro" maxlength="50"
                           value="<?= html_escape(_forn_valor($registro, 'bairro')) ?>">
                </div>

                <div class="campo">
                    <label for="cidade">Cidade</label>
                    <input type="text" name="cidade" id="cidade" maxlength="20"
                           value="<?= html_escape(_forn_valor($registro, 'cidade')) ?>">
                </div>

                <div class="campo">
                    <label for="uf">UF</label>
                    <input type="text" name="uf" id="uf" maxlength="2"
                           value="<?= html_escape(_forn_valor($registro, 'uf')) ?>">
                </div>

                <div class="campo">
                    <div class="check">
                        <input type="checkbox" name="ativo" id="ativo" value="1" <?= $ativo ? 'checked' : '' ?>>
                        <label for="ativo">Fornecedor ativo</label>
                    </div>
                </div>

                <div class="campo full">
                    <label for="ponto_referencia">Ponto de referencia / observacoes</label>
                    <textarea name="ponto_referencia" id="ponto_referencia"><?= html_escape(_forn_valor($registro, 'ponto_referencia')) ?></textarea>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao">Salvar fornecedor</button>
                <a class="botao secundario" href="<?= site_url('estoque/fornecedores') ?>">Cancelar</a>
            </div>
        <?= form_close() ?>
    </div>
</div>

<?php if ($editando): ?>
    <div class="painel">
        <div class="painel-cabecalho">
            <h3>Produtos deste fornecedor</h3>
            <span class="badge info"><?= numero_br(count($produtos)) ?> vinculo(s)</span>
        </div>

        <div class="tabela-wrap">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th class="centro" style="width:70px">Unid.</th>
                        <th class="num" style="width:80px">Saldo</th>
                        <th style="width:130px">Codigo no fornecedor</th>
                        <th style="width:160px">Nome no fornecedor</th>
                        <th class="num" style="width:110px">Preco</th>
                        <th class="centro" style="width:80px">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produtos)): ?>
                        <tr><td colspan="7" class="vazio">Nenhum produto vinculado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($produtos as $p): ?>
                            <tr>
                                <td><a href="<?= site_url('estoque/ver/' . $p['produto_id']) ?>"><?= html_escape($p['descricao']) ?></a></td>
                                <td class="centro"><?= html_escape($p['unidade'] ? $p['unidade'] : '-') ?></td>
                                <td class="num"><?= numero_br($p['saldo']) ?></td>
                                <td><?= html_escape($p['codigo_fornecedor'] ? $p['codigo_fornecedor'] : '-') ?></td>
                                <td><?= html_escape($p['nome_fornecedor'] ? $p['nome_fornecedor'] : '-') ?></td>
                                <td class="num"><?= $p['preco_venda'] !== NULL ? moeda_br($p['preco_venda']) : '-' ?></td>
                                <td class="centro">
                                    <?= form_open('estoque/fornecedor_desvincular', array('class' => 'inline', 'onsubmit' => "return confirm('Remover este vinculo?');")) ?>
                                        <input type="hidden" name="fornecedor_codigo" value="<?= (int) $registro['codigo'] ?>">
                                        <input type="hidden" name="produto_id" value="<?= (int) $p['produto_id'] ?>">
                                        <button type="submit" class="botao perigo pequeno">Remover</button>
                                    <?= form_close() ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="painel-corpo">
            <h4 class="secao-titulo">Vincular produto</h4>

            <?= form_open('estoque/fornecedor_vincular') ?>
                <input type="hidden" name="fornecedor_codigo" value="<?= (int) $registro['codigo'] ?>">

                <div class="form-grid">
                    <div class="campo full">
                        <label for="produto_busca">Produto *</label>
                        <div class="autocomplete" data-url="<?= site_url('estoque/buscar_produtos') ?>">
                            <input type="text" id="produto_busca" class="autocomplete-busca" placeholder="Codigo ou descricao do produto..." autocomplete="off">
                            <input type="hidden" name="produto_id" id="produto_id">
                            <div class="autocomplete-lista"></div>
                        </div>
                    </div>

                    <div class="campo">
                        <label for="codigo_fornecedor">Codigo no fornecedor</label>
                        <input type="text" name="codigo_fornecedor" id="codigo_fornecedor" maxlength="25">
                    </div>

                    <div class="campo">
                        <label for="nome_fornecedor">Nome no fornecedor</label>
                        <input type="text" name="nome_fornecedor" id="nome_fornecedor" maxlength="80">
                    </div>

                    <div class="campo">
                        <label for="preco_venda">Preco de compra</label>
                        <input type="text" name="preco_venda" id="preco_venda" placeholder="0,00" inputmode="decimal">
                    </div>
                </div>

                <div class="form-acoes">
                    <button type="submit" class="botao">Vincular produto</button>
                </div>
            <?= form_close() ?>
        </div>
    </div>

    <script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
<?php endif; ?>
