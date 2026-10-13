<?php defined('APP_ROOT') || exit; ?>
<?= partial('encabezado', [
    'volver' => [url('personas'), 'Volver a Personas'],
    'color' => 'rojo',
    'icono' => 'alert',
    'titulo' => '¿Desactivar a esta persona?',
    'texto' => $persona['nombre'] . ' · ' . $persona['codigo_credencial'],
]) ?>

<div class="confirm">
    <p class="confirm__intro">Si la desactivas:</p>
    <ul class="confirm__list">
        <li>Ya no podrá identificarse en el punto de retiro, ni con su credencial ni con su RUT.</li>
        <li>Lo que retiró antes queda en el historial.</li>
        <li>Puedes volver a activarla cuando quieras.</li>
    </ul>
</div>

<form method="post" action="<?= e(url('personas/' . $persona['id'] . '/desactivar')) ?>" data-submit-once>
    <?= csrf_field() ?>
    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e(url('personas')) ?>"><?= icon('close') ?>No, volver</a>
        <button class="btn btn--lg btn--si" type="submit" data-loading-text="Desactivando…"><?= icon('ok') ?>Sí, desactivar</button>
    </div>
</form>
