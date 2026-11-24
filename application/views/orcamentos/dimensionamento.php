<?php
$pode_editar = in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE);
$tem_orcamento = ! empty($registro['orcamento_numero']);

$total_venda = 0.0;
foreach ($itens as $item) { $total_venda += (float) $item['total_venda']; }

$abas_rotulos = array(
    'dados'        => 'Dados',
    'servicos'     => 'Serviços',
    'materiais'    => 'Materiais',
);

foreach ($classificacoes as $chave => $info)
{
    $abas_rotulos[$chave] = $info['rotulo'];
}

$abas_rotulos['itens']        = 'Itens';
$abas_rotulos['levantamento'] = 'Financeiro';

$partial = isset($classificacoes[$aba])
    ? 'orcamentos/_dim_produtos_classificacao'
    : 'orcamentos/_dim_' . $aba;
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>
            Dimensionamento <?= (int) $registro['numero'] ?>
            <?php if ($tem_orcamento): ?>
                <span class="badge ok">Orcamento <?= (int) $registro['orcamento_numero'] ?></span>
            <?php else: ?>
                <span class="badge alerta">Sem orcamento</span>
            <?php endif; ?>
        </h3>

        <div class="acoes-ficha">
            <?php if ($pode_editar): ?>
                <a class="botao secundario pequeno" href="<?= site_url('orcamentos/dimensionamento_editar/' . $registro['numero']) ?>">Editar</a>

                <?php if ( ! $tem_orcamento): ?>
                    <?= form_open('orcamentos/dimensionamento_gerar_orcamento/' . $registro['numero'], array('style' => 'display:inline')) ?>
                        <button type="submit" class="botao pequeno"
                                title="Gera um orcamento comercial com os itens e servicos deste dimensionamento">Gerar orcamento</button>
                    <?= form_close() ?>
                <?php endif; ?>
            <?php endif; ?>

            <a class="botao secundario pequeno" href="<?= site_url('orcamentos/dimensionamento_imprimir/' . $registro['numero']) ?>" target="_blank">Imprimir</a>
            <a class="botao secundario pequeno" href="<?= site_url('orcamentos/dimensionamentos') ?>">Voltar</a>
        </div>
    </div>

    <div class="painel-corpo">
        <div class="detalhes detalhes-dimensionamento">
            <div class="detalhe detalhe-data">
                <div class="rotulo">Data</div>
                <div class="valor"><?= data_br($registro['data']) ?></div>
            </div>
            <div class="detalhe detalhe-cliente">
                <div class="rotulo">Cliente</div>
                <div class="valor">
                    <?php if ( ! empty($registro['cliente_id'])): ?>
                        <a href="<?= site_url('clientes/ver/' . $registro['cliente_id']) ?>"><?= html_escape($registro['cliente_nome']) ?></a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
            <div class="detalhe detalhe-contato">
                <div class="rotulo">Contato</div>
                <div class="valor"><?= html_escape($registro['contato'] ? $registro['contato'] : '-') ?></div>
            </div>
            <div class="detalhe detalhe-chamado">
                <div class="rotulo">Chamado / OS</div>
                <div class="valor">
                    <?php if ( ! empty($registro['chamado_numero'])): ?>
                        <a href="<?= site_url('chamados/ver/' . $registro['chamado_numero']) ?>"><?= html_escape($registro['chamado_numero']) ?></a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
            <div class="detalhe detalhe-orcamento">
                <div class="rotulo">Nº Orçamento/Data</div>
                <div class="valor">
                    <?php if ($tem_orcamento): ?>
                        <a href="<?= site_url('orcamentos/ver/' . $registro['orcamento_numero']) ?>"><?= (int) $registro['orcamento_numero'] ?></a>
                        <?php if ( ! empty($registro['orcamento_data'])): ?>
                            <span class="orcamento-data"><?= data_br($registro['orcamento_data']) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="abas abas-orcamento">
        <?php foreach ($abas_rotulos as $chave => $rotulo): ?>
            <a class="aba <?= $aba === $chave ? 'ativa' : '' ?>" href="<?= site_url('orcamentos/dimensionamento/' . $registro['numero']) ?>?aba=<?= $chave ?>">
                <?= html_escape($rotulo) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="aba-conteudo">
        <?php $this->load->view($partial, array(
            'registro'           => $registro,
            'itens'              => $itens,
            'servicos'           => $servicos,
            'materiais'          => $materiais,
            'pode_editar'        => $pode_editar,
            'total_venda'        => $total_venda,
            'aba'                => $aba,
            'classificacao'      => $classificacao,
            'grupos_disponiveis' => $grupos_disponiveis,
            'subgrupos'          => $subgrupos,
            'produtos'           => $produtos,
            'itens_classificacao'=> $itens_classificacao,
            'filtros'            => $filtros,
            'pag'                => $pag,
        )); ?>
    </div>
</div>

<script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
<script src="<?= base_url('assets/js/orcamentos.js') ?>"></script>
<script src="<?= base_url('assets/js/orcamentos_dim_levantamento.js') ?>"></script>
