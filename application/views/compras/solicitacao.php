<?php
$registro = is_array($registro) ? $registro : array();

$edicao     = ! empty($registro['numero']);
$situacao   = $edicao ? $registro['situacao'] : 'aberta';
$aberta     = $situacao === 'aberta';
$pode_gerir = in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE);
$editavel   = $pode_gerir && $aberta;
$tem_pedido = $edicao && ! empty($registro['pedido_numero']);
$tem_cotacao = $edicao && ! empty($registro['cotacao_codigo']);

$data = $edicao ? $registro['data'] : date('Y-m-d');
$hora = $edicao && $registro['hora'] ? substr((string) $registro['hora'], 0, 5) : date('H:i');

$rotulos = array(
    'aberta'   => array('Aberta', 'alerta'),
    'fechada'  => array('Fechada', 'ok'),
    'cancelada'=> array('Cancelada', 'off'),
);

$situacao_rotulo = isset($rotulos[$situacao]) ? $rotulos[$situacao] : array($situacao, 'off');
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>
            <?= $edicao ? 'Solicitacao interna ' . (int) $registro['numero'] : 'Nova solicitacao interna' ?>
            <?php if ($edicao): ?>
                <span class="badge <?= $situacao_rotulo[1] ?>"><?= html_escape($situacao_rotulo[0]) ?></span>
            <?php endif; ?>
        </h3>

        <div class="acoes-ficha">
            <?php if ($edicao): ?>
                <a class="botao secundario pequeno" href="<?= site_url('compras/imprimir_solicitacao/' . $registro['numero']) ?>" target="_blank">Imprimir</a>
            <?php endif; ?>

            <?php if ($edicao && $aberta && $pode_gerir): ?>
                <?= form_open('compras/solicitacao_fechar/' . $registro['numero'], array('style' => 'display:inline')) ?>
                    <button type="submit" class="botao secundario pequeno" title="Somente fecha a solicitacao">Fechar</button>
                <?= form_close() ?>

                <?php if ( ! $tem_pedido): ?>
                    <?= form_open('compras/solicitacao_gerar_pedido/' . $registro['numero'], array('style' => 'display:inline')) ?>
                        <label class="check" style="display:inline-flex;gap:4px;font-size:12px;margin-right:4px">
                            <input type="checkbox" name="somente_selecionados" value="S" checked>
                            somente marcados
                        </label>
                        <button type="submit" class="botao pequeno">Gerar pedido</button>
                    <?= form_close() ?>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($edicao && ! $tem_cotacao && $pode_gerir): ?>
                <?= form_open('compras/importar_solicitacao/' . $registro['numero'], array('style' => 'display:inline')) ?>
                    <button type="submit" class="botao secundario pequeno">Importar p/ cotacao</button>
                <?= form_close() ?>
            <?php endif; ?>

            <?php if ($tem_cotacao): ?>
                <a class="botao secundario pequeno" href="<?= site_url('compras/cotacao/' . $registro['cotacao_codigo']) ?>">
                    Cotacao <?= (int) $registro['cotacao_codigo'] ?>
                </a>
            <?php endif; ?>

            <a class="botao secundario pequeno" href="<?= site_url('compras/solicitacoes') ?>">Voltar</a>
        </div>
    </div>

    <?php if ($tem_pedido): ?>
        <div class="painel-corpo" style="padding-bottom:0">
            <div class="alert alert-success">
                Solicitacao atendida pelo pedido de compra
                <a href="<?= site_url('compras/ver/' . $registro['pedido_numero']) ?>"><?= (int) $registro['pedido_numero'] ?></a>.
            </div>
        </div>
    <?php endif; ?>

    <?php if ($edicao): ?>
        <div class="painel-corpo" style="padding-bottom:0">
            <div class="detalhes">
                <div class="detalhe">
                    <div class="rotulo">Data</div>
                    <div class="valor">
                        <?= data_br($registro['data']) ?>
                        <?= $registro['hora'] ? ' as ' . html_escape(substr((string) $registro['hora'], 0, 5)) : '' ?>
                    </div>
                </div>
                <div class="detalhe">
                    <div class="rotulo">Setor</div>
                    <div class="valor"><?= html_escape($registro['setor_nome'] ? $registro['setor_nome'] : '-') ?></div>
                </div>
                <div class="detalhe">
                    <div class="rotulo">Solicitante</div>
                    <div class="valor"><?= html_escape($registro['funcionario_nome'] ? $registro['funcionario_nome'] : '-') ?></div>
                </div>
                <div class="detalhe">
                    <div class="rotulo">Local de entrega</div>
                    <div class="valor"><?= html_escape($registro['local_entrega'] ? $registro['local_entrega'] : '-') ?></div>
                </div>
                <div class="detalhe">
                    <div class="rotulo">Responsavel pelo recebimento</div>
                    <div class="valor">
                        <?= html_escape($registro['responsavel_recebimento'] ? $registro['responsavel_recebimento'] : '-') ?>
                        <?php if ( ! empty($registro['fone_responsavel'])): ?>
                            <div class="ajuda"><?= html_escape($registro['fone_responsavel']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="detalhe">
                    <div class="rotulo">Cadastro</div>
                    <div class="valor"><?= html_escape($registro['usuario_cadastro'] ? $registro['usuario_cadastro'] : '-') ?></div>
                    <div class="ajuda"><?= data_hora_br($registro['data_cadastro']) ?></div>
                </div>
                <div class="detalhe">
                    <div class="rotulo">Fechamento</div>
                    <div class="valor"><?= html_escape($registro['usuario_fechamento'] ? $registro['usuario_fechamento'] : '-') ?></div>
                    <div class="ajuda"><?= data_hora_br($registro['data_fechamento']) ?></div>
                </div>
                <div class="detalhe">
                    <div class="rotulo">Observacoes</div>
                    <div class="valor"><?= nl2br(html_escape($registro['observacao'] ? $registro['observacao'] : '-')) ?></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($edicao && ! $editavel): ?>
        <div class="painel-corpo" style="padding-bottom:0">
            <hr class="divisor">
            <h4 class="secao-titulo">Itens da solicitacao</h4>
        </div>
        <div class="tabela-wrap">
            <table class="tabela">
                <thead>
                    <tr>
                        <th style="width:70px">Codigo</th>
                        <th>Produto</th>
                        <th class="centro" style="width:60px">Unid.</th>
                        <th class="num" style="width:70px">Saldo</th>
                        <th class="num" style="width:80px">Qtd</th>
                        <th style="width:120px">Utilizacao</th>
                        <th style="width:110px">Periodo</th>
                        <th class="centro" style="width:70px">Repor</th>
                        <th class="centro" style="width:80px">Marcado</th>
                        <th class="centro" style="width:80px">Exportado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($itens)): ?>
                        <tr><td colspan="10" class="vazio">Nenhum item na solicitacao.</td></tr>
                    <?php else: ?>
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td><?= html_escape($item['produto_codigo'] ? $item['produto_codigo'] : (string) $item['produto_id']) ?></td>
                                <td>
                                    <a href="<?= site_url('produtos/ver/' . $item['produto_id']) ?>"><strong><?= html_escape($item['descricao']) ?></strong></a>
                                </td>
                                <td class="centro"><?= html_escape($item['unidade']) ?></td>
                                <td class="num"><?= numero_br($item['saldo'], 3) ?></td>
                                <td class="num"><?= numero_br($item['quantidade'], 3) ?></td>
                                <td><?= data_br($item['data_utilizacao']) ?></td>
                                <td><?= html_escape($item['periodo'] ? $item['periodo'] : '-') ?></td>
                                <td class="centro"><?= bd_bool($item['repor_estoque']) ? 'Sim' : 'Nao' ?></td>
                                <td class="centro"><?= bd_bool($item['selecionado']) ? 'Sim' : 'Nao' ?></td>
                                <td class="centro"><?= bd_bool($item['exportado']) ? 'Sim' : 'Nao' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="painel-corpo">
            <?= form_open($edicao ? 'compras/solicitacao/' . $registro['numero'] : 'compras/solicitacao') ?>

                <div class="form-grid">
                    <div class="campo terco">
                        <label for="data">Data *</label>
                        <input type="date" name="data" id="data" value="<?= html_escape($data) ?>" required>
                    </div>

                    <div class="campo terco">
                        <label for="hora">Hora</label>
                        <input type="time" name="hora" id="hora" value="<?= html_escape($hora) ?>">
                    </div>

                    <div class="campo terco">
                        <label for="setor_id">Setor solicitante</label>
                        <select name="setor_id" id="setor_id">
                            <option value="">-</option>
                            <?php foreach ($setores as $s): ?>
                                <option value="<?= (int) $s['id'] ?>"
                                    <?= $edicao && (int) $registro['setor_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                                    <?= html_escape($s['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="campo terco">
                        <label for="funcionario_id">Solicitante</label>
                        <select name="funcionario_id" id="funcionario_id">
                            <option value="">-</option>
                            <?php foreach ($funcionarios as $f): ?>
                                <option value="<?= (int) $f['id'] ?>"
                                    <?= $edicao && (int) $registro['funcionario_id'] === (int) $f['id'] ? 'selected' : '' ?>>
                                    <?= html_escape($f['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="campo terco">
                        <label for="responsavel_recebimento">Responsavel pelo recebimento</label>
                        <input type="text" name="responsavel_recebimento" id="responsavel_recebimento" maxlength="80"
                               value="<?= $edicao ? html_escape($registro['responsavel_recebimento']) : '' ?>">
                    </div>

                    <div class="campo terco">
                        <label for="fone_responsavel">Fone do responsavel</label>
                        <input type="text" name="fone_responsavel" id="fone_responsavel" maxlength="20"
                               value="<?= $edicao ? html_escape($registro['fone_responsavel']) : '' ?>">
                    </div>

                    <div class="campo full">
                        <label for="local_entrega">Local de entrega</label>
                        <input type="text" name="local_entrega" id="local_entrega" maxlength="200"
                               value="<?= $edicao ? html_escape($registro['local_entrega']) : '' ?>">
                    </div>

                    <div class="campo full">
                        <label for="observacao">Observacoes</label>
                        <textarea name="observacao" id="observacao"><?= $edicao ? html_escape($registro['observacao']) : '' ?></textarea>
                    </div>
                </div>

                <hr class="divisor">

                <h4 class="secao-titulo">Itens solicitados</h4>

                <div class="tabela-wrap">
                    <table class="tabela tabela-itens" data-solicitacao
                           data-template="linha-solicitacao" data-grupo="solicitacao">
                        <thead>
                            <tr>
                                <th class="num" style="width:36px">#</th>
                                <th style="min-width:230px">Produto</th>
                                <th class="centro" style="width:58px">Unid.</th>
                                <th class="num" style="width:70px">Saldo</th>
                                <th class="num" style="width:80px">Qtd</th>
                                <th style="width:135px">Utilizacao</th>
                                <th style="width:110px">Periodo</th>
                                <th class="centro" style="width:70px" title="Repor no estoque">Repor</th>
                                <th class="centro" style="width:80px" title="Usar ao gerar o pedido">Marcado</th>
                                <th class="centro" style="width:44px"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="form-acoes" style="margin-top:10px">
                    <button type="button" class="botao secundario" data-adicionar-item="solicitacao">+ Adicionar item</button>
                </div>

                <div class="form-acoes">
                    <button type="submit" class="botao"><?= $edicao ? 'Salvar alteracoes' : 'Criar solicitacao' ?></button>
                    <a class="botao secundario" href="<?= site_url($edicao ? 'compras/solicitacao/' . $registro['numero'] : 'compras/solicitacoes') ?>">Cancelar</a>
                </div>

            <?= form_close() ?>
        </div>
    <?php endif; ?>

    <?php if ( ! $edicao && ! empty($abaixo_minimo) && $editavel): ?>
        <div class="painel-corpo">
            <hr class="divisor">
            <h4 class="secao-titulo">Produtos abaixo do estoque minimo</h4>
            <div class="ajuda" style="margin-bottom:8px">
                Clique em adicionar para incluir o produto na solicitacao com a quantidade sugerida.
            </div>

            <div class="tabela-wrap">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th style="width:80px">Codigo</th>
                            <th>Produto</th>
                            <th class="centro" style="width:60px">Unid.</th>
                            <th class="num" style="width:80px">Saldo</th>
                            <th class="num" style="width:90px">Minimo</th>
                            <th class="num" style="width:100px">Sugerido</th>
                            <th class="num" style="width:110px">Custo</th>
                            <th class="centro" style="width:100px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($abaixo_minimo as $p): ?>
                            <?php
                            $sugerido = (float) $p['estoque_minimo'] - (float) $p['saldo'];
                            if ($sugerido <= 0)
                            {
                                $sugerido = 1;
                            }
                            ?>
                            <tr>
                                <td><?= html_escape($p['codigo'] ? $p['codigo'] : (string) $p['id']) ?></td>
                                <td><?= html_escape($p['descricao']) ?></td>
                                <td class="centro"><?= html_escape($p['unidade']) ?></td>
                                <td class="num"><?= numero_br($p['saldo'], 3) ?></td>
                                <td class="num"><?= numero_br($p['estoque_minimo'], 3) ?></td>
                                <td class="num"><?= numero_br($sugerido, 3) ?></td>
                                <td class="num"><?= moeda_br($p['valor_compra']) ?></td>
                                <td class="centro">
                                    <button type="button" class="botao secundario pequeno"
                                            data-add-produto
                                            data-produto-id="<?= (int) $p['id'] ?>"
                                            data-descricao="<?= html_escape($p['descricao']) ?>"
                                            data-unidade="<?= html_escape($p['unidade']) ?>"
                                            data-saldo="<?= (float) $p['saldo'] ?>"
                                            data-quantidade="<?= (float) $sugerido ?>">
                                        + Adicionar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ( ! $edicao || $editavel): ?>
<template id="linha-solicitacao">
    <table><tbody>
        <tr class="linha-item">
            <td class="num numero-item">1</td>
            <td>
                <div class="autocomplete"
                     data-url="<?= site_url('compras/buscar_produtos') ?>"
                     data-estoque=".saldo"
                     data-unidade=".unidade">
                    <input type="text" class="autocomplete-busca" placeholder="Codigo ou descricao..." autocomplete="off">
                    <input type="hidden" name="produto_id[]">
                    <div class="autocomplete-lista"></div>
                </div>
            </td>
            <td><input type="text" class="unidade" readonly tabindex="-1"></td>
            <td><input type="text" class="saldo" readonly tabindex="-1" style="text-align:right"></td>
            <td><input type="number" name="quantidade[]" class="qtd" value="1" min="0.001" step="0.001" style="text-align:right"></td>
            <td><input type="date" name="data_utilizacao[]"></td>
            <td><input type="text" name="periodo[]" maxlength="20" placeholder="ex.: mensal"></td>
            <td class="centro"><input type="checkbox" name="repor_estoque[]" value="S"></td>
            <td class="centro"><input type="checkbox" name="selecionado[]" value="S" checked></td>
            <td class="centro"><button type="button" class="botao perigo pequeno remover-item" title="Remover item">&times;</button></td>
        </tr>
    </tbody></table>
</template>

<script>
window.BIOSTECH_SOLICITACAO_ITENS = <?= json_encode(array_map(function ($i) {
    return array(
        'produto_id'      => (int) $i['produto_id'],
        'descricao'       => $i['descricao'],
        'unidade'         => $i['unidade'],
        'saldo'           => (float) $i['saldo'],
        'quantidade'      => (float) $i['quantidade'],
        'repor_estoque'   => bd_bool($i['repor_estoque']),
        'data_utilizacao' => $i['data_utilizacao'],
        'periodo'         => $i['periodo'],
        'selecionado'     => bd_bool($i['selecionado']),
    );
}, $itens), JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
<script src="<?= base_url('assets/js/compras.js') ?>"></script>
<?php endif; ?>
