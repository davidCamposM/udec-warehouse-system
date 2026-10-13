<?php

defined('APP_ROOT') || exit;

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
    'titulo' => $editando ? 'Cambiar nombre del sector' : 'Agregar sector',
    'texto' => $editando
        ? 'Su código (' . $sector['codigo'] . ') no cambia: la etiqueta pegada sigue sirviendo.'
        : 'El sistema le asigna un código solo (SEC001, SEC002…).',
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

    <div class="question<?= error_de('nombre') !== null ? ' has-error' : '' ?>">
        <label class="question__label" for="nombre">
            <span class="question__number">1</span>¿Cómo se llama este sector?
        </label>
        <p class="question__hint">Un nombre que todos reconozcan en la bodega. Ejemplo: Estante A, Repisa de líquidos.</p>
        <input class="input" id="nombre" name="nombre" type="text" maxlength="80" required autofocus
            value="<?= e(old('nombre', $sector['nombre'] ?? '')) ?>" <?= aria_error('nombre') ?>>
        <?= mensaje_error('nombre') ?>
    </div>

    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e($volver[0]) ?>"><?= icon('close') ?>Cancelar</a>
        <button class="btn btn--lg btn--si" type="submit" data-loading-text="Guardando…"><?= icon('ok') ?>Guardar</button>
    </div>
</form>
