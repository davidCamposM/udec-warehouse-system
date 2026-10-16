<?php

use App\Support\Fecha;

defined('APP_ROOT') || exit;

// Etapa 2, su propia ventana («¿Qué llegó?»), en un solo formulario: arriba la reserva (con «Cambiar»); la fila para
// agregar (el código del material de la reserva, 4105031, o el nombre · la cantidad · «Agregar»; Enter o la pistola
// también agregan); debajo, la lista de lo que llegó, cada uno con su cantidad (− / +, se puede escribir) y la ✕ para
// quitarlo. «Ingresar a la bodega» suma todo de una vez. Si la reserva ya se había ingresado (puede llegar en partes),
// se avisa.
$unidades = array_sum(array_column($lista, 'cantidad'));
$cuantos = count($lista);
?>
<div class="abastecimiento">
    <?= partial('campo-fijo', [
        'etiqueta' => 'Reserva',
        'icono' => 'clipboard',
        'valor' => $reserva,
        'cambiar' => url('abastecimiento'),
        'compacto' => true,
    ]) ?>

    <?php if ($anterior !== null) : ?>
        <div class="alert alert--warning" role="status">
            <?= icon('alert') ?>
            <p>
                Esta reserva ya se ingresó el <?= e(Fecha::mostrar($anterior['fecha'])) ?><?= $anterior['quien'] !== null ? ' (' . e($anterior['quien']) . ')' : '' ?>.
                Sigue solo si llegó otra parte.
            </p>
        </div>
    <?php endif; ?>

    <form class="formulario" method="post" action="<?= e(url('abastecimiento/productos')) ?>" data-submit-once>
        <?= csrf_field() ?>

        <?php // La fila para agregar: Enter (o la pistola) usa «Agregar», el primer botón del formulario. ?>
        <div class="abastecimiento__nueva">
            <?= partial('Insumos::partials/buscar-producto', [
                'texto' => $texto,
                'resultados' => $resultados,
                'ruta' => 'abastecimiento',
                'sinEtiqueta' => true,
                'ayuda' => 'Escribe el código del material, como aparece en la reserva (ejemplo: 4105031), o el nombre.',
            ]) ?>
            <div class="abastecimiento__cuantas<?= error_de('cantidad') !== null ? ' has-error' : '' ?>">
                <label class="abastecimiento__etiqueta" for="cantidad">Cantidad</label>
                <input class="input input--relleno" id="cantidad" name="cantidad" type="text" inputmode="numeric" maxlength="5"
                    autocomplete="off" placeholder="1" value="<?= e(old('cantidad')) ?>" <?= aria_error('cantidad') ?>>
            </div>
            <button class="btn btn--tarea abastecimiento__agregar" type="submit" name="accion" value="agregar" data-loading-text="Agregando…">
                <?= icon('plus') ?>Agregar
            </button>
        </div>
        <?= mensaje_error('cantidad') ?>

        <?php if ($agregado !== null) : ?>
            <p class="cantidad-foco__sumada" role="status"><?= icon('check') ?>Se agregó «<?= e($agregado) ?>»</p>
        <?php endif; ?>

        <section class="abastecimiento__lista<?= error_de('lista') !== null ? ' has-error' : '' ?>" aria-labelledby="titulo-lista">
            <h2 class="abastecimiento__titulo" id="titulo-lista">
                Lo que llegó<?= $lista !== [] ? ' · ' . e($cuantos === 1 ? '1 producto' : "{$cuantos} productos") . ', ' . e(unidades($unidades)) : '' ?>
            </h2>
            <?= mensaje_error('lista') ?>
            <?php if ($lista === []) : ?>
                <p class="abastecimiento__vacio">Todavía no agregas productos. Escribe el primero arriba.</p>
            <?php else : ?>
                <ul class="abastecimiento__filas">
                    <?php foreach ($lista as ['insumo' => $insumo, 'cantidad' => $cantidad]) : ?>
                        <?php $campo = 'cantidad-' . $insumo['id']; ?>
                        <li class="abastecimiento__fila">
                            <span class="abastecimiento__producto">
                                <strong><?= e($insumo['nombre']) ?></strong>
                                <span><?= e($insumo['codigo_udec'] ?? $insumo['codigo']) ?></span>
                            </span>
                            <span class="abastecimiento__stepper" data-stepper data-min="1" data-max="99999">
                                <button class="abastecimiento__paso" type="button" data-step="-1"><?= icon('minus') ?><span class="visually-hidden">Restar uno</span></button>
                                <label class="visually-hidden" for="<?= e($campo) ?>">Cantidad de <?= e($insumo['nombre']) ?></label>
                                <input class="abastecimiento__numero" id="<?= e($campo) ?>" name="cantidades[<?= e($insumo['id']) ?>]" type="text"
                                    inputmode="numeric" maxlength="5" autocomplete="off" value="<?= e($cantidad) ?>">
                                <button class="abastecimiento__paso" type="button" data-step="1"><?= icon('plus') ?><span class="visually-hidden">Sumar uno</span></button>
                            </span>
                            <button class="btn btn-outline-secondary btn-sm abastecimiento__quitar" type="submit" name="quitar" value="<?= e($insumo['id']) ?>"
                                title="Quitar de la lista"><?= icon('close') ?><span class="visually-hidden">Quitar <?= e($insumo['nombre']) ?></span></button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <div class="formulario__acciones">
            <a class="btn btn--secondary" href="<?= e(url('abastecimiento')) ?>"><?= icon('arrow-left') ?>Atrás</a>
            <button class="btn btn--si" type="submit" name="accion" value="ingresar" data-loading-text="Ingresando…" <?= $lista === [] ? 'disabled' : '' ?>>
                <?= icon('ok') ?>Ingresar a la bodega
            </button>
        </div>
    </form>
</div>
