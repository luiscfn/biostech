<?php
$aberto    = $registro['situacao'] === 'aberto';
$fechado   = $registro['situacao'] === 'fechado';
$cancelado = $registro['situacao'] === 'cancelado';
$pode_editar = in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE);

$abas_rotulos = array(
    'dados'    => 'Dados',
    'itens'    => 'Itens (' . count($itens) . ')',
    'parcelas' => 'Parcelas (' . count($parcelas) . ')',
    'contas'   => 'Contas a pagar (' . count($contas) . ')',
    'log'      => 'Historico (' . count($log) . ')',
);
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>
            Pedido de compra <?= (int) $registro['numero'] ?>
            <?php if ($aberto): ?>
                <span class="badge alerta">Aberto</span>
            <?php elseif ($fechado): ?>
                <span class="badge ok">Fechado</span>
            <?php else: ?>
                <span class="badge off">Cancelado</span>
            <?php endif; ?>
        </h3>

        <div class="acoes-ficha">
            <?php if ($aberto && $pode_editar): ?>
                <a class="botao secundario pequeno" href="<?= site_url('compras/editar/' . $registro['numero']) ?>">Editar</a>
                <a class="botao pequeno" href="<?= site_url('compras/fechar/' . $registro['numero']) ?>">Fechar pedido</a>
            <?php endif; ?>

            <?php if ($pode_editar): ?>
                <?= form_open('compras/replicar/' . $registro['numero'], array('style' => 'display:inline')) ?>
                    <button type="submit" class="botao secundario pequeno">Replicar</button>
                <?= form_close() ?>
            <?php endif; ?>

            <a class="botao secundario pequeno" href="<?= site_url('compras/imprimir_pedido/' . $registro['numero']) ?>" target="_blank">Imprimir</a>
            <a class="botao secundario pequeno" href="<?= site_url('compras') ?>">Voltar</a>
        </div>
    </div>

    <div class="painel-corpo">
        <div class="detalhes">
            <div class="detalhe">
                <div class="rotulo">Fornecedor</div>
                <div class="valor">
                    <?php if ( ! empty($registro['fornecedor_codigo'])): ?>
                        <a href="<?= site_url('estoque/fornecedor/' . $registro['fornecedor_codigo']) ?>"><?= html_escape($registro['fornecedor_nome']) ?></a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Data</div>
                <div class="valor">
                    <?= data_br($registro['data']) ?>
                    <?= $registro['hora'] ? ' as ' . html_escape(substr((string) $registro['hora'], 0, 5)) : '' ?>
                </div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Setor / Funcionario</div>
                <div class="valor">
                    <?= html_escape($registro['setor_nome'] ? $registro['setor_nome'] : '-') ?>
                    <div class="ajuda"><?= html_escape($registro['funcionario_nome'] ? $registro['funcionario_nome'] : '-') ?></div>
                </div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Total bruto</div>
                <div class="valor"><?= moeda_br($registro['total_bruto']) ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Desconto</div>
                <div class="valor">
                    <?= moeda_br($registro['desconto']) ?>
                    <?php if ($registro['desconto_percentual'] !== NULL): ?>
                        <div class="ajuda"><?= numero_br($registro['desconto_percentual'], 2) ?>%</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Total do pedido</div>
                <div class="valor"><strong><?= moeda_br($registro['total']) ?></strong></div>
            </div>
        </div>
    </div>

    <div class="abas">
        <?php foreach ($abas_rotulos as $chave => $rotulo): ?>
            <a class="aba <?= $aba === $chave ? 'ativa' : '' ?>" href="<?= site_url('compras/ver/' . $registro['numero']) ?>?aba=<?= $chave ?>">
                <?= html_escape($rotulo) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="aba-conteudo">
        <?php $this->load->view('compras/_' . $aba, array(
            'registro'    => $registro,
            'itens'       => $itens,
            'parcelas'    => $parcelas,
            'contas'      => $contas,
            'log'         => $log,
            'prazos'      => $prazos,
            'pode_editar' => $pode_editar,
        )); ?>
    </div>
</div>
