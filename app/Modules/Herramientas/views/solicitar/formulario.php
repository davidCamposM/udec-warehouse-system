<?php

use App\Modules\Herramientas\SolicitudService;
use App\Support\Fecha;

defined('APP_ROOT') || exit;

// «Solicitar herramientas», en una ventana ancha: el formulario en papel «Solicitud de instrumentos y herramientas»,
// en el mismo orden pero en columnas, para verlo casi entero sin bajar.
// - Arriba, los datos de a dos por fila: departamento y funcionario (el de la cuenta: no se escribe ni se firma), tipo
//   de actividad y quién retira, y las dos fechas junto a «¿Necesitas algo más?».
// - Abajo, los detalles: las herramientas del catálogo por categoría, una ficha por herramienta con sus unidades (− y
//   +), varias por fila. Arriba de ellas, el buscador y las categorías, para no recorrer la lista entera. La ficha
//   elegida se marca y bajo ellas se ve el total que se lleva (herramientas.js). Las que quedan en 0 no se piden.
//   «Estado en la recepción» lo anota la bodega al recibir.
$hoy = Fecha::hoy();
$departamento = old('departamento');
?>
<form class="formulario pedir" method="post" action="<?= e(url('herramientas/solicitar')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <div class="hoja hoja--formulario">
        <?= partial('Herramientas::partials/membrete') ?>

        <div class="pedir__datos">
            <fieldset class="campo<?= error_de('departamento') !== null ? ' has-error' : '' ?>">
                <legend class="campo__etiqueta">Departamento<span class="campo__obligatorio" aria-hidden="true">*</span></legend>
                <div class="options pedir__departamentos">
                    <?php foreach (SolicitudService::DEPARTAMENTOS as $clave => $nombre) : ?>
                        <label class="option">
                            <input class="option__input" type="radio" name="departamento" value="<?= e($clave) ?>"
                                <?= $clave === $departamento ? 'checked' : '' ?>>
                            <span class="option__box"><?= e($nombre) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?= mensaje_error('departamento') ?>
            </fieldset>

            <?= partial('campo-fijo', [
                'etiqueta' => 'Nombre del funcionario',
                'icono' => 'user',
                'valor' => $nombre,
                'detalle' => 'Va con tu cuenta: no necesitas firmar.',
            ]) ?>

            <div class="campo">
                <label class="campo__etiqueta" for="tipo_actividad">Tipo de actividad<span class="campo__obligatorio" aria-hidden="true">*</span></label>
                <input class="input input--relleno" id="tipo_actividad" name="tipo_actividad" type="text" maxlength="255" autofocus
                    aria-required="true" placeholder="Ejemplo: Terreno de ecología en la cantera"
                    value="<?= e(old('tipo_actividad')) ?>" <?= aria_error('tipo_actividad') ?>>
                <?= mensaje_error('tipo_actividad') ?>
            </div>

            <div class="campo">
                <label class="campo__etiqueta" for="autoriza_retiro">Autoriza el retiro a <span class="campo__opcional">(opcional)</span></label>
                <input class="input input--relleno" id="autoriza_retiro" name="autoriza_retiro" type="text" maxlength="150"
                    placeholder="Quien las retira, si no eres tú" value="<?= e(old('autoriza_retiro')) ?>" <?= aria_error('autoriza_retiro') ?>>
                <?= mensaje_error('autoriza_retiro') ?>
            </div>

            <div class="pedir__fechas">
                <div class="campo">
                    <label class="campo__etiqueta" for="fecha_retiro">Fecha de retiro<span class="campo__obligatorio" aria-hidden="true">*</span></label>
                    <input class="input input--relleno" id="fecha_retiro" name="fecha_retiro" type="date" min="<?= e($hoy) ?>"
                        aria-required="true" value="<?= e(old('fecha_retiro')) ?>" <?= aria_error('fecha_retiro') ?>>
                    <?= mensaje_error('fecha_retiro') ?>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="fecha_devolucion">Fecha de recepción<span class="campo__obligatorio" aria-hidden="true">*</span></label>
                    <input class="input input--relleno" id="fecha_devolucion" name="fecha_devolucion" type="date" min="<?= e($hoy) ?>"
                        aria-required="true" title="El día en que vuelven a la bodega" value="<?= e(old('fecha_devolucion')) ?>"
                        <?= aria_error('fecha_devolucion') ?>>
                    <?= mensaje_error('fecha_devolucion') ?>
                </div>
            </div>

            <div class="campo">
                <label class="campo__etiqueta" for="observacion">¿Necesitas algo más? <span class="campo__opcional">(opcional)</span></label>
                <input class="input input--relleno" id="observacion" name="observacion" type="text" maxlength="500"
                    placeholder="Algo que no está en la lista, y cuántas" value="<?= e(old('observacion')) ?>" <?= aria_error('observacion') ?>>
                <?= mensaje_error('observacion') ?>
            </div>
        </div>

        <fieldset class="pedir__detalles<?= error_de('detalle') !== null ? ' has-error' : '' ?>">
            <legend class="pedir__titulo">
                Detalles <span class="pedir__instruccion">Con − y + elige cuántas unidades de cada una. Las que quedan en 0 no se piden.</span>
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
                                ?>
                                <li class="pedir__herramienta<?= (int) $unidades > 0 ? ' is-elegida' : '' ?>" data-herramienta
                                    data-nombre="<?= e($herramienta['nombre']) ?>">
                                    <span class="pedir__nombre">
                                        <label for="<?= e($campo) ?>"><?= e($herramienta['nombre']) ?></label>
                                        <?php if ((int) $herramienta['prestadas'] > 0) : ?>
                                            <span class="pedir__descripcion"><?= e($herramienta['prestadas']) ?> prestadas ahora</span>
                                        <?php endif; ?>
                                    </span>
                                    <div class="stepper stepper--chico" data-stepper data-min="0" data-max="<?= e(SolicitudService::MAXIMO_UNIDADES) ?>">
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

            <p class="pedir__pie">
                <span class="pedir__total" data-total aria-live="polite">Todavía no eliges herramientas.</span>
                <span class="text-muted">El estado en la recepción lo anota la bodega cuando vuelven.</span>
            </p>
        </fieldset>
    </div>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url()) ?>"><?= icon('close') ?>Cancelar</a>
        <button class="btn btn--si" type="submit" data-loading-text="Enviando…"><?= icon('ok') ?>Enviar solicitud</button>
    </div>
</form>
