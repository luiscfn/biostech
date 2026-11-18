<div class="painel">
    <div class="painel-cabecalho">
        <h3>Tabelas Basicas</h3>

        <div class="acoes-ficha">
            <a class="botao pequeno" href="<?= site_url('dashboard') ?>">Retornar ao Menu</a>
        </div>
    </div>

    <div class="painel-corpo">
        <p class="ajuda" style="margin-top:0">
            Itens de apoio usados por Chamados, Orcamentos, Estoque e Financeiro.
            Os codigos herdados do Access sao preservados.
            <?php if ( ! $pode_editar): ?>
                <br>Seu perfil permite apenas consulta.
            <?php endif; ?>
        </p>

        <div class="cartoes" style="margin-top:16px">
            <?php foreach ($tipos as $chave => $cfg): ?>
                <?php
                $destino = 'tabelas_basicas/categoria/' . $chave;

                if ($cfg['fonte'] === 'parametros')
                {
                    $destino = 'tabelas_basicas/parametros';
                }
                elseif ($cfg['fonte'] === 'permissoes')
                {
                    $destino = 'tabelas_basicas/permissoes';
                }
                ?>
                <a class="cartao" href="<?= site_url($destino) ?>" style="text-decoration:none;color:inherit">
                    <div class="rotulo">
                        <?= html_escape($cfg['rotulo']) ?>
                    </div>
                    <div class="valor"><?= numero_br(isset($contagens[$chave]) ? $contagens[$chave] : 0) ?></div>
                    <div class="detalhe"><?= html_escape($cfg['descricao']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
