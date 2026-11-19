<?php
$saldo  = (int) $produto['saldo'];
$minimo = (int) $produto['estoque_minimo'];
$abaixo = bd_bool($produto['ativo']) && $saldo <= $minimo;
$valor  = $saldo * (float) $produto['valor_compra'];

if ( ! function_exists('_estoque_tipo_badge'))
{
    function _estoque_tipo_badge($tipo)
    {
        if ($tipo === 'entrada') { return 'ok'; }
        if ($tipo === 'saida')   { return 'alerta'; }
        return 'info';
    }
}
?>

<div class="painel">
    <div class="painel-cabecalho">
        <h3><?= html_escape($produto['descricao']) ?></h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('estoque') ?>">&larr; Saldos</a>
            <a class="botao secundario pequeno" href="<?= site_url('estoque/movimentos?produto_id=' . $produto['id']) ?>">Movimentos</a>
            <a class="botao secundario pequeno" href="<?= site_url('estoque/ajuste?produto_id=' . $produto['id']) ?>">Ajuste</a>
            <a class="botao pequeno" href="<?= site_url('estoque/entrada') ?>">+ Entrada</a>
            <a class="botao secundario pequeno" href="<?= site_url('estoque/saida') ?>">- Saida</a>
        </div>
    </div>

    <div class="painel-corpo">
        <div class="ficha-grade">
            <div>
                <div class="rotulo">Saldo atual</div>
                <div class="saldo-grande"><?= numero_br($saldo) ?></div>
                <div class="ajuda">
                    <?php if ($saldo <= 0): ?>
                        <span class="badge erro">sem saldo</span>
                    <?php elseif ($abaixo): ?>
                        <span class="badge alerta">abaixo do minimo (<?= numero_br($minimo) ?>)</span>
                    <?php else: ?>
                        <span class="badge ok">normal</span>
                    <?php endif; ?>
                    <?php if ( ! bd_bool($produto['ativo'])): ?>
                        <span class="badge off">produto inativo</span>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="rotulo">Consumido (historico)</div>
                <div class="dado" style="font-size:20px;font-weight:600"><?= numero_br($produto['consumida']) ?></div>
                <div class="ajuda">saidas e baixas acumuladas</div>
            </div>

            <div>
                <div class="rotulo">Valor em estoque</div>
                <div class="dado" style="font-size:20px;font-weight:600"><?= moeda_br($valor) ?></div>
                <div class="ajuda">custo x saldo</div>
            </div>

            <div>
                <div class="rotulo">Codigo</div>
                <div class="dado"><?= html_escape($produto['codigo'] ? $produto['codigo'] : '-') ?></div>
            </div>

            <div>
                <div class="rotulo">Unidade</div>
                <div class="dado"><?= html_escape($produto['unidade'] ? $produto['unidade'] : '-') ?></div>
            </div>

            <div>
                <div class="rotulo">Custo / Venda</div>
                <div class="dado"><?= moeda_br($produto['valor_compra']) ?> / <?= moeda_br($produto['valor_venda']) ?></div>
            </div>

            <div>
                <div class="rotulo">Grupo / Subgrupo</div>
                <div class="dado">
                    <?= html_escape($produto['grupo_nome'] ? $produto['grupo_nome'] : '-') ?>
                    <?= $produto['subgrupo_nome'] ? ' / ' . html_escape($produto['subgrupo_nome']) : '' ?>
                </div>
            </div>

            <div>
                <div class="rotulo">Localizacao</div>
                <div class="dado">
                    <?= html_escape($produto['estante_nome'] ? $produto['estante_nome'] : '-') ?>
                    <?= $produto['prateleira_nome'] ? ' / ' . html_escape($produto['prateleira_nome']) : '' ?>
                </div>
            </div>

            <div>
                <div class="rotulo">Estoque minimo</div>
                <div class="dado"><?= numero_br($minimo) ?></div>
            </div>

            <div>
                <div class="rotulo">Marca</div>
                <div class="dado"><?= html_escape($produto['marca'] ? $produto['marca'] : '-') ?></div>
            </div>

            <?php if ( ! empty($produto['aplicacao'])): ?>
                <div style="grid-column:1/-1">
                    <div class="rotulo">Aplicacao</div>
                    <div class="dado texto-longo"><?= html_escape($produto['aplicacao']) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px" class="grid-estoque">

    <div class="painel">
        <div class="painel-cabecalho">
            <h3>Movimentos do produto</h3>
            <span class="badge info">ultimos 100</span>
        </div>

        <div class="tabela-wrap">
            <table class="tabela">
                <thead>
                    <tr>
                        <th style="width:95px">Data</th>
                        <th style="width:75px">Tipo</th>
                        <th class="num" style="width:65px">Qtd</th>
                        <th class="num" style="width:120px">Saldo</th>
                        <th style="width:90px">Origem</th>
                        <th>Detalhe</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movimentos)): ?>
                        <tr><td colspan="6" class="vazio">Nenhum movimento registrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($movimentos as $m): ?>
                            <tr>
                                <td><?= data_hora_br($m['created_at']) ?></td>
                                <td>
                                    <span class="badge <?= _estoque_tipo_badge($m['tipo']) ?>"><?= html_escape($m['tipo']) ?></span>
                                    <?php if (bd_bool($m['estornado'])): ?>
                                        <span class="badge off">estornado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="num"><?= numero_br($m['quantidade']) ?></td>
                                <td class="num"><?= numero_br($m['saldo_anterior']) ?> &rarr; <strong><?= numero_br($m['saldo_posterior']) ?></strong></td>
                                <td><?= html_escape($m['origem'] ? $m['origem'] : '-') ?></td>
                                <td>
                                    <?php if ($m['fornecedor_nome']): ?>
                                        <div>Fornecedor: <?= html_escape($m['fornecedor_nome']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($m['documento']): ?>
                                        <div class="ajuda">Documento <?= html_escape($m['documento']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($m['chamado_numero']): ?>
                                        <div class="ajuda">Chamado <?= html_escape($m['chamado_numero']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($m['motivo']): ?>
                                        <div class="ajuda"><?= html_escape($m['motivo']) ?></div>
                                    <?php endif; ?>
                                    <div class="ajuda">por <?= html_escape($m['usuario']) ?><?= $m['lote'] ? ' · lote ' . (int) $m['lote'] : '' ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="painel" style="margin-bottom:16px">
            <div class="painel-cabecalho">
                <h3>Fornecedores</h3>
            </div>

            <?php if (empty($fornecedores)): ?>
                <div class="vazio-estado">Nenhum fornecedor vinculado.</div>
            <?php else: ?>
                <div class="painel-corpo">
                    <?php foreach ($fornecedores as $f): ?>
                        <div style="padding:8px 0;border-bottom:1px solid #eef1f5">
                            <a href="<?= site_url('estoque/fornecedor/' . $f['fornecedor_codigo']) ?>"><strong><?= html_escape($f['razao_social']) ?></strong></a>
                            <?php if ($f['codigo_fornecedor']): ?>
                                <div class="ajuda">codigo no fornecedor: <?= html_escape($f['codigo_fornecedor']) ?></div>
                            <?php endif; ?>
                            <div class="ajuda">
                                <?= $f['fone'] ? html_escape($f['fone']) : '' ?>
                                <?= $f['contato'] ? ' · ' . html_escape($f['contato']) : '' ?>
                                <?= $f['preco_venda'] !== NULL ? ' · ' . number_format((float) $f['preco_venda'], 2, ',', '.') : '' ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="painel">
            <div class="painel-cabecalho">
                <h3>Ultimas contagens</h3>
            </div>

            <?php if (empty($inventarios)): ?>
                <div class="vazio-estado">Nenhuma contagem registrada.</div>
            <?php else: ?>
                <div class="tabela-wrap">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th class="num">Sistema</th>
                                <th class="num">Contado</th>
                                <th class="num">Dif.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventarios as $i): ?>
                                <tr>
                                    <td><?= data_br($i['data']) ?></td>
                                    <td class="num"><?= numero_br($i['saldo_sistema']) ?></td>
                                    <td class="num"><?= numero_br($i['quantidade_contada']) ?></td>
                                    <td class="num">
                                        <?php $dif = (int) $i['diferenca']; ?>
                                        <?php if ($dif === 0): ?>
                                            <span class="badge ok">0</span>
                                        <?php else: ?>
                                            <span class="badge <?= $dif > 0 ? 'info' : 'alerta' ?>"><?= $dif > 0 ? '+' : '' ?><?= numero_br($dif) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
