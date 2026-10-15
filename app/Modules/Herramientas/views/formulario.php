<?php

defined('APP_ROOT') || exit;

// Agregar o editar una herramienta del catálogo: su nombre y su categoría (opciones grandes, sin lista desplegable).
// Al editar, abajo está «Desactivar».
$editando = $herramienta !== null;
$elegida = old('categoria_id', $editando && $herramienta['categoria_id'] !== null ? (string) $herramienta['categoria_id'] : '');
?>
<?= partial('encabezado', [
    'volver' => [url('herramientas'), 'Volver al Catálogo de herramientas'],
    'color' => $editando ? 'pizarra' : 'turquesa',
    'icono' => $editando ? 'edit' : 'tool',
    'titulo' => $editando ? 'Editar herramienta' : 'Agregar herramienta',
    'texto' => $editando ? $herramienta['nombre'] : 'Queda en el catálogo y los docentes ya la pueden solicitar.',
]) ?>

<?php if (errores() !== []) : ?>
    <div class="alert alert--danger flash" role="alert">
        <?= icon('alert') ?>
        <p>Hay que corregir algo. Revisa lo marcado en rojo.</p>
    </div>
<?php endif; ?>

<form class="form-simple" method="post" action="<?= e($editando ? url('herramientas/' . $herramienta['id']) : url('herramientas')) ?>"
    data-submit-once>
    <?= csrf_field() ?>

    <div class="question<?= error_de('nombre') !== null ? ' has-error' : '' ?>">
        <label class="question__label" for="nombre">
            <span class="question__number">1</span>¿Cómo se llama?
        </label>
        <p class="question__hint">Como la conocen en la bodega. Ejemplo: Huincha Distancia (30 m).</p>
        <input class="input" id="nombre" name="nombre" type="text" maxlength="150" required autofocus
            value="<?= e(old('nombre', $herramienta['nombre'] ?? '')) ?>" <?= aria_error('nombre') ?>>
        <?= mensaje_error('nombre') ?>
    </div>

    <fieldset class="question<?= error_de('categoria_id') !== null ? ' has-error' : '' ?>">
        <legend class="question__label"><span class="question__number">2</span>¿En qué categoría va?</legend>
        <div class="options">
            <?php foreach ($categorias as $categoria) : ?>
                <label class="option">
                    <input class="option__input" type="radio" name="categoria_id" value="<?= e($categoria['id']) ?>"
                        <?= (string) $categoria['id'] === $elegida ? 'checked' : '' ?>>
                    <span class="option__box"><?= e($categoria['nombre']) ?></span>
                </label>
            <?php endforeach; ?>
            <label class="option">
                <input class="option__input" type="radio" name="categoria_id" value="" <?= $elegida === '' ? 'checked' : '' ?>>
                <span class="option__box">Otras</span>
            </label>
        </div>
        <?= mensaje_error('categoria_id') ?>
    </fieldset>

    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e(url('herramientas')) ?>"><?= icon('close') ?>Cancelar</a>
        <button class="btn btn--lg btn--si" type="submit" data-loading-text="Guardando…"><?= icon('ok') ?>Guardar</button>
    </div>
</form>

<?php if ($editando && (int) $herramienta['activo'] === 1) : ?>
    <p class="catalogo__desactivar">
        ¿Ya no está en la bodega?
        <a class="btn btn-outline-danger btn-sm" href="<?= e(url('herramientas/' . $herramienta['id'] . '/desactivar')) ?>">Desactivar</a>
    </p>
<?php endif; ?>
