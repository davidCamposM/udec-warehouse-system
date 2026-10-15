<?php

use App\Modules\Insumos\RetirarController;

defined('APP_ROOT') || exit;

// Etapa 2, en la ventana: el producto elegido (con «Cambiar») y cuántas unidades se retiran. No se puede retirar más de
// lo que hay: el − y + se detienen ahí, y el servidor lo vuelve a revisar al descontar.
$hay = (int) $insumo['stock_actual'];
$detalle = $insumo['codigo'] . ' · Hay ' . unidades($hay) . ' en bodega'
    . ($insumo['sector'] !== null ? ' · ' . $insumo['sector'] : '');
?>
<?= partial('Insumos::partials/pasos', ['paso' => 2, 'pasos' => RetirarController::PASOS, 'color' => 'ocre']) ?>

<form class="formulario" method="post" action="<?= e(url('retirar/cantidad')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <?= partial('campo-fijo', [
        'etiqueta' => 'Producto',
        'icono' => 'box',
        'valor' => $insumo['nombre'],
        'detalle' => $detalle,
        'cambiar' => url('retirar'),
    ]) ?>

    <div class="campo">
        <label class="campo__etiqueta" for="cantidad">¿Cuántas unidades se retiran?<span class="campo__obligatorio" aria-hidden="true">*</span></label>
        <p class="campo__ayuda" id="cantidad-ayuda">Escribe el número o usa − y +. Como máximo, <?= e(unidades($hay)) ?>.</p>
        <div class="quantity">
            <div class="stepper" data-stepper data-min="1" data-max="<?= e($hay) ?>">
                <button class="stepper__button" type="button" data-step="-1"><?= icon('minus') ?><span class="visually-hidden">Restar uno</span></button>
                <input class="stepper__input" id="cantidad" name="cantidad" type="text" inputmode="numeric" autofocus
                    aria-required="true" aria-describedby="cantidad-ayuda"
                    value="<?= e(old('cantidad', '1')) ?>" <?= aria_error('cantidad') ?>>
                <button class="stepper__button" type="button" data-step="1"><?= icon('plus') ?><span class="visually-hidden">Sumar uno</span></button>
            </div>
            <span class="quantity__unit">unidades</span>
        </div>
        <?= mensaje_error('cantidad') ?>
    </div>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url('retirar')) ?>"><?= icon('arrow-left') ?>Atrás</a>
        <button class="btn btn--si" type="submit" data-loading-text="Retirando…"><?= icon('arrow-out') ?>Retirar</button>
    </div>
</form>
