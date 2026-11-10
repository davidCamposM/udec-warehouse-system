<?php

defined('APP_ROOT') || exit;

// Un estado de la bodega (insumos o herramientas) en el modelo nuevo de la interfaz (azul marino): una tarjeta blanca
// con la torta a la izquierda (un anillo liso, con el total al centro) y, a la derecha, el título y una fila por parte:
// su punto de color, su nombre y su cantidad. Si la parte tiene enlace, la fila lleva a su lista.
// Recibe lo mismo que Panel::grafico (PanelService::graficos()): $titulo, $unidad, $partes y $id; y $corto (opcional),
// un título más breve para esta tarjeta.
// El anillo: cada círculo mide 100 de largo (pathLength), así el largo de cada arco es su porcentaje; entre arcos queda
// una separación fina.

$total = array_sum(array_column($partes, 4));
$porcentaje = static fn (int $n): int => $total > 0 ? (int) round($n * 100 / $total) : 0;
$cuantos = static fn (int $n): string => $n . ' ' . ($n === 1 ? $unidad[0] : $unidad[1]);
$visibles = array_values(array_filter($partes, static fn (array $parte): bool => $parte[4] > 0));
$separacion = count($visibles) > 1 ? 1.0 : 0.0;
?>
<article class="torta" aria-labelledby="<?= e($id) ?>">
    <?php if ($total > 0) : ?>
        <div class="torta__anillo">
            <svg viewBox="0 0 100 100" role="img" aria-label="<?= e(($corto ?? $titulo) . ': ' . implode(', ', array_map(
                static fn (array $parte): string => $parte[1] . ' ' . $cuantos($parte[4]) . ' (' . $porcentaje($parte[4]) . ' %)',
                $partes,
            ))) ?>">
                <?php $inicio = 0.0; ?>
                <?php foreach ($visibles as [$clave, , , , $cantidad]) : ?>
                    <?php
                    $largo = $cantidad * 100 / $total;
                    $trazo = max(0.6, $largo - $separacion);
                    ?>
                    <circle class="torta__tramo torta__tramo--<?= e($clave) ?>" cx="50" cy="50" r="40" pathLength="100"
                        stroke-dasharray="<?= e(round($trazo, 3)) ?> 100" stroke-dashoffset="<?= e(round(-$inicio, 3)) ?>" />
                    <?php $inicio += $largo; ?>
                <?php endforeach; ?>
            </svg>
            <p class="torta__centro" aria-hidden="true">
                <strong><?= e($total) ?></strong>
                <span><?= e($total === 1 ? $unidad[0] : $unidad[1]) ?></span>
            </p>
        </div>
    <?php endif; ?>

    <div class="torta__lado">
        <h3 class="torta__titulo" id="<?= e($id) ?>"><?= e($corto ?? $titulo) ?></h3>
        <?php if ($total === 0) : ?>
            <p class="torta__vacio">Todavía no hay <?= e($unidad[1]) ?> para mostrar.</p>
        <?php else : ?>
            <ul class="torta__partes">
                <?php foreach ($partes as $n => [$clave, $nombre, $explicacion, , $cantidad, $enlace]) : ?>
                    <?php // Las tres primeras partes van siempre; una cuarta (como «Sin unidades»), solo si tiene algo. ?>
                    <?php if ($n >= 3 && $cantidad === 0) : ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <li>
                        <<?= $enlace !== null ? 'a href="' . e($enlace) . '"' : 'div' ?> class="torta__parte" title="<?= e($explicacion) ?>">
                            <span class="torta__punto torta__punto--<?= e($clave) ?>" aria-hidden="true"></span>
                            <span class="torta__nombre"><?= e($nombre) ?></span>
                            <strong class="torta__cantidad"><?= e($cantidad) ?></strong>
                        </<?= $enlace !== null ? 'a' : 'div' ?>>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</article>
