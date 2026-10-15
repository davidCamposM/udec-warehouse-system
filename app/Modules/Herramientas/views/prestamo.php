<?php

defined('APP_ROOT') || exit;

// Una solicitud para el encargado, como el formulario en papel, con lo que se puede hacer según su estado: por
// entregar → «Entregar herramientas» (o rechazarla con un motivo); prestada → «Recibir devolución». Las
// administrativas solo la ven.
$base = 'prestamos/' . $solicitud['id'];
?>
<?= partial('encabezado', [
    'volver' => [url('prestamos'), 'Volver a Préstamos de herramientas'],
    'tarea' => 'prestamos',
    'titulo' => 'Solicitud N° ' . $solicitud['id'],
    'texto' => $solicitud['docente'] . ' · ' . $solicitud['tipo_actividad'],
]) ?>

<?= mensaje_error('solicitud') ?>

<?php if ($puedeAdministrar && $solicitud['estado'] === 'prestada') : ?>
    <div class="hoja__acciones hoja__acciones--arriba">
        <p><?= $atrasada ? '<strong>Está atrasada.</strong> ' : '' ?>Cuando vuelvan, anota cuántas volvieron y en qué estado.</p>
        <a class="btn btn-success" href="<?= e(url($base . '/recibir')) ?>"><?= icon('arrow-in') ?>Recibir devolución</a>
    </div>
<?php endif; ?>

<?= partial('Herramientas::partials/hoja', ['solicitud' => $solicitud, 'items' => $items, 'atrasada' => $atrasada]) ?>

<?php if ($puedeAdministrar && $solicitud['estado'] === 'pendiente') : ?>
    <div class="hoja__decision">
        <form class="hoja__acciones" method="post" action="<?= e(url($base . '/entregar')) ?>" data-submit-once>
            <?= csrf_field() ?>
            <p>Al entregar las herramientas a <?= e($solicitud['autoriza_retiro'] ?? $solicitud['docente']) ?>, tócalo para que quede prestada.</p>
            <button class="btn btn-success" type="submit" data-loading-text="Un momento…"><?= icon('arrow-out') ?>Entregar herramientas</button>
        </form>

        <details class="hoja__rechazar"<?= error_de('motivo') !== null ? ' open' : '' ?>>
            <summary>No se puede entregar</summary>
            <form method="post" action="<?= e(url($base . '/rechazar')) ?>" data-submit-once>
                <?= csrf_field() ?>
                <label class="campo__etiqueta" for="motivo">¿Por qué?</label>
                <p class="campo__ayuda" id="motivo-ayuda">Opcional. El docente lo verá. Ejemplo: no hay hipsómetros disponibles ese día.</p>
                <input class="input" id="motivo" name="motivo" type="text" maxlength="255" aria-describedby="motivo-ayuda"
                    value="<?= e(old('motivo')) ?>" <?= aria_error('motivo') ?>>
                <?= mensaje_error('motivo') ?>
                <button class="btn btn-outline-danger" type="submit" data-loading-text="Rechazando…">Rechazar solicitud</button>
            </form>
        </details>
    </div>
<?php endif; ?>
