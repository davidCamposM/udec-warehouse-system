<?php defined('APP_ROOT') || exit; ?>
<?= partial('encabezado', [
    'volver' => [url('punto-de-retiro'), 'Volver a Punto de retiro'],
    'color' => 'rojo',
    'icono' => 'alert',
    'titulo' => '¿Deshabilitar este equipo?',
    'texto' => $equipo['nombre'],
]) ?>

<div class="confirm">
    <p class="confirm__intro">Si lo deshabilitas:</p>
    <ul class="confirm__list">
        <li>En ese computador ya no se podrá retirar.</li>
        <li>Lo que se retiró antes queda en el historial.</li>
        <li>Para volver a usarlo, habilítalo otra vez desde ese computador.</li>
    </ul>
</div>

<form method="post" action="<?= e(url('punto-de-retiro/' . $equipo['id'] . '/deshabilitar')) ?>" data-submit-once>
    <?= csrf_field() ?>
    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e(url('punto-de-retiro')) ?>"><?= icon('close') ?>No, volver</a>
        <button class="btn btn--lg btn--si" type="submit" data-loading-text="Deshabilitando…"><?= icon('ok') ?>Sí, deshabilitar</button>
    </div>
</form>
