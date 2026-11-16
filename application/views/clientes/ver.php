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
        </div>
    </div>

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

        <div class="form-acoes">
            <a class="botao" href="<?= site_url('clientes/editar/' . $registro['id']) ?>">Editar cliente</a>
            <a class="botao secundario" href="<?= site_url('clientes') ?>">Voltar</a>
        </div>
    </div>
</div>
