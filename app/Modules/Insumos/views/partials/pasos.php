<?php

defined('APP_ROOT') || exit;

/**
 * En qué parte de una tarea va la persona. Recibe $paso (1, 2 o 3), $pasos (los nombres de cada paso) y $color
 * (el de la tarea).
 */
?>
<ol class="pasos tono-<?= e($color) ?>" aria-label="Pasos">
    <?php foreach ($pasos as $i => $texto) : ?>
        <?php
        $numero = $i + 1;
        $estado = $numero < $paso ? 'is-hecho' : ($numero === $paso ? 'is-actual' : '');
        ?>
        <li class="pasos__paso <?= $estado ?>"<?= $numero === $paso ? ' aria-current="step"' : '' ?>>
            <span class="pasos__numero"><?= $numero < $paso ? icon('ok') : e($numero) ?></span>
            <?= e($texto) ?>
        </li>
    <?php endforeach; ?>
</ol>
