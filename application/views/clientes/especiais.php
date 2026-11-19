<?php
/**
 * Precos especiais do cliente.
 *
 * Variaveis esperadas:
 *   $registro   - cliente
 *   $registros  - linhas cliente x produto
 *   $busca      - filtro por produto
 *   $pag        - paginacao
 *   $quantidade - total de produtos com preco especial
 */
$id      = (int) $registro['id'];
$base    = site_url('clientes/especiais/' . $id);
$especial = $quantidade > 0;
?>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Precos especiais - <?= html_escape($registro['nome']) ?></h3>

        <form method="get" action="<?= $base ?>" class="filtros filtros-largos">
            <input type="text" name="busca" value="<?= html_escape($busca) ?>"
                   placeholder="Produto: codigo ou descricao...">

            <button type="submit" class="botao">Pesquisar</button>

            <?php if ($busca !== ''): ?>
                <a class="botao secundario" href="<?= $base ?>">Limpar</a>
            <?php endif; ?>
        </form>

        <a class="botao secundario" href="<?= site_url('clientes/ver/' . $id) ?>">Voltar ao cliente</a>
    </div>

    <?php $this->load->view('clientes/_abas', array('registro' => $registro, 'aba' => 'especiais')); ?>

    <div class="painel-corpo">
        <div class="detalhes">
            <div class="detalhe">
                <div class="rotulo">Situacao</div>
                <div class="valor">
                    <?php if ($especial): ?>
                        <span class="badge ok">Cliente especial</span>
                    <?php else: ?>
                        <span class="badge off">Sem precos especiais</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Produtos com preco especial</div>
                <div class="valor"><?= (int) $quantidade ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Codigo do cliente</div>
                <div class="valor"><?= $id ?></div>
            </div>
        </div>

        <hr class="divisor">

        <h4 class="secao-titulo">Incluir / atualizar produto</h4>

        <?= form_open('clientes/especial_salvar/' . $id, array('id' => 'form-especial')) ?>

            <div class="form-grid">
                <div class="campo metade">
                    <label for="esp_busca">Produto</label>
                    <div class="autocomplete"
                         data-url="<?= site_url('orcamentos/buscar_produtos') ?>"
                         data-custo="#esp_custo" data-preco="#esp_valor">
                        <input type="text" id="esp_busca" class="autocomplete-busca"
                               placeholder="Digite o codigo ou a descricao..." autocomplete="off">
                        <input type="hidden" name="produto_id" id="esp_produto_id">
                        <div class="autocomplete-lista"></div>
                    </div>
                    <div class="ajuda">Se o produto ja tiver preco especial, o cadastro sera atualizado.</div>
                </div>

                <div class="campo curto">
                    <label for="esp_custo">Custo</label>
                    <input type="text" id="esp_custo" readonly tabindex="-1" value="">
                </div>

                <div class="campo curto">
                    <label for="esp_perc">% lucro</label>
                    <input type="text" name="perc_lucro" id="esp_perc" class="esp-perc" value="15" inputmode="decimal">
                </div>

                <div class="campo curto">
                    <label for="esp_valor">Valor especial</label>
                    <input type="text" name="valor_venda" id="esp_valor" class="esp-valor" inputmode="decimal" placeholder="0,00">
                </div>

                <div class="campo terco" style="align-self:end">
                    <button type="submit" class="botao">Salvar preco especial</button>
                </div>
            </div>

        <?= form_close() ?>

        <hr class="divisor">

        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
            <h4 class="secao-titulo" style="margin:0">Produtos com preco especial</h4>

            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <?= form_open('clientes/especial_alternar/' . $id, array(
                    'style'    => 'display:inline',
                    'onsubmit' => "return confirm('Incluir os produtos ativos que ainda nao tem preco especial, com 15% sobre o custo?');",
                )) ?>
                    <input type="hidden" name="acao" value="incluir">
                    <input type="hidden" name="perc_lucro" value="15">
                    <button type="submit" class="botao">Incluir produtos ativos faltantes</button>
                <?= form_close() ?>

                <?php if ($especial): ?>
                    <?= form_open('clientes/especial_alternar/' . $id, array(
                        'style'    => 'display:inline',
                        'onsubmit' => "return confirm('Excluir este cliente dos precos especiais? Os " . (int) $quantidade . " preco(s) cadastrado(s) serao removidos.');",
                    )) ?>
                        <input type="hidden" name="acao" value="excluir">
                        <button type="submit" class="botao perigo">Excluir como cliente especial</button>
                    <?= form_close() ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="tabela-wrap">
            <table class="tabela">
                <thead>
                    <tr>
                        <th style="width:80px">Codigo</th>
                        <th>Produto</th>
                        <th style="width:60px">Un.</th>
                        <th class="num" style="width:110px">Custo</th>
                        <th class="num" style="width:110px">% lucro</th>
                        <th class="num" style="width:130px">Valor especial</th>
                        <th class="num" style="width:120px">Tabela</th>
                        <th class="centro" style="width:170px">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registros)): ?>
                        <tr>
                            <td colspan="8" class="vazio">
                                <?= $busca !== ''
                                    ? 'Nenhum produto encontrado para a busca.'
                                    : 'Nenhum preco especial cadastrado. Use "Incluir todos os produtos ativos" ou inclua um produto acima.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($registros as $r): ?>
                            <?php $pid = (int) $r['produto_id']; $form_id = 'esp-linha-' . $pid; ?>
                            <tr>
                                <td class="num">
                                    <a href="<?= site_url('produtos/ver/' . $pid) ?>"><?= $pid ?></a>
                                </td>
                                <td>
                                    <?= html_escape($r['descricao']) ?>
                                    <?php if ( ! bd_bool($r['ativo'])): ?>
                                        <span class="badge off">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= html_escape($r['unidade'] ? $r['unidade'] : '-') ?></td>
                                <td class="num"><?= moeda_br($r['valor_compra']) ?></td>
                                <td class="num">
                                    <input type="text" name="perc_lucro" form="<?= $form_id ?>"
                                           class="esp-perc" value="<?= $r['perc_lucro'] !== NULL ? moeda_br($r['perc_lucro']) : '' ?>"
                                           data-custo="<?= (float) $r['valor_compra'] ?>"
                                           inputmode="decimal" style="width:90px;text-align:right">
                                </td>
                                <td class="num">
                                    <input type="text" name="valor_venda" form="<?= $form_id ?>"
                                           class="esp-valor"
                                           value="<?= $r['valor_venda'] !== NULL ? moeda_br($r['valor_venda']) : '' ?>"
                                           inputmode="decimal" style="width:110px;text-align:right">
                                </td>
                                <td class="num"><?= moeda_br($r['valor_tabela']) ?></td>
                                <td class="centro" style="white-space:nowrap">
                                    <?= form_open('clientes/especial_salvar/' . $id, array('id' => $form_id)) ?>
                                        <input type="hidden" name="produto_id" value="<?= $pid ?>">
                                    <?= form_close() ?>

                                    <button type="submit" form="<?= $form_id ?>" class="botao pequeno">Salvar</button>

                                    <?= form_open('clientes/especial_excluir/' . $id . '/' . $pid, array(
                                        'style'    => 'display:inline',
                                        'onsubmit' => "return confirm('Remover o preco especial deste produto?');",
                                    )) ?>
                                        <button type="submit" class="botao pequeno perigo">Remover</button>
                                    <?= form_close() ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php $this->load->view('layouts/paginacao', array(
            'pag'   => $pag,
            'base'  => $base,
            'busca' => $busca,
        )); ?>
    </div>
</div>

<script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
<script>
(function () {
    'use strict';

    function numero(texto) {
        if (texto === null || texto === undefined) {
            return NaN;
        }

        texto = String(texto).replace(/R\$\s?/g, '').trim();

        if (texto.indexOf(',') !== -1) {
            texto = texto.replace(/\./g, '').replace(',', '.');
        }

        return parseFloat(texto);
    }

    function formatar(valor) {
        return valor.toFixed(2).replace('.', ',');
    }

    function calcular(perc, valor, custo) {
        var p = numero(perc.value);

        if (isNaN(p) || isNaN(custo)) {
            return;
        }

        valor.value = formatar(custo * (1 + p / 100));
    }

    // Formulario de inclusao.
    var percNovo  = document.getElementById('esp_perc');
    var valorNovo = document.getElementById('esp_valor');
    var custoNovo = document.getElementById('esp_custo');
    var formNovo  = document.getElementById('form-especial');

    if (percNovo && valorNovo && custoNovo) {
        percNovo.addEventListener('input', function () {
            calcular(percNovo, valorNovo, numero(custoNovo.value));
        });
    }

    if (formNovo) {
        formNovo.addEventListener('autocomplete:selecionar', function (ev) {
            var item = ev.detail || {};

            if (item.valor_compra !== undefined && item.valor_compra !== null) {
                custoNovo.value = formatar(Number(item.valor_compra));
            }

            calcular(percNovo, valorNovo, numero(custoNovo.value));
        });
    }

    // Linhas da tabela: recalcula o valor ao mudar o percentual.
    var linhas = document.querySelectorAll('.tabela tbody tr');

    for (var i = 0; i < linhas.length; i++) {
        (function (linha) {
            var perc  = linha.querySelector('.esp-perc');
            var valor = linha.querySelector('.esp-valor');

            if ( ! perc || ! valor) {
                return;
            }

            var custo = parseFloat(linha.querySelector('.esp-perc').getAttribute('data-custo'));

            perc.addEventListener('input', function () {
                calcular(perc, valor, custo);
            });
        })(linhas[i]);
    }
})();
</script>
