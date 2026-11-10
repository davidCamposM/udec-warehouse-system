<?php

use App\Modules\Herramientas\HerramientaService;

defined('APP_ROOT') || exit;

// Agregar o editar una herramienta del catálogo, en un formulario compacto: su nombre, su categoría (pastillas chicas,
// agrupadas: las principales arriba y las de cada una, como «Práctica de Invierno», bajo su nombre) y cuántas hay
// (con − y +). Al editar, abajo está «Eliminar».
$editando = $herramienta !== null;
$elegida = old('categoria_id', $editando && $herramienta['categoria_id'] !== null ? (string) $herramienta['categoria_id'] : '');

// Las categorías por grupo: «Categoría» (las principales y «Otras») y una por cada principal con subcategorías.
$gruposCategorias = ['Categoría' => []];
foreach ($categorias as $categoria) {
    $gruposCategorias[$categoria['padre'] ?? 'Categoría'][] = $categoria;
}
?>
<?= partial('encabezado', [
    'volver' => [url('herramientas'), 'Volver al Catálogo de herramientas'],
    'color' => $editando ? 'pizarra' : 'turquesa',
    'icono' => $editando ? 'edit' : 'tool',
    'titulo' => $editando ? 'Editar herramienta' : 'Agregar herramienta',
    'texto' => $editando ? $herramienta['nombre'] : '',
]) ?>

<?php if (errores() !== []) : ?>
    <div class="alert alert--danger flash" role="alert">
        <?= icon('alert') ?>
        <p>Hay que corregir algo. Revisa lo marcado en rojo.</p>
    </div>
<?php endif; ?>

<form class="form-simple form-simple--compacto form-simple--medio" method="post"
    action="<?= e($editando ? url('herramientas/' . $herramienta['id']) : url('herramientas')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <div class="question<?= error_de('nombre') !== null ? ' has-error' : '' ?>">
        <label class="question__label" for="nombre">Nombre</label>
        <input class="input" id="nombre" name="nombre" type="text" maxlength="150" required autofocus
            value="<?= e(old('nombre', $herramienta['nombre'] ?? '')) ?>" <?= aria_error('nombre') ?>>
        <?= mensaje_error('nombre') ?>
    </div>

    <fieldset class="question question__set<?= error_de('categoria_id') !== null ? ' has-error' : '' ?>">
        <legend class="question__label">Categoría</legend>
        <?php foreach ($gruposCategorias as $grupo => $opciones) : ?>
            <div class="pastillas">
                <?php if ($grupo !== 'Categoría') : ?>
                    <p class="pastillas__grupo"><?= e($grupo) ?></p>
                <?php endif; ?>
                <div class="pastillas__lista">
                    <?php foreach ($opciones as $categoria) : ?>
                        <label class="pastilla">
                            <input class="pastilla__input" type="radio" name="categoria_id" value="<?= e($categoria['id']) ?>"
                                <?= (string) $categoria['id'] === $elegida ? 'checked' : '' ?>>
                            <span class="pastilla__texto"><?= e($categoria['nombre']) ?></span>
                        </label>
                    <?php endforeach; ?>
                    <?php if ($grupo === 'Categoría') : ?>
                        <label class="pastilla">
                            <input class="pastilla__input" type="radio" name="categoria_id" value="" <?= $elegida === '' ? 'checked' : '' ?>>
                            <span class="pastilla__texto">Otras</span>
                        </label>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?= mensaje_error('categoria_id') ?>
    </fieldset>

    <?php
    $fuera = $editando ? (int) $herramienta['prestadas'] + (int) $herramienta['por_resolver'] : 0;
    ?>
    <div class="question<?= error_de('unidades') !== null ? ' has-error' : '' ?>">
        <label class="question__label" for="unidades">Unidades</label>
        <div class="stepper" data-stepper data-min="0" data-max="<?= e(HerramientaService::MAXIMO_UNIDADES) ?>">
            <button class="stepper__button" type="button" data-step="-1"><?= icon('minus') ?><span class="visually-hidden">Una menos</span></button>
            <input class="stepper__input" id="unidades" name="unidades" type="text" inputmode="numeric" required
                value="<?= e(old('unidades', $editando ? (string) $herramienta['unidades'] : '1')) ?>"
                <?= error_de('unidades') !== null ? aria_error('unidades') : ($fuera > 0 ? 'aria-describedby="unidades-ayuda"' : '') ?>>
            <button class="stepper__button" type="button" data-step="1"><?= icon('plus') ?><span class="visually-hidden">Una más</span></button>
        </div>
        <?php if ($fuera > 0) : ?>
            <p class="question__hint" id="unidades-ayuda"><?= e($fuera) ?> fuera de la bodega (prestadas o por resolver).</p>
        <?php endif; ?>
        <?= mensaje_error('unidades') ?>
    </div>

    <div class="form-simple__acciones">
        <a class="btn btn--secondary" href="<?= e(url('herramientas')) ?>">Cancelar</a>
        <button class="btn btn--si" type="submit" data-loading-text="Guardando…"><?= icon('ok') ?>Guardar</button>
    </div>
</form>

<?php if ($editando) : ?>
    <p class="catalogo__eliminar">
        <a class="boton-fila boton-fila--peligro" href="<?= e(url('herramientas/' . $herramienta['id'] . '/eliminar')) ?>"><?= icon('close') ?>Eliminar esta herramienta</a>
    </p>
<?php endif; ?>
