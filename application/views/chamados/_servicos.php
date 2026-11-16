<?php
$encerrado = bd_bool($registro['encerrado']);
$total_avulso = 0;
foreach ($servicos as $s)
{
    if ($s['tipo'] === 'avulso')
    {
        $total_avulso += (float) $s['valor_total'];
    }
}
?>
<div class="painel-corpo">
    <h4 class="secao-titulo">Servicos</h4>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:95px">Tipo</th>
                    <th style="width:55px" class="num">Item</th>
                    <th>Descricao</th>
                    <th class="num" style="width:70px">Qtd</th>
                    <th class="num" style="width:100px">Unitario</th>
                    <th class="num" style="width:100px">Total</th>
                    <th class="centro" style="width:100px">Situacao</th>
                    <th class="centro" style="width:80px">Acao</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($servicos)): ?>
                    <tr><td colspan="8" class="vazio">Nenhum servico cadastrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($servicos as $s): ?>
                        <?php
                        $rotulo = array('previsto' => 'Previsto', 'executado' => 'Executado', 'avulso' => 'Avulso');
                        $classe = array('previsto' => 'alerta', 'executado' => 'ok', 'avulso' => 'info');
                        ?>
                        <tr>
                            <td>
                                <span class="badge <?= $classe[$s['tipo']] ?>"><?= $rotulo[$s['tipo']] ?></span>
                            </td>
                            <td class="num"><?= (int) $s['numero_item'] ?></td>
                            <td class="texto-longo"><?= nl2br(html_escape($s['descricao'])) ?></td>
                            <td class="num"><?= (int) $s['quantidade'] ?></td>
                            <td class="num"><?= moeda_br($s['valor_unitario']) ?></td>
                            <td class="num"><?= moeda_br($s['valor_total']) ?></td>
                            <td class="centro">
                                <?= bd_bool($s['concluido']) ? '<span class="badge ok">concluido</span>' : '<span class="badge off">pendente</span>' ?>
                            </td>
                            <td class="centro">
                                <?php if ($s['tipo'] === 'previsto' && ! $encerrado): ?>
                                    <?= form_open('chamados/concluir_servico/' . $registro['numero'] . '/' . $s['id'], array('class' => 'inline', 'onsubmit' => "return confirm('Marcar este servico como executado?');")) ?>
                                        <button type="submit" class="botao secundario pequeno">Concluir</button>
                                    <?= form_close() ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="num"><strong>Total de servicos avulsos</strong></td>
                    <td class="num"><strong><?= moeda_br($total_avulso) ?></strong></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if ( ! $encerrado): ?>
        <hr class="divisor">

        <h4 class="secao-titulo">Incluir servico</h4>

        <?= form_open('chamados/servicos/' . $registro['numero']) ?>
            <div class="form-grid">
                <div class="campo">
                    <label for="tipo">Tipo *</label>
                    <select name="tipo" id="tipo">
                        <option value="previsto">Previsto</option>
                        <option value="avulso">Avulso (cobra na OS)</option>
                    </select>
                </div>

                <div class="campo">
                    <label for="quantidade_servico">Quantidade *</label>
                    <input type="number" name="quantidade" id="quantidade_servico" value="1" min="1">
                </div>

                <div class="campo">
                    <label for="valor_servico">Valor unitario (avulso)</label>
                    <input type="text" name="valor_unitario" id="valor_servico" placeholder="0,00">
                </div>

                <div class="campo full">
                    <label for="descricao">Descricao *</label>
                    <textarea name="descricao" id="descricao" required></textarea>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao">Incluir servico</button>
            </div>
        <?= form_close() ?>
    <?php endif; ?>
</div>
