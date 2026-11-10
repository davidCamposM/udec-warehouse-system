<?php

defined('APP_ROOT') || exit;

/**
 * Las categorías para elegir, como pastillas chicas agrupadas bajo su categoría principal (un título chico en gris), y
 * al final «Sin categoría». La elegida, en verde con su ✓. Recibe $grupos (InsumoService::opcionesFormulario) y
 * $categoriaId (la elegida, '' si ninguna).
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
    <div class="pastillas">
        <p class="pastillas__grupo"><?= e($grupo['titulo']) ?></p>
        <div class="pastillas__lista">
            <?php foreach ($opciones as $opcion) : ?>
                <label class="pastilla">
                    <input class="pastilla__input" type="radio" name="categoria_id" value="<?= e($opcion['id']) ?>"
                        <?= (string) $opcion['id'] === $categoriaId ? 'checked' : '' ?>>
                    <span class="pastilla__texto"><?= e($opcion['nombre']) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
<div class="pastillas">
    <p class="pastillas__grupo">Otra</p>
    <div class="pastillas__lista">
        <label class="pastilla">
            <input class="pastilla__input" type="radio" name="categoria_id" value="" <?= $categoriaId === '' ? 'checked' : '' ?>>
            <span class="pastilla__texto">Sin categoría</span>
        </label>
    </div>
</div>
