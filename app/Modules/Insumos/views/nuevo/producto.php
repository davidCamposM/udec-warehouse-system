<?php

use App\Modules\Insumos\NuevoProductoController;

defined('APP_ROOT') || exit;

// Etapa 1, en la ventana: el nombre y, si tiene, el código de barras y el del catálogo UdeC. Si el nombre ya viene
// escrito (lo que se buscó sin encontrarlo), el cursor parte en el código de barras.
$nombre = old('nombre', $nuevo['nombre']);
$codigo = old('codigo_barras', $nuevo['codigo_barras']);
$focoEnCodigo = $nombre !== '' && error_de('nombre') === null;
?>
<?= partial('Insumos::partials/pasos', ['paso' => 1, 'pasos' => NuevoProductoController::PASOS, 'color' => 'turquesa']) ?>

<form class="formulario" method="post" action="<?= e(url('insumos/nuevo/producto')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <div class="campo">
        <label class="campo__etiqueta" for="nombre">Nombre del producto<span class="campo__obligatorio" aria-hidden="true">*</span></label>
        <p class="campo__ayuda" id="nombre-ayuda">
            Como aparece en el envase o en el pedido. Ejemplo: Cloro envase 1 litro.
        </p>
        <input class="input input--relleno" id="nombre" name="nombre" type="text" maxlength="150" autocomplete="off"
            aria-required="true" aria-describedby="nombre-ayuda" value="<?= e($nombre) ?>"
            <?= $focoEnCodigo ? '' : 'autofocus' ?> <?= aria_error('nombre') ?>>
        <?= mensaje_error('nombre') ?>
    </div>

    <div class="campo">
        <label class="campo__etiqueta" for="codigo_barras">Código de barras<span class="campo__opcional">(opcional)</span></label>
        <p class="campo__ayuda" id="codigo-ayuda">
            Haz clic aquí y escanéalo con la pistola. Si no tiene, déjalo en blanco: el sistema le asigna uno propio.
        </p>
        <input class="input input--relleno input--code" id="codigo_barras" name="codigo_barras" type="text" maxlength="64"
            autocomplete="off" aria-describedby="codigo-ayuda" placeholder="Código de barras" value="<?= e($codigo) ?>"
            <?= $focoEnCodigo ? 'autofocus' : '' ?> <?= aria_error('codigo_barras') ?>>
        <?= mensaje_error('codigo_barras') ?>
    </div>

    <div class="campo">
        <label class="campo__etiqueta" for="codigo_udec">Código del catálogo UdeC<span class="campo__opcional">(opcional)</span></label>
        <p class="campo__ayuda" id="udec-ayuda">El número que tiene en el pedido de materiales de la UdeC. Ejemplo: 3001027.</p>
        <input class="input input--relleno" id="codigo_udec" name="codigo_udec" type="text" maxlength="20" inputmode="numeric"
            autocomplete="off" aria-describedby="udec-ayuda" placeholder="Código UdeC"
            value="<?= e(old('codigo_udec', $nuevo['codigo_udec'])) ?>" <?= aria_error('codigo_udec') ?>>
        <?= mensaje_error('codigo_udec') ?>
    </div>

    <div class="formulario__acciones">
        <?php if ($nuevo['para_guardar']) : ?>
            <a class="btn btn--secondary" href="<?= e(url('guardar')) ?>"><?= icon('arrow-left') ?>Atrás</a>
        <?php else : ?>
            <a class="btn btn--secondary" href="<?= e(url()) ?>"><?= icon('close') ?>Cancelar</a>
        <?php endif; ?>
        <button class="btn btn--si" type="submit" data-loading-text="Un momento…">Siguiente<?= icon('arrow-right') ?></button>
    </div>
</form>
