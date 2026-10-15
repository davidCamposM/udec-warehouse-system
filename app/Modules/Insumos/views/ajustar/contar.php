<?php

defined('APP_ROOT') || exit;

// Etapa 2, su propia ventana («¿Cuántas hay realmente?»): el producto elegido (con «Cambiar»), el número contado en
// grande y al centro, y por qué cambia (los motivos en columnas, para que todo quepa sin bajar). «Actualizar» deja la
// cantidad nueva al tiro y vuelve a la etapa 1 con la boleta.
$elegido = old('motivo', $motivo);
$detalleProducto = $insumo['codigo'] . ($insumo['sector'] !== null ? ' · ' . $insumo['sector'] : '');
?>

<form class="formulario" method="post" action="<?= e(url('ajustar/contar')) ?>" data-submit-once>
    <?= csrf_field() ?>
    <?php // Lo que decía el sistema al contar: si cambia antes de actualizar, el servidor avisa en vez de pisarlo. ?>
    <input type="hidden" name="visto" value="<?= e($insumo['stock_actual']) ?>">

    <?php if (errores() !== []) : ?>
        <div class="alert alert--danger" role="alert">
            <?= icon('alert') ?>
            <p>Falta algo o hay que corregir un dato. Revisa lo marcado en rojo.</p>
        </div>
    <?php endif; ?>

    <?= partial('campo-fijo', [
        'etiqueta' => 'Producto',
        'icono' => 'box',
        'valor' => $insumo['nombre'],
        'detalle' => $detalleProducto,
        'cambiar' => url('ajustar'),
    ]) ?>

    <div class="cantidad-foco<?= error_de('contado') !== null ? ' has-error' : '' ?>">
        <label class="visually-hidden" for="contado">¿Cuántas hay realmente?</label>
        <p class="cantidad-foco__sistema" id="contado-ayuda">
            El sistema dice <strong><?= e(unidades($insumo['stock_actual'])) ?></strong>. Cuéntalas y escribe el total que hay
            (0 si no queda ninguna).
        </p>
        <div class="cantidad-foco__control" data-stepper data-min="0">
            <div class="cantidad-foco__fila">
                <button class="cantidad-foco__boton" type="button" data-step="-1"><?= icon('minus') ?><span class="visually-hidden">Restar uno</span></button>
                <input class="cantidad-foco__numero" id="contado" name="contado" type="text" inputmode="numeric" autocomplete="off"
                    autofocus aria-required="true" aria-describedby="contado-ayuda"
                    value="<?= e(old('contado', $contado)) ?>" <?= aria_error('contado') ?>>
                <button class="cantidad-foco__boton" type="button" data-step="1"><?= icon('plus') ?><span class="visually-hidden">Sumar uno</span></button>
            </div>
            <p class="cantidad-foco__unidad">unidades</p>
        </div>
        <?= mensaje_error('contado') ?>
    </div>

    <fieldset class="campo<?= error_de('motivo') !== null ? ' has-error' : '' ?>">
        <legend class="campo__etiqueta">¿Por qué cambia?<span class="campo__obligatorio" aria-hidden="true">*</span></legend>
        <div class="options options--motivos">
            <?php foreach ($motivos as $clave => $texto) : ?>
                <label class="option">
                    <input class="option__input" type="radio" name="motivo" value="<?= e($clave) ?>"
                        <?= $clave === $elegido ? 'checked' : '' ?>>
                    <span class="option__box"><?= e($texto) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?= mensaje_error('motivo') ?>
    </fieldset>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url('ajustar')) ?>"><?= icon('arrow-left') ?>Atrás</a>
        <button class="btn btn--si" type="submit" data-loading-text="Actualizando…"><?= icon('ok') ?>Actualizar</button>
    </div>
</form>
