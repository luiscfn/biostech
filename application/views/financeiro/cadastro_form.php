<?php
$pk_por_tipo = array(
    'bancos' => 'codigo', 'agencias' => 'id', 'contas' => 'id', 'cartoes' => 'codigo',
    'centros' => 'codigo', 'naturezas' => 'codigo', 'status' => 'codigo',
    'periodicidades' => 'codigo', 'despesas' => 'codigo',
);

$pk      = $pk_por_tipo[$tipo];
$edicao  = ! empty($registro) && ! empty($registro[$pk]);

$campo = function ($nome, $default = '') use ($registro, $edicao) {
    if ( ! $edicao)
    {
        return $default;
    }

    return isset($registro[$nome]) ? $registro[$nome] : $default;
};
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>Cadastro de <?= html_escape($rotulo) ?></h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('financeiro/cadastros') ?>">Cadastros</a>
            <?php if ($edicao): ?>
                <a class="botao secundario pequeno" href="<?= site_url('financeiro/cadastro/' . $tipo) ?>">Novo</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="painel-corpo">
        <?= form_open($edicao
            ? 'financeiro/cadastro/' . $tipo . '/' . rawurlencode($registro[$pk])
            : 'financeiro/cadastro/' . $tipo) ?>

            <div class="form-grid">
                <?php if (in_array($tipo, array('bancos', 'cartoes', 'centros', 'naturezas', 'status', 'periodicidades', 'despesas'), TRUE)): ?>
                    <div class="campo terco">
                        <label for="codigo">Codigo *</label>
                        <?php if ($tipo === 'bancos'): ?>
                            <input type="text" name="codigo" id="codigo" maxlength="20" required
                                   value="<?= html_escape($campo('codigo')) ?>"
                                   <?= $edicao ? 'readonly' : '' ?>>
                        <?php else: ?>
                            <input type="number" name="codigo" id="codigo" min="0" step="1" required
                                   value="<?= html_escape($campo('codigo')) ?>"
                                   <?= $edicao ? 'readonly' : '' ?>>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($tipo === 'agencias' || $tipo === 'contas'): ?>
                    <div class="campo terco">
                        <label for="banco_codigo">Banco *</label>
                        <select name="banco_codigo" id="banco_codigo" required>
                            <option value="">-</option>
                            <?php foreach ($bancos as $b): ?>
                                <option value="<?= html_escape($b['codigo']) ?>"
                                    <?= (string) $campo('banco_codigo') === (string) $b['codigo'] ? 'selected' : '' ?>>
                                    <?= html_escape($b['codigo'] . ' - ' . $b['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if ($tipo === 'agencias'): ?>
                    <div class="campo terco">
                        <label for="agencia">Agencia *</label>
                        <input type="text" name="agencia" id="agencia" maxlength="25" required
                               value="<?= html_escape($campo('agencia')) ?>">
                    </div>
                <?php endif; ?>

                <?php if ($tipo === 'contas'): ?>
                    <div class="campo terco">
                        <label for="agencia">Agencia *</label>
                        <input type="text" name="agencia" id="agencia" maxlength="25" required
                               value="<?= html_escape($campo('agencia')) ?>">
                    </div>

                    <div class="campo terco">
                        <label for="conta">Conta *</label>
                        <input type="text" name="conta" id="conta" maxlength="25" required
                               value="<?= html_escape($campo('conta')) ?>">
                    </div>
                <?php endif; ?>

                <?php if ( ! in_array($tipo, array('agencias', 'contas', 'despesas'), TRUE)): ?>
                    <div class="campo terco">
                        <label for="nome">Nome *</label>
                        <input type="text" name="nome" id="nome" maxlength="120" required
                               value="<?= html_escape($campo('nome')) ?>">
                    </div>
                <?php endif; ?>

                <?php if ($tipo === 'agencias'): ?>
                    <div class="campo terco">
                        <label for="nome">Nome</label>
                        <input type="text" name="nome" id="nome" maxlength="120"
                               value="<?= html_escape($campo('nome')) ?>">
                    </div>

                    <div class="campo full">
                        <label for="endereco">Endereco</label>
                        <input type="text" name="endereco" id="endereco" maxlength="120"
                               value="<?= html_escape($campo('endereco')) ?>">
                    </div>

                    <div class="campo terco">
                        <label for="bairro">Bairro</label>
                        <input type="text" name="bairro" id="bairro" maxlength="60"
                               value="<?= html_escape($campo('bairro')) ?>">
                    </div>

                    <div class="campo terco">
                        <label for="cep">CEP</label>
                        <input type="text" name="cep" id="cep" maxlength="10"
                               value="<?= html_escape($campo('cep')) ?>">
                    </div>

                    <div class="campo terco">
                        <label for="cidade">Cidade</label>
                        <input type="text" name="cidade" id="cidade" maxlength="60"
                               value="<?= html_escape($campo('cidade')) ?>">
                    </div>

                    <div class="campo terco">
                        <label for="uf">UF</label>
                        <input type="text" name="uf" id="uf" maxlength="2"
                               value="<?= html_escape($campo('uf')) ?>">
                    </div>

                    <div class="campo terco">
                        <label for="telefone1">Telefone 1</label>
                        <input type="text" name="telefone1" id="telefone1" maxlength="30"
                               value="<?= html_escape($campo('telefone1')) ?>">
                    </div>

                    <div class="campo terco">
                        <label for="telefone2">Telefone 2</label>
                        <input type="text" name="telefone2" id="telefone2" maxlength="30"
                               value="<?= html_escape($campo('telefone2')) ?>">
                    </div>

                    <div class="campo terco">
                        <label for="fax">Fax</label>
                        <input type="text" name="fax" id="fax" maxlength="30"
                               value="<?= html_escape($campo('fax')) ?>">
                    </div>

                    <div class="campo terco">
                        <label for="contato">Contato</label>
                        <input type="text" name="contato" id="contato" maxlength="80"
                               value="<?= html_escape($campo('contato')) ?>">
                    </div>
                <?php endif; ?>

                <?php if ($tipo === 'contas'): ?>
                    <div class="campo terco">
                        <label for="titular">Titular</label>
                        <input type="text" name="titular" id="titular" maxlength="120"
                               value="<?= html_escape($campo('titular')) ?>">
                    </div>

                    <div class="campo terco">
                        <label for="valor_limite">Limite</label>
                        <input type="text" name="valor_limite" id="valor_limite" inputmode="decimal"
                               value="<?= $edicao && $registro['valor_limite'] !== NULL ? number_format((float) $registro['valor_limite'], 2, ',', '') : '' ?>">
                    </div>

                    <div class="campo terco">
                        <label for="valor_real">Saldo real</label>
                        <input type="text" name="valor_real" id="valor_real" inputmode="decimal"
                               value="<?= $edicao && $registro['valor_real'] !== NULL ? number_format((float) $registro['valor_real'], 2, ',', '') : '' ?>">
                    </div>
                <?php endif; ?>

                <?php if ($tipo === 'despesas'): ?>
                    <div class="campo terco">
                        <label for="nome">Nome *</label>
                        <input type="text" name="nome" id="nome" maxlength="120" required
                               value="<?= html_escape($campo('nome')) ?>">
                    </div>

                    <div class="campo terco">
                        <label for="natureza_codigo">Natureza</label>
                        <select name="natureza_codigo" id="natureza_codigo">
                            <option value="">-</option>
                            <?php foreach ($naturezas as $n): ?>
                                <option value="<?= (int) $n['codigo'] ?>"
                                    <?= (int) $campo('natureza_codigo', 0) === (int) $n['codigo'] ? 'selected' : '' ?>>
                                    <?= html_escape($n['codigo'] . ' - ' . $n['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="campo terco">
                        <label for="periodicidade">Periodicidade</label>
                        <select name="periodicidade" id="periodicidade">
                            <option value="">-</option>
                            <?php foreach ($periodicidades as $p): ?>
                                <option value="<?= (int) $p['codigo'] ?>"
                                    <?= (int) $campo('periodicidade', 0) === (int) $p['codigo'] ? 'selected' : '' ?>>
                                    <?= html_escape($p['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="campo terco">
                        <label for="centro_custo">Centro de custo</label>
                        <select name="centro_custo" id="centro_custo">
                            <option value="">-</option>
                            <?php foreach ($centros as $c): ?>
                                <option value="<?= (int) $c['codigo'] ?>"
                                    <?= (int) $campo('centro_custo', 0) === (int) $c['codigo'] ? 'selected' : '' ?>>
                                    <?= html_escape($c['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="campo terco">
                        <label for="fornecedor_codigo">Fornecedor (codigo)</label>
                        <input type="number" name="fornecedor_codigo" id="fornecedor_codigo" min="0" step="1"
                               value="<?= html_escape($campo('fornecedor_codigo')) ?>">
                    </div>

                    <div class="campo terco">
                        <label for="valor">Valor</label>
                        <input type="text" name="valor" id="valor" inputmode="decimal"
                               value="<?= $edicao ? number_format((float) $registro['valor'], 2, ',', '') : '' ?>">
                    </div>

                    <div class="campo terco">
                        <label for="dia_vencimento">Dia de vencimento</label>
                        <input type="number" name="dia_vencimento" id="dia_vencimento" min="1" max="31" step="1"
                               value="<?= html_escape($campo('dia_vencimento')) ?>">
                    </div>

                    <div class="campo full">
                        <label for="observacao">Observacao</label>
                        <textarea name="observacao" id="observacao" rows="3"><?= html_escape($campo('observacao')) ?></textarea>
                    </div>

                    <div class="campo">
                        <label class="check">
                            <input type="checkbox" name="ativo" value="1"
                                <?= ( ! $edicao || bd_bool($registro['ativo'])) ? 'checked' : '' ?>>
                            Ativo
                        </label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-acoes">
                <button type="submit" class="botao"><?= $edicao ? 'Salvar alteracoes' : 'Incluir cadastro' ?></button>
                <?php if ($edicao): ?>
                    <a class="botao secundario" href="<?= site_url('financeiro/cadastro/' . $tipo) ?>">Cancelar</a>
                <?php endif; ?>
            </div>

        <?= form_close() ?>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <?php if (in_array($tipo, array('bancos', 'cartoes', 'centros', 'naturezas', 'status', 'periodicidades', 'despesas'), TRUE)): ?>
                        <th style="width:80px">Codigo</th>
                    <?php endif; ?>
                    <th>Descricao</th>
                    <?php if ($tipo === 'agencias' || $tipo === 'contas'): ?>
                        <th style="width:180px">Banco / Agencia</th>
                    <?php endif; ?>
                    <?php if ($tipo === 'contas'): ?>
                        <th style="width:130px">Conta</th>
                        <th style="width:130px">Titular</th>
                    <?php endif; ?>
                    <?php if ($tipo === 'despesas'): ?>
                        <th style="width:130px">Periodicidade</th>
                        <th class="num" style="width:110px">Valor</th>
                        <th style="width:80px">Dia</th>
                    <?php endif; ?>
                    <th class="centro" style="width:150px">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="7" class="vazio">Nenhum registro cadastrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <?php if (in_array($tipo, array('bancos', 'cartoes', 'centros', 'naturezas', 'status', 'periodicidades', 'despesas'), TRUE)): ?>
                                <td><strong><?= html_escape($r['codigo']) ?></strong></td>
                            <?php endif; ?>

                            <td>
                                <?= html_escape(isset($r['nome']) && $r['nome'] !== NULL ? $r['nome'] : (isset($r['agencia']) ? $r['agencia'] : '-')) ?>
                                <?php if ($tipo === 'agencias' && ! empty($r['cidade'])): ?>
                                    <div class="ajuda"><?= html_escape($r['cidade']) ?><?= ! empty($r['uf']) ? '/' . html_escape($r['uf']) : '' ?></div>
                                <?php endif; ?>
                                <?php if ($tipo === 'despesas' && ! empty($r['observacao'])): ?>
                                    <div class="ajuda"><?= html_escape(mb_substr($r['observacao'], 0, 70)) ?></div>
                                <?php endif; ?>
                            </td>

                            <?php if ($tipo === 'agencias' || $tipo === 'contas'): ?>
                                <td><?= html_escape($r['banco_codigo']) ?> / <?= html_escape($r['agencia']) ?></td>
                            <?php endif; ?>

                            <?php if ($tipo === 'contas'): ?>
                                <td><?= html_escape($r['conta']) ?></td>
                                <td><?= html_escape($r['titular'] ? $r['titular'] : '-') ?></td>
                            <?php endif; ?>

                            <?php if ($tipo === 'despesas'): ?>
                                <td><?= html_escape($r['periodicidade'] !== NULL ? $r['periodicidade'] : '-') ?></td>
                                <td class="num"><?= moeda_br($r['valor']) ?></td>
                                <td><?= $r['dia_vencimento'] !== NULL ? (int) $r['dia_vencimento'] : '-' ?></td>
                            <?php endif; ?>

                            <td class="centro">
                                <a class="botao secundario pequeno"
                                   href="<?= site_url('financeiro/cadastro/' . $tipo . '/' . rawurlencode($r[$pk])) ?>">Editar</a>

                                <?= form_open('financeiro/cadastro_excluir/' . $tipo . '/' . rawurlencode($r[$pk]), array('style' => 'display:inline')) ?>
                                    <button type="submit" class="botao perigo pequeno">Excluir</button>
                                <?= form_close() ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
