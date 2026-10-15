<?php

defined('APP_ROOT') || exit;

// Etapa 3, su propia ventana («¿Cuántas unidades llegaron?»): el número en grande y al centro, como en «Registrar
// producto». Se cambia con − y +, con +5 / +10, escribiendo el número o escaneando otra vez el producto, que suma 1.
// Enter pasa a «Dónde quedó». Arriba, lo de las etapas anteriores (con «Cambiar»).
?>

<form class="formulario" method="post" action="<?= e(url('insumos/nuevo/cantidad')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <div class="resumen-fijo">
        <?= partial('campo-fijo', [
            'etiqueta' => 'Producto',
            'icono' => 'box',
            'valor' => $nuevo['nombre'],
            'detalle' => $nuevo['codigo_barras'] !== '' ? 'Código de barras ' . $nuevo['codigo_barras'] : 'Sin código de barras',
            'cambiar' => url('insumos/nuevo/producto'),
            'compacto' => true,
        ]) ?>
        <?= partial('campo-fijo', [
            'etiqueta' => 'Tipo',
            'icono' => 'layers',
            'valor' => $categoria,
            'cambiar' => url('insumos/nuevo/tipo'),
            'compacto' => true,
        ]) ?>
    </div>

    <div class="cantidad-foco<?= error_de('cantidad') !== null ? ' has-error' : '' ?>">
        <label class="visually-hidden" for="cantidad">¿Cuántas unidades llegaron?</label>

        <div class="cantidad-foco__control" data-stepper data-min="1">
            <div class="cantidad-foco__fila">
                <button class="cantidad-foco__boton" type="button" data-step="-1"><?= icon('minus') ?><span class="visually-hidden">Restar uno</span></button>
                <input class="cantidad-foco__numero" id="cantidad" name="cantidad" type="text" inputmode="numeric" autocomplete="off"
                    autofocus aria-required="true" aria-describedby="cantidad-ayuda"
                    value="<?= e(old('cantidad', $nuevo['cantidad'])) ?>" <?= aria_error('cantidad') ?>>
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
        <p class="cantidad-foco__ayuda" id="cantidad-ayuda">
            <?= icon('scan') ?><?= $nuevo['codigo_barras'] !== ''
                ? '¿Llegaron varias? Escanea cada una y se suman solas. Enter para seguir.'
                : 'Escribe el número o usa − y +. Enter para seguir.' ?>
        </p>
    </div>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url('insumos/nuevo/tipo')) ?>"><?= icon('arrow-left') ?>Atrás</a>
        <button class="btn btn--si" type="submit" data-loading-text="Un momento…">Siguiente<?= icon('arrow-right') ?></button>
    </div>
</form>
