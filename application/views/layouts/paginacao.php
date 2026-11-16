<?php
/**
 * Partial de paginacao.
 *
 * Variaveis esperadas:
 *   $pag   - array retornado por MY_Controller::paginar()
 *   $base  - URL base (ex.: site_url('clientes'))
 *   $busca - termo de busca (opcional)
 *   $extra - array de parametros adicionais (opcional)
 */

$extra = isset($extra) ? $extra : array();
$busca = isset($busca) ? $busca : '';
$ordem = isset($ordem) ? $ordem : '';

function _url_pagina($base, $pagina, $busca, $ordem, $extra)
{
    $params = array('pagina' => $pagina);

    if ($busca !== '')
    {
        $params['busca'] = $busca;
    }
    if ($ordem !== '')
    {
        $params['ordem'] = $ordem;
    }
    foreach ($extra as $k => $v)
    {
        $params[$k] = $v;
    }

    return $base . '?' . http_build_query($params);
}

$de = $pag['total'] === 0 ? 0 : $pag['offset'] + 1;
$ate = min($pag['offset'] + $pag['por_pagina'], $pag['total']);
?>
<div class="paginacao">
    <div>
        Exibindo <strong><?= $de ?></strong> a <strong><?= $ate ?></strong> de <strong><?= $pag['total'] ?></strong> registro(s)
    </div>

    <?php if ($pag['paginas'] > 1): ?>
        <div class="paginas">
            <a class="<?= $pag['pagina'] <= 1 ? 'desabilitado' : '' ?>"
               href="<?= _url_pagina($base, max(1, $pag['pagina'] - 1), $busca, $ordem, $extra) ?>">&laquo; Anterior</a>

            <?php
            $inicio = max(1, $pag['pagina'] - 2);
            $fim    = min($pag['paginas'], $pag['pagina'] + 2);

            for ($i = $inicio; $i <= $fim; $i++):
                if ($i === (int) $pag['pagina']): ?>
                    <span class="atual"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= _url_pagina($base, $i, $busca, $ordem, $extra) ?>"><?= $i ?></a>
                <?php endif;
            endfor;
            ?>

            <a class="<?= $pag['pagina'] >= $pag['paginas'] ? 'desabilitado' : '' ?>"
               href="<?= _url_pagina($base, min($pag['paginas'], $pag['pagina'] + 1), $busca, $ordem, $extra) ?>">Proxima &raquo;</a>
        </div>
    <?php endif; ?>
</div>
