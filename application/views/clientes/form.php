<?php
$editando = ! empty($registro);

function _cli_valor($registro, $campo)
{
    if ( ! empty($registro))
    {
        return (string) $registro[$campo];
    }

    return (string) set_value($campo);
}

$ativo = $editando ? bd_bool($registro['ativo']) : TRUE;
$tipo  = $editando ? (string) $registro['tipo'] : set_value('tipo');
$cancelar = $editando ? site_url('clientes/ver/' . $registro['id']) : site_url('clientes');
?>

<div class="painel">
    <div class="painel-cabecalho">
        <h3><?= $editando ? 'Editar cliente' : 'Novo cliente' ?></h3>
        <?php if ($editando): ?>
            <span class="badge info">codigo <?= (int) $registro['id'] ?></span>
        <?php endif; ?>
    </div>

    <div class="painel-corpo">
        <?= form_open($editando ? 'clientes/editar/' . $registro['id'] : 'clientes/novo') ?>

            <div class="form-grid">
                <div class="campo full">
                    <label for="nome">Nome / Razao social *</label>
                    <input type="text" name="nome" id="nome" value="<?= html_escape(_cli_valor($registro, 'nome')) ?>" maxlength="120" autofocus>
                    <?= form_error('nome') ?>
                </div>

                <div class="campo">
                    <label for="fantasia">Nome fantasia</label>
                    <input type="text" name="fantasia" id="fantasia" value="<?= html_escape(_cli_valor($registro, 'fantasia')) ?>" maxlength="120">
                </div>

                <div class="campo">
                    <label for="tipo">Tipo</label>
                    <select name="tipo" id="tipo">
                        <option value="">(nao informado)</option>
                        <option value="1" <?= $tipo === '1' ? 'selected' : '' ?>>Juridica</option>
                        <option value="2" <?= $tipo === '2' ? 'selected' : '' ?>>Fisica</option>
                    </select>
                </div>

                <div class="campo">
                    <label for="cpf_cnpj">CPF / CNPJ</label>
                    <input type="text" name="cpf_cnpj" id="cpf_cnpj" value="<?= html_escape(_cli_valor($registro, 'cpf_cnpj')) ?>" maxlength="20">
                </div>

                <div class="campo">
                    <label for="ie">Inscricao estadual</label>
                    <input type="text" name="ie" id="ie" value="<?= html_escape(_cli_valor($registro, 'ie')) ?>" maxlength="20">
                </div>

                <div class="campo">
                    <label for="rg">RG</label>
                    <input type="text" name="rg" id="rg" value="<?= html_escape(_cli_valor($registro, 'rg')) ?>" maxlength="20">
                </div>

                <div class="campo">
                    <label for="contato">Contato</label>
                    <input type="text" name="contato" id="contato" value="<?= html_escape(_cli_valor($registro, 'contato')) ?>" maxlength="40">
                </div>

                <div class="campo full">
                    <label for="endereco">Endereco</label>
                    <input type="text" name="endereco" id="endereco" value="<?= html_escape(_cli_valor($registro, 'endereco')) ?>" maxlength="80">
                </div>

                <div class="campo terco">
                    <label for="numero">Numero</label>
                    <input type="text" name="numero" id="numero" value="<?= html_escape(_cli_valor($registro, 'numero')) ?>" maxlength="10">
                </div>

                <div class="campo terco">
                    <label for="complemento">Complemento</label>
                    <input type="text" name="complemento" id="complemento" value="<?= html_escape(_cli_valor($registro, 'complemento')) ?>" maxlength="50">
                </div>

                <div class="campo terco">
                    <label for="bairro">Bairro</label>
                    <input type="text" name="bairro" id="bairro" value="<?= html_escape(_cli_valor($registro, 'bairro')) ?>" maxlength="50">
                </div>

                <div class="campo">
                    <label for="cidade">Cidade</label>
                    <input type="text" name="cidade" id="cidade" value="<?= html_escape(_cli_valor($registro, 'cidade')) ?>" maxlength="60">
                </div>

                <div class="campo terco">
                    <label for="uf">UF</label>
                    <input type="text" name="uf" id="uf" value="<?= html_escape(_cli_valor($registro, 'uf')) ?>" maxlength="2">
                </div>

                <div class="campo terco">
                    <label for="cep">CEP</label>
                    <input type="text" name="cep" id="cep" value="<?= html_escape(_cli_valor($registro, 'cep')) ?>" maxlength="10">
                </div>

                <div class="campo">
                    <label for="telefone">Telefone</label>
                    <input type="text" name="telefone" id="telefone" value="<?= html_escape(_cli_valor($registro, 'telefone')) ?>" maxlength="20">
                </div>

                <div class="campo">
                    <label for="celular">Celular</label>
                    <input type="text" name="celular" id="celular" value="<?= html_escape(_cli_valor($registro, 'celular')) ?>" maxlength="20">
                </div>

                <div class="campo">
                    <label for="fax">Fax</label>
                    <input type="text" name="fax" id="fax" value="<?= html_escape(_cli_valor($registro, 'fax')) ?>" maxlength="20">
                </div>

                <div class="campo full">
                    <label for="email">E-mail</label>
                    <input type="text" name="email" id="email" value="<?= html_escape(_cli_valor($registro, 'email')) ?>" maxlength="120">
                    <?= form_error('email') ?>
                </div>

                <div class="campo full">
                    <label for="observacao">Observacoes</label>
                    <textarea name="observacao" id="observacao"><?= html_escape(_cli_valor($registro, 'observacao')) ?></textarea>
                </div>

                <div class="campo">
                    <div class="check">
                        <input type="checkbox" name="ativo" id="ativo" value="1" <?= $ativo ? 'checked' : '' ?>>
                        <label for="ativo" style="margin:0;text-transform:none;letter-spacing:0;font-size:13px">Cliente ativo</label>
                    </div>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao"><?= $editando ? 'Salvar alteracoes' : 'Cadastrar cliente' ?></button>
                <a class="botao secundario" href="<?= $cancelar ?>">Cancelar</a>
            </div>

        <?= form_close() ?>
    </div>
</div>
