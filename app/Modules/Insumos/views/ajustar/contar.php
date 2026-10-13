<?php

use App\Modules\Insumos\AjusteController;

defined('APP_ROOT') || exit;

// Etapa 2, en la ventana: el producto elegido (con «Cambiar»), cuántas hay realmente y por qué cambia.
$elegido = old('motivo', $motivo);
$detalleProducto = $insumo['codigo'] . ($insumo['sector'] !== null ? ' · ' . $insumo['sector'] : '');
?>
<?= partial('Insumos::partials/pasos', ['paso' => 2, 'pasos' => AjusteController::PASOS, 'color' => 'naranja']) ?>

<form class="formulario" method="post" action="<?= e(url('ajustar/contar')) ?>" data-submit-once>
    <?= csrf_field() ?>

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

    <div class="campo">
        <label class="campo__etiqueta" for="contado">¿Cuántas hay realmente?<span class="campo__obligatorio" aria-hidden="true">*</span></label>
        <p class="campo__ayuda" id="contado-ayuda">
            El sistema dice <strong><?= e(unidades($insumo['stock_actual'])) ?></strong>. Cuéntalas y escribe el total que hay, no
            la diferencia. Si no queda ninguna, deja 0.
        </p>
        <div class="quantity">
            <div class="stepper" data-stepper data-min="0">
                <button class="stepper__button" type="button" data-step="-1"><?= icon('minus') ?><span class="visually-hidden">Restar uno</span></button>
                <input class="stepper__input" id="contado" name="contado" type="text" inputmode="numeric" autofocus
                    aria-required="true" aria-describedby="contado-ayuda"
                    value="<?= e(old('contado', $contado)) ?>" <?= aria_error('contado') ?>>
                <button class="stepper__button" type="button" data-step="1"><?= icon('plus') ?><span class="visually-hidden">Sumar uno</span></button>
            </div>
            <span class="quantity__unit">unidades</span>
        </div>
        <?= mensaje_error('contado') ?>
    </div>

    <fieldset class="campo<?= error_de('motivo') !== null ? ' has-error' : '' ?>">
        <legend class="campo__etiqueta">¿Por qué cambia?<span class="campo__obligatorio" aria-hidden="true">*</span></legend>
        <div class="options options--wide">
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

    <div class="campo">
        <label class="campo__etiqueta" for="detalle">Detalle</label>
        <p class="campo__ayuda" id="detalle-ayuda">Opcional. Si elegiste «Otro motivo», escribe cuál.</p>
        <input class="input input--relleno" id="detalle" name="detalle" type="text" maxlength="150" aria-describedby="detalle-ayuda"
            placeholder="Ejemplo: faltaban en el estante B" value="<?= e(old('detalle', $detalle)) ?>" <?= aria_error('detalle') ?>>
        <?= mensaje_error('detalle') ?>
    </div>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url('ajustar')) ?>"><?= icon('arrow-left') ?>Atrás</a>
        <button class="btn btn--si" type="submit" data-loading-text="Un momento…">Siguiente<?= icon('arrow-right') ?></button>
    </div>
</form>
