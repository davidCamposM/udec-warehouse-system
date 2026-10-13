<?php

use App\Modules\Insumos\NuevoProductoController;

defined('APP_ROOT') || exit;

// Etapa 2, en la ventana: lo de la etapa 1 (con «Cambiar») y el tipo: una de las categorías principales.
$elegida = old('categoria_id', $nuevo['categoria_id']);
?>
<?= partial('Insumos::partials/pasos', ['paso' => 2, 'pasos' => NuevoProductoController::PASOS, 'color' => 'turquesa']) ?>

<form class="formulario" method="post" action="<?= e(url('insumos/nuevo/tipo')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <?= partial('campo-fijo', [
        'etiqueta' => 'Producto',
        'icono' => 'box',
        'valor' => $nuevo['nombre'],
        'detalle' => $nuevo['codigo_barras'] !== '' ? 'Código de barras ' . $nuevo['codigo_barras'] : 'Sin código de barras',
        'cambiar' => url('insumos/nuevo/producto'),
    ]) ?>

    <fieldset class="campo<?= error_de('categoria_id') !== null ? ' has-error' : '' ?>">
        <legend class="campo__etiqueta">¿De qué tipo es?<span class="campo__obligatorio" aria-hidden="true">*</span></legend>
        <div class="options options--wide">
            <?php foreach ($categorias as $categoria) : ?>
                <label class="option">
                    <input class="option__input" type="radio" name="categoria_id" value="<?= e($categoria['id']) ?>"
                        <?= (string) $categoria['id'] === $elegida ? 'checked' : '' ?>>
                    <span class="option__box"><?= e($categoria['nombre']) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?= mensaje_error('categoria_id') ?>
    </fieldset>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url('insumos/nuevo/producto')) ?>"><?= icon('arrow-left') ?>Atrás</a>
        <button class="btn btn--si" type="submit" data-loading-text="Un momento…">Siguiente<?= icon('arrow-right') ?></button>
    </div>
</form>
