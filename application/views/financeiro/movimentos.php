<?php
$pode_editar = in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE);

$tem_filtro = $filtros['busca'] !== '' || $filtros['banco'] !== '' || $filtros['conta'] !== ''
           || $filtros['tipo'] !== '' || $filtros['data_de'] !== ''
           || $filtros['data_ate'] !== '' || $filtros['vinculo'] !== '';

$saldo_total = 0.0;

foreach ($posicao as $p)
{
    $saldo_total += (float) $p['saldo'];
}
?>
<div class="cartoes">
    <div class="cartao">
        <div class="rotulo">Saldo bancario total</div>
        <div class="valor"><?= moeda_br($saldo_total) ?></div>
        <div class="detalhe"><?= numero_br(count($posicao)) ?> conta(s) ativa(s)</div>
    </div>

    <div class="cartao verde">
        <div class="rotulo">Resultado liquido do filtro</div>
        <div class="valor"><?= moeda_br($total['liquido']) ?></div>
        <div class="detalhe">Creditos menos debitos</div>
    </div>

    <div class="cartao">
        <div class="rotulo">Movimentos no filtro</div>
        <div class="valor"><?= numero_br($total['total']) ?></div>
        <div class="detalhe">Historico completo do legado</div>
    </div>
</div>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Movimentos bancarios</h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('financeiro') ?>">Painel</a>
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/relatorios?tipo=posicao') ?>">Posicao bancaria</a>
            <?php if ($pode_editar): ?>
                <a class="botao pequeno" href="<?= site_url('financeiro/movimento_novo') ?>">+ Novo movimento</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="painel-corpo">
        <form method="get" action="<?= site_url('financeiro/movimentos') ?>" class="filtros">
            <input type="text" name="busca" value="<?= html_escape($filtros['busca']) ?>" placeholder="Numero ou historico...">

            <select name="banco">
                <option value="">Banco</option>
                <?php foreach ($bancos as $b): ?>
                    <option value="<?= html_escape($b['codigo']) ?>" <?= $filtros['banco'] === (string) $b['codigo'] ? 'selected' : '' ?>>
                        <?= html_escape($b['codigo'] . ' - ' . $b['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="conta">
                <option value="">Conta</option>
                <?php foreach ($contas as $c): ?>
                    <option value="<?= html_escape($c['conta']) ?>" <?= $filtros['conta'] === (string) $c['conta'] ? 'selected' : '' ?>>
                        <?= html_escape($c['banco_codigo'] . ' | ' . $c['agencia'] . ' | ' . $c['conta']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="tipo">
                <option value="">Tipo</option>
                <option value="C" <?= $filtros['tipo'] === 'C' ? 'selected' : '' ?>>Credito</option>
                <option value="D" <?= $filtros['tipo'] === 'D' ? 'selected' : '' ?>>Debito</option>
            </select>

            <select name="vinculo">
                <option value="">Vinculo</option>
                <option value="cp"  <?= $filtros['vinculo'] === 'cp' ? 'selected' : '' ?>>Conta a pagar</option>
                <option value="cr"  <?= $filtros['vinculo'] === 'cr' ? 'selected' : '' ?>>Conta a receber</option>
                <option value="sem" <?= $filtros['vinculo'] === 'sem' ? 'selected' : '' ?>>Sem vinculo</option>
            </select>

            <input type="date" name="data_de" value="<?= html_escape($filtros['data_de']) ?>" title="De">
            <input type="date" name="data_ate" value="<?= html_escape($filtros['data_ate']) ?>" title="Ate">

            <button type="submit" class="botao secundario">Pesquisar</button>

            <?php if ($tem_filtro): ?>
                <a class="botao secundario" href="<?= site_url('financeiro/movimentos') ?>">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:80px">Numero</th>
                    <th style="width:95px">Data</th>
                    <th style="width:70px">Banco</th>
                    <th style="width:90px">Agencia</th>
                    <th style="width:100px">Conta</th>
                    <th>Historico</th>
                    <th style="width:80px">Vinculo</th>
                    <th class="num" style="width:110px">Valor</th>
                    <th class="num" style="width:110px">Saldo</th>
                    <th class="centro" style="width:90px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="10" class="vazio">Nenhum movimento encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><?= (int) $r['numero'] ?></td>
                            <td><?= data_br($r['data']) ?></td>
                            <td><?= html_escape($r['banco_codigo'] ? $r['banco_codigo'] : '-') ?></td>
                            <td><?= html_escape($r['agencia'] ? $r['agencia'] : '-') ?></td>
                            <td><?= html_escape($r['conta'] ? $r['conta'] : '-') ?></td>
                            <td>
                                <?= html_escape($r['historico'] ? mb_substr($r['historico'], 0, 120) : '-') ?>
                                <div class="ajuda">
                                    <?php if ((string) $r['tipo'] === 'C'): ?>
                                        <span class="badge ok">Credito</span>
                                    <?php else: ?>
                                        <span class="badge erro">Debito</span>
                                    <?php endif; ?>
                                    <?php if ( ! empty($r['conta_pagar_numero'])): ?>
                                        | CP <?= (int) $r['conta_pagar_numero'] ?>/<?= (int) $r['conta_pagar_parcela'] ?>
                                    <?php endif; ?>
                                    <?php if ( ! empty($r['conta_receber_codigo'])): ?>
                                        | CR <?= (int) $r['conta_receber_codigo'] ?>/<?= (int) $r['conta_receber_parcela'] ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ( ! empty($r['conta_pagar_numero'])): ?>
                                    CP
                                <?php elseif ( ! empty($r['conta_receber_codigo'])): ?>
                                    CR
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="num"><strong><?= moeda_br($r['valor']) ?></strong></td>
                            <td class="num"><?= moeda_br($r['saldo_atual']) ?></td>
                            <td class="centro">
                                <?php if ($pode_editar): ?>
                                    <a class="botao secundario pequeno" href="<?= site_url('financeiro/movimento_editar/' . $r['id']) ?>">Editar</a>
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
        'base'  => site_url('financeiro/movimentos'),
        'busca' => $filtros['busca'],
        'extra' => array(
            'banco'    => $filtros['banco'],
            'conta'    => $filtros['conta'],
            'tipo'     => $filtros['tipo'],
            'vinculo'  => $filtros['vinculo'],
            'data_de'  => $filtros['data_de'],
            'data_ate' => $filtros['data_ate'],
        ),
    )); ?>
</div>
