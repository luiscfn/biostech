<?php
$consulta = ! empty($consulta);
$na_ficha = ( ! $consulta && ! empty($registro));

$base  = $na_ficha
    ? site_url('clientes/notas/' . (int) $registro['id'])
    : site_url('clientes/notas');

$extra = ( ! $na_ficha && ! empty($registro))
    ? array('cliente' => (int) $registro['id'])
    : array();

$limpar = $na_ficha
    ? site_url('clientes/notas/' . (int) $registro['id'])
    : site_url('clientes/notas');
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3><?= $na_ficha ? 'Notas fiscais' : 'Notas fiscais do cliente' ?></h3>

        <div class="acoes-ficha">
            <?php if ( ! empty($registro)): ?>
                <span class="badge info"><?= html_escape($registro['nome']) ?></span>
                <a class="botao secundario pequeno" href="<?= site_url('clientes/ver/' . (int) $registro['id']) ?>">Voltar ao cliente</a>
            <?php endif; ?>
        </div>
    </div>

    <?php $this->load->view('clientes/_abas', array('registro' => $registro, 'aba' => 'notas')); ?>

    <div class="painel-corpo">
        <form method="get" action="<?= $base ?>" class="filtros filtros-largos">
            <?php if (empty($registro)): ?>
                <div class="autocomplete" data-url="<?= site_url('clientes/buscar_clientes') ?>" style="flex:4 1 320px; min-width:300px">
                    <input type="text" class="autocomplete-busca" placeholder="Cliente: nome, fantasia ou CNPJ..." autocomplete="off" style="max-width:none">
                    <input type="hidden" name="cliente" value="">
                    <div class="autocomplete-lista"></div>
                </div>
            <?php elseif ($consulta): ?>
                <input type="hidden" name="cliente" value="<?= (int) $registro['id'] ?>">
            <?php endif; ?>

            <input type="text" name="busca" value="<?= html_escape($busca) ?>"
                   placeholder="Numero da nota fiscal..."
                   <?php if (empty($registro)): ?>style="flex:1 1 180px; max-width:260px; min-width:140px"<?php endif; ?>>

            <button type="submit" class="botao">Pesquisar</button>

            <?php if ($busca !== '' || $extra !== array()): ?>
                <a class="botao secundario" href="<?= $limpar ?>">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($registro)): ?>
        <div class="tabela-wrap">
            <table class="tabela">
                <tbody>
                    <tr><td class="vazio">Selecione o cliente acima para consultar as notas fiscais.</td></tr>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="tabela-wrap">
            <table class="tabela">
                <thead>
                    <tr>
                        <th style="width:130px">Nota fiscal</th>
                        <th style="width:95px">Emissao</th>
                        <th style="width:210px">Vencimentos</th>
                        <th class="centro" style="width:95px">Parcelas</th>
                        <th class="num" style="width:120px">Valor total</th>
                        <th class="num" style="width:120px">Baixado</th>
                        <th class="centro" style="width:110px">Situacao</th>
                        <th class="centro" style="width:90px">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registros)): ?>
                        <tr><td colspan="8" class="vazio">Nenhuma nota fiscal encontrada para este cliente.</td></tr>
                    <?php else: ?>
                        <?php foreach ($registros as $r): ?>
                            <?php
                            $parcelas   = (int) $r['parcelas'];
                            $baixadas   = (int) $r['parcelas_baixadas'];
                            $canceladas = (int) $r['parcelas_canceladas'];

                            if ($parcelas > 0 && $canceladas === $parcelas)
                            {
                                $classe_nf = 'off';
                                $rotulo_nf = 'Cancelada';
                            }
                            elseif ($parcelas > 0 && $baixadas === $parcelas)
                            {
                                $classe_nf = 'ok';
                                $rotulo_nf = 'Baixada';
                            }
                            elseif ($baixadas > 0)
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
                            <tr>
                                <td><strong><?= html_escape($r['numero']) ?></strong></td>
                                <td><?= data_br($r['data_emissao']) ?></td>
                                <td>
                                    <?= data_br($r['primeiro_vencimento']) ?>
                                    <?php if ($r['ultimo_vencimento'] && $r['ultimo_vencimento'] !== $r['primeiro_vencimento']): ?>
                                        a <?= data_br($r['ultimo_vencimento']) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="centro">
                                    <?= numero_br($parcelas) ?>
                                    <?php if ($canceladas > 0): ?>
                                        <div class="ajuda"><?= numero_br($canceladas) ?> cancelada(s)</div>
                                    <?php endif; ?>
                                </td>
                                <td class="num"><strong><?= moeda_br($r['valor_total']) ?></strong></td>
                                <td class="num"><?= moeda_br($r['valor_baixado']) ?></td>
                                <td class="centro"><span class="badge <?= $classe_nf ?>"><?= $rotulo_nf ?></span></td>
                                <td class="centro">
                                    <a class="botao pequeno"
                                       href="<?= site_url('clientes/nota/' . (int) $registro['id']) ?>?numero=<?= rawurlencode($r['numero']) ?>">Abrir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php $this->load->view('layouts/paginacao', array(
            'pag'   => $pag,
            'base'  => $base,
            'busca' => $busca,
            'extra' => $extra,
        )); ?>
    <?php endif; ?>
</div>

<script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
