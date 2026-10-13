<?php defined('APP_ROOT') || exit; ?>
<?php if ($ingresado !== null) : ?>
    <?php // Entrada de un auxiliar: sigue identificado; puede registrar otra o terminar. ?>
    <div class="result result--retiro">
        <span class="result__icon"><?= icon('ok') ?></span>
        <h1 class="result__title">Listo, <?= e(explode(' ', $ingresado['persona'])[0]) ?></h1>
        <p class="result__texto">Se registró en <strong><?= e($ingresado['sector']) ?></strong>:</p>
        <ul class="retirado">
            <?php foreach ($ingresado['lineas'] as $linea) : ?>
                <li><strong><?= e(unidades($linea['cantidad'])) ?></strong> · <?= e($linea['nombre']) ?> (ahora hay <?= e(unidades($linea['stock'])) ?>)</li>
            <?php endforeach; ?>
        </ul>
        <p class="result__detalle">Quedó anotado a tu nombre.</p>
        <div class="retiro-listo__acciones">
            <form method="post" action="<?= e(url('retiro/terminar')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn--lg btn--secondary" type="submit"><?= icon('close') ?>Terminar</button>
            </form>
            <a class="btn btn--lg btn--si" href="<?= e(url('retiro')) ?>" autofocus><?= icon('plus') ?>Registrar otra entrada</a>
        </div>
    </div>
<?php else : ?>
    <div class="result result--retiro">
        <span class="result__icon"><?= icon('ok') ?></span>
        <h1 class="result__title">Listo, <?= e(explode(' ', $retirado['persona'])[0]) ?></h1>
        <p class="result__texto">Retiraste:</p>
        <ul class="retirado">
            <?php foreach ($retirado['lineas'] as $linea) : ?>
                <li><strong><?= e(unidades($linea['cantidad'])) ?></strong> · <?= e($linea['nombre']) ?></li>
            <?php endforeach; ?>
        </ul>
        <p class="result__detalle">Quedó anotado a tu nombre. La pantalla vuelve al inicio en unos segundos.</p>
        <a class="btn btn--lg btn--tarea tono-grafito" href="<?= e(url('retiro')) ?>" autofocus><?= icon('ok') ?>Terminar</a>
    </div>
<?php endif; ?>
