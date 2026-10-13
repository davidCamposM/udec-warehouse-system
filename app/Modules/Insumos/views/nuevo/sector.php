<?php

use App\Modules\Insumos\NuevoProductoController;

defined('APP_ROOT') || exit;

// Etapa 4, en la ventana: lo de las etapas anteriores (con «Cambiar») y dónde quedó. Si el sector tiene etiqueta,
// también se puede escanear. «Agregar producto» crea el producto y registra sus unidades.
$marcado = old('sector_id', $nuevo['sector_id']);
?>
<?= partial('Insumos::partials/pasos', ['paso' => 4, 'pasos' => NuevoProductoController::PASOS, 'color' => 'turquesa']) ?>

<form class="formulario" method="post" action="<?= e(url('insumos/nuevo/sector')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <?= partial('campo-fijo', [
        'etiqueta' => 'Producto',
        'icono' => 'box',
        'valor' => $nuevo['nombre'],
        'detalle' => $categoria,
        'cambiar' => url('insumos/nuevo/producto'),
    ]) ?>
    <?= partial('campo-fijo', [
        'etiqueta' => 'Cantidad',
        'icono' => 'layers',
        'valor' => unidades((int) $nuevo['cantidad']),
        'cambiar' => url('insumos/nuevo/cantidad'),
    ]) ?>

    <?php if ($sectores === []) : ?>
        <div class="campo">
            <p class="campo__etiqueta">¿Dónde quedó?</p>
            <div class="vacio vacio--compacto">
                <p class="vacio__titulo">Todavía no hay sectores</p>
                <?php if ($puedeCrearSectores) : ?>
                    <p>Agrega las partes de la bodega (Estante A, Repisa de líquidos…). Lo que llevas no se pierde.</p>
                    <a class="btn btn--tarea tono-turquesa" href="<?= e(url('sectores/nuevo', ['para' => 'nuevo'])) ?>">
                        <?= icon('plus') ?>Agregar sector
                    </a>
                <?php else : ?>
                    <p>Pide al encargado de bodega que agregue los sectores.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php else : ?>
        <fieldset class="campo<?= error_de('sector_id') !== null ? ' has-error' : '' ?>">
            <legend class="campo__etiqueta">¿Dónde quedó?<span class="campo__obligatorio" aria-hidden="true">*</span></legend>
            <div class="options options--wide">
                <?php foreach ($sectores as $sector) : ?>
                    <label class="option">
                        <input class="option__input" type="radio" name="sector_id" value="<?= e($sector['id']) ?>"
                            <?= (string) $sector['id'] === $marcado || count($sectores) === 1 ? 'checked' : '' ?>>
                        <span class="option__box option__box--detalle">
                            <?= icon('map-pin') ?>
                            <span>
                                <?= e($sector['nombre']) ?>
                                <small><?= e($sector['codigo']) ?></small>
                            </span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?= mensaje_error('sector_id') ?>
        </fieldset>

        <div class="campo">
            <label class="campo__etiqueta campo__etiqueta--suave" for="codigo"><?= icon('scan') ?>¿Tiene etiqueta? También puedes escanearla</label>
            <input class="input input--relleno input--code" id="codigo" name="codigo" type="text" autocomplete="off"
                placeholder="Código del sector" data-foco data-lectura <?= aria_error('codigo') ?>>
            <?= mensaje_error('codigo') ?>
        </div>
    <?php endif; ?>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url('insumos/nuevo/cantidad')) ?>"><?= icon('arrow-left') ?>Atrás</a>
        <?php if ($sectores !== []) : ?>
            <button class="btn btn--si" type="submit" data-loading-text="Agregando…"><?= icon('ok') ?>Agregar producto</button>
        <?php endif; ?>
    </div>
</form>
