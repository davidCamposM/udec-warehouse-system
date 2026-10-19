<?php

use App\Modules\Herramientas\HerramientaService;

defined('APP_ROOT') || exit;

// Agregar o editar una herramienta del catálogo: su nombre, su categoría (opciones grandes, sin lista desplegable; las
// subcategorías con su principal: «Práctica de Invierno › Bastones») y cuántas hay (con − y +). Al editar, abajo está
// «Eliminar».
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
                    <span class="option__box"><?= e($categoria['padre'] !== null ? "{$categoria['padre']} › {$categoria['nombre']}" : $categoria['nombre']) ?></span>
                </label>
            <?php endforeach; ?>
            <label class="option">
                <input class="option__input" type="radio" name="categoria_id" value="" <?= $elegida === '' ? 'checked' : '' ?>>
                <span class="option__box">Otras</span>
            </label>
        </div>
        <?= mensaje_error('categoria_id') ?>
    </fieldset>

    <?php
    $fuera = $editando ? (int) $herramienta['prestadas'] + (int) $herramienta['por_resolver'] : 0;
    ?>
    <div class="question<?= error_de('unidades') !== null ? ' has-error' : '' ?>">
        <label class="question__label" for="unidades">
            <span class="question__number">3</span>¿Cuántas hay?
        </label>
        <p class="question__hint" id="unidades-ayuda">Todas las que tiene la bodega, también las prestadas.<?= $fuera > 0
            ? ' Ahora hay ' . e($fuera) . ' fuera de la bodega (prestadas o por resolver).'
            : '' ?></p>
        <div class="stepper" data-stepper data-min="0" data-max="<?= e(HerramientaService::MAXIMO_UNIDADES) ?>">
            <button class="stepper__button" type="button" data-step="-1"><?= icon('minus') ?><span class="visually-hidden">Una menos</span></button>
            <input class="stepper__input" id="unidades" name="unidades" type="text" inputmode="numeric" required
                value="<?= e(old('unidades', $editando ? (string) $herramienta['unidades'] : '1')) ?>"
                <?= error_de('unidades') !== null ? aria_error('unidades') : 'aria-describedby="unidades-ayuda"' ?>>
            <button class="stepper__button" type="button" data-step="1"><?= icon('plus') ?><span class="visually-hidden">Una más</span></button>
        </div>
        <?= mensaje_error('unidades') ?>
    </div>

    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e(url('herramientas')) ?>"><?= icon('close') ?>Cancelar</a>
        <button class="btn btn--lg btn--si" type="submit" data-loading-text="Guardando…"><?= icon('ok') ?>Guardar</button>
    </div>
</form>

<?php if ($editando) : ?>
    <p class="catalogo__eliminar">
        ¿Ya no está en la bodega?
        <a class="btn btn-outline-danger btn-sm" href="<?= e(url('herramientas/' . $herramienta['id'] . '/eliminar')) ?>">Eliminar esta herramienta</a>
    </p>
<?php endif; ?>
