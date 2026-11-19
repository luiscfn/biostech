<?php
$emissao    = NULL;
$primeiro   = NULL;
$ultimo     = NULL;
$total      = 0.0;
$baixado    = 0.0;
$canceladas = 0;

foreach ($parcelas as $p)
{
    if ($emissao === NULL && ! empty($p['data_emissao']))
    {
        $emissao = $p['data_emissao'];
    }

    if ( ! empty($p['data_vencimento']))
    {
        $primeiro = ($primeiro === NULL || $p['data_vencimento'] < $primeiro) ? $p['data_vencimento'] : $primeiro;
        $ultimo   = ($ultimo === NULL || $p['data_vencimento'] > $ultimo) ? $p['data_vencimento'] : $ultimo;
    }

    $total += (float) $p['valor'];

    if ($p['situacao'] === 'baixada')
    {
        $baixado += (float) $p['valor'];
    }

    if ($p['situacao'] === 'cancelada')
    {
        $canceladas++;
    }
}

$qtd = count($parcelas);

if ($qtd > 0 && $canceladas === $qtd)
{
    $classe_nf = 'off';
    $rotulo_nf = 'Cancelada';
}
elseif ($qtd > 0 && $baixado >= $total && $canceladas === 0)
{
    $classe_nf = 'ok';
    $rotulo_nf = 'Baixada';
}
elseif ($baixado > 0)
{
    $classe_nf = 'info';
    $rotulo_nf = 'Parcial';
}
else
{
    $classe_nf = 'alerta';
    $rotulo_nf = 'Em aberto';
}
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>Nota fiscal <?= html_escape($numero) ?></h3>

        <div class="acoes-ficha">
            <span class="badge <?= $classe_nf ?>"><?= $rotulo_nf ?></span>
            <span class="badge info"><?= html_escape($registro['nome']) ?></span>
            <a class="botao secundario pequeno" href="<?= site_url('clientes/notas/' . (int) $registro['id']) ?>">Voltar as notas</a>
        </div>
    </div>

    <?php $this->load->view('clientes/_abas', array('registro' => $registro, 'aba' => 'notas')); ?>

    <div class="painel-corpo">
        <div class="detalhes">
            <div class="detalhe">
                <div class="rotulo">Emissao</div>
                <div class="valor"><?= data_br($emissao) ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Vencimentos</div>
                <div class="valor">
                    <?= data_br($primeiro) ?>
                    <?php if ($ultimo !== NULL && $ultimo !== $primeiro): ?>
                        a <?= data_br($ultimo) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Parcelas</div>
                <div class="valor"><?= numero_br($qtd) ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Valor total</div>
                <div class="valor"><strong><?= moeda_br($total) ?></strong></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Baixado</div>
                <div class="valor"><?= moeda_br($baixado) ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Cliente</div>
                <div class="valor">
                    <a href="<?= site_url('clientes/ver/' . (int) $registro['id']) ?>"><?= html_escape($registro['nome']) ?></a>
                </div>
            </div>
        </div>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:120px">Titulo</th>
                    <th style="width:95px">Emissao</th>
                    <th style="width:110px">Vencimento</th>
                    <th>Documento</th>
                    <th style="width:100px">OS</th>
                    <th class="num" style="width:110px">Valor</th>
                    <th class="num" style="width:110px">Liquido</th>
                    <th class="centro" style="width:105px">Situacao</th>
                    <th class="centro" style="width:90px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($parcelas as $p): ?>
                    <?php
                    if ($p['situacao'] === 'baixada')
                    {
                        $classe = 'ok';
                        $rotulo = 'Baixada';
                    }
                    elseif ($p['situacao'] === 'cancelada')
                    {
                        $classe = 'off';
                        $rotulo = 'Cancelada';
                    }
                    else
                    {
                        $classe = 'alerta';
                        $rotulo = 'Aberta';
                    }
                    ?>
                    <tr>
                        <td>
                            <a href="<?= site_url('financeiro/conta_receber_ver/' . (int) $p['codigo']) ?>">
                                <strong><?= (int) $p['codigo'] ?>/<?= (int) $p['parcela'] ?></strong>
                            </a>
                            <?php if (bd_bool($p['arquivada'])): ?>
                                <div class="ajuda">arquivado</div>
                            <?php endif; ?>
                        </td>
                        <td><?= data_br($p['data_emissao']) ?></td>
                        <td><?= data_br($p['data_vencimento']) ?></td>
                        <td><?= html_escape($p['numero_documento'] ? $p['numero_documento'] : '-') ?></td>
                        <td><?= html_escape($p['chamado_numero'] ? $p['chamado_numero'] : '-') ?></td>
                        <td class="num"><?= moeda_br($p['valor']) ?></td>
                        <td class="num"><?= moeda_br($p['vliq']) ?></td>
                        <td class="centro"><span class="badge <?= $classe ?>"><?= $rotulo ?></span></td>
                        <td class="centro">
                            <a class="botao secundario pequeno"
                               href="<?= site_url('financeiro/conta_receber_ver/' . (int) $p['codigo']) ?>">Abrir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
