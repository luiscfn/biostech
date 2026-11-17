<?php
$edicao = ! empty($registro['numero']);
$data   = $edicao ? $registro['data'] : date('Y-m-d');

$v = function ($campo, $padrao = '') use ($edicao, $registro) {
    return $edicao && isset($registro[$campo]) && $registro[$campo] !== NULL ? $registro[$campo] : $padrao;
};

$num = function ($campo, $dec = 2) use ($v) {
    $valor = $v($campo, NULL);
    return $valor === NULL ? '' : number_format((float) $valor, $dec, ',', '.');
};

$dias = array(
    'segunda' => 'Segunda',
    'terca'   => 'Terca',
    'quarta'  => 'Quarta',
    'quinta'  => 'Quinta',
    'sexta'   => 'Sexta',
    'sabado'  => 'Sabado',
    'domingo' => 'Domingo',
);
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>
            <?= $edicao ? 'Editar dimensionamento ' . (int) $registro['numero'] : 'Novo dimensionamento' ?>
        </h3>

        <div class="acoes-ficha">
            <?php if ($edicao): ?>
                <a class="botao secundario pequeno" href="<?= site_url('orcamentos/dimensionamento/' . $registro['numero']) ?>">Ver ficha</a>
            <?php endif; ?>
            <a class="botao secundario pequeno" href="<?= site_url('orcamentos/dimensionamentos') ?>">Voltar</a>
        </div>
    </div>

    <div class="painel-corpo">
        <?= form_open($edicao ? 'orcamentos/dimensionamento_editar/' . $registro['numero'] : 'orcamentos/dimensionamento_novo') ?>

            <h4 class="secao-titulo">Identificacao</h4>

            <div class="form-grid">
                <div class="campo terco">
                    <label for="data">Data *</label>
                    <input type="date" name="data" id="data" value="<?= html_escape($data) ?>" required>
                </div>

                <div class="campo terco">
                    <label for="chamado_numero">Chamado / OS</label>
                    <input type="text" name="chamado_numero" id="chamado_numero" maxlength="16"
                           value="<?= html_escape($v('chamado_numero')) ?>">
                </div>

                <div class="campo terco">
                    <label for="tecnico_id">Tecnico responsavel</label>
                    <select name="tecnico_id" id="tecnico_id">
                        <option value="">-</option>
                        <?php foreach ($funcionarios as $f): ?>
                            <option value="<?= (int) $f['funcionario_id'] ?>"
                                <?= (int) $v('tecnico_id', 0) === (int) $f['funcionario_id'] ? 'selected' : '' ?>>
                                <?= html_escape($f['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo full">
                    <label for="cliente_busca">Cliente</label>
                    <div class="autocomplete" data-url="<?= site_url('orcamentos/buscar_clientes') ?>">
                        <input type="text" id="cliente_busca" class="autocomplete-busca"
                               placeholder="Digite o nome, fantasia ou CPF/CNPJ..."
                               value="<?= $edicao ? html_escape($registro['cliente_nome']) : '' ?>" autocomplete="off">
                        <input type="hidden" name="cliente_id" id="cliente_id"
                               value="<?= $edicao ? (int) $registro['cliente_id'] : '' ?>">
                        <div class="autocomplete-lista"></div>
                    </div>
                </div>

                <div class="campo terco">
                    <label for="contato">Contato</label>
                    <input type="text" name="contato" id="contato" maxlength="30" value="<?= html_escape($v('contato')) ?>">
                </div>
                <div class="campo terco">
                    <label for="tipo_contato">Tipo de contato</label>
                    <input type="text" name="tipo_contato" id="tipo_contato" maxlength="15" value="<?= html_escape($v('tipo_contato')) ?>">
                </div>
                <div class="campo terco">
                    <label for="setor">Setor</label>
                    <input type="text" name="setor" id="setor" maxlength="30" value="<?= html_escape($v('setor')) ?>">
                </div>
                <div class="campo terco">
                    <label for="email">E-mail</label>
                    <input type="text" name="email" id="email" maxlength="100" value="<?= html_escape($v('email')) ?>">
                </div>
                <div class="campo terco">
                    <label for="solicitante">Solicitante</label>
                    <input type="text" name="solicitante" id="solicitante" maxlength="50" value="<?= html_escape($v('solicitante')) ?>">
                </div>
                <div class="campo terco">
                    <label for="qtd_dias">Dias previstos</label>
                    <input type="number" name="qtd_dias" id="qtd_dias" min="0" step="1"
                           value="<?= $v('qtd_dias') !== '' ? (int) $v('qtd_dias') : '' ?>">
                </div>

                <div class="campo full">
                    <label for="local_instalacao">Local de instalacao</label>
                    <textarea name="local_instalacao" id="local_instalacao" rows="2"><?= html_escape($v('local_instalacao')) ?></textarea>
                </div>

                <div class="campo terco">
                    <label for="qtd_tecnicos">Qtd. tecnicos</label>
                    <input type="number" name="qtd_tecnicos" id="qtd_tecnicos" min="0" step="1"
                           value="<?= $v('qtd_tecnicos') !== '' ? (int) $v('qtd_tecnicos') : '' ?>">
                </div>
                <div class="campo terco">
                    <label for="qtd_comerciais">Qtd. comerciais</label>
                    <input type="text" name="qtd_comerciais" id="qtd_comerciais" inputmode="decimal" value="<?= $num('qtd_comerciais') ?>">
                </div>
                <div class="campo terco">
                    <label for="qtd_nao_comerciais">Qtd. nao comerciais</label>
                    <input type="text" name="qtd_nao_comerciais" id="qtd_nao_comerciais" inputmode="decimal" value="<?= $num('qtd_nao_comerciais') ?>">
                </div>
            </div>

            <hr class="divisor">
            <h4 class="secao-titulo">Levantamento tecnico</h4>

            <div class="form-grid">
                <div class="campo metade">
                    <label for="que_funciona">O que funciona</label>
                    <textarea name="que_funciona" id="que_funciona" rows="3"><?= html_escape($v('que_funciona')) ?></textarea>
                </div>
                <div class="campo metade">
                    <label for="quer_fazer">O que quer fazer</label>
                    <textarea name="quer_fazer" id="quer_fazer" rows="3"><?= html_escape($v('quer_fazer')) ?></textarea>
                </div>
                <div class="campo metade">
                    <label for="dificuldades">Dificuldades</label>
                    <textarea name="dificuldades" id="dificuldades" rows="3"><?= html_escape($v('dificuldades')) ?></textarea>
                </div>
                <div class="campo metade">
                    <label for="material_precisa">Material necessario</label>
                    <textarea name="material_precisa" id="material_precisa" rows="3"><?= html_escape($v('material_precisa')) ?></textarea>
                </div>
                <div class="campo metade">
                    <label for="providenciar">Providenciar</label>
                    <textarea name="providenciar" id="providenciar" rows="3"><?= html_escape($v('providenciar')) ?></textarea>
                </div>
                <div class="campo metade">
                    <label for="observacao">Observacoes</label>
                    <textarea name="observacao" id="observacao" rows="3"><?= html_escape($v('observacao')) ?></textarea>
                </div>
            </div>

            <hr class="divisor">
            <h4 class="secao-titulo">Medidas e caracteristicas do local</h4>

            <div class="form-grid">
                <div class="campo terco">
                    <label for="altura">Altura (m)</label>
                    <input type="text" name="altura" id="altura" inputmode="decimal" value="<?= $num('altura') ?>">
                </div>
                <div class="campo terco">
                    <label for="comprimento_muro">Comprimento do muro (m)</label>
                    <input type="text" name="comprimento_muro" id="comprimento_muro" inputmode="decimal" value="<?= $num('comprimento_muro') ?>">
                </div>
                <div class="campo terco">
                    <label for="frente">Frente (m)</label>
                    <input type="text" name="frente" id="frente" inputmode="decimal" value="<?= $num('frente') ?>">
                </div>
                <div class="campo terco">
                    <label for="fundo">Fundo (m)</label>
                    <input type="text" name="fundo" id="fundo" inputmode="decimal" value="<?= $num('fundo') ?>">
                </div>
                <div class="campo terco">
                    <label for="lado_direito">Lado direito (m)</label>
                    <input type="text" name="lado_direito" id="lado_direito" inputmode="decimal" value="<?= $num('lado_direito') ?>">
                </div>
                <div class="campo terco">
                    <label for="lado_esquerdo">Lado esquerdo (m)</label>
                    <input type="text" name="lado_esquerdo" id="lado_esquerdo" inputmode="decimal" value="<?= $num('lado_esquerdo') ?>">
                </div>

                <div class="campo">
                    <label class="check"><input type="checkbox" name="rebocado" value="S" <?= bd_bool($v('rebocado', FALSE)) ? 'checked' : '' ?>> Rebocado</label>
                </div>
                <div class="campo">
                    <label class="check"><input type="checkbox" name="cerca_arame" value="S" <?= bd_bool($v('cerca_arame', FALSE)) ? 'checked' : '' ?>> Cerca de arame</label>
                </div>
                <div class="campo">
                    <label class="check"><input type="checkbox" name="telhado" value="S" <?= bd_bool($v('telhado', FALSE)) ? 'checked' : '' ?>> Telhado</label>
                </div>
                <div class="campo">
                    <label class="check"><input type="checkbox" name="arvore" value="S" <?= bd_bool($v('arvore', FALSE)) ? 'checked' : '' ?>> Arvore</label>
                </div>
                <div class="campo">
                    <label class="check"><input type="checkbox" name="cinta_cimento" value="S" <?= bd_bool($v('cinta_cimento', FALSE)) ? 'checked' : '' ?>> Cinta de cimento</label>
                </div>
                <div class="campo">
                    <label class="check"><input type="checkbox" name="pega_ladrao" value="S" <?= bd_bool($v('pega_ladrao', FALSE)) ? 'checked' : '' ?>> Pega ladrao</label>
                </div>
            </div>

            <hr class="divisor">
            <h4 class="secao-titulo">Percentuais</h4>
            <p class="ajuda" style="margin-top:-4px">
                Os percentuais sao usados no calculo do total de venda do dimensionamento e copiados para o
                orcamento comercial gerado.
            </p>

            <div class="form-grid">
                <div class="campo terco">
                    <label for="percentual_mao_obra">Mao de obra (%)</label>
                    <input type="text" name="percentual_mao_obra" id="percentual_mao_obra" inputmode="decimal" value="<?= $num('percentual_mao_obra', 4) ?>">
                </div>
                <div class="campo terco">
                    <label for="percentual_mat_fixacao">Material de fixacao (%)</label>
                    <input type="text" name="percentual_mat_fixacao" id="percentual_mat_fixacao" inputmode="decimal" value="<?= $num('percentual_mat_fixacao', 4) ?>">
                </div>
                <div class="campo terco">
                    <label for="percentual_nota_fiscal">Nota fiscal (%)</label>
                    <input type="text" name="percentual_nota_fiscal" id="percentual_nota_fiscal" inputmode="decimal" value="<?= $num('percentual_nota_fiscal', 4) ?>">
                </div>
                <div class="campo terco">
                    <label for="percentual_acrescimo_custo">Acrescimo de custo (%)</label>
                    <input type="text" name="percentual_acrescimo_custo" id="percentual_acrescimo_custo" inputmode="decimal" value="<?= $num('percentual_acrescimo_custo', 4) ?>">
                </div>
            </div>

            <?php if ($edicao): ?>
                <div class="detalhes" style="margin-top:10px">
                    <div class="detalhe">
                        <div class="rotulo">Mao de obra</div>
                        <div class="valor"><?= moeda_br($registro['mao_obra']) ?></div>
                    </div>
                    <div class="detalhe">
                        <div class="rotulo">Material de fixacao</div>
                        <div class="valor"><?= moeda_br($registro['material_fixacao']) ?></div>
                    </div>
                    <div class="detalhe">
                        <div class="rotulo">Nota fiscal</div>
                        <div class="valor"><?= moeda_br($registro['nota_fiscal']) ?></div>
                    </div>
                    <div class="detalhe">
                        <div class="rotulo">Acrescimo de custo</div>
                        <div class="valor"><?= moeda_br($registro['acrescimo_custo']) ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <hr class="divisor">
            <h4 class="secao-titulo">Cronograma semanal</h4>

            <div class="tabela-wrap">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th style="width:130px">Dia</th>
                            <th class="centro" style="width:90px">Trabalha</th>
                            <th class="num" style="width:140px">Inicio (h)</th>
                            <th class="num" style="width:140px">Fim (h)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dias as $chave => $rotulo): ?>
                            <tr>
                                <td><?= html_escape($rotulo) ?></td>
                                <td class="centro">
                                    <input type="checkbox" name="<?= $chave ?>" value="S"
                                        <?= bd_bool($v($chave, FALSE)) ? 'checked' : '' ?>>
                                </td>
                                <td class="num">
                                    <input type="text" name="<?= $chave ?>_inicio" inputmode="decimal"
                                           value="<?= $num($chave . '_inicio') ?>" style="width:110px;text-align:right">
                                </td>
                                <td class="num">
                                    <input type="text" name="<?= $chave ?>_fim" inputmode="decimal"
                                           value="<?= $num($chave . '_fim') ?>" style="width:110px;text-align:right">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao"><?= $edicao ? 'Salvar alteracoes' : 'Criar dimensionamento' ?></button>
                <a class="botao secundario" href="<?= site_url($edicao ? 'orcamentos/dimensionamento/' . $registro['numero'] : 'orcamentos/dimensionamentos') ?>">Cancelar</a>
            </div>

        <?= form_close() ?>
    </div>
</div>

<script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
