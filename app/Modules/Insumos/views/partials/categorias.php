<?php

defined('APP_ROOT') || exit;

/**
 * Las categorías para elegir, como opciones grandes agrupadas bajo su categoría principal, y al final «Sin
 * categoría». Recibe $grupos (InsumoService::opcionesFormulario) y $categoriaId (la elegida, '' si ninguna).
 */
?>
<?php foreach ($grupos as $grupo) : ?>
    <?php
    $opciones = $grupo['opciones'];
    // Un producto guardado en la categoría principal (sin subcategoría) también debe poder verse elegido.
    if ($categoriaId === (string) $grupo['principal']['id'] && !in_array((int) $categoriaId, array_column($opciones, 'id'), true)) {
        array_unshift($opciones, $grupo['principal']);
    }
    ?>
    <p class="options__group"><?= e($grupo['titulo']) ?></p>
    <div class="options options--wide">
        <?php foreach ($opciones as $opcion) : ?>
            <label class="option">
                <input class="option__input" type="radio" name="categoria_id" value="<?= e($opcion['id']) ?>"
                    <?= (string) $opcion['id'] === $categoriaId ? 'checked' : '' ?>>
                <span class="option__box"><?= e($opcion['nombre']) ?></span>
            </label>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
<p class="options__group">Otra</p>
<div class="options options--wide">
    <label class="option">
        <input class="option__input" type="radio" name="categoria_id" value="" <?= $categoriaId === '' ? 'checked' : '' ?>>
        <span class="option__box">Sin categoría</span>
    </label>
</div>
