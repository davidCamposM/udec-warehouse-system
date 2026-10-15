<?php

defined('APP_ROOT') || exit;

// Agregar o editar un sector: su etiqueta (se escanea; Enter, que manda la pistola, guarda) y su nombre, opcional.
$editando = $sector !== null;
// Desde «Registrar producto» o «Agregar nuevo producto» (no había sectores) se vuelve a ese paso.
$volver = match (true) {
    $editando => [url('sectores'), 'Volver a Sectores'],
    $para === 'guardar' => [url('guardar/sector'), 'Volver a Registrar producto'],
    $para === 'nuevo' => [url('insumos/nuevo/sector'), 'Volver a Agregar nuevo producto'],
    default => [url('sectores'), 'Volver a Sectores'],
};
?>
<?= partial('encabezado', [
    'volver' => $volver,
    'color' => $editando ? 'pizarra' : 'turquesa',
    'icono' => $editando ? 'edit' : 'map-pin',
    'titulo' => $editando ? 'Editar sector' : 'Agregar sector',
    'texto' => $editando
        ? 'Para cambiar su etiqueta, escanea la nueva. La de ahora es ' . $sector['codigo'] . '.'
        : 'Escanea la etiqueta impresa de la repisa o cubículo y toca Guardar.',
]) ?>

<?php if (errores() !== []) : ?>
    <div class="alert alert--danger flash" role="alert">
        <?= icon('alert') ?>
        <p>Hay que corregir algo. Revisa lo marcado en rojo.</p>
    </div>
<?php endif; ?>

<form class="form-simple" method="post" action="<?= e($editando ? url('sectores/' . $sector['id']) : url('sectores')) ?>"
    data-submit-once>
    <?= csrf_field() ?>
    <?php if ($para !== null) : ?>
        <input type="hidden" name="para" value="<?= e($para) ?>">
    <?php endif; ?>

    <div class="question<?= error_de('codigo') !== null ? ' has-error' : '' ?>">
        <label class="question__label" for="codigo">
            <span class="question__number">1</span>Escanea la etiqueta del sector
        </label>
        <p class="question__hint">Apunta la pistola al código de barras de la etiqueta. Ejemplo: M12.</p>
        <input class="input" id="codigo" name="codigo" type="text" maxlength="20" autocomplete="off" autofocus data-lectura
            value="<?= e(old('codigo', $sector['codigo'] ?? '')) ?>" <?= aria_error('codigo') ?>>
        <?= mensaje_error('codigo') ?>
    </div>

    <div class="question<?= error_de('nombre') !== null ? ' has-error' : '' ?>">
        <label class="question__label" for="nombre">
            <span class="question__number">2</span>Nombre (opcional)
        </label>
        <p class="question__hint">
            Si lo dejas en blanco, se usa el de la etiqueta: M12 queda como «Mueble de madera · Cubículo 12».
        </p>
        <input class="input" id="nombre" name="nombre" type="text" maxlength="80" autocomplete="off"
            value="<?= e(old('nombre', $sector['nombre'] ?? '')) ?>" <?= aria_error('nombre') ?>>
        <?= mensaje_error('nombre') ?>
    </div>

    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e($volver[0]) ?>"><?= icon('close') ?>Cancelar</a>
        <button class="btn btn--lg btn--si" type="submit" data-loading-text="Guardando…"><?= icon('ok') ?>Guardar</button>
    </div>
</form>
