<?php

use App\Modules\Herramientas\SolicitudService;

defined('APP_ROOT') || exit;

// Una solicitud para el encargado, como el formulario en papel, con lo que se puede hacer según su estado: por
// entregar → «Entregar herramientas» (o rechazarla con un motivo); prestada → «Recibir devolución» o «Extender
// plazo»; devuelta con daños o faltas → «Resolver» cada una. Las administrativas solo la ven.
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
    <div class="hoja__decision hoja__decision--arriba">
        <div class="hoja__acciones">
            <p><?= $atrasada ? '<strong>Está atrasada.</strong> ' : '' ?>Cuando vuelvan, anota cuántas volvieron y en qué estado.</p>
            <a class="btn btn-success" href="<?= e(url($base . '/recibir')) ?>"><?= icon('arrow-in') ?>Recibir devolución</a>
        </div>

        <details class="hoja__plegable"<?= error_de('fecha_devolucion') !== null ? ' open' : '' ?>>
            <summary>¿Necesitan más tiempo? Extender el plazo</summary>
            <form class="hoja__extender" method="post" action="<?= e(url($base . '/extender')) ?>" data-submit-once>
                <?= csrf_field() ?>
                <label class="campo__etiqueta" for="fecha_devolucion">Nueva fecha de recepción</label>
                <div class="hoja__extender-fila">
                    <input class="input" id="fecha_devolucion" name="fecha_devolucion" type="date" min="<?= e(date('Y-m-d')) ?>"
                        value="<?= e(old('fecha_devolucion')) ?>" <?= aria_error('fecha_devolucion') ?>>
                    <button class="btn btn-outline-secondary" type="submit" data-loading-text="Guardando…"><?= icon('clock') ?>Extender plazo</button>
                </div>
                <?= mensaje_error('fecha_devolucion') ?>
            </form>
        </details>
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

        <details class="hoja__plegable"<?= error_de('motivo') !== null ? ' open' : '' ?>>
            <summary>No se puede entregar</summary>
            <form method="post" action="<?= e(url($base . '/rechazar')) ?>" data-submit-once>
                <?= csrf_field() ?>
                <label class="campo__etiqueta" for="motivo">¿Por qué?</label>
                <input class="input" id="motivo" name="motivo" type="text" maxlength="255"
                    value="<?= e(old('motivo')) ?>" <?= aria_error('motivo') ?>>
                <?= mensaje_error('motivo') ?>
                <button class="btn btn-outline-danger" type="submit" data-loading-text="Rechazando…">Rechazar solicitud</button>
            </form>
        </details>
    </div>
<?php endif; ?>

<?php
// Lo que volvió con daños o no volvió, y aún no se resuelve: una línea por herramienta con sus tres salidas.
$problemas = array_values(array_filter($items, static fn (array $item): bool => (int) $item['problema'] === 1));
?>
<?php if ($puedeAdministrar && $problemas !== []) : ?>
    <section class="hoja__problemas" aria-labelledby="titulo-problemas">
        <h2 class="hoja__subtitulo" id="titulo-problemas">Por resolver</h2>
        <p class="text-muted">Volvieron con daños o no volvieron. Cuando se arreglen, se repongan o se descarten, anótalo aquí.</p>
        <ul class="problemas">
            <?php foreach ($problemas as $item) : ?>
                <?php $campo = 'resolucion_' . $item['id']; ?>
                <li class="problema">
                    <p class="problema__texto">
                        <strong><?= e($item['nombre']) ?></strong>
                        <span class="text-muted"><?= e($item['detalle_recepcion'] ?? '') ?></span>
                    </p>
                    <form class="problema__form" method="post" action="<?= e(url($base . '/items/' . $item['id'] . '/resolver')) ?>" data-submit-once>
                        <?= csrf_field() ?>
                        <div class="problema__opciones" role="radiogroup" aria-label="Cómo se resolvió <?= e($item['nombre']) ?>">
                            <?php foreach (SolicitudService::RESOLUCIONES as $clave => $texto) : ?>
                                <label class="problema__opcion">
                                    <input type="radio" name="resolucion" value="<?= e($clave) ?>"> <?= e($texto) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" type="submit" data-loading-text="Guardando…"><?= icon('ok') ?>Resolver</button>
                        <?= mensaje_error($campo) ?>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
