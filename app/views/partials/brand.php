<?php

defined('APP_ROOT') || exit;

// $variante opcional: 'inverse' para fondos de color (barra superior).
$clase = isset($variante) ? 'brand brand--' . $variante : 'brand';
?>
<a class="<?= e($clase) ?>" href="<?= e(url()) ?>">
    <span class="brand__mark"><?= icon('tree') ?></span>
    <span class="brand__text">
        <span class="brand__name">Bodega FCF</span>
        <span class="brand__sub">Ciencias Forestales · UdeC</span>
    </span>
</a>
