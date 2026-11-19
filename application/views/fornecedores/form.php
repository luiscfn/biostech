<?php
$editando = ! empty($registro);

$valor = function ($campo, $padrao = '') use ($registro, $editando) {
    $atual = $editando && array_key_exists($campo, $registro) ? $registro[$campo] : $padrao;
    return set_value($campo, (string) $atual);
};

$ativo = $editando ? bd_bool($registro['ativo']) : TRUE;

$prazo_form = array(
    'codigo'          => isset($prazo_edicao['codigo']) ? (int) $prazo_edicao['codigo'] : 0,
    'forma_pagamento' => isset($prazo_edicao['forma_pagamento']) ? $prazo_edicao['forma_pagamento'] : '',
    'prazo'           => isset($prazo_edicao['prazo']) ? $prazo_edicao['prazo'] : '',
);
?>

<div class="painel">
    <div class="painel-cabecalho">
        <h3><?= $editando ? 'Editar fornecedor' : 'Novo fornecedor' ?></h3>

        <div>
            <?php if ($editando): ?>
                <span class="badge info">codigo <?= (int) $registro['codigo'] ?></span>

                <?php if ( ! empty($cliente)): ?>
                    <a class="badge info" style="text-decoration:none"
                       href="<?= site_url('clientes/ver/' . (int) $cliente['id']) ?>"
                       title="Abrir o cliente gerado a partir deste fornecedor">Tambem cliente</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="painel-corpo">
        <?php if ( ! empty($erro_codigo)): ?>
            <div class="alert alert-danger"><?= html_escape($erro_codigo) ?></div>
        <?php endif; ?>

        <?= form_open($editando ? 'fornecedores/editar/' . (int) $registro['codigo'] : 'fornecedores/novo', array('id' => 'form-fornecedor')) ?>

            <div class="form-grid">
                <div class="campo codigo">
                    <label for="codigo">Codigo</label>
                    <input type="number" name="codigo" id="codigo" min="1" step="1"
                           value="<?= html_escape($editando ? (int) $registro['codigo'] : set_value('codigo', $proximo_codigo)) ?>"
                           <?= $editando ? 'readonly' : '' ?>>
                </div>

                <div class="campo nome">
                    <label for="razao_social">Nome / Razao social *</label>
                    <input type="text" name="razao_social" id="razao_social" maxlength="100" autofocus
                           value="<?= $valor('razao_social') ?>">
                    <?= form_error('razao_social') ?>
                </div>

                <div class="campo ativo">
                    <div class="check">
                        <input type="checkbox" name="ativo" id="ativo" value="1" <?= $ativo ? 'checked' : '' ?>>
                        <label for="ativo" style="margin:0;text-transform:none;letter-spacing:0;font-size:13px">Ativo</label>
                    </div>
                </div>

                <div class="campo">
                    <label for="nome_fantasia">Nome fantasia</label>
                    <input type="text" name="nome_fantasia" id="nome_fantasia" maxlength="100"
                           value="<?= $valor('nome_fantasia') ?>">
                </div>

                <div class="campo">
                    <label for="inscricao_estadual">Inscricao estadual</label>
                    <input type="text" name="inscricao_estadual" id="inscricao_estadual" maxlength="20"
                           value="<?= $valor('inscricao_estadual') ?>">
                </div>

                <div class="campo">
                    <label for="cnpj">CNPJ</label>
                    <input type="text" name="cnpj" id="cnpj" maxlength="20"
                           value="<?= $valor('cnpj') ?>">
                    <?= form_error('cnpj') ?>
                </div>

                <div class="campo">
                    <label for="contato">Contato</label>
                    <input type="text" name="contato" id="contato" maxlength="25"
                           value="<?= $valor('contato') ?>">
                </div>

                <div class="campo nome">
                    <label for="endereco">Endereco</label>
                    <input type="text" name="endereco" id="endereco" maxlength="60"
                           value="<?= $valor('endereco') ?>">
                </div>

                <div class="campo codigo">
                    <label for="numero">Numero</label>
                    <input type="text" name="numero" id="numero" maxlength="10"
                           value="<?= $valor('numero') ?>">
                </div>

                <div class="campo codigo">
                    <label for="complemento">Complemento</label>
                    <input type="text" name="complemento" id="complemento" maxlength="50"
                           value="<?= $valor('complemento') ?>">
                </div>

                <div class="campo terco">
                    <label for="bairro">Bairro</label>
                    <input type="text" name="bairro" id="bairro" maxlength="50"
                           value="<?= $valor('bairro') ?>">
                </div>

                <div class="campo terco">
                    <label for="cidade">Cidade</label>
                    <input type="text" name="cidade" id="cidade" maxlength="20"
                           value="<?= $valor('cidade') ?>">
                </div>

                <div class="campo codigo">
                    <label for="uf">UF</label>
                    <input type="text" name="uf" id="uf" maxlength="2"
                           value="<?= $valor('uf') ?>">
                    <?= form_error('uf') ?>
                </div>

                <div class="campo codigo">
                    <label for="cep">CEP</label>
                    <input type="text" name="cep" id="cep" maxlength="10"
                           value="<?= $valor('cep') ?>">
                </div>

                <div class="campo terco">
                    <label for="fone">Fone</label>
                    <input type="text" name="fone" id="fone" maxlength="15"
                           value="<?= $valor('fone') ?>">
                </div>

                <div class="campo terco">
                    <label for="fax">Fax</label>
                    <input type="text" name="fax" id="fax" maxlength="15"
                           value="<?= $valor('fax') ?>">
                </div>

                <div class="campo terco">
                    <label for="email">E-mail</label>
                    <input type="text" name="email" id="email" maxlength="50"
                           value="<?= $valor('email') ?>">
                    <?= form_error('email') ?>
                </div>

                <div class="campo full">
                    <label for="ponto_referencia">Ponto de referencia / observacoes</label>
                    <textarea name="ponto_referencia" id="ponto_referencia"><?= $valor('ponto_referencia') ?></textarea>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao"><?= $editando ? 'Gravar alteracoes' : 'Incluir' ?></button>
                <a class="botao" href="<?= site_url('fornecedores') ?>">Cancelar</a>
                <a class="botao" href="<?= site_url('fornecedores/novo') ?>">Novo</a>

                <?php if ($editando): ?>
                    <button type="submit" form="form-copiar-cliente" class="botao"
                            title="Criar ou atualizar o cliente com os dados deste fornecedor"
                            onclick="return confirm('Copiar este fornecedor para a base de clientes?');">Copiar p/ cliente</button>

                    <button type="submit" form="form-excluir-fornecedor" class="botao perigo"
                            onclick="return confirm('Excluir este fornecedor?');">Excluir</button>
                <?php endif; ?>

                <a class="botao" href="<?= site_url('fornecedores') ?>">Retornar aos Fornecedores</a>
            </div>

        <?= form_close() ?>

        <?php if ($editando): ?>
            <?php
            // Formularios auxiliares ficam fora do form principal; os botoes os
            // acionam pelo atributo HTML5 "form", mantendo as acoes na mesma linha.
            ?>
            <div style="display:none">
                <?= form_open('fornecedores/copiar_cliente/' . (int) $registro['codigo'], array('id' => 'form-copiar-cliente')) ?>
                <?= form_close() ?>

                <?= form_open('fornecedores/excluir/' . (int) $registro['codigo'], array('id' => 'form-excluir-fornecedor')) ?>
                <?= form_close() ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($editando): ?>
    <div class="painel">
        <div class="painel-cabecalho">
            <h3>Formas de pagamento e prazos oferecidos</h3>
            <span class="badge info"><?= numero_br(count($prazos)) ?> prazo(s)</span>
        </div>

        <div class="painel-corpo">
            <h4 class="secao-titulo"><?= $prazo_form['codigo'] > 0 ? 'Editar prazo' : 'Novo prazo' ?></h4>

            <?= form_open('fornecedores/prazo_salvar') ?>
                <input type="hidden" name="fornecedor_codigo" value="<?= (int) $registro['codigo'] ?>">
                <input type="hidden" name="prazo_codigo" value="<?= (int) $prazo_form['codigo'] ?>">

                <div class="form-grid">
                    <div class="campo terco">
                        <label for="forma_pagamento">Forma de pagamento</label>
                        <input type="text" name="forma_pagamento" id="forma_pagamento" maxlength="40"
                               value="<?= html_escape($prazo_form['forma_pagamento']) ?>">
                    </div>

                    <div class="campo terco">
                        <label for="prazo">Prazo</label>
                        <input type="text" name="prazo" id="prazo" maxlength="60"
                               value="<?= html_escape($prazo_form['prazo']) ?>">
                    </div>

                    <div class="campo terco">
                        <label>&nbsp;</label>
                        <button type="submit" class="botao"><?= $prazo_form['codigo'] > 0 ? 'Gravar prazo' : 'Incluir prazo' ?></button>

                        <?php if ($prazo_form['codigo'] > 0): ?>
                            <a class="botao secundario" href="<?= site_url('fornecedores/editar/' . (int) $registro['codigo']) ?>">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?= form_close() ?>
        </div>

        <div class="tabela-wrap">
            <table class="tabela">
                <thead>
                    <tr>
                        <th style="width:80px">Codigo</th>
                        <th>Forma de pagamento</th>
                        <th>Prazo</th>
                        <th class="centro" style="width:170px">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($prazos)): ?>
                        <tr><td colspan="4" class="vazio">Nenhum prazo cadastrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($prazos as $p): ?>
                            <tr>
                                <td class="num"><?= (int) $p['codigo'] ?></td>
                                <td><?= html_escape($p['forma_pagamento'] ? $p['forma_pagamento'] : '-') ?></td>
                                <td><?= html_escape($p['prazo'] ? $p['prazo'] : '-') ?></td>
                                <td class="centro">
                                    <a class="botao secundario pequeno"
                                       href="<?= site_url('fornecedores/editar/' . (int) $registro['codigo'] . '?prazo=' . (int) $p['codigo']) ?>">Editar</a>

                                    <?= form_open('fornecedores/prazo_excluir', array('style' => 'display:inline')) ?>
                                        <input type="hidden" name="fornecedor_codigo" value="<?= (int) $registro['codigo'] ?>">
                                        <input type="hidden" name="prazo_codigo" value="<?= (int) $p['codigo'] ?>">
                                        <button type="submit" class="botao perigo pequeno"
                                                onclick="return confirm('Remover este prazo?');">Excluir</button>
                                    <?= form_close() ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="painel">
        <div class="painel-cabecalho">
            <h3>Relacao de produtos fornecidos</h3>
            <span class="badge info"><?= numero_br(count($produtos)) ?> vinculo(s)</span>
        </div>

        <div class="painel-corpo">
            <h4 class="secao-titulo">Vincular produto</h4>

            <?= form_open('fornecedores/produto_vincular') ?>
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

                    <div class="campo terco">
                        <label for="codigo_fornecedor">Codigo no fornecedor</label>
                        <input type="text" name="codigo_fornecedor" id="codigo_fornecedor" maxlength="25">
                    </div>

                    <div class="campo terco">
                        <label for="nome_fornecedor">Nome no fornecedor</label>
                        <input type="text" name="nome_fornecedor" id="nome_fornecedor" maxlength="80">
                    </div>

                    <div class="campo terco">
                        <label for="preco_venda">Preco de compra</label>
                        <input type="text" name="preco_venda" id="preco_venda" placeholder="0,00" inputmode="decimal">
                    </div>
                </div>

                <div class="form-acoes">
                    <button type="submit" class="botao">Vincular produto</button>
                </div>
            <?= form_close() ?>
        </div>

        <div class="tabela-wrap">
            <table class="tabela">
                <thead>
                    <tr>
                        <th style="width:80px">Codigo</th>
                        <th>Nome do produto</th>
                        <th class="centro" style="width:80px">Unidade</th>
                        <th style="width:150px">Codigo no fornecedor</th>
                        <th style="width:170px">Nome no fornecedor</th>
                        <th class="num" style="width:120px">Valor venda</th>
                        <th class="centro" style="width:90px">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produtos)): ?>
                        <tr><td colspan="7" class="vazio">Nenhum produto vinculado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($produtos as $p): ?>
                            <tr>
                                <td class="num"><?= (int) $p['produto_id'] ?></td>
                                <td><a href="<?= site_url('estoque/ver/' . (int) $p['produto_id']) ?>"><?= html_escape($p['descricao']) ?></a></td>
                                <td class="centro"><?= html_escape($p['unidade'] ? $p['unidade'] : '-') ?></td>
                                <td><?= html_escape($p['codigo_fornecedor'] ? $p['codigo_fornecedor'] : '-') ?></td>
                                <td><?= html_escape($p['nome_fornecedor'] ? $p['nome_fornecedor'] : '-') ?></td>
                                <td class="num"><?= $p['preco_venda'] !== NULL ? moeda_br($p['preco_venda']) : '-' ?></td>
                                <td class="centro">
                                    <?= form_open('fornecedores/produto_desvincular', array('style' => 'display:inline')) ?>
                                        <input type="hidden" name="fornecedor_codigo" value="<?= (int) $registro['codigo'] ?>">
                                        <input type="hidden" name="produto_id" value="<?= (int) $p['produto_id'] ?>">
                                        <button type="submit" class="botao perigo pequeno"
                                                onclick="return confirm('Remover este produto do fornecedor?');">Excluir</button>
                                    <?= form_close() ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
<?php endif; ?>
