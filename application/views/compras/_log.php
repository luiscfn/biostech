<?php
$acoes = array(
    'I' => 'Inclusao',
    'A' => 'Alteracao',
    'E' => 'Exclusao',
    'C' => 'Cancelamento',
    'R' => 'Reabertura',
    'F' => 'Fechamento',
    'D' => 'Cancelamento do fechamento',
);
?>
<div class="painel-corpo">
    <?php if (empty($log)): ?>
        <div class="vazio-estado">Nenhum evento registrado.</div>
    <?php else: ?>
        <ul class="lista-eventos">
            <?php foreach ($log as $evento): ?>
                <li>
                    <span class="quando"><?= data_hora_br($evento['data_mov']) ?></span>
                    <span class="quem"><?= html_escape($evento['usuario']) ?></span>
                    <?= html_escape(isset($acoes[$evento['acao']]) ? $acoes[$evento['acao']] : $evento['acao']) ?>

                    <?php if ( ! empty($evento['detalhe'])): ?>
                        <?php $detalhe = json_decode($evento['detalhe'], TRUE); ?>
                        <?php if (is_array($detalhe) && ! empty($detalhe)): ?>
                            <div class="ajuda">
                                <?php
                                $partes = array();

                                foreach ($detalhe as $chave => $valor)
                                {
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
