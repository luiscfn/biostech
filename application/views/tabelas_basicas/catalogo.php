<?php
$edicao = ! empty($registro);

$campo = function ($nome, $default = '') use ($registro, $edicao) {
    if ( ! $edicao)
    {
        return $default;
    }

    return isset($registro[$nome]) ? $registro[$nome] : $default;
};

$valor_decimal = function ($valor) {
    if ($valor === NULL || $valor === '')
    {
        return '';
    }

    return number_format((float) $valor, 2, ',', '');
};

$mostra_valor = ! empty($cfg['rotulo_valor']);
$mostra_uso   = ! empty($cfg['rotulo_uso']);
$eh_setor     = ($cfg['fonte'] === 'setores');
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>
            <?php if ( ! empty($cfg['letra'])): ?>
                <span class="badge info"><?= html_escape($cfg['letra']) ?></span>
            <?php endif; ?>
            <?= html_escape($cfg['rotulo']) ?>
        </h3>

        <div class="acoes-ficha">
            <a class="botao secundario pequeno" href="<?= site_url('tabelas_basicas') ?>">Tabelas Basicas</a>
            <?php if ($edicao): ?>
                <a class="botao secundario pequeno" href="<?= site_url('tabelas_basicas/categoria/' . $chave) ?>">Novo</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="painel-corpo">
        <p class="ajuda" style="margin-top:0"><?= html_escape($cfg['descricao']) ?></p>

        <?php if ($pode_editar): ?>
            <?= form_open('tabelas_basicas/salvar/' . $chave) ?>
                <input type="hidden" name="editar" value="<?= $edicao ? (int) $registro['codigo'] : 0 ?>">

                <div class="form-grid">
                    <div class="campo terco">
                        <label for="codigo">Codigo *</label>
                        <input type="number" name="codigo" id="codigo" min="1" step="1" required
                               value="<?= html_escape($edicao ? $registro['codigo'] : $proximo_codigo) ?>"
                               <?= $edicao ? 'readonly' : '' ?>>
                        <?php if ($edicao): ?>
                            <div class="ajuda">O codigo herdado do Access nao pode ser alterado.</div>
                        <?php endif; ?>
                    </div>

                    <div class="campo <?= $mostra_valor ? 'terco' : 'full' ?>">
                        <label for="nome">Nome *</label>
                        <input type="text" name="nome" id="nome" maxlength="120" required
                               value="<?= html_escape($campo('nome')) ?>">
                    </div>

                    <?php if ($mostra_valor): ?>
                        <div class="campo terco">
                            <label for="valor"><?= html_escape($cfg['rotulo_valor']) ?></label>
                            <input type="text" name="valor" id="valor" inputmode="decimal"
                                   value="<?= html_escape($valor_decimal($campo('valor'))) ?>">
                        </div>
                    <?php endif; ?>

                    <?php if ( ! $eh_setor): ?>
                        <div class="campo terco">
                            <label for="ordem">Ordem</label>
                            <input type="number" name="ordem" id="ordem" min="0" step="1"
                                   value="<?= html_escape($campo('ordem', '')) ?>">
                            <div class="ajuda">Deixe em branco para usar o proprio codigo.</div>
                        </div>
                    <?php endif; ?>

                    <div class="campo terco">
                        <label class="check" style="margin-top:26px">
                            <input type="checkbox" name="ativo" value="1"
                                <?= ( ! $edicao || bd_bool($registro['ativo'])) ? 'checked' : '' ?>>
                            Ativo
                        </label>
                    </div>
                </div>

                <div class="form-acoes">
                    <button type="submit" class="botao"><?= $edicao ? 'Gravar alteracoes' : 'Incluir' ?></button>
                    <?php if ($edicao): ?>
                        <a class="botao secundario" href="<?= site_url('tabelas_basicas/categoria/' . $chave) ?>">Cancelar</a>
                    <?php endif; ?>
                </div>
            <?= form_close() ?>
        <?php else: ?>
            <div class="alert alert-warning">Consulta somente leitura. Inclusao e alteracao exigem perfil admin ou gestor.</div>
        <?php endif; ?>
    </div>
</div>

<div class="painel">
    <div class="painel-cabecalho">
        <h3>Itens cadastrados (<?= numero_br(count($registros)) ?>)</h3>

        <form method="get" action="<?= site_url('tabelas_basicas/categoria/' . $chave) ?>" class="acoes-ficha">
            <input type="text" name="busca" placeholder="Pesquisar nome ou codigo"
                   value="<?= html_escape($busca) ?>" style="padding:6px 10px;border:1px solid var(--borda);border-radius:8px">
            <button type="submit" class="botao secundario pequeno">Pesquisar</button>
            <?php if ($busca !== ''): ?>
                <a class="botao secundario pequeno" href="<?= site_url('tabelas_basicas/categoria/' . $chave) ?>">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="tabela-wrap">
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width:90px">Codigo</th>
                    <th>Nome</th>
                    <?php if ($mostra_valor): ?>
                        <th class="num" style="width:120px"><?= html_escape($cfg['rotulo_valor']) ?></th>
                    <?php endif; ?>
                    <?php if ($mostra_uso): ?>
                        <th class="num" style="width:110px"><?= html_escape($cfg['rotulo_uso']) ?></th>
                    <?php endif; ?>
                    <th style="width:100px">Situacao</th>
                    <?php if ($pode_editar): ?>
                        <th class="centro" style="width:170px">Acoes</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr>
                        <td colspan="<?= 4 + ($mostra_valor ? 1 : 0) + ($mostra_uso ? 1 : 0) + ($pode_editar ? 1 : 0) ?>" class="vazio">
                            Nenhum item encontrado.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><strong><?= html_escape($r['codigo']) ?></strong></td>
                            <td><?= html_escape($r['nome']) ?></td>

                            <?php if ($mostra_valor): ?>
                                <td class="num">
                                    <?= $r['valor'] !== NULL ? numero_br($r['valor'], 2) : '-' ?>
                                </td>
                            <?php endif; ?>

                            <?php if ($mostra_uso): ?>
                                <td class="num"><?= numero_br(isset($r['uso']) ? $r['uso'] : 0) ?></td>
                            <?php endif; ?>

                            <td>
                                <?php if (bd_bool($r['ativo'])): ?>
                                    <span class="badge ok">Ativo</span>
                                <?php else: ?>
                                    <span class="badge off">Inativo</span>
                                <?php endif; ?>
                            </td>

                            <?php if ($pode_editar): ?>
                                <td class="centro">
                                    <a class="botao secundario pequeno"
                                       href="<?= site_url('tabelas_basicas/categoria/' . $chave . '?editar=' . rawurlencode($r['codigo'])) ?>">Editar</a>

                                    <?= form_open('tabelas_basicas/excluir/' . $chave . '/' . rawurlencode($r['codigo']), array('style' => 'display:inline')) ?>
                                        <button type="submit" class="botao perigo pequeno"
                                            onclick="return confirm('Excluir este item?')">Excluir</button>
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
