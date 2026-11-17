<div class="painel-corpo">
    <?php if (empty($materiais)): ?>
        <div class="vazio-estado">
            Nenhum material previsto registrado no legado para este dimensionamento.
        </div>
    <?php else: ?>
        <div class="tabela-wrap">
            <table class="tabela">
                <thead>
                    <tr>
                        <th class="num" style="width:40px">#</th>
                        <th>Material previsto</th>
                        <th class="num" style="width:110px">Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materiais as $material): ?>
                        <tr>
                            <td class="num"><?= (int) $material['numero_item'] ?></td>
                            <td><?= html_escape($material['descricao']) ?></td>
                            <td class="num"><?= numero_br($material['quantidade'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
