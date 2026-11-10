<?php

use App\Modules\Herramientas\SolicitudService;
use App\Support\Fecha;

defined('APP_ROOT') || exit;

// «Solicitar herramientas»: datos esenciales en dos filas y catálogo acotado dentro de la ventana.
// - Los datos: departamento, funcionario de la cuenta, actividad, fechas y quién retira.
// - Los detalles: el buscador, las categorías y una línea por herramienta con sus unidades (− y +), varias por fila.
//   La elegida se marca y abajo se ve el total que se lleva (herramientas.js). Las que quedan en 0 no se piden. Junto
//   al nombre, cuántas hay en bodega hoy; el + llega hasta las que tiene la bodega (pueden volver antes del retiro).
//   «Estado en la recepción» lo anota la bodega al recibir.
$hoy = Fecha::hoy();
$departamento = old('departamento');
$invalido = static fn (string $campo): string => error_de($campo) !== null ? ' is-invalid' : '';
?>
<form class="pedir form-bs" method="post" action="<?= e(url('herramientas/solicitar')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <div class="hoja hoja--formulario">
        <p class="pedir__solicitante">
            <span>Nombre del funcionario</span>
            <strong><?= e($nombre) ?></strong>
        </p>

        <div class="pedir__datos">
            <fieldset class="form-group pedir__departamento">
                <legend class="form-label">Departamento <span class="form-requerido" aria-hidden="true">*</span></legend>
                <div class="form-checks">
                    <?php foreach (SolicitudService::DEPARTAMENTOS as $clave => $texto) : ?>
                        <div class="form-check">
                            <input class="form-check-input" id="departamento-<?= e($clave) ?>" type="radio" name="departamento"
                                value="<?= e($clave) ?>" <?= $clave === $departamento ? 'checked' : '' ?>>
                            <label class="form-check-label" for="departamento-<?= e($clave) ?>"><?= e($texto) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?= mensaje_error('departamento') ?>
            </fieldset>

            <div class="form-group pedir__actividad">
                <label class="form-label" for="tipo_actividad">Tipo de actividad <span class="form-requerido" aria-hidden="true">*</span></label>
                <input class="form-control<?= $invalido('tipo_actividad') ?>" id="tipo_actividad" name="tipo_actividad" type="text"
                    maxlength="255" autofocus aria-required="true" placeholder="Ejemplo: Terreno de ecología en la cantera"
                    value="<?= e(old('tipo_actividad')) ?>" <?= aria_error('tipo_actividad') ?>>
                <?= mensaje_error('tipo_actividad') ?>
            </div>

            <div class="form-group pedir__retiro">
                <label class="form-label" for="fecha_retiro">Fecha de retiro <span class="form-requerido" aria-hidden="true">*</span></label>
                <input class="form-control<?= $invalido('fecha_retiro') ?>" id="fecha_retiro" name="fecha_retiro" type="date"
                    min="<?= e($hoy) ?>" aria-required="true" value="<?= e(old('fecha_retiro')) ?>" <?= aria_error('fecha_retiro') ?>>
                <?= mensaje_error('fecha_retiro') ?>
            </div>

            <div class="form-group pedir__recepcion">
                <label class="form-label" for="fecha_devolucion">Fecha de recepción <span class="form-requerido" aria-hidden="true">*</span></label>
                <input class="form-control<?= $invalido('fecha_devolucion') ?>" id="fecha_devolucion" name="fecha_devolucion" type="date"
                    min="<?= e($hoy) ?>" aria-required="true" title="El día en que vuelven a la bodega"
                    value="<?= e(old('fecha_devolucion')) ?>" <?= aria_error('fecha_devolucion') ?>>
                <?= mensaje_error('fecha_devolucion') ?>
            </div>

            <div class="form-group pedir__autoriza">
                <label class="form-label" for="autoriza_retiro">Autoriza el retiro a <span class="form-opcional">(opcional)</span></label>
                <input class="form-control<?= $invalido('autoriza_retiro') ?>" id="autoriza_retiro" name="autoriza_retiro" type="text"
                    maxlength="150" placeholder="Quien las retira, si no eres tú" value="<?= e(old('autoriza_retiro')) ?>"
                    <?= aria_error('autoriza_retiro') ?>>
                <?= mensaje_error('autoriza_retiro') ?>
            </div>
        </div>

        <fieldset class="pedir__detalles">
            <legend class="pedir__titulo">
                Herramientas
            </legend>
            <?= mensaje_error('detalle') ?>

            <?= partial('Herramientas::partials/buscador', ['grupos' => $grupos, 'conElegidas' => true]) ?>

            <div class="pedir__grupos">
                <?php foreach (array_values($grupos) as $i => $herramientas) : ?>
                    <section class="pedir__grupo" data-grupo="<?= e($i) ?>">
                        <h4 class="pedir__categoria"><?= e(array_keys($grupos)[$i]) ?></h4>
                        <ul class="pedir__herramientas">
                            <?php foreach ($herramientas as $herramienta) : ?>
                                <?php
                                $campo = 'unidades_' . $herramienta['id'];
                                $unidades = old($campo, '0');
                                $hay = (int) $herramienta['en_bodega'];
                                $maximo = min(SolicitudService::MAXIMO_UNIDADES, (int) $herramienta['unidades']);
                                ?>
                                <li class="pedir__herramienta<?= (int) $unidades > 0 ? ' is-elegida' : '' ?>" data-herramienta
                                    data-nombre="<?= e($herramienta['nombre']) ?>">
                                    <label class="pedir__nombre" for="<?= e($campo) ?>">
                                        <?= e($herramienta['nombre']) ?>
                                        <span class="pedir__hay"><?= e($hay === 0 ? 'Ninguna en bodega hoy' : "{$hay} en bodega") ?></span>
                                    </label>
                                    <div class="stepper stepper--chico" data-stepper data-min="0" data-max="<?= e($maximo) ?>">
                                        <button class="stepper__button" type="button" data-step="-1"><?= icon('minus') ?><span class="visually-hidden">Una menos de <?= e($herramienta['nombre']) ?></span></button>
                                        <input class="stepper__input" id="<?= e($campo) ?>" name="<?= e($campo) ?>" type="text" inputmode="numeric"
                                            value="<?= e($unidades) ?>">
                                        <button class="stepper__button" type="button" data-step="1"><?= icon('plus') ?><span class="visually-hidden">Una más de <?= e($herramienta['nombre']) ?></span></button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endforeach; ?>
            </div>
        </fieldset>
    </div>

    <div class="pedir__acciones">
        <a class="btn btn-outline-secondary" href="<?= e(url()) ?>">Cancelar</a>
        <button class="btn btn-primary" type="submit" data-loading-text="Enviando…"><?= icon('ok') ?>Enviar solicitud</button>
    </div>
</form>
