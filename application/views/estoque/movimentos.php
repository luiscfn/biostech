<?php
$tem_filtro = $filtros['busca'] !== '' || $filtros['tipo'] !== '' || $filtros['origem'] !== ''
           || $filtros['data_ini'] !== '' || $filtros['data_fim'] !== '' || $filtros['chamado'] !== ''
           || $filtros['produto_id'] > 0;
?>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Movimentos de estoque</h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('estoque') ?>">Saldos</a>
            <a class="botao pequeno" href="<?= site_url('estoque/entrada') ?>">+ Entrada</a>
            <a class="botao secundario pequeno" href="<?= site_url('estoque/saida') ?>">- Saida</a>
        </div>
    </div>

    <div class="painel-corpo">
        <form method="get" action="<?= site_url('estoque/movimentos') ?>" class="filtros">
            <input type="text" name="busca" value="<?= html_escape($filtros['busca']) ?>" placeholder="Produto, codigo...">

            <select name="tipo">
                <option value="">Tipo</option>
                <option value="entrada" <?= $filtros['tipo'] === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                <option value="saida"   <?= $filtros['tipo'] === 'saida' ? 'selected' : '' ?>>Saida</option>
                <option value="ajuste"  <?= $filtros['tipo'] === 'ajuste' ? 'selected' : '' ?>>Ajuste</option>
            </select>

            <select name="origem">
                <option value="">Origem</option>
                <option value="manual"     <?= $filtros['origem'] === 'manual' ? 'selected' : '' ?>>Manual</option>
                <option value="chamado"    <?= $filtros['origem'] === 'chamado' ? 'selected' : '' ?>>Chamado / OS</option>
                <option value="inventario" <?= $filtros['origem'] === 'inventario' ? 'selected' : '' ?>>Inventario</option>
                <option value="estorno"    <?= $filtros['origem'] === 'estorno' ? 'selected' : '' ?>>Estorno</option>
            </select>

            <input type="text" name="chamado" value="<?= html_escape($filtros['chamado']) ?>" placeholder="Chamado" style="max-width:110px">

            <input type="date" name="data_ini" value="<?= html_escape($filtros['data_ini']) ?>" title="Data inicial">
            <input type="date" name="data_fim" value="<?= html_escape($filtros['data_fim']) ?>" title="Data final">

            <?php if ($filtros['produto_id'] > 0): ?>
                <input type="hidden" name="produto_id" value="<?= (int) $filtros['produto_id'] ?>">
            <?php endif; ?>

            <button type="submit" class="botao secundario">Pesquisar</button>

            <?php if ($tem_filtro): ?>
                <a class="botao secundario" href="<?= site_url('estoque/movimentos') ?>">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:120px">Data</th>
                    <th>Produto</th>
                    <th style="width:80px">Tipo</th>
                    <th class="num" style="width:65px">Qtd</th>
                    <th class="num" style="width:120px">Saldo</th>
                    <th style="width:95px">Origem</th>
                    <th>Detalhe</th>
                    <th class="centro" style="width:230px">Acao</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="8" class="vazio">Nenhum movimento encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $m): ?>
                        <tr>
                            <td><?= data_hora_br($m['created_at']) ?></td>
                            <td>
                                <a href="<?= site_url('estoque/ver/' . $m['produto_id']) ?>"><?= html_escape($m['produto']) ?></a>
                                <?php if ($m['produto_codigo']): ?>
                                    <div class="ajuda">codigo <?= html_escape($m['produto_codigo']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $m['tipo'] === 'entrada' ? 'ok' : ($m['tipo'] === 'saida' ? 'alerta' : 'info') ?>">
                                    <?= html_escape($m['tipo']) ?>
                                </span>
                            </td>
                            <td class="num"><?= numero_br($m['quantidade']) ?></td>
                            <td class="num"><?= numero_br($m['saldo_anterior']) ?> &rarr; <strong><?= numero_br($m['saldo_posterior']) ?></strong></td>
                            <td><?= html_escape($m['origem'] ? $m['origem'] : '-') ?></td>
                            <td>
                                <?php if ($m['fornecedor_nome']): ?>
                                    <div>Fornecedor: <?= html_escape($m['fornecedor_nome']) ?></div>
                                <?php endif; ?>
                                <?php if ($m['setor_nome']): ?>
                                    <div class="ajuda">Setor: <?= html_escape($m['setor_nome']) ?></div>
                                <?php endif; ?>
                                <?php if ($m['chamado_numero']): ?>
                                    <div class="ajuda">Chamado <a href="<?= site_url('chamados/ver/' . $m['chamado_numero']) ?>"><?= html_escape($m['chamado_numero']) ?></a></div>
                                <?php endif; ?>
                                <?php if ($m['documento']): ?>
                                    <div class="ajuda">Documento <?= html_escape($m['documento']) ?></div>
                                <?php endif; ?>
                                <?php if ($m['motivo']): ?>
                                    <div class="ajuda"><?= html_escape($m['motivo']) ?></div>
                                <?php endif; ?>
                                <?php if ($m['observacao']): ?>
                                    <div class="ajuda"><?= html_escape($m['observacao']) ?></div>
                                <?php endif; ?>
                                <div class="ajuda">por <?= html_escape($m['usuario']) ?><?= $m['lote'] ? ' · lote ' . (int) $m['lote'] : '' ?></div>
                            </td>
                            <td class="centro">
                                <?php if (bd_bool($m['estornado'])): ?>
                                    <span class="badge off">estornado</span>
                                <?php elseif ($m['origem'] === 'chamado'): ?>
                                    <span class="ajuda">estorno pelo chamado</span>
                                <?php elseif ($m['origem'] === 'estorno'): ?>
                                    <span class="ajuda">-</span>
                                <?php else: ?>
                                    <?= form_open('estoque/estornar/' . $m['id'], array('class' => 'inline', 'onsubmit' => "return confirm('Estornar este movimento?');")) ?>
                                        <input type="hidden" name="voltar" value="estoque/movimentos">
                                        <input type="text" name="motivo" placeholder="motivo" style="width:95px;padding:6px 8px;border:1px solid var(--borda);border-radius:6px;font-size:12px">
                                        <button type="submit" class="botao perigo pequeno">Estornar</button>
                                    <?= form_close() ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $this->load->view('layouts/paginacao', array(
        'pag'   => $pag,
        'base'  => site_url('estoque/movimentos'),
        'busca' => $filtros['busca'],
        'extra' => array(
            'tipo'       => $filtros['tipo'],
            'origem'     => $filtros['origem'],
            'produto_id' => $filtros['produto_id'] > 0 ? $filtros['produto_id'] : '',
            'chamado'    => $filtros['chamado'],
            'data_ini'   => $filtros['data_ini'],
            'data_fim'   => $filtros['data_fim'],
        ),
    )); ?>
</div>
