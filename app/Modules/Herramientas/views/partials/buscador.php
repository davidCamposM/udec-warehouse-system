<?php

defined('APP_ROOT') || exit;

/**
 * Para encontrar rápido una herramienta entre muchas (herramientas.js): un campo que filtra por nombre mientras se
 * escribe y una pastilla por categoría. Recibe $grupos (categoría => herramientas) y, opcional, $conElegidas: suma la
 * pastilla «Elegidas» (en «Solicitar herramientas»). Sin JavaScript no se muestra y se ven todas.
 * Cada grupo de la página lleva data-grupo="<n>" y cada herramienta data-herramienta y data-nombre.
 */
$total = array_sum(array_map('count', $grupos));
?>
<div class="buscador-herramientas" data-buscador-herramientas hidden>
    <div class="buscador-herramientas__campo">
        <span class="buscador-herramientas__icono" aria-hidden="true"><?= icon('search') ?></span>
        <label class="visually-hidden" for="buscar-herramienta">Buscar herramienta</label>
        <input class="input" id="buscar-herramienta" type="search" placeholder="Buscar herramienta por nombre" autocomplete="off"
            data-buscar-herramienta>
    </div>
    <div class="nav-pills buscador-herramientas__grupos" role="group" aria-label="Ver por categoría">
        <button class="nav-link active" type="button" data-ver-grupo="todas" aria-pressed="true">
            Todas <span class="nav-count"><?= e($total) ?></span>
        </button>
        <?php foreach (array_keys($grupos) as $i => $categoria) : ?>
            <button class="nav-link" type="button" data-ver-grupo="<?= e($i) ?>" aria-pressed="false">
                <?= e($categoria) ?> <span class="nav-count"><?= e(count($grupos[$categoria])) ?></span>
            </button>
        <?php endforeach; ?>
        <?php if (!empty($conElegidas)) : ?>
            <button class="nav-link" type="button" data-ver-grupo="elegidas" aria-pressed="false">
                Elegidas <span class="nav-count" data-cuenta-elegidas>0</span>
            </button>
        <?php endif; ?>
    </div>
</div>
<p class="buscador-herramientas__vacio" data-sin-resultados hidden>Ninguna herramienta coincide. Prueba con otra palabra.</p>
