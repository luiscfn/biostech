<?php
$pode_editar = in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE);
$saldo_bancos = 0.0;

foreach ($posicao as $p)
{
    $saldo_bancos += (float) $p['saldo'];
}
?>
<div class="cartoes">
    <div class="cartao amarelo">
        <div class="rotulo">Contas a pagar em aberto</div>
        <div class="valor"><?= moeda_br($resumo['cp_valor']) ?></div>
        <div class="detalhe">
            <?= numero_br($resumo['cp_abertas']) ?> titulo(s)
            | vencido <?= moeda_br($resumo['cp_vencido']) ?>
        </div>
    </div>

    <div class="cartao verde">
        <div class="rotulo">Contas a receber em aberto</div>
        <div class="valor"><?= moeda_br($resumo['cr_valor']) ?></div>
        <div class="detalhe">
            <?= numero_br($resumo['cr_abertas']) ?> titulo(s)
            | vencido <?= moeda_br($resumo['cr_vencido']) ?>
        </div>
    </div>

    <div class="cartao">
        <div class="rotulo">Saldo em caixa</div>
        <div class="valor"><?= moeda_br($resumo['caixa']) ?></div>
        <div class="detalhe">Ultimo fechamento de caixa</div>
    </div>

    <div class="cartao">
        <div class="rotulo">Saldo bancario</div>
        <div class="valor"><?= moeda_br($saldo_bancos) ?></div>
        <div class="detalhe"><?= numero_br(count($posicao)) ?> conta(s) ativa(s)</div>
    </div>
</div>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Atalhos</h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/contas_pagar') ?>">Contas a pagar</a>
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/contas_receber') ?>">Contas a receber</a>
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/movimentos') ?>">Movimentos</a>
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/caixa') ?>">Caixa</a>
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/cheques') ?>">Cheques</a>
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/balanco') ?>">Balanco</a>
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/cadastros') ?>">Cadastros</a>
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/relatorios') ?>">Relatorios</a>

            <?php if ($pode_editar): ?>
                <a class="botao pequeno" href="<?= site_url('financeiro/conta_pagar_novo') ?>">+ Conta a pagar</a>
                <a class="botao pequeno" href="<?= site_url('financeiro/conta_receber_novo') ?>">+ Conta a receber</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Posicao bancaria</h3>
        <a class="botao secundario pequeno" href="<?= site_url('financeiro/relatorios?tipo=posicao') ?>">Detalhar</a>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th>Banco</th>
                    <th>Agencia</th>
                    <th>Conta</th>
                    <th>Titular</th>
                    <th class="num" style="width:140px">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posicao)): ?>
                    <tr><td colspan="5" class="vazio">Nenhuma conta bancaria cadastrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($posicao as $p): ?>
                        <tr>
                            <td><?= html_escape($p['banco_nome'] ? $p['banco_nome'] : $p['banco_codigo']) ?></td>
                            <td><?= html_escape($p['agencia'] ? $p['agencia'] : '-') ?></td>
                            <td><?= html_escape($p['conta'] ? $p['conta'] : '-') ?></td>
                            <td><?= html_escape($p['titular'] ? $p['titular'] : '-') ?></td>
                            <td class="num"><strong><?= moeda_br($p['saldo']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(380px,1fr));gap:18px">
    <div class="painel">
        <div class="painel-cabecalho">
            <h3>Contas a pagar a vencer</h3>
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/contas_pagar') ?>">Ver todas</a>
        </div>

        <div class="tabela-wrap">
            <table class="tabela">
                <thead>
                    <tr><th>Titulo</th><th>Fornecedor</th><th>Vencimento</th><th class="num">Valor</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($vencendo)): ?>
                        <tr><td colspan="4" class="vazio">Nenhum titulo em aberto.</td></tr>
                    <?php else: ?>
                        <?php foreach ($vencendo as $r): ?>
                            <tr>
                                <td><a href="<?= site_url('financeiro/conta_pagar_ver/' . $r['id']) ?>"><?= (int) $r['numero'] ?>/<?= (int) $r['parcela'] ?></a></td>
                                <td><?= html_escape($r['fornecedor_nome'] ? $r['fornecedor_nome'] : '-') ?></td>
                                <td><?= data_br($r['vencimento']) ?></td>
                                <td class="num"><?= moeda_br($r['valor']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="painel">
        <div class="painel-cabecalho">
            <h3>Contas a receber em aberto</h3>
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/contas_receber') ?>">Ver todas</a>
        </div>

        <div class="tabela-wrap">
            <table class="tabela">
                <thead>
                    <tr><th>Titulo</th><th>Cliente</th><th>Vencimento</th><th class="num">Valor</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($recebendo)): ?>
                        <tr><td colspan="4" class="vazio">Nenhum titulo em aberto.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recebendo as $r): ?>
                            <tr>
                                <td><a href="<?= site_url('financeiro/conta_receber_ver/' . $r['codigo']) ?>"><?= (int) $r['codigo'] ?>/<?= (int) $r['parcela'] ?></a></td>
                                <td><?= html_escape($r['cliente_nome'] ? $r['cliente_nome'] : '-') ?></td>
                                <td><?= data_br($r['data_vencimento']) ?></td>
                                <td class="num"><?= moeda_br($r['valor']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Ultimos dias de caixa</h3>
        <a class="botao secundario pequeno" href="<?= site_url('financeiro/caixa') ?>">Ver todos</a>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th>Data</th>
                    <th class="num">Abertura</th>
                    <th class="num">Entradas</th>
                    <th class="num">Saidas</th>
                    <th class="num">Fechamento</th>
                    <th class="centro">Situacao</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($caixa)): ?>
                    <tr><td colspan="6" class="vazio">Nenhum dia de caixa registrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($caixa as $c): ?>
                        <tr>
                            <td><a href="<?= site_url('financeiro/caixa_dia/' . $c['data']) ?>"><?= data_br($c['data']) ?></a></td>
                            <td class="num"><?= moeda_br($c['saldo_anterior']) ?></td>
                            <td class="num"><?= moeda_br($c['total_entradas']) ?></td>
                            <td class="num"><?= moeda_br($c['total_saidas']) ?></td>
                            <td class="num"><strong><?= moeda_br($c['saldo_atual']) ?></strong></td>
                            <td class="centro">
                                <?php if (bd_bool($c['fechado'])): ?>
                                    <span class="badge ok">Fechado</span>
                                <?php else: ?>
                                    <span class="badge alerta">Aberto</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
