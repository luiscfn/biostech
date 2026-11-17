<?php
$pode_editar = in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE);

$tem_filtro = $filtros['busca'] !== '' || $filtros['fornecedor_nome'] !== ''
           || $filtros['situacao'] !== '' || $filtros['centro_custo'] > 0
           || $filtros['venc_de'] !== '' || $filtros['venc_ate'] !== ''
           || $filtros['origem'] !== '';

function cp_situacao_badge($situacao)
{
    $situacao = (int) $situacao;

    if ($situacao === 2)
    {
        return '<span class="badge ok">Baixada</span>';
    }

    if ($situacao === 3)
    {
        return '<span class="badge off">Cancelada</span>';
    }

    return '<span class="badge alerta">Aberta</span>';
}
?>
<div class="cartoes">
    <div class="cartao amarelo">
        <div class="rotulo">Em aberto</div>
        <div class="valor"><?= moeda_br($resumo['cp_valor']) ?></div>
        <div class="detalhe"><?= numero_br($resumo['cp_abertas']) ?> titulo(s)</div>
    </div>

    <div class="cartao vermelho">
        <div class="rotulo">Vencido</div>
        <div class="valor"><?= moeda_br($resumo['cp_vencido']) ?></div>
        <div class="detalhe">Titulos em aberto com vencimento passado</div>
    </div>

    <div class="cartao verde">
        <div class="rotulo">Pago no mes</div>
        <div class="valor"><?= moeda_br($resumo['cp_pago_mes']) ?></div>
        <div class="detalhe">Baixas do mes corrente</div>
    </div>

    <div class="cartao">
        <div class="rotulo">Resultado do filtro</div>
        <div class="valor"><?= numero_br($total['total']) ?></div>
        <div class="detalhe"><?= moeda_br($total['valor']) ?> em titulos</div>
    </div>
</div>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Contas a pagar</h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('financeiro') ?>">Painel</a>
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/relatorios?tipo=cp') ?>">Relatorio</a>
            <?php if ($pode_editar): ?>
                <a class="botao pequeno" href="<?= site_url('financeiro/conta_pagar_novo') ?>">+ Novo titulo</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="painel-corpo">
        <form method="get" action="<?= site_url('financeiro/contas_pagar') ?>" class="filtros">
            <input type="text" name="busca" value="<?= html_escape($filtros['busca']) ?>" placeholder="Numero, documento ou historico...">
            <input type="text" name="fornecedor_nome" value="<?= html_escape($filtros['fornecedor_nome']) ?>" placeholder="Fornecedor...">

            <select name="situacao">
                <option value="">Situacao</option>
                <option value="aberta"    <?= $filtros['situacao'] === 'aberta' ? 'selected' : '' ?>>Aberta</option>
                <option value="vencido"   <?= $filtros['situacao'] === 'vencido' ? 'selected' : '' ?>>Aberta e vencida</option>
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

            <select name="origem">
                <option value="">Origem</option>
                <option value="legado"  <?= $filtros['origem'] === 'legado' ? 'selected' : '' ?>>Legado (Access)</option>
                <option value="compra"  <?= $filtros['origem'] === 'compra' ? 'selected' : '' ?>>Compra</option>
                <option value="manual"  <?= $filtros['origem'] === 'manual' ? 'selected' : '' ?>>Manual</option>
            </select>

            <input type="date" name="venc_de" value="<?= html_escape($filtros['venc_de']) ?>" title="Vencimento de">
            <input type="date" name="venc_ate" value="<?= html_escape($filtros['venc_ate']) ?>" title="Vencimento ate">

            <button type="submit" class="botao secundario">Pesquisar</button>

            <?php if ($tem_filtro): ?>
                <a class="botao secundario" href="<?= site_url('financeiro/contas_pagar') ?>">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:95px">Titulo</th>
                    <th style="width:90px">Emissao</th>
                    <th>Fornecedor</th>
                    <th style="width:120px">Documento</th>
                    <th style="width:100px">Vencimento</th>
                    <th>Centro de custo</th>
                    <th class="num" style="width:110px">Valor</th>
                    <th class="centro" style="width:95px">Situacao</th>
                    <th class="centro" style="width:140px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="9" class="vazio">Nenhum titulo encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td>
                                <a href="<?= site_url('financeiro/conta_pagar_ver/' . $r['id']) ?>">
                                    <strong><?= (int) $r['numero'] ?>/<?= (int) $r['parcela'] ?></strong>
                                </a>
                            </td>
                            <td><?= data_br($r['emissao']) ?></td>
                            <td>
                                <?= html_escape($r['fornecedor_nome'] ? $r['fornecedor_nome'] : 'Sem fornecedor') ?>
                                <?php if ( ! empty($r['historico'])): ?>
                                    <div class="ajuda"><?= html_escape(mb_substr($r['historico'], 0, 70)) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= html_escape($r['documento'] ? $r['documento'] : '-') ?></td>
                            <td>
                                <?= data_br($r['vencimento']) ?>
                                <?php if ((int) $r['situacao'] === 1 && ! empty($r['vencimento']) && $r['vencimento'] < date('Y-m-d')): ?>
                                    <div class="ajuda" style="color:var(--vermelho)">vencido</div>
                                <?php endif; ?>
                            </td>
                            <td><?= html_escape($r['centro_custo_nome'] ? $r['centro_custo_nome'] : '-') ?></td>
                            <td class="num"><strong><?= moeda_br($r['valor']) ?></strong></td>
                            <td class="centro"><?= cp_situacao_badge($r['situacao']) ?></td>
                            <td class="centro">
                                <a class="botao secundario pequeno" href="<?= site_url('financeiro/conta_pagar_ver/' . $r['id']) ?>">Abrir</a>
                                <?php if ((int) $r['situacao'] === 1): ?>
                                    <a class="botao pequeno" href="<?= site_url('financeiro/conta_pagar_baixar/' . $r['id']) ?>">Baixar</a>
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
        'base'  => site_url('financeiro/contas_pagar'),
        'busca' => $filtros['busca'],
        'extra' => array(
            'fornecedor_nome' => $filtros['fornecedor_nome'],
            'situacao'        => $filtros['situacao'],
            'centro_custo'    => $filtros['centro_custo'] > 0 ? $filtros['centro_custo'] : '',
            'venc_de'         => $filtros['venc_de'],
            'venc_ate'        => $filtros['venc_ate'],
            'origem'          => $filtros['origem'],
        ),
    )); ?>
</div>
