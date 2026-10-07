<?php defined('APP_ROOT') || exit; ?>
<main class="error-page">
    <?= partial('brand') ?>
    <p class="error-page__code"><?= e($codigo) ?></p>
    <h1 class="error-page__title"><?= e($titulo) ?></h1>
    <p class="error-page__message"><?= e($mensaje) ?></p>
    <?php if ($detalle !== null) : ?>
        <pre class="error-page__detail"><?= e($detalle) ?></pre>
    <?php endif; ?>
    <a class="btn btn--primary" href="<?= e(url()) ?>">Volver al inicio</a>
</main>
