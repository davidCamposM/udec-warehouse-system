<?php

defined('APP_ROOT') || exit;

// Una solicitud del docente, como el formulario en papel. Mientras está por entregar, la puede cancelar.
?>
<?= partial('encabezado', [
    'volver' => [url('herramientas/mis-solicitudes'), 'Volver a Mis solicitudes'],
    'tarea' => 'mis-solicitudes',
    'titulo' => 'Solicitud N° ' . $solicitud['id'],
    'texto' => $solicitud['tipo_actividad'],
]) ?>

<?= mensaje_error('solicitud') ?>

<?= partial('Herramientas::partials/hoja', ['solicitud' => $solicitud, 'items' => $items, 'atrasada' => $atrasada]) ?>

<?php if ($solicitud['estado'] === 'pendiente') : ?>
    <form class="hoja__acciones" method="post" action="<?= e(url('herramientas/solicitudes/' . $solicitud['id'] . '/cancelar')) ?>" data-submit-once>
        <?= csrf_field() ?>
        <p class="text-muted">¿Ya no las necesitas? Cancélala para que la bodega no las prepare.</p>
        <button class="btn btn-outline-danger" type="submit" data-loading-text="Cancelando…"><?= icon('close') ?>Cancelar solicitud</button>
    </form>
<?php endif; ?>
