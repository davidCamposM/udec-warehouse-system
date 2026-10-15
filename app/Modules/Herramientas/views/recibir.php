<?php

defined('APP_ROOT') || exit;

// «Recibir devolución»: el «Estado en la recepción» del formulario en papel. Por cada herramienta, cuántas volvieron
// (parte con todas) y en qué estado; abajo, una nota opcional. Al guardar, el préstamo queda devuelto.
?>
<?= partial('encabezado', [
    'volver' => [url('prestamos/' . $solicitud['id']), 'Volver a la solicitud'],
    'tarea' => 'prestamos',
    'titulo' => 'Recibir devolución',
    'texto' => 'Solicitud N° ' . $solicitud['id'] . ' · ' . $solicitud['docente'] . ' · ' . $solicitud['tipo_actividad'],
]) ?>

<?= mensaje_error('recepcion') ?>

<form class="hoja hoja--formulario" method="post" action="<?= e(url('prestamos/' . $solicitud['id'] . '/recibir')) ?>" data-submit-once>
    <?= csrf_field() ?>
    <h2 class="hoja__subtitulo">Estado en la recepción</h2>

    <ul class="recepcion">
        <?php foreach ($items as $item) : ?>
            <?php
            $devueltas = 'devueltas_' . $item['id'];
            $estado = 'estado_' . $item['id'];
            $elegido = old($estado, 'bien');
            ?>
            <li class="recepcion__linea">
                <p class="recepcion__nombre"><?= e($item['nombre']) ?> <span class="text-muted">· se prestaron <?= e($item['cantidad']) ?></span></p>
                <div class="recepcion__campos">
                    <div class="campo">
                        <label class="campo__etiqueta" for="<?= e($devueltas) ?>">¿Cuántas volvieron?</label>
                        <div class="stepper stepper--chico" data-stepper data-min="0" data-max="<?= e($item['cantidad']) ?>">
                            <button class="stepper__button" type="button" data-step="-1"><?= icon('minus') ?><span class="visually-hidden">Una menos</span></button>
                            <input class="stepper__input" id="<?= e($devueltas) ?>" name="<?= e($devueltas) ?>" type="text" inputmode="numeric"
                                value="<?= e(old($devueltas, (string) $item['cantidad'])) ?>" <?= aria_error($devueltas) ?>>
                            <button class="stepper__button" type="button" data-step="1"><?= icon('plus') ?><span class="visually-hidden">Una más</span></button>
                        </div>
                        <?= mensaje_error($devueltas) ?>
                    </div>
                    <fieldset class="campo">
                        <legend class="campo__etiqueta">¿Cómo volvieron?</legend>
                        <div class="options">
                            <?php foreach (['bien' => 'En buen estado', 'danada' => 'Con daños'] as $clave => $texto) : ?>
                                <label class="option">
                                    <input class="option__input" type="radio" name="<?= e($estado) ?>" value="<?= e($clave) ?>"
                                        <?= $clave === $elegido ? 'checked' : '' ?>>
                                    <span class="option__box"><?= e($texto) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?= mensaje_error($estado) ?>
                    </fieldset>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="campo">
        <label class="campo__etiqueta" for="observacion_recepcion">Nota</label>
        <p class="campo__ayuda" id="observacion_recepcion-ayuda">Opcional. Ejemplo: un hipsómetro volvió sin su estuche.</p>
        <textarea class="input hoja__texto" id="observacion_recepcion" name="observacion_recepcion" rows="2" maxlength="500"
            aria-describedby="observacion_recepcion-ayuda" <?= aria_error('observacion_recepcion') ?>><?= e(old('observacion_recepcion')) ?></textarea>
        <?= mensaje_error('observacion_recepcion') ?>
    </div>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url('prestamos/' . $solicitud['id'])) ?>"><?= icon('close') ?>Cancelar</a>
        <button class="btn btn--si" type="submit" data-loading-text="Guardando…"><?= icon('ok') ?>Guardar devolución</button>
    </div>
</form>
