<?php
$pode_editar = in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE);

$tem_filtro = $filtros['busca'] !== '' || $filtros['situacao'] !== '' || $filtros['banco'] !== '';

function cheque_situacao_badge($situacao)
{
    $situacao = (int) $situacao;

    if ($situacao === 2)
    {
        return '<span class="badge ok">Compensado</span>';
    }

    if ($situacao === 3)
    {
        return '<span class="badge off">Cancelado</span>';
    }

    return '<span class="badge alerta">Emitido</span>';
}
?>
<div class="cartoes">
    <div class="cartao amarelo">
        <div class="rotulo">Cheques no filtro</div>
        <div class="valor"><?= numero_br($total['total']) ?></div>
        <div class="detalhe"><?= moeda_br($total['valor']) ?> em cheques</div>
    </div>
</div>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Cheques</h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('financeiro') ?>">Painel</a>
            <?php if ($pode_editar): ?>
                <a class="botao pequeno" href="<?= site_url('financeiro/cheque_novo') ?>">+ Novo cheque</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="painel-corpo">
        <form method="get" action="<?= site_url('financeiro/cheques') ?>" class="filtros">
            <input type="text" name="busca" value="<?= html_escape($filtros['busca']) ?>" placeholder="Numero ou favorecido...">

            <select name="situacao">
                <option value="">Situacao</option>
                <option value="1" <?= $filtros['situacao'] === '1' ? 'selected' : '' ?>>Emitido</option>
                <option value="2" <?= $filtros['situacao'] === '2' ? 'selected' : '' ?>>Compensado</option>
                <option value="3" <?= $filtros['situacao'] === '3' ? 'selected' : '' ?>>Cancelado</option>
            </select>

            <select name="banco">
                <option value="">Banco</option>
                <?php foreach ($bancos as $b): ?>
                    <option value="<?= html_escape($b['codigo']) ?>" <?= $filtros['banco'] === (string) $b['codigo'] ? 'selected' : '' ?>>
                        <?= html_escape($b['codigo'] . ' - ' . $b['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="botao secundario">Pesquisar</button>

            <?php if ($tem_filtro): ?>
                <a class="botao secundario" href="<?= site_url('financeiro/cheques') ?>">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:110px">Numero</th>
                    <th style="width:90px">Emissao</th>
                    <th style="width:100px">Vencimento</th>
                    <th>Favorecido</th>
                    <th style="width:160px">Banco / Conta</th>
                    <th class="num" style="width:110px">Valor</th>
                    <th class="centro" style="width:105px">Situacao</th>
                    <th class="centro" style="width:220px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="8" class="vazio">Nenhum cheque encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><strong><?= html_escape($r['numero'] ? $r['numero'] : '-') ?></strong></td>
                            <td><?= data_br($r['data_emissao']) ?></td>
                            <td><?= data_br($r['data_vencimento']) ?></td>
                            <td>
                                <?= html_escape($r['favorecido'] ? $r['favorecido'] : '-') ?>
                                <?php if ( ! empty($r['observacao'])): ?>
                                    <div class="ajuda"><?= html_escape(mb_substr($r['observacao'], 0, 70)) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= html_escape($r['banco_codigo'] ? $r['banco_codigo'] : '-') ?>
                                <?= $r['agencia'] ? ' | ' . html_escape($r['agencia']) : '' ?>
                                <?= $r['conta'] ? ' | ' . html_escape($r['conta']) : '' ?>
                            </td>
                            <td class="num"><strong><?= moeda_br($r['valor']) ?></strong></td>
                            <td class="centro"><?= cheque_situacao_badge($r['situacao']) ?></td>
                            <td class="centro">
                                <?php if ($pode_editar): ?>
                                    <a class="botao secundario pequeno" href="<?= site_url('financeiro/cheque_editar/' . $r['id']) ?>">Editar</a>

                                    <?php if ((int) $r['situacao'] === 1): ?>
                                        <?= form_open('financeiro/cheque_compensar/' . $r['id'], array('style' => 'display:inline')) ?>
                                            <input type="hidden" name="data_compensacao" value="<?= date('Y-m-d') ?>">
                                            <button type="submit" class="botao pequeno">Compensar</button>
                                        <?= form_close() ?>
                                    <?php endif; ?>

                                    <?= form_open('financeiro/cheque_excluir/' . $r['id'], array('style' => 'display:inline')) ?>
                                        <button type="submit" class="botao perigo pequeno">Excluir</button>
                                    <?= form_close() ?>
                                <?php else: ?>
                                    -
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
        'base'  => site_url('financeiro/cheques'),
        'busca' => $filtros['busca'],
        'extra' => array(
            'situacao' => $filtros['situacao'],
            'banco'    => $filtros['banco'],
        ),
    )); ?>
</div>
