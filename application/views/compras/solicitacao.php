<?php
$registro = is_array($registro) ? $registro : array();

$edicao     = ! empty($registro['numero']);
$situacao   = $edicao ? $registro['situacao'] : 'aberta';
$aberta     = $situacao === 'aberta';
$pode_gerir = in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE);
$editavel   = $pode_gerir && $aberta;
$tem_pedido = $edicao && ! empty($registro['pedido_numero']);
$tem_cotacao = $edicao && ! empty($registro['cotacao_codigo']);

$data = $edicao ? $registro['data'] : date('Y-m-d');
$hora = $edicao && $registro['hora'] ? substr((string) $registro['hora'], 0, 5) : date('H:i');

$rotulos = array(
    'aberta'   => array('Aberta', 'alerta'),
    'fechada'  => array('Fechada', 'ok'),
    'cancelada'=> array('Cancelada', 'off'),
);

$situacao_rotulo = isset($rotulos[$situacao]) ? $rotulos[$situacao] : array($situacao, 'off');

$nome_cadastro = ! empty($registro['usuario_cadastro_nome']) ? $registro['usuario_cadastro_nome']
               : (! empty($registro['usuario_cadastro']) ? $registro['usuario_cadastro'] : '-');
$nome_alteracao = ! empty($registro['usuario_alteracao_nome']) ? $registro['usuario_alteracao_nome']
                : (! empty($registro['usuario_ultima_alteracao']) ? $registro['usuario_ultima_alteracao'] : '-');
$nome_fechamento = ! empty($registro['usuario_fechamento_nome']) ? $registro['usuario_fechamento_nome']
                 : (! empty($registro['usuario_fechamento']) ? $registro['usuario_fechamento'] : '-');

$disabled_attr = $editavel ? '' : ' disabled';
?>
<style>
.cabecalho-titulo { display:flex; align-items:center; gap:18px; flex-wrap:wrap; }
.dados-solicitacao { display:flex; gap:18px; flex-wrap:wrap; align-items:center; font-size:14px; color:#2a3a55; margin-top:0; }
.dados-solicitacao strong { color:#123; font-size:15px; }
.dados-solicitacao .badge { font-size:12px; }
.sol-campos .campo.sol-meio { grid-column:span 6; }
.sol-campos .campo.sol-setor { grid-column:span 4; }
.sol-campos .campo.sol-solicitante { grid-column:span 3; }
.sol-campos .campo.sol-responsavel { grid-column:span 3; }
.sol-campos .campo.sol-fone { grid-column:span 2; }
.sol-campos .campo.sol-auditoria { grid-column:span 5; }
.sol-campos > .campo { display:flex; flex-direction:column; justify-content:flex-end; }
.sol-item-cadastro { margin:10px 0 14px; padding:12px; border:1px solid var(--borda); border-radius:6px; background:#fafcff; }
.sol-item-cadastro .campo.sol-produto { grid-column:span 7; }
.sol-item-cadastro .campo.sol-unid { grid-column:span 1; }
.sol-item-cadastro .campo.sol-saldo { grid-column:span 1; }
.sol-item-cadastro .campo.sol-qtd { grid-column:span 1; }
.sol-item-cadastro .campo.sol-repor { grid-column:span 1; }
.sol-item-cadastro .campo.sol-botao { grid-column:span 1; align-self:end; }
.sol-item-cadastro input[readonly] { background:#f0f3f7; }
.detalhes-auditoria { grid-template-columns:repeat(3, 1fr); gap:8px; }
.detalhes-auditoria .rotulo { font-size:10px; margin-bottom:2px; }
.detalhes-auditoria .valor { font-size:12px; }
.detalhes-auditoria .ajuda { font-size:11px; }
.tabela-itens .qtd { text-align:center; }
.tabela-itens input.qtd::-webkit-outer-spin-button,
.tabela-itens input.qtd::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
.sol-campos textarea { width:100%; height:62px; min-height:62px; resize:vertical; }
.tabela-itens-rolagem { max-height:260px; overflow-y:auto; }
table.tabela.tabela-itens { font-size:12px; }
.tabela-itens th:nth-child(3), .tabela-itens td:nth-child(3) { width:72px; min-width:72px; }
.tabela-itens th:nth-child(4), .tabela-itens td:nth-child(4) { width:88px; min-width:88px; }
.tabela-itens th:nth-child(5), .tabela-itens td:nth-child(5) { width:98px; min-width:98px; }
</style>
<div class="painel">
    <div class="painel-cabecalho">
        <div class="cabecalho-titulo">
            <h3>Solicitacao de Compra</h3>
            <div class="dados-solicitacao">
                <span>N&deg;: <strong><?= $edicao ? (int) $registro['numero'] : '-' ?></strong></span>
                <span>Data: <strong><?= data_br($data) ?></strong></span>
                <?php if ($edicao): ?>
                    <span class="badge <?= $situacao_rotulo[1] ?>"><?= html_escape($situacao_rotulo[0]) ?></span>
                <?php endif; ?>
                <span>N&deg; PC: <strong><?= $edicao && ! empty($registro['pedido_numero']) ? (int) $registro['pedido_numero'] : '-' ?></strong></span>
                <span>Cota&ccedil;&atilde;o: <strong><?= $edicao && ! empty($registro['cotacao_codigo']) ? (int) $registro['cotacao_codigo'] : '-' ?></strong></span>
            </div>
        </div>

    </div>

        <div class="painel-corpo" style="padding-bottom:12px">
            <?= form_open($edicao ? 'compras/solicitacao/' . $registro['numero'] : 'compras/solicitacao', array('id' => 'form-solicitacao')) ?>

                <input type="hidden" name="data" value="<?= html_escape($data) ?>">
                <input type="hidden" name="hora" value="<?= html_escape($hora) ?>">

                <div class="form-grid sol-campos">
                    <div class="campo sol-setor">
                        <label for="setor_id">Setor solicitante</label>
                        <select name="setor_id" id="setor_id" <?= $disabled_attr ?>>
                            <option value="">-</option>
                            <?php foreach ($setores as $s): ?>
                                <option value="<?= (int) $s['id'] ?>"
                                    <?= $edicao && (int) $registro['setor_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                                    <?= html_escape($s['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="campo sol-solicitante">
                        <label for="funcionario_id">Solicitante</label>
                        <select name="funcionario_id" id="funcionario_id" <?= $disabled_attr ?>>
                            <option value="">-</option>
                            <?php foreach ($funcionarios as $f): ?>
                                <option value="<?= (int) $f['id'] ?>"
                                    <?= $edicao && (int) $registro['funcionario_id'] === (int) $f['id'] ? 'selected' : '' ?>>
                                    <?= html_escape($f['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="campo sol-responsavel">
                        <label for="responsavel_recebimento">Responsavel pelo recebimento</label>
                        <input type="text" name="responsavel_recebimento" id="responsavel_recebimento" maxlength="80"
                               value="<?= $edicao ? html_escape($registro['responsavel_recebimento']) : '' ?>" <?= $disabled_attr ?>>
                    </div>

                    <div class="campo sol-fone">
                        <label for="fone_responsavel">Fone do responsavel</label>
                        <input type="text" name="fone_responsavel" id="fone_responsavel" maxlength="20"
                               value="<?= $edicao ? html_escape($registro['fone_responsavel']) : '' ?>" <?= $disabled_attr ?>>
                    </div>

                    <div class="campo sol-setor">
                        <label for="local_entrega">Local de entrega</label>
                        <textarea name="local_entrega" id="local_entrega" rows="2" maxlength="200" <?= $disabled_attr ?>><?= $edicao ? html_escape($registro['local_entrega']) : '' ?></textarea>
                    </div>

                    <div class="campo sol-solicitante">
                        <label for="observacao">Observacoes</label>
                        <textarea name="observacao" id="observacao" rows="2" <?= $disabled_attr ?>><?= $edicao ? html_escape($registro['observacao']) : '' ?></textarea>
                    </div>

                    <?php if ($edicao): ?>
                    <div class="campo sol-auditoria">
                        <div class="detalhes detalhes-auditoria">
                        <div class="detalhe">
                            <div class="rotulo">Cadastro</div>
                            <div class="valor"><?= html_escape($nome_cadastro) ?></div>
                            <?php if ( ! empty($registro['data_cadastro'])): ?>
                                <div class="ajuda"><?= data_hora_br($registro['data_cadastro']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="detalhe">
                            <div class="rotulo">Alteracao</div>
                            <div class="valor"><?= html_escape($nome_alteracao) ?></div>
                            <?php if ( ! empty($registro['data_ultima_alteracao'])): ?>
                                <div class="ajuda"><?= data_hora_br($registro['data_ultima_alteracao']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="detalhe">
                            <div class="rotulo">Fechamento</div>
                            <div class="valor"><?= html_escape($nome_fechamento) ?></div>
                            <?php if ( ! empty($registro['data_fechamento'])): ?>
                                <div class="ajuda"><?= data_hora_br($registro['data_fechamento']) ?></div>
                            <?php endif; ?>
                        </div>
                        </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="sol-item-cadastro">
                    <div class="form-grid">
                        <div class="campo sol-produto">
                            <label for="pf_novo_produto">Produto:</label>
                            <div class="autocomplete" data-url="<?= site_url('compras/buscar_produtos') ?>"
                                 data-unidade=".unidade-cad" data-estoque=".saldo-cad">
                                <input type="text" class="autocomplete-busca" id="pf_novo_produto"
                                       placeholder="Codigo ou descricao..." autocomplete="off" <?= $disabled_attr ?>>
                                <input type="hidden" id="pf_novo_produto_id">
                                <div class="autocomplete-lista"></div>
                            </div>
                        </div>
                        <div class="campo sol-unid">
                            <label for="pf_novo_unidade">Unid.</label>
                            <input type="text" id="pf_novo_unidade" class="unidade-cad" readonly tabindex="-1">
                        </div>
                        <div class="campo sol-saldo">
                            <label for="pf_novo_saldo">Saldo</label>
                            <input type="text" id="pf_novo_saldo" class="saldo-cad" readonly tabindex="-1">
                        </div>
                        <div class="campo sol-qtd">
                            <label for="pf_novo_qtd">Qtd</label>
                            <input type="text" inputmode="numeric" id="pf_novo_qtd" class="qtd-cad" value="1" <?= $disabled_attr ?>>
                        </div>
                        <div class="campo sol-repor">
                            <label for="pf_novo_repor">Repor</label>
                            <input type="checkbox" id="pf_novo_repor" class="repor-cad" value="S" <?= $disabled_attr ?>>
                        </div>
                        <div class="campo sol-botao">
                            <button type="button" class="botao pequeno" id="btn-adicionar-produto" <?= $disabled_attr ?>>+ Adicionar item</button>
                        </div>
                    </div>
                </div>

                <hr class="divisor">

                <h4 class="secao-titulo">Itens solicitados</h4>

                <div class="tabela-wrap tabela-itens-rolagem">
                    <table class="tabela tabela-itens" data-solicitacao
                           data-template="linha-solicitacao" data-grupo="solicitacao">
                        <thead>
                            <tr>
                                <th class="num" style="width:36px">#</th>
                                <th style="min-width:230px">Produto</th>
                                <th class="centro" style="width:72px">Unid.</th>
                                <th class="num" style="width:88px">Saldo</th>
                                <th class="centro" style="width:98px">Qtd</th>
                                <th class="centro" style="width:70px" title="Repor no estoque">Repor</th>
                                <th class="centro" style="width:44px"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="form-acoes" style="margin-top:6px;padding-top:8px">
                    <button type="submit" class="botao" <?= $disabled_attr ?>><?= $edicao ? 'Salvar alteracoes' : 'Criar solicitacao' ?></button>
                    <a class="botao secundario" href="<?= site_url($edicao ? 'compras/solicitacao/' . $registro['numero'] : 'compras/solicitacoes') ?>"
                       <?= $editavel ? '' : 'aria-disabled="true" tabindex="-1" onclick="return false" style="opacity:.5;pointer-events:none"' ?>>Cancelar</a>

                    <?php if ($edicao && ! $tem_pedido && $pode_gerir): ?>
                        <button type="submit" class="botao pequeno" <?= $disabled_attr ?>
                                formaction="<?= site_url('compras/solicitacao_gerar_pedido/' . $registro['numero']) ?>">Gerar pedido</button>
                    <?php endif; ?>

                    <?php if ($edicao && ! $tem_cotacao && $pode_gerir): ?>
                        <button type="submit" class="botao secundario pequeno" <?= $disabled_attr ?>
                                formaction="<?= site_url('compras/importar_solicitacao/' . $registro['numero']) ?>">Importar p/ cotacao</button>
                    <?php endif; ?>

                    <?php if ($tem_cotacao): ?>
                        <a class="botao secundario pequeno" href="<?= site_url('compras/cotacao/' . $registro['cotacao_codigo']) ?>">Cotacao</a>
                    <?php endif; ?>

                    <?php if ($edicao): ?>
                        <a class="botao secundario pequeno" href="<?= site_url('compras/imprimir_solicitacao/' . $registro['numero']) ?>" target="_blank">Imprimir</a>
                    <?php endif; ?>

                    <?php if ($edicao && $aberta && $pode_gerir): ?>
                        <button type="submit" class="botao secundario pequeno" <?= $disabled_attr ?>
                                title="Somente finaliza a solicitacao"
                                formaction="<?= site_url('compras/solicitacao_fechar/' . $registro['numero']) ?>">Finalizar Solicitacao</button>
                    <?php endif; ?>

                    <a class="botao secundario pequeno" href="<?= site_url('compras/solicitacoes') ?>">Voltar</a>
                </div>

            <?= form_close() ?>
        </div>

</div>

<template id="linha-solicitacao">
    <table><tbody>
        <tr class="linha-item">
            <td class="num numero-item">1</td>
            <td>
                <input type="text" class="produto-desc" readonly tabindex="-1">
                <input type="hidden" name="produto_id[]">
            </td>
            <td><input type="text" class="unidade" readonly tabindex="-1"></td>
            <td><input type="text" class="saldo" readonly tabindex="-1" style="text-align:right"></td>
            <td>
                <input type="text" inputmode="numeric" name="quantidade[]" class="qtd" value="1" placeholder="0">
                <input type="hidden" name="data_utilizacao[]">
                <input type="hidden" name="periodo[]">
            </td>
            <td class="centro"><input type="checkbox" name="repor_estoque[]" value="S"></td>
            <td class="centro"><button type="button" class="botao perigo pequeno remover-item" title="Remover item">&times;</button></td>
        </tr>
    </tbody></table>
</template>

<script>
window.BIOSTECH_SOLICITACAO_ITENS = <?= json_encode(array_map(function ($i) {
    return array(
        'produto_id'      => (int) $i['produto_id'],
        'descricao'       => $i['descricao'],
        'unidade'         => $i['unidade'],
        'saldo'           => (float) $i['saldo'],
        'quantidade'      => (float) $i['quantidade'],
        'repor_estoque'   => bd_bool($i['repor_estoque']),
        'data_utilizacao' => $i['data_utilizacao'],
        'periodo'         => $i['periodo'],
    );
}, $itens), JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="<?= base_url('assets/js/autocomplete.js?v=3') ?>"></script>
<script src="<?= base_url('assets/js/compras.js?v=5') ?>"></script>

<script>
(function () {
    var form = document.getElementById('form-solicitacao');

    if (form) {
        form.addEventListener('keydown', function (event) {
            var alvo = event.target;

            if (event.key === 'Enter' && alvo && alvo.tagName === 'INPUT'
                && alvo.type !== 'submit' && alvo.type !== 'button') {
                event.preventDefault();
            }
        });
    }

    document.addEventListener('input', function (event) {
        var alvo = event.target;

        if (alvo && alvo.classList && alvo.classList.contains('qtd')) {
            alvo.value = alvo.value.replace(/\D/g, '');
        }
    });

    var fone = document.getElementById('fone_responsavel');

    if (fone) {
        function mascaraTelefone(valor) {
            var digitos = valor.replace(/\D/g, '').slice(0, 11);

            if (digitos === '') { return ''; }

            var texto = '(' + digitos.slice(0, 2) + ')';

            if (digitos.length > 2) { texto += ' ' + digitos.slice(2, 7); }
            if (digitos.length > 7) { texto += '-' + digitos.slice(7, 11); }

            return texto;
        }

        fone.maxLength = 15;
        fone.value = mascaraTelefone(fone.value);
        fone.addEventListener('input', function () { fone.value = mascaraTelefone(fone.value); });
    }
})();
</script>
