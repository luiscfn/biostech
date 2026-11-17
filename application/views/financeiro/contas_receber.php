<?php
$pode_editar = in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE);

$tem_filtro = $filtros['busca'] !== '' || $filtros['situacao'] !== ''
           || $filtros['centro_custo'] > 0 || $filtros['venc_de'] !== ''
           || $filtros['venc_ate'] !== '' || $filtros['arquivada'] !== '';

function cr_situacao_badge($situacao)
{
    if ($situacao === 'baixada')
    {
        return '<span class="badge ok">Baixada</span>';
    }

    if ($situacao === 'cancelada')
    {
        return '<span class="badge off">Cancelada</span>';
    }

    return '<span class="badge alerta">Aberta</span>';
}
?>
<div class="cartoes">
    <div class="cartao verde">
        <div class="rotulo">Em aberto</div>
        <div class="valor"><?= moeda_br($resumo['cr_valor']) ?></div>
        <div class="detalhe"><?= numero_br($resumo['cr_abertas']) ?> titulo(s)</div>
    </div>

    <div class="cartao vermelho">
        <div class="rotulo">Vencido</div>
        <div class="valor"><?= moeda_br($resumo['cr_vencido']) ?></div>
        <div class="detalhe">Titulos em aberto com vencimento passado</div>
    </div>

    <div class="cartao">
        <div class="rotulo">Resultado do filtro</div>
        <div class="valor"><?= numero_br($total['total']) ?></div>
        <div class="detalhe"><?= moeda_br($total['valor']) ?> em titulos</div>
    </div>

    <div class="cartao amarelo">
        <div class="rotulo">Saldo em caixa</div>
        <div class="valor"><?= moeda_br($resumo['caixa']) ?></div>
        <div class="detalhe">Ultimo fechamento</div>
    </div>
</div>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Contas a receber</h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('financeiro') ?>">Painel</a>
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/relatorios?tipo=cr') ?>">Relatorio</a>
            <?php if ($pode_editar): ?>
                <a class="botao pequeno" href="<?= site_url('financeiro/conta_receber_novo') ?>">+ Novo titulo</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="painel-corpo">
        <form method="get" action="<?= site_url('financeiro/contas_receber') ?>" class="filtros">
            <input type="text" name="busca" value="<?= html_escape($filtros['busca']) ?>" placeholder="Codigo, documento, historico ou cliente...">

            <select name="situacao">
                <option value="">Situacao</option>
                <option value="aberta"    <?= $filtros['situacao'] === 'aberta' ? 'selected' : '' ?>>Aberta</option>
                <option value="baixada"   <?= $filtros['situacao'] === 'baixada' ? 'selected' : '' ?>>Baixada</option>
                <option value="cancelada" <?= $filtros['situacao'] === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
            </select>

            <select name="centro_custo">
                <option value="">Centro de custo</option>
                <?php foreach ($centros as $c): ?>
                    <option value="<?= (int) $c['codigo'] ?>" <?= (int) $c['codigo'] === (int) $filtros['centro_custo'] ? 'selected' : '' ?>>
                        <?= html_escape($c['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="arquivada">
                <option value="">Ativos e arquivados</option>
                <option value="N" <?= $filtros['arquivada'] === 'N' ? 'selected' : '' ?>>Somente ativos</option>
                <option value="S" <?= $filtros['arquivada'] === 'S' ? 'selected' : '' ?>>Somente arquivados</option>
            </select>

            <input type="date" name="venc_de" value="<?= html_escape($filtros['venc_de']) ?>" title="Vencimento de">
            <input type="date" name="venc_ate" value="<?= html_escape($filtros['venc_ate']) ?>" title="Vencimento ate">

            <button type="submit" class="botao secundario">Pesquisar</button>

            <?php if ($tem_filtro): ?>
                <a class="botao secundario" href="<?= site_url('financeiro/contas_receber') ?>">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:95px">Titulo</th>
                    <th style="width:90px">Emissao</th>
                    <th>Cliente</th>
                    <th style="width:110px">Documento</th>
                    <th style="width:75px">OS</th>
                    <th style="width:100px">Vencimento</th>
                    <th class="num" style="width:105px">Valor</th>
                    <th class="num" style="width:105px">Liquido</th>
                    <th class="centro" style="width:95px">Situacao</th>
                    <th class="centro" style="width:140px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="10" class="vazio">Nenhum titulo encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td>
                                <a href="<?= site_url('financeiro/conta_receber_ver/' . $r['codigo']) ?>">
                                    <strong><?= (int) $r['codigo'] ?>/<?= (int) $r['parcela'] ?></strong>
                                </a>
                                <?php if (bd_bool($r['arquivada'])): ?>
                                    <div class="ajuda">arquivado</div>
                                <?php endif; ?>
                            </td>
                            <td><?= data_br($r['data_emissao']) ?></td>
                            <td>
                                <?= html_escape($r['cliente_nome'] ? $r['cliente_nome'] : 'Sem cliente') ?>
                                <?php if ( ! empty($r['historico'])): ?>
                                    <div class="ajuda"><?= html_escape(mb_substr($r['historico'], 0, 60)) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= html_escape($r['numero_documento'] ? $r['numero_documento'] : '-') ?></td>
                            <td><?= html_escape($r['chamado_numero'] ? $r['chamado_numero'] : '-') ?></td>
                            <td>
                                <?= data_br($r['data_vencimento']) ?>
                                <?php if ($r['situacao'] === 'aberta' && ! empty($r['data_vencimento']) && $r['data_vencimento'] < date('Y-m-d')): ?>
                                    <div class="ajuda" style="color:var(--vermelho)">vencido</div>
                                <?php endif; ?>
                            </td>
                            <td class="num"><?= moeda_br($r['valor']) ?></td>
                            <td class="num"><strong><?= moeda_br($r['vliq']) ?></strong></td>
                            <td class="centro"><?= cr_situacao_badge($r['situacao']) ?></td>
                            <td class="centro">
                                <a class="botao secundario pequeno" href="<?= site_url('financeiro/conta_receber_ver/' . $r['codigo']) ?>">Abrir</a>
                                <?php if ($r['situacao'] === 'aberta'): ?>
                                    <a class="botao pequeno" href="<?= site_url('financeiro/conta_receber_baixar/' . $r['codigo']) ?>">Receber</a>
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
        'base'  => site_url('financeiro/contas_receber'),
        'busca' => $filtros['busca'],
        'extra' => array(
            'situacao'     => $filtros['situacao'],
            'centro_custo' => $filtros['centro_custo'] > 0 ? $filtros['centro_custo'] : '',
            'venc_de'      => $filtros['venc_de'],
            'venc_ate'     => $filtros['venc_ate'],
            'arquivada'    => $filtros['arquivada'],
        ),
    )); ?>
</div>
