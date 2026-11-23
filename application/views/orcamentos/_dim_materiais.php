<div class="painel-corpo">
    <?php if ($pode_editar): ?>
        <div class="bloco-inclusao">
            <h4 class="secao-titulo">Incluir material</h4>

            <?= form_open('orcamentos/dim_adicionar_material/' . $registro['numero']) ?>
                <div class="form-grid">
                    <div class="campo" style="grid-column:span 8">
                        <label for="dim_material_descricao">Descrição do material</label>
                        <input type="text" name="descricao" id="dim_material_descricao" maxlength="200" required>
                    </div>
                    <div class="campo" style="grid-column:span 2">
                        <label for="dim_material_quantidade">Quantidade</label>
                        <input type="number" name="quantidade" id="dim_material_quantidade" step="0.01" min="0" value="1" required>
                    </div>
                    <div class="campo" style="grid-column:span 2;align-self:end">
                        <button type="submit" class="botao">Incluir material</button>
                    </div>
                </div>
            <?= form_close() ?>
        </div>
    <?php endif; ?>

    <div class="tabela-wrap tabela-rolagem materiais-rolagem">
        <table class="tabela tabela-materiais">
            <thead>
                <tr>
                    <th class="num" style="width:60px">Item</th>
                    <th>Material Necessário</th>
                    <th class="num" style="width:140px">Quantidade</th>
                    <?php if ($pode_editar): ?>
                        <th class="centro" style="width:160px">Acoes</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($materiais)): ?>
                    <tr><td colspan="<?= $pode_editar ? 4 : 3 ?>" class="vazio">Nenhum material cadastrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($materiais as $material): ?>
                        <?php $form_id = 'dim-material-' . (int) $material['id']; ?>
                        <tr>
                            <td class="num"><?= (int) $material['numero_item'] ?></td>
                            <td>
                                <?php if ($pode_editar): ?>
                                    <input type="text" class="material-descricao" name="descricao" form="<?= $form_id ?>"
                                           value="<?= html_escape($material['descricao']) ?>" maxlength="200">
                                <?php else: ?>
                                    <?= html_escape($material['descricao']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="num">
                                <?php if ($pode_editar): ?>
                                    <input type="number" class="material-quantidade" name="quantidade" form="<?= $form_id ?>"
                                           step="0.01" min="0" value="<?= html_escape($material['quantidade']) ?>">
                                <?php else: ?>
                                    <?= numero_br($material['quantidade'], 2) ?>
                                <?php endif; ?>
                            </td>
                            <?php if ($pode_editar): ?>
                                <td class="centro">
                                    <div class="acoes-linha">
                                        <?= form_open('orcamentos/dim_atualizar_material/' . $registro['numero'] . '/' . $material['id'],
                                            array('id' => $form_id)) ?>
                                            <button type="submit" class="botao secundario pequeno">Salvar</button>
                                        <?= form_close() ?>

                                        <?= form_open('orcamentos/dim_excluir_material/' . $registro['numero'] . '/' . $material['id'],
                                            array('onsubmit' => "return confirm('Excluir o material?')")) ?>
                                            <button type="submit" class="botao perigo pequeno">Excluir</button>
                                        <?= form_close() ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
