<?php
$campo = function ($nome, $default = '') use ($registro) {
    return isset($registro[$nome]) && $registro[$nome] !== NULL ? $registro[$nome] : $default;
};

$dec = function ($valor) {
    if ($valor === NULL || $valor === '')
    {
        return '';
    }

    return number_format((float) $valor, 2, ',', '');
};

$dis = $pode_editar ? '' : 'disabled';
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>Valor Orcamento Nao Aprovado</h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('tabelas_basicas') ?>">Tabelas Basicas</a>
        </div>
    </div>

    <div class="painel-corpo">
        <p class="ajuda" style="margin-top:0">
            Parametros usados na emissao do orcamento e no rodape impresso.
            Origem no Access: tabela <strong>VALOR_ORC_NAPROV</strong> (registro unico).
        </p>

        <?php if ( ! $pode_editar): ?>
            <div class="alert alert-warning">Consulta somente leitura. Alteracao exige perfil admin ou gestor.</div>
        <?php endif; ?>

        <?= form_open('tabelas_basicas/parametros_salvar') ?>
            <div class="form-grid">
                <div class="campo terco">
                    <label for="valor">Valor por equipamento nao aprovado (R$) *</label>
                    <input type="text" name="valor" id="valor" inputmode="decimal" required
                           value="<?= html_escape($dec($campo('valor', 0))) ?>" <?= $dis ?>>
                </div>

                <div class="campo terco">
                    <label for="valor_taxa_perm">Taxa de permanencia diaria (R$)</label>
                    <input type="text" name="valor_taxa_perm" id="valor_taxa_perm" inputmode="decimal"
                           value="<?= html_escape($dec($campo('valor_taxa_perm'))) ?>" <?= $dis ?>>
                </div>

                <div class="campo terco">
                    <label for="prazo_ret_equip">Prazo para retirada do equipamento (dias)</label>
                    <input type="number" name="prazo_ret_equip" id="prazo_ret_equip" min="0" step="1"
                           value="<?= html_escape($campo('prazo_ret_equip')) ?>" <?= $dis ?>>
                </div>

                <div class="campo terco">
                    <label for="prazo_ini_envio_orc">Inicio do prazo de envio do orcamento (horas)</label>
                    <input type="number" name="prazo_ini_envio_orc" id="prazo_ini_envio_orc" min="0" step="1"
                           value="<?= html_escape($campo('prazo_ini_envio_orc')) ?>" <?= $dis ?>>
                </div>

                <div class="campo terco">
                    <label for="prazo_fim_envio_orc">Fim do prazo de envio do orcamento (horas)</label>
                    <input type="number" name="prazo_fim_envio_orc" id="prazo_fim_envio_orc" min="0" step="1"
                           value="<?= html_escape($campo('prazo_fim_envio_orc')) ?>" <?= $dis ?>>
                </div>

                <div class="campo terco">
                    <label for="dias_limite">Dias limite para aprovacao</label>
                    <input type="number" name="dias_limite" id="dias_limite" min="0" step="1"
                           value="<?= html_escape($campo('dias_limite')) ?>" <?= $dis ?>>
                </div>

                <div class="campo full">
                    <label for="texto_rodape">Texto de rodape do orcamento</label>
                    <textarea name="texto_rodape" id="texto_rodape" rows="12" <?= $dis ?>><?= html_escape($campo('texto_rodape')) ?></textarea>
                </div>
            </div>

            <?php if ($pode_editar): ?>
                <div class="form-acoes">
                    <button type="submit" class="botao">Gravar parametros</button>
                </div>
            <?php endif; ?>
        <?= form_close() ?>
    </div>
</div>
