<?php

use App\Modules\Insumos\GuardarController;

defined('APP_ROOT') || exit;

// Etapa 3, en la ventana: lo elegido antes (con «Cambiar») y dónde quedó. Viene marcado el sector donde estaba el
// producto; si el sector tiene etiqueta, también se puede escanear. «Confirmar producto» registra todo.
$marcado = old('sector_id', (string) ($insumo['sector_id'] ?? ''));
?>
<?= partial('Insumos::partials/pasos', ['paso' => 3, 'pasos' => GuardarController::PASOS, 'color' => 'verde']) ?>

<form class="formulario" method="post" action="<?= e(url('guardar/sector')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <?= partial('campo-fijo', [
        'etiqueta' => 'Producto',
        'icono' => 'box',
        'valor' => $insumo['nombre'],
        'detalle' => $insumo['codigo'],
        'cambiar' => url('guardar'),
    ]) ?>
    <?= partial('campo-fijo', [
        'etiqueta' => 'Cantidad',
        'icono' => 'layers',
        'valor' => unidades($cantidad),
        'cambiar' => url('guardar/cantidad'),
    ]) ?>

    <?php if ($sectores === []) : ?>
        <div class="campo">
            <p class="campo__etiqueta">¿Dónde quedó?</p>
            <div class="vacio vacio--compacto">
                <p class="vacio__titulo">Todavía no hay sectores</p>
                <?php if ($puedeCrearSectores) : ?>
                    <p>Agrega las partes de la bodega (Estante A, Repisa de líquidos…). Lo que llevas no se pierde.</p>
                    <a class="btn btn--tarea tono-turquesa" href="<?= e(url('sectores/nuevo', ['para' => 'guardar'])) ?>">
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
                    <?php $estaba = (int) $sector['id'] === (int) $insumo['sector_id']; ?>
                    <label class="option">
                        <input class="option__input" type="radio" name="sector_id" value="<?= e($sector['id']) ?>"
                            <?= (string) $sector['id'] === $marcado ? 'checked' : '' ?>>
                        <span class="option__box option__box--detalle">
                            <?= icon('map-pin') ?>
                            <span>
                                <?= e($sector['nombre']) ?>
                                <small><?= e($sector['codigo']) ?><?= $estaba ? ' · Aquí estaba antes' : '' ?></small>
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
        <a class="btn btn--secondary" href="<?= e(url('guardar/cantidad')) ?>"><?= icon('arrow-left') ?>Atrás</a>
        <?php if ($sectores !== []) : ?>
            <button class="btn btn--si" type="submit" data-loading-text="Registrando…"><?= icon('ok') ?>Confirmar producto</button>
        <?php endif; ?>
    </div>
</form>
