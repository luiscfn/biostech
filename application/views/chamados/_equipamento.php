<?php
function _eq_valor($equipamento, $campo)
{
    return isset($equipamento[$campo]) ? (string) $equipamento[$campo] : '';
}

function _eq_check($equipamento, $campo)
{
    return isset($equipamento[$campo]) && bd_bool($equipamento[$campo]);
}
?>
<div class="painel-corpo">
    <?= form_open('chamados/equipamento/' . $registro['numero']) ?>

        <h4 class="secao-titulo">Identificacao</h4>

        <div class="form-grid">
            <div class="campo">
                <label for="equipamento_serie">Numero de serie</label>
                <input type="text" name="equipamento_serie" id="equipamento_serie" value="<?= html_escape(_eq_valor($equipamento, 'equipamento_serie')) ?>" maxlength="30">
            </div>

            <div class="campo">
                <label for="tipo_garantia_codigo">Tipo de garantia</label>
                <select name="tipo_garantia_codigo" id="tipo_garantia_codigo">
                    <option value="">(nao informado)</option>
                    <?php foreach ($dominios['tipo_garantia'] as $cod => $nome): ?>
                        <option value="<?= (int) $cod ?>" <?= (string) $cod === _eq_valor($equipamento, 'tipo_garantia_codigo') ? 'selected' : '' ?>><?= html_escape($nome) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="patrimonio">Patrimonio</label>
                <input type="text" name="patrimonio" id="patrimonio" value="<?= html_escape(_eq_valor($equipamento, 'patrimonio')) ?>" maxlength="25">
            </div>

            <div class="campo">
                <label for="voltagem">Voltagem</label>
                <input type="number" name="voltagem" id="voltagem" value="<?= html_escape(_eq_valor($equipamento, 'voltagem')) ?>">
            </div>

            <div class="campo">
                <label for="voltagem_saida">Voltagem de saida</label>
                <input type="number" name="voltagem_saida" id="voltagem_saida" value="<?= html_escape(_eq_valor($equipamento, 'voltagem_saida')) ?>">
            </div>

            <div class="campo">
                <label for="autorizada">Autorizada</label>
                <input type="number" name="autorizada" id="autorizada" value="<?= html_escape(_eq_valor($equipamento, 'autorizada')) ?>">
            </div>

            <div class="campo">
                <label for="nota_fiscal_chegada">Nota fiscal de chegada</label>
                <input type="text" name="nota_fiscal_chegada" id="nota_fiscal_chegada" value="<?= html_escape(_eq_valor($equipamento, 'nota_fiscal_chegada')) ?>" maxlength="20">
            </div>

            <div class="campo">
                <label for="solicitante">Solicitante</label>
                <input type="text" name="solicitante" id="solicitante" value="<?= html_escape(_eq_valor($equipamento, 'solicitante')) ?>" maxlength="50">
            </div>

            <div class="campo">
                <label for="contato">Contato</label>
                <input type="text" name="contato" id="contato" value="<?= html_escape(_eq_valor($equipamento, 'contato')) ?>" maxlength="50">
            </div>

            <div class="campo full">
                <label for="local_atendimento">Local de atendimento</label>
                <input type="text" name="local_atendimento" id="local_atendimento" value="<?= html_escape(_eq_valor($equipamento, 'local_atendimento')) ?>" maxlength="50">
            </div>

            <div class="campo full">
                <label for="acessorios">Acessorios</label>
                <textarea name="acessorios" id="acessorios"><?= html_escape(_eq_valor($equipamento, 'acessorios')) ?></textarea>
            </div>

            <div class="campo full">
                <label for="lista_equipamento">Lista de equipamentos</label>
                <textarea name="lista_equipamento" id="lista_equipamento"><?= html_escape(_eq_valor($equipamento, 'lista_equipamento')) ?></textarea>
            </div>

            <div class="campo full">
                <label class="check">
                    <input type="checkbox" name="recolhido" value="1" <?= _eq_check($equipamento, 'recolhido') ? 'checked' : '' ?>>
                    Equipamento recolhido
                </label>
            </div>
        </div>

        <h4 class="secao-titulo">Checklist de bancada</h4>

        <div class="form-grid">
            <div class="campo terco">
                <label for="rede_fn">Rede FN</label>
                <input type="text" name="rede_fn" id="rede_fn" value="<?= html_escape(_eq_valor($equipamento, 'rede_fn')) ?>" maxlength="15">
            </div>
            <div class="campo terco">
                <label for="rede_ft">Rede FT</label>
                <input type="text" name="rede_ft" id="rede_ft" value="<?= html_escape(_eq_valor($equipamento, 'rede_ft')) ?>" maxlength="15">
            </div>
            <div class="campo terco">
                <label for="rede_nt">Rede NT</label>
                <input type="text" name="rede_nt" id="rede_nt" value="<?= html_escape(_eq_valor($equipamento, 'rede_nt')) ?>" maxlength="15">
            </div>

            <div class="campo">
                <label for="estab_marca">Estabilizador - marca</label>
                <input type="text" name="estab_marca" id="estab_marca" value="<?= html_escape(_eq_valor($equipamento, 'estab_marca')) ?>" maxlength="20">
            </div>
            <div class="campo">
                <label for="estab_potencia">Estabilizador - potencia</label>
                <input type="text" name="estab_potencia" id="estab_potencia" value="<?= html_escape(_eq_valor($equipamento, 'estab_potencia')) ?>" maxlength="10">
            </div>
            <div class="campo">
                <label for="estab_kva">Estabilizador - KVA</label>
                <input type="text" name="estab_kva" id="estab_kva" value="<?= html_escape(_eq_valor($equipamento, 'estab_kva')) ?>" maxlength="10">
            </div>
            <div class="campo">
                <label for="def_outros">Outros defeitos</label>
                <input type="text" name="def_outros" id="def_outros" value="<?= html_escape(_eq_valor($equipamento, 'def_outros')) ?>" maxlength="50">
            </div>

            <div class="campo full">
                <div class="check-grid">
                    <?php foreach (array(
                        'estabilizador'       => 'Possui estabilizador',
                        'def_virus'           => 'Virus',
                        'def_software'        => 'Defeito de software',
                        'def_disquete'        => 'Drive de disquete',
                        'def_cd_dvd'          => 'Drive de CD/DVD',
                        'def_tensao_imp'      => 'Tensao imprecisa',
                        'def_cabos_desc'      => 'Cabos desconectados',
                        'def_nao_canc_chamado'=> 'Nao cancelar chamado',
                        'def_abert_equip'     => 'Equipamento aberto',
                    ) as $campo => $rotulo): ?>
                        <label class="check">
                            <input type="checkbox" name="<?= $campo ?>" value="1" <?= _eq_check($equipamento, $campo) ? 'checked' : '' ?>>
                            <?= html_escape($rotulo) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="form-acoes">
            <button type="submit" class="botao">Salvar equipamento</button>
        </div>

    <?= form_close() ?>
</div>
