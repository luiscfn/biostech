<div class="painel-corpo">
    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th class="num" style="width:40px">#</th>
                    <th>Servico a executar</th>
                    <th class="centro" style="width:100px">Concluido</th>
                    <?php if ($pode_editar): ?>
                        <th class="centro" style="width:160px">Acoes</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($servicos)): ?>
                    <tr><td colspan="<?= $pode_editar ? 4 : 3 ?>" class="vazio">Nenhum servico cadastrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($servicos as $servico): ?>
                        <?php $form_id = 'dim-servico-' . (int) $servico['id']; ?>
                        <tr>
                            <td class="num"><?= (int) $servico['numero_item'] ?></td>
                            <td>
                                <?php if ($pode_editar): ?>
                                    <input type="text" name="descricao" form="<?= $form_id ?>"
                                           value="<?= html_escape($servico['descricao']) ?>" maxlength="200">
                                <?php else: ?>
                                    <?= html_escape($servico['descricao']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="centro">
                                <?php if ($pode_editar): ?>
                                    <input type="checkbox" name="concluido" value="S" form="<?= $form_id ?>"
                                        <?= bd_bool($servico['concluido']) ? 'checked' : '' ?>>
                                <?php else: ?>
                                    <?= bd_bool($servico['concluido']) ? '<span class="badge ok">Sim</span>' : '<span class="badge off">Nao</span>' ?>
                                <?php endif; ?>
                            </td>
                            <?php if ($pode_editar): ?>
                                <td class="centro">
                                    <?= form_open('orcamentos/dim_atualizar_servico/' . $registro['numero'] . '/' . $servico['id'],
                                        array('id' => $form_id)) ?>
                                        <button type="submit" class="botao secundario pequeno">Salvar</button>
                                    <?= form_close() ?>

                                    <?= form_open('orcamentos/dim_excluir_servico/' . $registro['numero'] . '/' . $servico['id'],
                                        array('style' => 'margin-top:4px', 'onsubmit' => "return confirm('Excluir o servico?')")) ?>
                                        <button type="submit" class="botao perigo pequeno">Excluir</button>
                                    <?= form_close() ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pode_editar): ?>
        <hr class="divisor">
        <h4 class="secao-titulo">Incluir servico</h4>

        <?= form_open('orcamentos/dim_adicionar_servico/' . $registro['numero']) ?>
            <div class="form-grid">
                <div class="campo" style="grid-column:span 8">
                    <label for="dim_servico_descricao">Descricao do servico</label>
                    <input type="text" name="descricao" id="dim_servico_descricao" maxlength="200" required>
                </div>
                <div class="campo terco">
                    <label class="check">
                        <input type="checkbox" name="concluido" value="S"> Concluido
                    </label>
                </div>
                <div class="campo terco" style="align-self:end">
                    <button type="submit" class="botao">Incluir servico</button>
                </div>
            </div>
        <?= form_close() ?>
    <?php endif; ?>
</div>
