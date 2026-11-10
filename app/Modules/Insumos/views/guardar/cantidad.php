<?php

defined('APP_ROOT') || exit;

// Etapa 2, su propia ventana («¿Cuántas unidades llegaron?»): el número en grande y al centro. Parte en 1 (la unidad
// recién escaneada). Se cambia con − y +, con +5 / +10, escribiendo el número o escaneando otra vez el mismo producto,
// que suma 1. Enter pasa a «Dónde quedó». Arriba, el producto elegido, con «Cambiar».
$detalle = codigo_producto($insumo) . ' · Hay ' . unidades($insumo['stock_actual']) . ' en bodega'
    . ($insumo['sector'] !== null ? ' · ' . $insumo['sector'] : '');
?>

<form class="formulario" method="post" action="<?= e(url('guardar/cantidad')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <?= partial('campo-fijo', [
        'etiqueta' => 'Producto',
        'icono' => 'box',
        'valor' => $insumo['nombre'],
        'detalle' => $detalle,
        'cambiar' => url('guardar'),
    ]) ?>

    <div class="cantidad-foco<?= error_de('cantidad') !== null ? ' has-error' : '' ?>">
        <label class="visually-hidden" for="cantidad">¿Cuántas unidades llegaron?</label>

        <div class="cantidad-foco__control" data-stepper data-min="1">
            <div class="cantidad-foco__fila">
                <button class="cantidad-foco__boton" type="button" data-step="-1"><?= icon('minus') ?><span class="visually-hidden">Restar uno</span></button>
                <input class="cantidad-foco__numero" id="cantidad" name="cantidad" type="text" inputmode="numeric" autocomplete="off"
                    autofocus aria-required="true"
                    value="<?= e(old('cantidad', (string) $cantidad)) ?>" <?= aria_error('cantidad') ?>>
                <button class="cantidad-foco__boton" type="button" data-step="1"><?= icon('plus') ?><span class="visually-hidden">Sumar uno</span></button>
            </div>
            <p class="cantidad-foco__unidad">unidades</p>
            <div class="cantidad-foco__rapidos" aria-label="Sumar varias">
                <button class="cantidad-foco__rapido" type="button" data-step="5">+5</button>
                <button class="cantidad-foco__rapido" type="button" data-step="10">+10</button>
            </div>
            <input type="hidden" name="cantidad_antes" data-cantidad-antes>
        </div>

        <?php if ($sumada) : ?>
            <p class="cantidad-foco__sumada" role="status"><?= icon('check') ?>Se sumó 1 al escanear</p>
        <?php endif; ?>
        <?= mensaje_error('cantidad') ?>
    </div>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url('guardar')) ?>"><?= icon('arrow-left') ?>Atrás</a>
        <button class="btn btn--si" type="submit" data-loading-text="Un momento…">Siguiente<?= icon('arrow-right') ?></button>
    </div>
</form>
