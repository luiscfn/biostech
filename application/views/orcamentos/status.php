<?php
$pode_gerar = array();

if (empty($registro['chamado_numero'])) { $pode_gerar[] = 'Sem chamado vinculado: OK'; }
else { $pode_gerar[] = 'ERRO: ja existe o chamado ' . $registro['chamado_numero']; }

if ((int) $registro['situacao'] >= 4) { $pode_gerar[] = 'Situacao concluido/encerrado: OK'; }
else { $pode_gerar[] = 'ERRO: situacao deve ser Concluido ou Encerrado'; }

if ((int) $registro['resultado'] === 1) { $pode_gerar[] = 'Resultado aprovado: OK'; }
else { $pode_gerar[] = 'ERRO: resultado deve ser Aprovado'; }

if ( ! empty($registro['data_envio'])) { $pode_gerar[] = 'Data de envio: OK'; }
else { $pode_gerar[] = 'ERRO: informe a data de envio'; }

if ( ! empty($registro['data_aprovacao'])) { $pode_gerar[] = 'Data de aprovacao: OK'; }
else { $pode_gerar[] = 'ERRO: informe a data de aprovacao'; }

if ( ! empty($registro['cliente_id'])) { $pode_gerar[] = 'Cliente: OK'; }
else { $pode_gerar[] = 'ERRO: o orcamento nao possui cliente'; }
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>Status do orcamento <?= (int) $registro['numero'] ?></h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('orcamentos/ver/' . $registro['numero']) ?>">Voltar</a>
        </div>
    </div>

    <div class="painel-corpo">
        <?= form_open('orcamentos/status/' . $registro['numero']) ?>

            <div class="form-grid">
                <div class="campo" style="grid-column:span 3">
                    <label for="situacao">Situacao</label>
                    <select name="situacao" id="situacao">
                        <option value="">-</option>
                        <?php foreach ($situacoes as $s): ?>
                            <option value="<?= (int) $s['codigo'] ?>"
                                <?= (int) $registro['situacao'] === (int) $s['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($s['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="ajuda">1 Aberto, 2 Enviado, 3 Pendente, 4 Concluido, 5 Encerrado.</div>
                </div>

                <div class="campo" style="grid-column:span 4">
                    <label for="resultado">Resultado</label>
                    <select name="resultado" id="resultado">
                        <option value="">-</option>
                        <?php foreach ($resultados as $r): ?>
                            <option value="<?= (int) $r['codigo'] ?>"
                                <?= (int) $registro['resultado'] === (int) $r['codigo'] ? 'selected' : '' ?>>
                                <?= html_escape($r['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="ajuda">Ao marcar Aprovado a data de aprovacao e preenchida se estiver vazia.</div>
                </div>

                <div class="campo" style="grid-column:span 5">
                    <div class="campos-data">
                        <div class="campo-data">
                            <label for="data_envio">Data de envio</label>
                            <input type="date" name="data_envio" id="data_envio" value="<?= html_escape($registro['data_envio']) ?>">
                        </div>
                        <div class="campo-data">
                            <label for="data_aprovacao">Data de aprovacao</label>
                            <input type="date" name="data_aprovacao" id="data_aprovacao" value="<?= html_escape($registro['data_aprovacao']) ?>">
                        </div>
                    </div>
                </div>

                <div class="campo full" id="bloco-motivo" style="display:none">
                    <label for="motivo_cancelamento">Motivo do cancelamento *</label>
                    <textarea name="motivo_cancelamento" id="motivo_cancelamento" rows="3"><?= html_escape($registro['motivo_cancelamento']) ?></textarea>
                    <div class="ajuda">Obrigatorio para os resultados Cancelado pelo cliente e Cancelado pela Biostech.</div>
                </div>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao">Salvar status</button>
                <a class="botao secundario" href="<?= site_url('orcamentos/ver/' . $registro['numero']) ?>">Cancelar</a>
            </div>
        <?= form_close() ?>

        <hr class="divisor">
        <h4 class="secao-titulo">Requisitos para gerar chamado</h4>

        <ul class="lista-eventos">
            <?php foreach ($pode_gerar as $linha): ?>
                <li><?= html_escape($linha) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<script>
(function () {
    var resultado = document.getElementById('resultado');
    var bloco     = document.getElementById('bloco-motivo');

    function atualizar() {
        var valor = resultado.value;
        bloco.style.display = (valor === '6' || valor === '7') ? '' : 'none';
    }

    resultado.addEventListener('change', atualizar);
    atualizar();
})();
</script>
