<?php

use App\Core\Config;

defined('APP_ROOT') || exit;

/**
 * Ventana «¿Sigues ahí?» (sesion.js): aparece tras session.idle_minutes sin uso. «Sí» sigue en el sistema; «No», o no
 * responder en session.respuesta_segundos, cierra la sesión. Sin JavaScript no aparece y el servidor cierra la sesión
 * por su cuenta.
 */
$aviso = max(1, (int) Config::get('session.idle_minutes', 20)) * 60;
$respuesta = max(10, (int) Config::get('session.respuesta_segundos', 60));
?>
<dialog class="sesion" aria-labelledby="sesion-titulo" aria-describedby="sesion-texto"
    data-sesion data-aviso="<?= e($aviso) ?>" data-respuesta="<?= e($respuesta) ?>"
    data-seguir-url="<?= e(url('sesion/seguir')) ?>" data-estado-url="<?= e(url('sesion/estado')) ?>">
    <span class="sesion__icono"><?= icon('clock') ?></span>
    <h2 class="sesion__titulo" id="sesion-titulo">¿Sigues ahí?</h2>
    <p class="sesion__texto" id="sesion-texto">
        Hace un rato que no se usa el sistema. Si no respondes, la sesión se cerrará en
        <strong data-sesion-segundos><?= e($respuesta) ?></strong> segundos.
    </p>
    <form class="sesion__acciones" method="post" action="<?= e(url('logout')) ?>" data-sesion-salir>
        <?= csrf_field() ?>
        <input type="hidden" name="motivo" value="" data-sesion-motivo>
        <button class="btn btn--secondary" type="submit"><?= icon('logout') ?>No, cerrar sesión</button>
        <button class="btn btn--si" type="button" data-sesion-sigo autofocus><?= icon('ok') ?>Sí, sigo aquí</button>
    </form>
</dialog>
