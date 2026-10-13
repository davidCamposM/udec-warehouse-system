<?php

use App\Modules\Insumos\GuardarController;

defined('APP_ROOT') || exit;

// Etapa 2, en la ventana: el producto elegido (con «Cambiar») y cuántas unidades llegaron.
$detalle = $insumo['codigo'] . ' · Hay ' . unidades($insumo['stock_actual']) . ' en bodega'
    . ($insumo['sector'] !== null ? ' · ' . $insumo['sector'] : '');
?>
<?= partial('Insumos::partials/pasos', ['paso' => 2, 'pasos' => GuardarController::PASOS, 'color' => 'verde']) ?>

<form class="formulario" method="post" action="<?= e(url('guardar/cantidad')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <?= partial('campo-fijo', [
        'etiqueta' => 'Producto',
        'icono' => 'box',
        'valor' => $insumo['nombre'],
        'detalle' => $detalle,
        'cambiar' => url('guardar'),
    ]) ?>

    <div class="campo">
        <label class="campo__etiqueta" for="cantidad">¿Cuántas unidades llegaron?<span class="campo__obligatorio" aria-hidden="true">*</span></label>
        <p class="campo__ayuda" id="cantidad-ayuda">Escribe el número o usa − y +.</p>
        <div class="quantity">
            <div class="stepper" data-stepper data-min="1">
                <button class="stepper__button" type="button" data-step="-1"><?= icon('minus') ?><span class="visually-hidden">Restar uno</span></button>
                <input class="stepper__input" id="cantidad" name="cantidad" type="text" inputmode="numeric" autofocus
                    aria-required="true" aria-describedby="cantidad-ayuda"
                    value="<?= e(old('cantidad', (string) $cantidad)) ?>" <?= aria_error('cantidad') ?>>
                <button class="stepper__button" type="button" data-step="1"><?= icon('plus') ?><span class="visually-hidden">Sumar uno</span></button>
            </div>
            <span class="quantity__unit">unidades</span>
        </div>
        <?= mensaje_error('cantidad') ?>
    </div>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url('guardar')) ?>"><?= icon('arrow-left') ?>Atrás</a>
        <button class="btn btn--si" type="submit" data-loading-text="Un momento…">Siguiente<?= icon('arrow-right') ?></button>
    </div>
</form>
