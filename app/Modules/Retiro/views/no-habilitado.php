<?php defined('APP_ROOT') || exit; ?>
<section class="kiosco-tarjeta">
    <span class="kiosco-tarjeta__icono kiosco-tarjeta__icono--gris"><?= icon('lock') ?></span>
    <h1 class="kiosco-tarjeta__titulo">Este computador no está habilitado como punto de retiro</h1>
    <p class="kiosco-tarjeta__texto">
        El encargado de bodega puede habilitarlo aquí mismo: toca el botón e ingresa con su cuenta.
    </p>
    <a class="btn btn--lg btn--primary" href="<?= e(url('punto-de-retiro')) ?>"><?= icon('scan') ?>Habilitar este computador</a>
</section>
