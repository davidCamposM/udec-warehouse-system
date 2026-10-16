<?php

defined('APP_ROOT') || exit;

// Antes de eliminar a una persona se pregunta en una pantalla propia. Para ella, la cuenta deja de existir; por dentro
// solo se desactiva y sus solicitudes quedan (CuentaService::eliminar).
?>
<?= partial('encabezado', [
    'volver' => [url('usuarios'), 'Volver a Usuarios'],
    'color' => 'rojo',
    'icono' => 'alert',
    'titulo' => '¿Eliminar a esta persona?',
    'texto' => $cuenta['nombre'] . ' · ' . $cuenta['email'],
]) ?>

<div class="confirm">
    <p class="confirm__intro">Si la eliminas:</p>
    <ul class="confirm__list">
        <li>Ya no podrá entrar al sistema.</li>
        <li>Lo que pidió o hizo antes se conserva.</li>
        <li>Si después se vuelve a agregar su correo, recupera el acceso.</li>
    </ul>
</div>

<form method="post" action="<?= e(url('usuarios/' . $cuenta['id'] . '/eliminar')) ?>" data-submit-once>
    <?= csrf_field() ?>
    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e(url('usuarios')) ?>"><?= icon('close') ?>No, volver</a>
        <button class="btn btn--lg btn--si" type="submit" data-loading-text="Eliminando…"><?= icon('ok') ?>Sí, eliminar</button>
    </div>
</form>
