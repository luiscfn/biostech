<div class="painel-corpo">
    <?php if ($pode_editar): ?>
        <div class="bloco-inclusao">
            <h4 class="secao-titulo destaque">Registrar contato</h4>

            <?= form_open('orcamentos/adicionar_contato/' . $registro['numero']) ?>
                <div class="form-grid">
                    <div class="campo terco">
                        <label for="contato_data">Data</label>
                        <input type="date" name="data_mov" id="contato_data" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="campo terco">
                        <label for="contato_hora">Hora</label>
                        <input type="time" name="hora_mov" id="contato_hora" value="<?= date('H:i') ?>">
                    </div>
                    <div class="campo terco">
                        <label for="contato_assunto">Assunto</label>
                        <input type="text" name="assunto" id="contato_assunto" maxlength="80">
                    </div>

                    <div class="campo terco">
                        <label for="contato_funcionario">Funcionario</label>
                        <select name="funcionario_id" id="contato_funcionario">
                            <option value="">-</option>
                            <?php foreach ($funcionarios as $f): ?>
                                <option value="<?= (int) $f['funcionario_id'] ?>"><?= html_escape($f['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo terco">
                        <label for="contato_nome">Pessoa de contato</label>
                        <input type="text" name="contato" id="contato_nome" maxlength="50">
                    </div>
                    <div class="campo terco">
                        <label for="contato_proximo">Proximo contato</label>
                        <div class="campos-data">
                            <input type="date" name="data_proximo_contato" id="contato_proximo">
                            <input type="time" name="hora_proximo_contato" id="contato_proximo_hora">
                        </div>
                    </div>

                    <div class="campo full">
                        <label for="contato_descricao">Descricao</label>
                        <textarea name="descricao" id="contato_descricao" rows="3"></textarea>
                    </div>
                </div>

                <div class="form-acoes">
                    <button type="submit" class="botao">Registrar contato</button>
                </div>
            <?= form_close() ?>
        </div>
    <?php endif; ?>

    <div class="tabela-wrap tabela-rolagem" id="tabela-contatos">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:95px">Data</th>
                    <th style="width:70px">Hora</th>
                    <th style="width:170px">Assunto</th>
                    <th style="width:160px">Funcionario</th>
                    <th style="width:140px">Contato</th>
                    <th>Descricao</th>
                    <th style="width:150px">Proximo contato</th>
                    <?php if ($pode_editar): ?>
                        <th class="centro" style="width:80px"></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contatos)): ?>
                    <tr><td colspan="<?= $pode_editar ? 8 : 7 ?>" class="vazio">Nenhum contato registrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($contatos as $contato): ?>
                        <tr>
                            <td><?= data_br($contato['data_mov']) ?></td>
                            <td><?= $contato['hora_mov'] ? html_escape(substr((string) $contato['hora_mov'], 0, 5)) : '-' ?></td>
                            <td><?= html_escape($contato['assunto'] ? $contato['assunto'] : '-') ?></td>
                            <td><?= html_escape($contato['funcionario_nome'] ? $contato['funcionario_nome'] : '-') ?></td>
                            <td><?= html_escape($contato['contato'] ? $contato['contato'] : '-') ?></td>
                            <td><?= nl2br(html_escape($contato['descricao'] ? $contato['descricao'] : '-')) ?></td>
                            <td>
                                <?php if ( ! empty($contato['data_proximo_contato'])): ?>
                                    <?= data_br($contato['data_proximo_contato']) ?>
                                    <?= $contato['hora_proximo_contato'] ? ' as ' . html_escape(substr((string) $contato['hora_proximo_contato'], 0, 5)) : '' ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <?php if ($pode_editar): ?>
                                <td class="centro">
                                    <?= form_open('orcamentos/excluir_contato/' . $registro['numero'] . '/' . $contato['id'],
                                        array('onsubmit' => "return confirm('Excluir o contato?')")) ?>
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
</div>

<script>
(function () {
    // Mostra 6 contatos por vez; o restante fica na barra de rolagem vertical.
    var VISIVEIS = 6;
    var wrap = document.getElementById('tabela-contatos');
    if ( ! wrap) { return; }

    var linhas = wrap.querySelectorAll('tbody tr');
    if (linhas.length <= VISIVEIS) { return; }

    function ajustar() {
        wrap.style.maxHeight = '';

        var topo = wrap.getBoundingClientRect().top;
        var fim  = linhas[VISIVEIS - 1].getBoundingClientRect().bottom;

        var alvo = Math.ceil(fim - topo + 1);
        wrap.style.maxHeight = alvo + 'px';

        // A barra de rolagem horizontal reduz a altura util: compensa para a
        // ultima linha nao ficar cortada.
        var sobra = linhas[VISIVEIS - 1].getBoundingClientRect().bottom
                  - (wrap.getBoundingClientRect().top + wrap.clientHeight);

        if (sobra > 0) {
            wrap.style.maxHeight = (alvo + Math.ceil(sobra) + 1) + 'px';
        }
    }

    ajustar();
    window.addEventListener('load', ajustar);
})();
</script>
