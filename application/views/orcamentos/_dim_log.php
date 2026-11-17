<div class="painel-corpo">
    <?php if (empty($log)): ?>
        <div class="vazio-estado">Nenhum evento registrado.</div>
    <?php else: ?>
        <ul class="lista-eventos">
            <?php foreach ($log as $evento): ?>
                <li>
                    <span class="quando"><?= data_hora_br($evento['data_mov']) ?></span>
                    <span class="quem"><?= html_escape( ! empty($evento['usuario_nome']) ? $evento['usuario_nome'] : ($evento['usuario'] ? $evento['usuario'] : '-')) ?></span>

                    <?php if ($evento['tipo_mov'] === 'C'): ?>
                        Criacao do dimensionamento
                    <?php elseif ( ! empty($evento['campo'])): ?>
                        Alteracao de <strong><?= html_escape($evento['campo']) ?></strong>
                        <?php if ($evento['valor_anterior'] !== NULL || $evento['valor_novo'] !== NULL): ?>
                            <div class="ajuda">
                                de <?= html_escape($evento['valor_anterior'] !== NULL ? $evento['valor_anterior'] : '(vazio)') ?>
                                para <?= html_escape($evento['valor_novo'] !== NULL ? $evento['valor_novo'] : '(vazio)') ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        Alteracao
                    <?php endif; ?>

                    <?php if ( ! empty($evento['dados'])): ?>
                        <?php $dados = json_decode($evento['dados'], TRUE); ?>
                        <?php if (is_array($dados)): ?>
                            <div class="ajuda">
                                <?php
                                $partes = array();

                                foreach ($dados as $chave => $valor)
                                {
                                    if ($chave === 'registro' && is_array($valor))
                                    {
                                        continue;
                                    }

                                    if (is_array($valor))
                                    {
                                        $valor = json_encode($valor, JSON_UNESCAPED_UNICODE);
                                    }

                                    $partes[] = $chave . ': ' . $valor;
                                }

                                echo html_escape(implode(' | ', $partes));
                                ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
