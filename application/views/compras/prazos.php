<?php
$fornecedor = is_array($fornecedor) ? $fornecedor : array();
$selecionado = ! empty($fornecedor['codigo']);
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>Prazos de fornecedor</h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('compras') ?>">Pedidos</a>
            <a class="botao secundario pequeno" href="<?= site_url('compras/cotacoes') ?>">Cotacoes</a>
            <a class="botao secundario pequeno" href="<?= site_url('compras/solicitacoes') ?>">Solicitacoes</a>
        </div>
    </div>

    <div class="painel-corpo">
        <div class="form-grid">
            <div class="campo full">
                <label for="fornecedor_busca">Fornecedor</label>
                <div class="autocomplete" data-url="<?= site_url('compras/buscar_fornecedores') ?>" data-nav-fornecedor>
                    <input type="text" id="fornecedor_busca" class="autocomplete-busca"
                           placeholder="Digite a razao social, nome fantasia ou CNPJ..."
                           value="<?= $selecionado ? html_escape($fornecedor['razao_social']) : '' ?>" autocomplete="off">
                    <input type="hidden" id="fornecedor_codigo" value="<?= $selecionado ? (int) $fornecedor['codigo'] : '' ?>">
                    <div class="autocomplete-lista"></div>
                </div>
                <div class="ajuda">Selecione o fornecedor para consultar e manter os prazos de pagamento.</div>
            </div>
        </div>
    </div>

    <?php if ( ! $selecionado): ?>
        <div class="painel-corpo">
            <div class="vazio-estado">Nenhum fornecedor selecionado.</div>
        </div>
    <?php else: ?>
        <div class="painel-corpo" style="padding-bottom:0">
            <div class="detalhes">
                <div class="detalhe">
                    <div class="rotulo">Fornecedor</div>
                    <div class="valor"><strong><?= html_escape($fornecedor['razao_social']) ?></strong></div>
                    <?php if ( ! empty($fornecedor['nome_fantasia'])): ?>
                        <div class="ajuda"><?= html_escape($fornecedor['nome_fantasia']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="detalhe">
                    <div class="rotulo">CNPJ / CPF</div>
                    <div class="valor"><?= html_escape($fornecedor['cnpj'] ? $fornecedor['cnpj'] : '-') ?></div>
                </div>
                <div class="detalhe">
                    <div class="rotulo">Telefone</div>
                    <div class="valor"><?= html_escape($fornecedor['fone'] ? $fornecedor['fone'] : '-') ?></div>
                </div>
                <div class="detalhe">
                    <div class="rotulo">Contato</div>
                    <div class="valor"><?= html_escape($fornecedor['contato'] ? $fornecedor['contato'] : '-') ?></div>
                </div>
            </div>
        </div>

        <div class="painel-corpo">
            <hr class="divisor">
            <h4 class="secao-titulo">Prazos cadastrados</h4>

            <div class="tabela-wrap">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th style="width:80px">Codigo</th>
                            <th>Forma de pagamento</th>
                            <th style="width:220px">Prazo</th>
                            <th class="centro" style="width:170px">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($registros)): ?>
                            <tr><td colspan="4" class="vazio">Nenhum prazo cadastrado para este fornecedor.</td></tr>
                        <?php else: ?>
                            <?php foreach ($registros as $p): ?>
                                <tr>
                                    <td><?= (int) $p['codigo'] ?></td>
                                    <td><?= html_escape($p['forma_pagamento'] ? $p['forma_pagamento'] : '-') ?></td>
                                    <td><?= html_escape($p['prazo'] ? $p['prazo'] : '-') ?></td>
                                    <td class="centro">
                                        <button type="button" class="botao secundario pequeno"
                                                data-editar-prazo
                                                data-codigo="<?= (int) $p['codigo'] ?>"
                                                data-forma="<?= html_escape($p['forma_pagamento']) ?>"
                                                data-prazo="<?= html_escape($p['prazo']) ?>">
                                            Editar
                                        </button>

                                        <?= form_open('compras/prazo_excluir/' . (int) $fornecedor['codigo'] . '/' . (int) $p['codigo'], array('style' => 'display:inline')) ?>
                                            <button type="submit" class="botao perigo pequeno">Excluir</button>
                                        <?= form_close() ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE)): ?>
                <hr class="divisor">
                <h4 class="secao-titulo" id="titulo-form-prazo">Incluir prazo</h4>

                <?= form_open('compras/prazo_salvar', array('id' => 'form-prazo')) ?>
                    <input type="hidden" name="fornecedor_codigo" value="<?= (int) $fornecedor['codigo'] ?>">

                    <div class="form-grid">
                        <div class="campo terco">
                            <label for="prazo_codigo">Codigo</label>
                            <input type="number" name="codigo" id="prazo_codigo" min="1" step="1"
                                   placeholder="Automatico">
                            <div class="ajuda">Deixe em branco para gerar o proximo codigo.</div>
                        </div>

                        <div class="campo terco">
                            <label for="forma_pagamento">Forma de pagamento</label>
                            <input type="text" name="forma_pagamento" id="forma_pagamento" maxlength="40"
                                   placeholder="ex.: 30/60 dias">
                        </div>

                        <div class="campo terco">
                            <label for="prazo">Prazo</label>
                            <input type="text" name="prazo" id="prazo" maxlength="60"
                                   placeholder="ex.: 30 dias">
                        </div>
                    </div>

                    <div class="form-acoes" style="justify-content:flex-start">
                        <button type="submit" class="botao">Salvar prazo</button>
                        <button type="reset" class="botao secundario" id="limpar-prazo">Limpar</button>
                    </div>
                <?= form_close() ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var caixa = document.querySelector('[data-nav-fornecedor]');

    if (caixa) {
        caixa.addEventListener('autocomplete:selecionar', function (ev) {
            if (ev.detail && ev.detail.id) {
                window.location.href = <?= json_encode(site_url('compras/prazos')) ?> + '/' + ev.detail.id;
            }
        });
    }

    var form = document.getElementById('form-prazo');
    var titulo = document.getElementById('titulo-form-prazo');
    var botaoLimpar = document.getElementById('limpar-prazo');

    document.querySelectorAll('[data-editar-prazo]').forEach(function (botao) {
        botao.addEventListener('click', function () {
            if (!form) {
                return;
            }

            form.querySelector('[name="codigo"]').value = botao.getAttribute('data-codigo');
            form.querySelector('[name="forma_pagamento"]').value = botao.getAttribute('data-forma') || '';
            form.querySelector('[name="prazo"]').value = botao.getAttribute('data-prazo') || '';

            if (titulo) {
                titulo.textContent = 'Alterar prazo ' + botao.getAttribute('data-codigo');
            }

            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    if (botaoLimpar && titulo) {
        botaoLimpar.addEventListener('click', function () {
            titulo.textContent = 'Incluir prazo';

            var codigo = form ? form.querySelector('[name="codigo"]') : null;

            if (codigo) {
                codigo.value = '';
            }
        });
    }
});
</script>
