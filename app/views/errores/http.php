<?php

defined('APP_ROOT') || exit;

// 404: no existe; 403: sin permiso; el resto: algo falló.
$icono = match ((int) $codigo) {
    404 => 'search',
    403 => 'lock',
    default => 'alert',
};
?>
<main class="guest-card error-page">
    <?= partial('brand') ?>
    <span class="error-page__icono"><?= icon($icono) ?></span>
    <h1 class="error-page__title"><?= e($titulo) ?></h1>
    <p class="error-page__message"><?= e($mensaje) ?></p>
    <p class="error-page__code">Código <?= e($codigo) ?></p>
    <?php if ($detalle !== null) : ?>
        <pre class="error-page__detail"><?= e($detalle) ?></pre>
    <?php endif; ?>
    <a class="btn btn--primary error-page__volver" href="<?= e(url()) ?>"><?= icon('home') ?>Volver al inicio</a>
</main>
