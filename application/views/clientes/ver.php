<div class="painel">
    <div class="painel-cabecalho">
        <h3><?= html_escape($registro['nome']) ?></h3>
        <div>
            <?php if (bd_bool($registro['ativo'])): ?>
                <span class="badge ok">Ativo</span>
            <?php else: ?>
                <span class="badge off">Inativo</span>
            <?php endif; ?>
            <span class="badge info">codigo <?= (int) $registro['id'] ?></span>

            <?php if ( ! empty($especial_qtd)): ?>
                <span class="badge info">Cliente especial</span>
            <?php endif; ?>

            <?php if ( ! empty($registro['fornecedor_codigo'])): ?>
                <a class="badge info" style="text-decoration:none"
                   href="<?= site_url('fornecedores/editar/' . (int) $registro['fornecedor_codigo']) ?>">Tambem fornecedor</a>
            <?php endif; ?>
        </div>
    </div>

    <?php $this->load->view('clientes/_abas', array('registro' => $registro, 'aba' => 'dados')); ?>

    <div class="painel-corpo">
        <div class="detalhes">
            <div class="detalhe">
                <div class="rotulo">Nome / Razao social</div>
                <div class="valor"><?= html_escape($registro['nome']) ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Nome fantasia</div>
                <div class="valor"><?= html_escape($registro['fantasia'] ? $registro['fantasia'] : '-') ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">CPF / CNPJ</div>
                <div class="valor"><?= html_escape($registro['cpf_cnpj'] ? $registro['cpf_cnpj'] : '-') ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Inscricao estadual</div>
                <div class="valor"><?= html_escape($registro['ie'] ? $registro['ie'] : '-') ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">RG</div>
                <div class="valor"><?= html_escape($registro['rg'] ? $registro['rg'] : '-') ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">E-mail</div>
                <div class="valor"><?= html_escape($registro['email'] ? $registro['email'] : '-') ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Telefone</div>
                <div class="valor"><?= html_escape($registro['telefone'] ? $registro['telefone'] : '-') ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Celular</div>
                <div class="valor"><?= html_escape($registro['celular'] ? $registro['celular'] : '-') ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Contato</div>
                <div class="valor"><?= html_escape($registro['contato'] ? $registro['contato'] : '-') ?></div>
            </div>
        </div>

        <hr style="border:none;border-top:1px solid #e3e8ef;margin:22px 0">

        <div class="detalhes">
            <div class="detalhe">
                <div class="rotulo">Endereco</div>
                <div class="valor">
                    <?= html_escape($registro['endereco'] ? $registro['endereco'] : '-') ?>
                    <?= $registro['numero'] ? ', ' . html_escape($registro['numero']) : '' ?>
                    <?= $registro['complemento'] ? ' - ' . html_escape($registro['complemento']) : '' ?>
                </div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Bairro</div>
                <div class="valor"><?= html_escape($registro['bairro'] ? $registro['bairro'] : '-') ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Cidade / UF</div>
                <div class="valor">
                    <?= html_escape($registro['cidade'] ? $registro['cidade'] : '-') ?>
                    <?= $registro['uf'] ? '/' . html_escape($registro['uf']) : '' ?>
                </div>
            </div>
            <div class="detalhe">
                <div class="rotulo">CEP</div>
                <div class="valor"><?= html_escape($registro['cep'] ? $registro['cep'] : '-') ?></div>
            </div>
        </div>

        <?php if ( ! empty($registro['observacao'])): ?>
            <hr style="border:none;border-top:1px solid #e3e8ef;margin:22px 0">
            <div class="detalhe">
                <div class="rotulo">Observacoes</div>
                <div class="valor" style="white-space:pre-line"><?= html_escape($registro['observacao']) ?></div>
            </div>
        <?php endif; ?>

        <div class="form-acoes" style="flex-wrap:wrap;align-items:center">
            <a class="botao" href="<?= site_url('clientes/editar/' . $registro['id']) ?>">Editar cliente</a>

            <?php if ( ! empty($especial_qtd)): ?>
                <a class="botao secundario" href="<?= site_url('clientes/especiais/' . $registro['id']) ?>">
                    Precos especiais (<?= (int) $especial_qtd ?>)
                </a>

                <?= form_open('clientes/especial_alternar/' . $registro['id'], array(
                    'style'    => 'display:inline',
                    'onsubmit' => "return confirm('Excluir este cliente dos precos especiais? Os " . (int) $especial_qtd . " preco(s) cadastrado(s) serao removidos.');",
                )) ?>
                    <input type="hidden" name="acao" value="excluir">
                    <button type="submit" class="botao perigo">Excluir como cliente especial</button>
                <?= form_close() ?>
            <?php else: ?>
                <?= form_open('clientes/especial_alternar/' . $registro['id'], array(
                    'style'    => 'display:inline',
                    'onsubmit' => "return confirm('Inserir este cliente como cliente especial? Serao criados precos para todos os produtos ativos, com 15% sobre o custo.');",
                )) ?>
                    <input type="hidden" name="acao" value="incluir">
                    <button type="submit" class="botao">Inserir como cliente especial</button>
                <?= form_close() ?>
            <?php endif; ?>

            <a class="botao secundario" href="<?= site_url('clientes') ?>">Voltar</a>
        </div>
    </div>
</div>
