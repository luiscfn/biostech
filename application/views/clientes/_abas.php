<?php
/**
 * Abas do cliente.
 *
 * Variaveis esperadas:
 *   $registro - cliente (NULL nas telas de consulta sem cliente escolhido)
 *   $aba      - 'dados' | 'ordens' | 'orcamentos' | 'notas'
 */

$aba = isset($aba) ? $aba : 'dados';

if (empty($registro))
{
    $itens = array(
        'ordens'     => array('rotulo' => 'Ordens de servico', 'url' => site_url('clientes/ordens')),
        'orcamentos' => array('rotulo' => 'Orcamentos',         'url' => site_url('clientes/orcamentos')),
        'notas'      => array('rotulo' => 'Notas fiscais',      'url' => site_url('clientes/notas')),
    );
}
else
{
    $id = (int) $registro['id'];

    $itens = array(
        'dados'      => array('rotulo' => 'Dados',              'url' => site_url('clientes/ver/' . $id)),
        'ordens'     => array('rotulo' => 'Ordens de servico',  'url' => site_url('clientes/ordens/' . $id)),
        'orcamentos' => array('rotulo' => 'Orcamentos',         'url' => site_url('clientes/orcamentos/' . $id)),
        'notas'      => array('rotulo' => 'Notas fiscais',      'url' => site_url('clientes/notas/' . $id)),
        'especiais'  => array('rotulo' => 'Precos especiais',   'url' => site_url('clientes/especiais/' . $id)),
    );
}
?>
<div class="abas">
    <?php foreach ($itens as $chave => $item): ?>
        <a class="aba <?= $aba === $chave ? 'ativa' : '' ?>" href="<?= $item['url'] ?>"><?= html_escape($item['rotulo']) ?></a>
    <?php endforeach; ?>
</div>
