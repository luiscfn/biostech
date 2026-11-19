<?php
$moeda = function ($valor) {
    return number_format((float) $valor, 2, ',', '.');
};
?>

<div class="cartoes">
    <div class="cartao">
        <div class="rotulo">Clientes</div>
        <div class="valor"><?= number_format($clientes_total, 0, ',', '.') ?></div>
        <div class="detalhe"><?= number_format($clientes_ativos, 0, ',', '.') ?> ativos</div>
    </div>

    <div class="cartao verde">
        <div class="rotulo">Produtos</div>
        <div class="valor"><?= number_format($produtos_total, 0, ',', '.') ?></div>
        <div class="detalhe"><?= number_format($produtos_ativos, 0, ',', '.') ?> ativos</div>
    </div>

    <div class="cartao">
        <div class="rotulo">Setores</div>
        <div class="valor"><?= number_format($setores_total, 0, ',', '.') ?></div>
        <div class="detalhe">estrutura organizacional</div>
    </div>

    <div class="cartao">
        <div class="rotulo">Usuarios</div>
        <div class="valor"><?= number_format($usuarios_total, 0, ',', '.') ?></div>
        <div class="detalhe">acessos cadastrados</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px" class="grid-dashboard">

    <div class="painel">
        <div class="painel-cabecalho">
            <h3>Ultimos eventos</h3>
            <span class="badge info">auditoria</span>
        </div>

        <?php if (empty($eventos)): ?>
            <div class="vazio-estado">Nenhum evento registrado ainda.</div>
        <?php else: ?>
            <div class="painel-corpo">
                <ul class="lista-eventos">
                    <?php foreach ($eventos as $evento): ?>
                        <li>
                            <span class="quando"><?= date('d/m/Y H:i', strtotime($evento['created_at'])) ?></span>
                            <span>
                                <span class="quem"><?= html_escape($evento['usuario_login'] ? $evento['usuario_login'] : 'sistema') ?></span>
                                <?= html_escape($evento['acao']) ?>
                                <strong><?= html_escape($evento['entidade']) ?></strong>
                                <?= html_escape($evento['entidade_id']) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <div class="painel">
        <div class="painel-cabecalho">
            <h3>Conversao</h3>
            <span class="badge alerta">fase 1</span>
        </div>
        <div class="painel-corpo">
            <p style="margin-top:0;color:#6b7785;font-size:13px">
                Modulos ja disponiveis sobre os dados migrados do Access:
            </p>
            <ul style="padding-left:18px;font-size:13px;line-height:1.9;margin:0">
                <li>Autenticacao e perfis</li>
                <li>Cadastro de clientes</li>
                <li>Catalogo de produtos</li>
                <li>Setores</li>
                <li>Usuarios e permissoes</li>
                <li>Auditoria</li>
            </ul>
        </div>
    </div>

</div>

<style>
@media (max-width: 900px) {
    .grid-dashboard { grid-template-columns: 1fr !important; }
}
</style>
