<div class="painel">
    <div class="painel-cabecalho">
        <h3>Chamados e Ordens de Servico</h3>

        <div class="filtros">
            <form method="get" action="<?= site_url('chamados') ?>" class="filtros">
                <input type="text" name="busca" value="<?= html_escape($filtros['busca']) ?>" placeholder="Numero, cliente, serie, defeito...">

                <select name="categoria">
                    <option value="">Categoria</option>
                    <option value="Chamado" <?= $filtros['categoria'] === 'Chamado' ? 'selected' : '' ?>>Chamado</option>
                    <option value="Ordem Serviço" <?= $filtros['categoria'] === 'Ordem Serviço' ? 'selected' : '' ?>>Ordem de Servico</option>
                </select>

                <select name="status">
                    <option value="">Status</option>
                    <?php foreach ($status as $s): ?>
                        <option value="<?= (int) $s['codigo'] ?>" <?= (string) $s['codigo'] === $filtros['status'] ? 'selected' : '' ?>>
                            <?= html_escape($s['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="situacao">
                    <option value="">Situacao</option>
                    <?php foreach ($situacoes as $s): ?>
                        <option value="<?= (int) $s['codigo'] ?>" <?= (string) $s['codigo'] === $filtros['situacao'] ? 'selected' : '' ?>>
                            <?= html_escape($s['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="encerrado">
                    <option value="">Todos</option>
                    <option value="0" <?= $filtros['encerrado'] === '0' ? 'selected' : '' ?>>Em aberto</option>
                    <option value="1" <?= $filtros['encerrado'] === '1' ? 'selected' : '' ?>>Encerrados</option>
                </select>

                <input type="date" name="de"  value="<?= html_escape($filtros['de']) ?>"  title="Abertura de">
                <input type="date" name="ate" value="<?= html_escape($filtros['ate']) ?>" title="Abertura ate">

                <button type="submit" class="botao secundario">Pesquisar</button>

                <?php if ($filtros['busca'] !== '' || $filtros['categoria'] !== '' || $filtros['status'] !== '' || $filtros['situacao'] !== '' || $filtros['de'] !== '' || $filtros['ate'] !== '' || $filtros['encerrado'] !== ''): ?>
                    <a class="botao secundario" href="<?= site_url('chamados') ?>">Limpar</a>
                <?php endif; ?>
            </form>

            <a class="botao" href="<?= site_url('chamados/novo') ?>">+ Novo chamado / OS</a>
        </div>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:110px">Numero</th>
                    <th style="width:90px">Abertura</th>
                    <th>Cliente</th>
                    <th style="width:110px">Categoria</th>
                    <th>Equipamento</th>
                    <th>Status</th>
                    <th>Situacao atual</th>
                    <th class="num" style="width:110px">Total</th>
                    <th class="centro" style="width:90px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="9" class="vazio">Nenhum chamado encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <?php
                        $total = (float) $r['valor_material'] + (float) $r['valor_servico']
                               + (float) $r['valor_mao_obra'] - (float) $r['valor_desconto'];
                        ?>
                        <tr>
                            <td>
                                <a href="<?= site_url('chamados/ver/' . $r['numero']) ?>"><strong><?= html_escape($r['numero']) ?></strong></a>
                                <?php if (bd_bool($r['encerrado'])): ?>
                                    <span class="badge off">encerrado</span>
                                <?php endif; ?>
                            </td>
                            <td><?= data_br($r['data_abertura']) ?></td>
                            <td><?= html_escape($r['cliente_nome'] ? $r['cliente_nome'] : '-') ?></td>
                            <td>
                                <span class="badge <?= $r['categoria'] === 'Chamado' ? 'info' : 'alerta' ?>">
                                    <?= html_escape($r['categoria']) ?>
                                </span>
                            </td>
                            <td><?= html_escape($r['equipamento_serie'] ? $r['equipamento_serie'] : '-') ?></td>
                            <td><?= html_escape($r['status_nome'] ? $r['status_nome'] : '-') ?></td>
                            <td><?= html_escape($r['situacao_nome'] ? $r['situacao_nome'] : '-') ?></td>
                            <td class="num"><?= moeda_br($total) ?></td>
                            <td class="centro">
                                <a class="botao secundario pequeno" href="<?= site_url('chamados/ver/' . $r['numero']) ?>">Abrir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $this->load->view('layouts/paginacao', array(
        'pag'   => $pag,
        'base'  => site_url('chamados'),
        'extra' => array(
            'categoria' => $filtros['categoria'],
            'status'    => $filtros['status'],
            'situacao'  => $filtros['situacao'],
            'de'        => $filtros['de'],
            'ate'       => $filtros['ate'],
            'encerrado' => $filtros['encerrado'],
        ),
    )); ?>
</div>
