<?php
$fechado = bd_bool($registro['fechado']);
$edicao  = ! empty($mov_edicao);
$pode_editar = in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE);

$setores_nome = array();

foreach ($setores as $s)
{
    $setores_nome[(int) $s['id']] = $s['nome'];
}

$valor = function ($campo, $default = '') use ($mov_edicao, $edicao) {
    if ( ! $edicao)
    {
        return $default;
    }

    return isset($mov_edicao[$campo]) ? $mov_edicao[$campo] : $default;
};

$data = $registro['data'];
?>
<div class="cartoes">
    <div class="cartao">
        <div class="rotulo">Abertura</div>
        <div class="valor"><?= moeda_br($registro['saldo_anterior']) ?></div>
        <div class="detalhe">Saldo anterior ao dia</div>
    </div>

    <div class="cartao verde">
        <div class="rotulo">Entradas</div>
        <div class="valor"><?= moeda_br($total_entradas) ?></div>
        <div class="detalhe"><?= numero_br(count($movimentos)) ?> movimento(s)</div>
    </div>

    <div class="cartao vermelho">
        <div class="rotulo">Saidas</div>
        <div class="valor"><?= moeda_br($total_saidas) ?></div>
        <div class="detalhe">Pagamentos e retiradas</div>
    </div>

    <div class="cartao amarelo">
        <div class="rotulo"><?= $fechado ? 'Fechamento' : 'Saldo projetado' ?></div>
        <div class="valor"><?= moeda_br($registro['saldo_atual']) ?></div>
        <div class="detalhe"><?= $fechado ? 'Caixa fechado' : 'Caixa aberto' ?></div>
    </div>
</div>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>
            Caixa de <?= data_br($data) ?>
            <?php if ($fechado): ?>
                <span class="badge ok">Fechado</span>
            <?php else: ?>
                <span class="badge alerta">Aberto</span>
            <?php endif; ?>
        </h3>

        <div class="acoes-ficha">
            <?php if ($fechado && $pode_editar): ?>
                <?= form_open('financeiro/caixa_reabrir/' . $data, array('style' => 'display:inline')) ?>
                    <button type="submit" class="botao secundario pequeno">Reabrir caixa</button>
                <?= form_close() ?>
            <?php elseif ( ! $fechado): ?>
                <?= form_open('financeiro/caixa_fechar/' . $data, array('style' => 'display:inline')) ?>
                    <button type="submit" class="botao pequeno">Fechar caixa</button>
                <?= form_close() ?>
            <?php endif; ?>

            <a class="botao secundario pequeno" href="<?= site_url('financeiro/caixa') ?>">Voltar</a>
        </div>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:60px" class="num">No</th>
                    <th style="width:150px">Setor</th>
                    <th style="width:110px">Documento</th>
                    <th>Historico</th>
                    <th class="num" style="width:110px">Entrada</th>
                    <th class="num" style="width:110px">Saida</th>
                    <th style="width:130px">Forma</th>
                    <th class="centro" style="width:120px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movimentos)): ?>
                    <tr><td colspan="8" class="vazio">Nenhum movimento neste dia.</td></tr>
                <?php else: ?>
                    <?php foreach ($movimentos as $m): ?>
                        <tr>
                            <td class="num"><?= (int) $m['numero'] ?></td>
                            <td><?= html_escape(isset($setores_nome[(int) $m['setor']]) ? $setores_nome[(int) $m['setor']] : '-') ?></td>
                            <td><?= html_escape($m['documento'] ? $m['documento'] : '-') ?></td>
                            <td><?= html_escape($m['historico'] ? $m['historico'] : '-') ?></td>
                            <td class="num"><?= (float) $m['valor_entrada'] > 0 ? moeda_br($m['valor_entrada']) : '-' ?></td>
                            <td class="num"><?= (float) $m['valor_saida'] > 0 ? moeda_br($m['valor_saida']) : '-' ?></td>
                            <td><?= html_escape($m['forma_recebimento'] !== NULL ? $m['forma_recebimento'] : '-') ?></td>
                            <td class="centro">
                                <a class="botao secundario pequeno" href="<?= site_url('financeiro/caixa_dia/' . $data . '?editar=' . $m['id']) ?>#form-caixa">Editar</a>

                                <?php if ($pode_editar): ?>
                                    <?= form_open('financeiro/caixa_movimento_excluir/' . $m['id'], array('style' => 'display:inline')) ?>
                                        <button type="submit" class="botao perigo pequeno">Excluir</button>
                                    <?= form_close() ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="num">Totais</th>
                    <th class="num"><?= moeda_br($total_entradas) ?></th>
                    <th class="num"><?= moeda_br($total_saidas) ?></th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="painel" id="form-caixa">
    <div class="painel-cabecalho">
        <h3><?= $edicao ? 'Editar movimento ' . (int) $mov_edicao['numero'] : 'Novo movimento de caixa' ?></h3>

        <?php if ($edicao): ?>
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/caixa_dia/' . $data) ?>">Cancelar edicao</a>
        <?php endif; ?>
    </div>

    <div class="painel-corpo">
        <?= form_open('financeiro/caixa_movimento_salvar') ?>
            <input type="hidden" name="id" value="<?= $edicao ? (int) $mov_edicao['id'] : '' ?>">
            <input type="hidden" name="data" value="<?= html_escape($data) ?>">

            <div class="form-grid">
                <div class="campo terco">
                    <label for="valor_entrada">Entrada</label>
                    <input type="text" name="valor_entrada" id="valor_entrada" inputmode="decimal"
                           value="<?= $edicao && (float) $valor('valor_entrada', 0) > 0 ? number_format((float) $valor('valor_entrada', 0), 2, ',', '') : '' ?>">
                </div>

                <div class="campo terco">
                    <label for="valor_saida">Saida</label>
                    <input type="text" name="valor_saida" id="valor_saida" inputmode="decimal"
                           value="<?= $edicao && (float) $valor('valor_saida', 0) > 0 ? number_format((float) $valor('valor_saida', 0), 2, ',', '') : '' ?>">
                </div>

                <div class="campo terco">
                    <label for="setor">Setor</label>
                    <select name="setor" id="setor">
                        <option value="">-</option>
                        <?php foreach ($setores as $s): ?>
                            <option value="<?= (int) $s['id'] ?>" <?= $edicao && (int) $valor('setor', 0) === (int) $s['id'] ? 'selected' : '' ?>>
                                <?= html_escape($s['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo terco">
                    <label for="documento">Documento</label>
                    <input type="text" name="documento" id="documento" maxlength="30"
                           value="<?= html_escape($valor('documento')) ?>">
                </div>

                <div class="campo terco">
                    <label for="forma_recebimento">Forma de recebimento</label>
                    <select name="forma_recebimento" id="forma_recebimento">
                        <option value="">-</option>
                        <?php foreach ($dominios['forma_recebimento'] as $d): ?>
                            <option value="<?= (int) $d['codigo'] ?>" <?= $edicao && (int) $valor('forma_recebimento', 0) === (int) $d['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($d['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo terco">
                    <label for="documento_recebimento">Documento (tipo)</label>
                    <select name="documento_recebimento" id="documento_recebimento">
                        <option value="">-</option>
                        <?php foreach ($dominios['documento'] as $d): ?>
                            <option value="<?= (int) $d['codigo'] ?>" <?= $edicao && (int) $valor('documento_recebimento', 0) === (int) $d['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($d['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo terco">
                    <label for="chamado_numero">OS / Chamado</label>
                    <input type="text" name="chamado_numero" id="chamado_numero" maxlength="16"
                           value="<?= html_escape($valor('chamado_numero')) ?>">
                </div>

                <div class="campo full">
                    <label for="historico">Historico</label>
                    <textarea name="historico" id="historico" rows="2"><?= html_escape($valor('historico')) ?></textarea>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao"><?= $edicao ? 'Salvar movimento' : 'Lancar movimento' ?></button>
                <a class="botao secundario" href="<?= site_url('financeiro/caixa_dia/' . $data) ?>">Cancelar</a>
            </div>
        <?= form_close() ?>
    </div>
</div>
