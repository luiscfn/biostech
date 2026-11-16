<?php
$encerrado = bd_bool($registro['encerrado']);
$status_nome = $registro['status_nome'] ? $registro['status_nome'] : '-';
$situacao_nome = $registro['situacao_nome'] ? $registro['situacao_nome'] : '-';
$total = (float) $registro['valor_material'] + (float) $registro['valor_servico']
       + (float) $registro['valor_mao_obra'] + (float) $registro['material_fixacao']
       + (float) $registro['valor_imposto'] + (float) $registro['acrescimo_custo']
       - (float) $registro['valor_desconto'];

$abas = array(
    'dados'        => 'Dados',
    'equipamento'  => 'Equipamento',
    'atendimento'  => 'Atendimento',
    'intervencoes' => 'Intervencoes (' . count($intervencoes) . ')',
    'pecas'        => 'Pecas (' . count($pecas) . ')',
    'servicos'     => 'Servicos (' . count($servicos) . ')',
    'contatos'     => 'Contatos (' . count($contatos) . ')',
    'financeiro'   => 'Financeiro',
    'historico'    => 'Historico',
);
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>
            <?= html_escape($registro['categoria']) ?> <?= html_escape($registro['numero']) ?>
            <span class="badge <?= $registro['categoria'] === 'Chamado' ? 'info' : 'alerta' ?>"><?= html_escape($registro['categoria']) ?></span>
            <span class="badge <?= $encerrado ? 'off' : 'ok' ?>"><?= $encerrado ? 'Encerrado' : $status_nome ?></span>
            <?php if ( ! $encerrado): ?>
                <span class="badge alerta"><?= html_escape($situacao_nome) ?></span>
            <?php endif; ?>
        </h3>

        <div class="acoes-ficha">
            <?php if ( ! $encerrado): ?>
                <a class="botao secundario pequeno" href="<?= site_url('chamados/editar/' . $registro['numero']) ?>">Editar</a>
                <a class="botao secundario pequeno" href="<?= site_url('chamados/status/' . $registro['numero']) ?>">Mudar status</a>
                <a class="botao pequeno" href="<?= site_url('chamados/encerrar/' . $registro['numero']) ?>">Encerrar</a>
            <?php endif; ?>

            <a class="botao secundario pequeno" href="<?= site_url('chamados/imprimir/' . $registro['numero']) ?>" target="_blank">Imprimir</a>
            <a class="botao secundario pequeno" href="<?= site_url('chamados') ?>">Voltar</a>
        </div>
    </div>

    <div class="painel-corpo">
        <div class="detalhes">
            <div class="detalhe">
                <div class="rotulo">Cliente</div>
                <div class="valor">
                    <?php if ( ! empty($registro['cliente_id'])): ?>
                        <a href="<?= site_url('clientes/ver/' . $registro['cliente_id']) ?>"><?= html_escape($registro['cliente_nome']) ?></a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Abertura</div>
                <div class="valor">
                    <?= data_br($registro['data_abertura']) ?>
                    <?= $registro['hora_abertura'] ? ' as ' . html_escape(substr($registro['hora_abertura'], 0, 5)) : '' ?>
                </div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Equipamento / Serie</div>
                <div class="valor">
                    <?= html_escape( ! empty($equipamento['equipamento_serie']) ? $equipamento['equipamento_serie'] : '-') ?>
                </div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Numero par</div>
                <div class="valor"><?= html_escape($registro['numero_par'] ? $registro['numero_par'] : '-') ?></div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Registro gerado</div>
                <div class="valor">
                    <?php if ( ! empty($registro['numero_gerado'])): ?>
                        <a href="<?= site_url('chamados/ver/' . $registro['numero_gerado']) ?>"><?= html_escape($registro['numero_gerado']) ?></a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
            <div class="detalhe">
                <div class="rotulo">Total atual</div>
                <div class="valor"><strong><?= moeda_br($total) ?></strong></div>
            </div>
        </div>
    </div>

    <div class="abas">
        <?php foreach ($abas as $chave => $rotulo): ?>
            <a class="aba <?= $aba === $chave ? 'ativa' : '' ?>" href="<?= site_url('chamados/ver/' . $registro['numero']) ?>?aba=<?= $chave ?>">
                <?= html_escape($rotulo) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="aba-conteudo">
        <?php $this->load->view('chamados/_' . $aba, array(
            'registro'      => $registro,
            'equipamento'   => $equipamento,
            'atendimento'   => $atendimento,
            'intervencoes'  => $intervencoes,
            'pecas'         => $pecas,
            'servicos'      => $servicos,
            'contatos'      => $contatos,
            'plano'         => $plano,
            'parcelas'      => $parcelas,
            'contas'        => $contas,
            'eventos'       => $eventos,
            'log'           => $log,
            'tecnicos'      => $tecnicos,
            'dominios'      => $dominios,
            'status_opcoes' => $status_opcoes,
            'situacoes'     => $situacoes,
            'total_previsto'=> $total_previsto,
        )); ?>
    </div>
</div>
