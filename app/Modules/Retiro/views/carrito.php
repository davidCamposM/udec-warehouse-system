<?php

defined('APP_ROOT') || exit;

// Lo que la persona escanea: al retirar, lo que se lleva; al registrar una entrada (auxiliares), lo que llegó.
$entrada = $modo === 'entrada';
?>
<div class="retiro tono-<?= $entrada ? 'verde' : 'grafito' ?>" data-retiro data-inactividad="<?= e($inactividad) ?>" data-sigo-url="<?= e(url('retiro/sigo')) ?>"
    data-terminar-url="<?= e(url('retiro/terminar')) ?>" data-salir-url="<?= e(url('retiro')) ?>">
    <section class="retiro__lectura">
        <h1 class="retiro__saludo">Hola, <?= e(explode(' ', $persona['nombre'])[0]) ?>. <?= $entrada ? 'Escanea lo que llegó' : 'Escanea lo que retiras' ?></h1>

        <form class="scan-box tono-<?= $entrada ? 'verde' : 'grafito' ?>" method="post" action="<?= e(url('retiro/leer')) ?>" data-retiro-lectura>
            <?= csrf_field() ?>
            <label class="scan-box__label" for="codigo">
                <span class="scan-box__icono"><?= icon('barcode') ?></span>
                <span>El código del producto <span class="scan-box__o">o escribe su nombre</span></span>
            </label>
            <div class="scan-box__fila">
                <input class="scan-box__input" data-lectura id="codigo" name="codigo" type="text" autocomplete="off" autofocus
                    <?= aria_error('codigo') ?>>
                <button class="btn btn--lg scan-box__boton" type="submit"><?= icon('plus') ?>Agregar</button>
            </div>
        </form>

        <div class="retiro__mensaje" data-retiro-mensaje aria-live="polite">
            <?php if (error_de('codigo') !== null) : ?>
                <div class="alert alert--danger flash" role="alert"><?= icon('alert') ?><p><?= e(error_de('codigo')) ?></p></div>
            <?php endif; ?>
            <?= partial('flash') ?>
        </div>
    </section>

    <div data-retiro-pantalla>
        <?= partial('Retiro::partials/pantalla', [
            'modo' => $modo,
            'lineas' => $lineas,
            'total' => $total,
            'texto' => $texto,
            'resultados' => $resultados,
        ]) ?>
    </div>

    <div class="retiro__sigues" data-retiro-sigues hidden role="alertdialog" aria-labelledby="sigues-titulo">
        <p class="retiro__sigues-titulo" id="sigues-titulo">¿Sigues ahí?</p>
        <p>Si no tocas nada, la pantalla se cerrará en <strong data-retiro-cuenta>15</strong> segundos.</p>
        <button class="btn btn--lg btn--si" type="button" data-retiro-sigo><?= icon('ok') ?>Sí, sigo aquí</button>
    </div>
</div>
