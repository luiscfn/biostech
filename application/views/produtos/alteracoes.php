<div class="painel">
    <div class="painel-cabecalho">
        <h3>Alteracoes do produto</h3>
        <div>
            <span class="badge info"><?= html_escape($registro['descricao']) ?></span>
            <?php if ($ultima): ?>
                <a class="badge info" style="text-decoration:none" href="<?= site_url('produtos/alteracoes/' . (int) $registro['id']) ?>">Historico completo</a>
            <?php else: ?>
                <a class="badge info" style="text-decoration:none" href="<?= site_url('produtos/alteracoes/' . (int) $registro['id'] . '?ultima=1') ?>">Somente a ultima alteracao</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="painel-corpo">
        <?php if (empty($eventos)): ?>
            <p class="vazio">Nao existe alteracao para mostrar.</p>
        <?php else: ?>
            <div class="tabela-wrap">
                <table class="tabela compacta">
                    <thead>
                        <tr>
                            <th style="width:150px">Data</th>
                            <th style="width:100px">Acao</th>
                            <th style="width:140px">Usuario</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eventos as $e): ?>
                            <tr>
                                <td><?= data_hora_br($e['created_at']) ?></td>
                                <td><?= html_escape($e['acao']) ?></td>
                                <td><?= html_escape($e['usuario_login'] ? $e['usuario_login'] : '-') ?></td>
                                <td style="font-size:11.5px"><?= html_escape($e['detalhe'] ? json_encode(json_decode($e['detalhe'], TRUE), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="form-acoes">
            <a class="botao secundario" href="<?= site_url('produtos/ver/' . (int) $registro['id']) ?>">Voltar ao produto</a>
        </div>
    </div>
</div>
