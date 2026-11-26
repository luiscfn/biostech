<?php
$editando = ! empty($registro);
$id       = $editando ? (int) $registro['id'] : 0;

$valor = function ($campo, $padrao = '') use ($registro, $editando) {
    if (isset($_POST[$campo]))
    {
        return (string) $_POST[$campo];
    }

    return $editando && array_key_exists($campo, $registro) && $registro[$campo] !== NULL
        ? (string) $registro[$campo] : $padrao;
};

$num = function ($campo, $dec = 2) use ($valor) {
    $v = $valor($campo, '');

    if ($v === '')
    {
        return '';
    }

    return number_format((float) str_replace(',', '.', $v), $dec, ',', '.');
};

$ativo     = $editando ? (bd_bool($registro['ativo']) ? 'S' : 'N') : set_value('ativo', 'S');
$situacao  = $editando && $registro['situacao'] !== NULL ? (string) $registro['situacao'] : set_value('situacao', '');
$grupo     = $editando ? (string) $registro['grupo'] : set_value('grupo', '');
$subgrupo  = $editando ? (string) $registro['subgrupo'] : set_value('subgrupo', '');
$item      = $editando ? (string) $registro['item'] : set_value('item', '');
$estante   = $editando && $registro['estante'] !== NULL ? (string) $registro['estante'] : set_value('estante', '');
$prateleira = $editando && $registro['prateleira'] !== NULL ? (string) $registro['prateleira'] : set_value('prateleira', '');
?>
<style>
.bloco-pf { border:1px solid #9aa8bd; background:#e9edf6; padding:10px 12px; margin-bottom:10px; }
.titulo-pf { background:#17cfd0; color:#123; font-weight:700; text-align:center; padding:5px; margin:-10px -12px 10px; text-transform:uppercase; font-size:13px; }
.linha-pf { display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:7px; }
.campo-pf { display:flex; align-items:center; gap:6px; flex:0 0 auto; }
.campo-pf > label { width:110px; text-align:right; font-size:12px; color:#2a3a55; font-weight:600; white-space:nowrap; }
.campo-pf > label.pf-lbl-long { width:165px; }
.campo-pf > label.pf-lbl-left { text-align:left; }
.pf-cadastro { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:10px 40px; margin-bottom:7px; }
.pf-grupo { display:grid; align-items:center; gap:7px 6px; }
.pf-grupo-principal { flex:0 1 820px; max-width:100%; grid-template-columns:max-content minmax(240px,1fr); }
.pf-grupo-lateral { flex:0 0 auto; grid-template-columns:max-content 120px; }
.pf-grupo > label { font-size:12px; color:#2a3a55; font-weight:600; white-space:nowrap; text-align:left; }
.pf-grupo-lateral > label { text-align:left; font-size:13px; }
.pf-grupo input, .pf-grupo select { border:1px solid #7f90a8; background:#fff; padding:4px 6px; font-size:12px; border-radius:2px; width:120px; height:24px; box-sizing:border-box; }
.pf-grupo-principal input { width:100%; }
.pf-grupo input[readonly] { background:#d9dde5; }
.pf-w120 { width:120px !important; }
.pf-centro { text-align:center; }
.pf-dados { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:10px 50px; margin-bottom:7px; }
.pf-dados-esq { display:grid; grid-template-columns:70px 320px; align-items:center; gap:7px 6px; }
.pf-dados-dir { display:grid; grid-template-columns:max-content 120px; align-items:center; gap:7px 6px; }
.pf-dados label { font-size:12px; color:#2a3a55; font-weight:600; white-space:nowrap; }
.pf-dados-esq label { text-align:left; }
.pf-dados-dir label { text-align:right; }
.pf-dados select, .pf-dados input { border:1px solid #7f90a8; background:#fff; padding:4px 6px; font-size:12px; border-radius:2px; height:24px; box-sizing:border-box; }
.pf-dados-esq select { width:320px; }
.pf-dados-dir input { width:120px; text-align:right; font-weight:700; }
.pf-dados-aplicacao { display:grid; grid-template-columns:70px minmax(240px,1fr); align-items:center; gap:6px; margin-bottom:7px; }
.pf-dados-aplicacao label { font-size:12px; color:#2a3a55; font-weight:600; white-space:nowrap; }
.pf-dados-aplicacao input { width:100%; border:1px solid #7f90a8; background:#fff; padding:4px 6px; font-size:12px; border-radius:2px; height:24px; box-sizing:border-box; }
.pf-dados-acoes { display:flex; flex-direction:column; flex:0 0 auto; gap:8px; align-items:stretch; padding-top:1px; }
.pf-dados-acoes .botao { white-space:normal; text-align:center; }
.linha-auditoria { flex-wrap:nowrap; gap:14px; overflow-x:auto; }
.linha-auditoria .campo-pf > label { width:auto; text-align:left; }
.linha-auditoria input.pf-usuario { width:210px; }
.linha-auditoria input.pf-data { width:135px; }
.linha-auditoria .botao { white-space:nowrap; }
.esp-cadastro { display:flex; flex-wrap:wrap; align-items:flex-end; gap:10px 16px; margin-bottom:16px; }
.esp-campo { display:flex; flex-direction:column; gap:4px; flex:0 0 auto; }
.esp-campo > label { font-size:12px; color:#2a3a55; font-weight:600; white-space:nowrap; text-align:left; }
.esp-campo input { border:1px solid #7f90a8; background:#fff; padding:6px 8px; font-size:12px; border-radius:4px; height:30px; box-sizing:border-box; }
.esp-campo-cliente { flex:1 1 420px; min-width:320px; }
.esp-campo-cliente .autocomplete { width:100%; }
.esp-campo-cliente .autocomplete-busca { width:100%; }
.esp-campo-pequeno input { width:120px; }
.esp-campo-botao .botao { height:30px; }
.bloco-especiais { border:1px solid #c9d5e4; border-radius:6px; background:#fff; padding:12px 14px; margin:0 0 12px; }
.bloco-especiais > h3 { margin:0 0 10px; padding-bottom:6px; border-bottom:2px solid var(--azul); color:var(--azul); font-size:13px; font-weight:700; }
.campo-pf input[type=text], .campo-pf input[type=number], .campo-pf select, .campo-pf textarea { border:1px solid #7f90a8; background:#fff; padding:4px 6px; font-size:12px; border-radius:2px; }
.campo-pf input[readonly] { background:#d9dde5; }
.pf-largo { flex:1 1 360px; min-width:260px; }
.pf-largo input, .pf-largo textarea { flex:1; width:100%; min-width:200px; }
.pf-medio { width:180px; }
.pf-igual { width:220px; }
.pf-curto { width:80px; }
.pf-pequeno { width:120px; }
.pf-verde { background:#39d353 !important; color:#000; font-weight:700; text-align:center; }
.pf-colunas { display:flex; gap:14px; flex-wrap:wrap; }
.pf-colunas > div:first-child { flex:1 1 520px; }
.pf-side { display:flex; flex-direction:column; gap:8px; min-width:160px; }
.pf-side .botao { white-space:normal; text-align:center; }
.pf-acoes { display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
</style>

<div class="painel produto-form">
    <div class="painel-cabecalho">
        <h3><?= $editando ? 'Produto ' . $id : 'Novo produto' ?></h3>
        <div>
            <span class="badge info">Ultimo cadastrado: <?= (int) $ultimo ?></span>
            <?php if ($editando): ?>
                <?php if (bd_bool($registro['ativo'])): ?>
                    <span class="badge ok">Ativo</span>
                <?php else: ?>
                    <span class="badge off">Inativo</span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="painel-corpo">
        <?= form_open($editando ? 'produtos/ver/' . $id : 'produtos/novo', array('id' => 'form-produto')) ?>
            <button type="submit" style="display:none" tabindex="-1" aria-hidden="true"></button>

            <div class="bloco-pf">
                <div class="titulo-pf">Produtos</div>

                <div class="pf-cadastro">
                    <div class="pf-grupo pf-grupo-principal">
                        <label for="pf_descricao">Descricao:</label>
                        <input type="text" name="descricao" id="pf_descricao" maxlength="120" autofocus
                               value="<?= html_escape($valor('descricao')) ?>">

                        <label for="pf_cor">Cor:</label>
                        <input type="text" name="cor" id="pf_cor" maxlength="100" value="<?= html_escape($valor('cor')) ?>">

                        <label for="pf_tamanho">Tamanho:</label>
                        <input type="text" name="tamanho" id="pf_tamanho" maxlength="100" value="<?= html_escape($valor('tamanho')) ?>">

                        <label for="pf_capacidade">Capacidade:</label>
                        <input type="text" name="capacidade" id="pf_capacidade" maxlength="100" value="<?= html_escape($valor('capacidade')) ?>">

                        <label for="pf_marca">Marca:</label>
                        <input type="text" name="marca" id="pf_marca" maxlength="100" value="<?= html_escape($valor('marca')) ?>">

                        <label for="pf_referencia">Referencia:</label>
                        <input type="text" name="referencia" id="pf_referencia" maxlength="100" value="<?= html_escape($valor('referencia')) ?>">
                    </div>

                    <div class="pf-grupo pf-grupo-lateral">
                        <label for="pf_codigo">Codigo:</label>
                        <input type="text" name="id" id="pf_codigo" class="pf-w120" maxlength="15"
                               value="<?= html_escape($editando ? (string) $id : (string) $proximo_codigo) ?>"
                               <?= $editando ? 'readonly' : '' ?>>

                        <label for="pf_unidade">Unidade:</label>
                        <input type="text" name="unidade" id="pf_unidade" class="pf-w120" maxlength="10" value="<?= html_escape($valor('unidade')) ?>">

                        <label for="pf_ativo">Ativo:</label>
                        <select name="ativo" id="pf_ativo" class="pf-w120">
                            <option value="S" <?= $ativo === 'S' ? 'selected' : '' ?>>S</option>
                            <option value="N" <?= $ativo === 'N' ? 'selected' : '' ?>>N</option>
                        </select>

                        <label for="pf_situacao">Situacao:</label>
                        <select name="situacao" id="pf_situacao" class="pf-w120">
                            <option value="" <?= $situacao === '' ? 'selected' : '' ?>>NAO INFORMADO</option>
                            <option value="1" <?= $situacao === '1' ? 'selected' : '' ?>>NOVO</option>
                            <option value="2" <?= $situacao === '2' ? 'selected' : '' ?>>USADO</option>
                        </select>

                        <label>Estoque Atual</label>
                        <input type="text" class="pf-verde pf-w120" value="<?= (int) $saldo ?>" readonly tabindex="-1">

                        <label for="pf_estoque_minimo">Estoque Minimo</label>
                        <input type="text" name="estoque_minimo" id="pf_estoque_minimo" class="pf-w120 pf-centro"
                               value="<?= html_escape($num('estoque_minimo', 0)) ?>">
                    </div>
                </div>
                <?= form_error('descricao') ?>
                <input type="hidden" name="codigo" value="<?= html_escape($valor('codigo')) ?>">
            </div>

            <div class="bloco-pf" id="bloco-dados-gerais">
                <div class="titulo-pf">Dados gerais do produto</div>

                <div class="pf-dados">
                    <div class="pf-dados-esq">
                        <label for="pf_grupo">Grupo :</label>
                        <select name="grupo" id="pf_grupo">
                            <option value="">-</option>
                            <?php foreach ($grupos as $g): ?>
                                <option value="<?= html_escape($g['codigo']) ?>" <?= (string) $g['codigo'] === $grupo ? 'selected' : '' ?>>
                                    <?= html_escape($g['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="pf_subgrupo">Sub-Grupo :</label>
                        <select name="subgrupo" id="pf_subgrupo">
                            <option value="">-</option>
                            <?php foreach ($subgrupos as $sg): ?>
                                <option value="<?= html_escape($sg['codigo']) ?>" data-grupo="<?= html_escape($sg['grupo_codigo']) ?>"
                                        <?= (string) $sg['codigo'] === $subgrupo ? 'selected' : '' ?>>
                                    <?= html_escape($sg['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="pf_estante">Estante :</label>
                        <select name="estante" id="pf_estante">
                            <option value="">-</option>
                            <?php foreach ($estantes as $e): ?>
                                <option value="<?= (int) $e['codigo'] ?>" <?= (string) $e['codigo'] === $estante ? 'selected' : '' ?>>
                                    <?= html_escape($e['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="pf_prateleira">Prateleira :</label>
                        <select name="prateleira" id="pf_prateleira">
                            <option value="">-</option>
                            <?php foreach ($prateleiras as $pr): ?>
                                <option value="<?= (int) $pr['codigo'] ?>" data-estante="<?= (int) $pr['estante_codigo'] ?>"
                                        <?= (string) $pr['codigo'] === $prateleira ? 'selected' : '' ?>>
                                    <?= html_escape($pr['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="pf-dados-dir">
                        <label for="pf_valor_compra">Valor Atual de Compra :</label>
                        <input type="text" name="valor_compra" id="pf_valor_compra" value="<?= html_escape($num('valor_compra')) ?>">

                        <label for="pf_ultima_compra">Valor da Ultima Compra :</label>
                        <input type="text" name="valor_ultima_compra" id="pf_ultima_compra"
                               value="<?= html_escape($num('valor_ultima_compra')) ?>">

                        <label for="pf_perc">Perc.(%) de Lucro :</label>
                        <input type="text" name="percentual_lucro" id="pf_perc" value="<?= html_escape($num('percentual_lucro')) ?>">

                        <label for="pf_valor_venda">Valor Atual de Venda :</label>
                        <input type="text" name="valor_venda" id="pf_valor_venda" value="<?= html_escape($num('valor_venda')) ?>">
                    </div>

                    <div class="pf-dados-acoes">
                        <button type="submit" class="botao secundario pequeno"
                                formaction="<?= site_url('produtos/atualizar_precos') ?>"
                                onclick="return confirm('Atualiza todo Percentual de Lucro e Preco de venda pelo do produto acima. Confirma?');">Atualizar Preco de Venda - Geral</button>

                        <?php if ($editando): ?>
                            <button type="submit" class="botao secundario pequeno"
                                    formaction="<?= site_url('produtos/migrar_especiais/' . $id) ?>"
                                    onclick="return confirm('Este procedimento ira acoplar o cliente a precos especiais de venda. Confirma?');">Migrar os Clientes Especiais</button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="pf-dados-aplicacao">
                    <label for="pf_aplicacao">Aplicação :</label>
                    <input type="text" name="aplicacao" id="pf_aplicacao" value="<?= html_escape($valor('aplicacao')) ?>">
                </div>

                <input type="hidden" name="item" value="<?= html_escape($item) ?>">
            </div>

            <div class="bloco-pf">
                <div class="linha-pf linha-auditoria">
                    <div class="campo-pf">
                        <label>Cadastro:</label>
                        <input type="text" class="pf-usuario" value="<?= html_escape($usuario_cadastro) ?>" readonly tabindex="-1">
                    </div>
                    <div class="campo-pf">
                        <label>Data:</label>
                        <input type="text" class="pf-data" value="<?= $editando ? data_hora_br($registro['created_at']) : date('d/m/Y H:i') ?>" readonly tabindex="-1">
                    </div>
                    <div class="campo-pf">
                        <label>Alteracao:</label>
                        <input type="text" class="pf-usuario" value="<?= html_escape($usuario_alteracao) ?>" readonly tabindex="-1">
                    </div>
                    <div class="campo-pf">
                        <label>Data:</label>
                        <input type="text" class="pf-data" value="<?= $editando && $registro['updated_at'] ? data_hora_br($registro['updated_at']) : '-' ?>" readonly tabindex="-1">
                    </div>
                    <?php if ($editando): ?>
                        <a class="botao secundario pequeno" href="<?= site_url('produtos/alteracoes/' . $id) ?>">Mostrar Alteracoes</a>
                    <?php endif; ?>
                </div>
            </div>

        <?= form_close() ?>

        <?php if ($editando): ?>
            <div class="bloco-especiais">
                <h3>Precos especiais para clientes selecionados</h3>
                <?= form_open('produtos/especial_salvar/' . $id, array('id' => 'form-especial')) ?>
                    <div class="esp-cadastro">
                        <div class="esp-campo esp-campo-cliente">
                            <label for="pf_cliente">Cliente:</label>
                            <div class="autocomplete" data-url="<?= site_url('clientes/buscar_clientes') ?>">
                                <input type="text" class="autocomplete-busca" id="pf_cliente" placeholder="Codigo ou nome do cliente..."
                                       autocomplete="off" <?= $editar_esp ? 'readonly' : '' ?>
                                       value="<?= $editar_esp ? html_escape($esp_editar['cliente_nome']) : '' ?>">
                                <input type="hidden" name="cliente_id" id="pf_cliente_id" value="<?= $editar_esp ? (int) $editar_esp : '' ?>">
                                <div class="autocomplete-lista"></div>
                            </div>
                        </div>
                        <div class="esp-campo esp-campo-pequeno">
                            <label for="pf_esp_perc">(%) Lucro:</label>
                            <input type="text" name="perc_lucro" id="pf_esp_perc" placeholder="0,00"
                                   value="<?= $editar_esp && $esp_editar['perc_lucro'] !== NULL ? html_escape(numero_br($esp_editar['perc_lucro'], 2)) : '' ?>">
                        </div>
                        <div class="esp-campo esp-campo-pequeno">
                            <label for="pf_esp_valor">Valor de Venda:</label>
                            <input type="text" name="valor_venda" id="pf_esp_valor" placeholder="0,00"
                                   value="<?= $editar_esp && $esp_editar['valor_venda'] !== NULL ? html_escape(numero_br($esp_editar['valor_venda'], 2)) : '' ?>">
                        </div>
                        <div class="esp-campo esp-campo-botao">
                            <button type="submit" class="botao pequeno"><?= $editar_esp ? 'Atualizar preco especial' : 'Salvar preco especial' ?></button>
                        </div>
                        <?php if ($editar_esp): ?>
                            <a class="botao secundario pequeno" href="<?= site_url('produtos/ver/' . $id) ?>">Cancelar edicao</a>
                        <?php endif; ?>
                    </div>
                <?= form_close() ?>

                <div class="tabela-wrap">
                    <table class="tabela compacta">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th class="num" style="width:100px">(%) Lucro</th>
                                <th class="num" style="width:130px">Valor de Venda</th>
                                <th style="width:170px">Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($especiais)): ?>
                                <tr><td colspan="4" class="vazio">Nenhum preco especial cadastrado para este produto.</td></tr>
                            <?php else: ?>
                                <?php foreach ($especiais as $e): ?>
                                    <tr>
                                        <td><a href="<?= site_url('clientes/ver/' . (int) $e['cliente_id']) ?>"><?= html_escape($e['cliente_nome']) ?></a></td>
                                        <td class="num"><?= $e['perc_lucro'] !== NULL ? numero_br($e['perc_lucro'], 2) . '%' : '-' ?></td>
                                        <td class="num"><?= moeda_br($e['valor_venda']) ?></td>
                                        <td class="acoes-tabela">
                                            <a class="botao secundario pequeno" href="<?= site_url('produtos/ver/' . $id . '?editar_esp=' . (int) $e['cliente_id']) ?>">Editar</a>
                                            <?php if (in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE)): ?>
                                                <?= form_open('produtos/especial_excluir/' . $id . '/' . (int) $e['cliente_id'], array('onsubmit' => "return confirm('Excluir o preco especial deste cliente?');")) ?>
                                                    <button type="submit" class="botao pequeno perigo">Excluir</button>
                                                <?= form_close() ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($usuario['perfil'] === PERFIL_ADMIN): ?>
                    <?= form_open('produtos/excluir/' . $id, array('onsubmit' => "return confirm('Excluir definitivamente o produto " . $id . "?');")) ?>
                        <button type="submit" class="botao pequeno perigo">Apaga</button>
                    <?= form_close() ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="pf-acoes">
            <a class="botao secundario pequeno" href="<?= site_url('produtos/novo') ?>">Novo</a>
            <button type="submit" form="form-produto" class="botao pequeno"><?= $editando ? 'Atualizar' : 'Salvar' ?></button>
            <a class="botao secundario pequeno" href="<?= site_url('produtos') ?>">Cancela</a>
            <a class="botao secundario pequeno" href="<?= $editando ? site_url('produtos/dados_gerais/' . $id) : '#bloco-dados-gerais' ?>">Dados Gerais do Produto</a>

            <?php if ($editando): ?>
                <?php foreach (array('primeiro' => 'Primeiro', 'anterior' => 'Anterior', 'proximo' => 'Proximo', 'ultimo' => 'Ultimo') as $chave => $rotulo): ?>
                    <?php if ( ! empty($vizinhos[$chave])): ?>
                        <a class="botao secundario pequeno" href="<?= site_url('produtos/ver/' . (int) $vizinhos[$chave]) ?>"><?= $rotulo ?></a>
                    <?php else: ?>
                        <button type="button" class="botao secundario pequeno" disabled><?= $rotulo ?></button>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <a class="botao secundario pequeno" href="<?= site_url('dashboard') ?>">Sair</a>
        </div>
    </div>
</div>

<?php if ($editando): ?>
    <script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
<?php endif; ?>

<script>
(function () {
    function filtrarSelect(select, atributo, valor) {
        if (!select) { return; }

        var atual = select.value;
        var achou = false;

        Array.prototype.forEach.call(select.options, function (opcao) {
            if (!opcao.value) { return; }

            opcao.hidden = opcao.getAttribute(atributo) !== String(valor);

            if (!opcao.hidden && opcao.value === atual) { achou = true; }
        });

        if (!achou) { select.value = ''; }
    }

    var grupo = document.getElementById('pf_grupo');
    var subgrupo = document.getElementById('pf_subgrupo');
    var estante = document.getElementById('pf_estante');
    var prateleira = document.getElementById('pf_prateleira');

    filtrarSelect(subgrupo, 'data-grupo', grupo ? grupo.value : '');
    filtrarSelect(prateleira, 'data-estante', estante ? estante.value : '');

    if (grupo) {
        grupo.addEventListener('change', function () { filtrarSelect(subgrupo, 'data-grupo', this.value); });
    }
    if (estante) {
        estante.addEventListener('change', function () { filtrarSelect(prateleira, 'data-estante', this.value); });
    }
})();
</script>
