<?php
$grupos = array(
    'Bancos e contas' => array(
        'bancos'  => array('Bancos', 'Codigo e nome dos bancos usados nos titulos e movimentos.'),
        'agencias'=> array('Agencias', 'Agencias por banco, com endereco e contato.'),
        'contas'  => array('Contas bancarias', 'Contas correntes usadas nos movimentos e baixas.'),
        'cartoes' => array('Cartoes', 'Cartoes de credito e debito.'),
    ),
    'Classificacao' => array(
        'centros' => array('Centros de custo', 'Classificacao dos titulos e despesas.'),
        'naturezas' => array('Naturezas de operacao', 'Natureza contabil das despesas.'),
        'status'  => array('Status de pagamento', 'Situacoes de pagamento herdadas do legado.'),
        'periodicidades' => array('Periodicidades', 'Frequencia das despesas fixas.'),
    ),
    'Despesas' => array(
        'despesas' => array('Despesas fixas', 'Despesas recorrentes com fornecedor e vencimento.'),
    ),
);
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>Cadastros do financeiro</h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('financeiro') ?>">Painel</a>
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/relatorios') ?>">Relatorios</a>
        </div>
    </div>

    <div class="painel-corpo">
        <?php foreach ($grupos as $grupo => $itens): ?>
            <h4 class="secao-titulo"><?= html_escape($grupo) ?></h4>

            <div class="cartoes" style="margin-bottom:18px">
                <?php foreach ($itens as $tipo => $info): ?>
                    <a class="cartao" href="<?= site_url('financeiro/cadastro/' . $tipo) ?>" style="text-decoration:none;color:inherit">
                        <div class="rotulo"><?= html_escape($info[0]) ?></div>
                        <div class="valor"><?= numero_br(isset($contagens[$tipo]) ? $contagens[$tipo] : 0) ?></div>
                        <div class="detalhe"><?= html_escape($info[1]) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
