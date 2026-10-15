<?php

defined('APP_ROOT') || exit;

// Etapa 2, su propia ventana («¿Cuántas unidades se retiran?»): el número en grande y al centro, como en «Registrar
// producto». Parte en 1 (la unidad recién escaneada). Se cambia con − y +, escribiendo el número o escaneando otra vez
// el mismo producto, que suma 1. No se puede retirar más de lo que hay: el + se detiene ahí, y el servidor lo vuelve a
// revisar al descontar. Enter retira. Arriba, el producto elegido, con «Cambiar».
$hay = (int) $insumo['stock_actual'];
$detalle = $insumo['codigo'] . ' · Hay ' . unidades($hay) . ' en bodega'
    . ($insumo['sector'] !== null ? ' · ' . $insumo['sector'] : '');
?>
<form class="formulario" method="post" action="<?= e(url('retirar/cantidad')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <?= partial('campo-fijo', [
        'etiqueta' => 'Producto',
        'icono' => 'box',
        'valor' => $insumo['nombre'],
        'detalle' => $detalle,
        'cambiar' => url('retirar'),
    ]) ?>

    <div class="cantidad-foco<?= error_de('cantidad') !== null ? ' has-error' : '' ?>">
        <label class="visually-hidden" for="cantidad">¿Cuántas unidades se retiran?</label>
        <p class="cantidad-foco__sistema" id="cantidad-ayuda">Como máximo, <strong><?= e(unidades($hay)) ?></strong> (lo que hay en bodega).</p>

        <div class="cantidad-foco__control" data-stepper data-min="1" data-max="<?= e($hay) ?>">
            <div class="cantidad-foco__fila">
                <button class="cantidad-foco__boton" type="button" data-step="-1"><?= icon('minus') ?><span class="visually-hidden">Restar uno</span></button>
                <input class="cantidad-foco__numero" id="cantidad" name="cantidad" type="text" inputmode="numeric" autocomplete="off"
                    autofocus aria-required="true" aria-describedby="cantidad-ayuda"
                    value="<?= e(old('cantidad', (string) $cantidad)) ?>" <?= aria_error('cantidad') ?>>
                <button class="cantidad-foco__boton" type="button" data-step="1"><?= icon('plus') ?><span class="visually-hidden">Sumar uno</span></button>
            </div>
            <p class="cantidad-foco__unidad">unidades</p>
            <input type="hidden" name="cantidad_antes" data-cantidad-antes>
        </div>

        <?php if ($sumada) : ?>
            <p class="cantidad-foco__sumada" role="status"><?= icon('check') ?>Se sumó 1 al escanear</p>
        <?php endif; ?>
        <?= mensaje_error('cantidad') ?>
        <p class="cantidad-foco__ayuda">
            <?= icon('scan') ?>¿Se llevan varias? Escanea cada una y se suman solas. Enter para retirar.
        </p>
    </div>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url('retirar')) ?>"><?= icon('arrow-left') ?>Atrás</a>
        <button class="btn btn--si" type="submit" data-loading-text="Retirando…"><?= icon('arrow-out') ?>Retirar</button>
    </div>
</form>
