<?php

use App\Modules\Insumos\NuevoProductoController;

defined('APP_ROOT') || exit;

// Etapa 3, en la ventana: lo de las etapas anteriores (con «Cambiar») y cuántas unidades llegaron.
?>
<?= partial('Insumos::partials/pasos', ['paso' => 3, 'pasos' => NuevoProductoController::PASOS, 'color' => 'turquesa']) ?>

<form class="formulario" method="post" action="<?= e(url('insumos/nuevo/cantidad')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <?= partial('campo-fijo', [
        'etiqueta' => 'Producto',
        'icono' => 'box',
        'valor' => $nuevo['nombre'],
        'detalle' => $nuevo['codigo_barras'] !== '' ? 'Código de barras ' . $nuevo['codigo_barras'] : 'Sin código de barras',
        'cambiar' => url('insumos/nuevo/producto'),
    ]) ?>
    <?= partial('campo-fijo', [
        'etiqueta' => 'Tipo',
        'icono' => 'layers',
        'valor' => $categoria,
        'cambiar' => url('insumos/nuevo/tipo'),
    ]) ?>

    <div class="campo">
        <label class="campo__etiqueta" for="cantidad">¿Cuántas unidades llegaron?<span class="campo__obligatorio" aria-hidden="true">*</span></label>
        <p class="campo__ayuda" id="cantidad-ayuda">Escribe el número o usa − y +.</p>
        <div class="quantity">
            <div class="stepper" data-stepper data-min="1">
                <button class="stepper__button" type="button" data-step="-1"><?= icon('minus') ?><span class="visually-hidden">Restar uno</span></button>
                <input class="stepper__input" id="cantidad" name="cantidad" type="text" inputmode="numeric" autofocus
                    aria-required="true" aria-describedby="cantidad-ayuda"
                    value="<?= e(old('cantidad', $nuevo['cantidad'])) ?>" <?= aria_error('cantidad') ?>>
                <button class="stepper__button" type="button" data-step="1"><?= icon('plus') ?><span class="visually-hidden">Sumar uno</span></button>
            </div>
            <span class="quantity__unit">unidades</span>
        </div>
        <?= mensaje_error('cantidad') ?>
    </div>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url('insumos/nuevo/tipo')) ?>"><?= icon('arrow-left') ?>Atrás</a>
        <button class="btn btn--si" type="submit" data-loading-text="Un momento…">Siguiente<?= icon('arrow-right') ?></button>
    </div>
</form>
