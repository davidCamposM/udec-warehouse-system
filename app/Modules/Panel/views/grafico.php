<?php

defined('APP_ROOT') || exit;

// Un gráfico de torta (anillo) del inicio, en SVG: cada parte es un arco del mismo círculo (circunferencia 100, así el
// largo de cada arco es su porcentaje), con un espacio entre partes. Al centro, el porcentaje de lo que está en bodega.
// Al lado, la leyenda con ícono, nombre, explicación y cantidad (el color nunca va solo); si la parte tiene enlace, la
// fila lleva a su lista (aunque esté en 0). Debajo, unos datos más.
// Recibe lo de PanelService::graficos(): $titulo, $texto, $unidad, $centro, $partes, $datos y $id.

$total = array_sum(array_column($partes, 4));
$visibles = array_values(array_filter($partes, static fn (array $parte): bool => $parte[4] > 0));
$espacio = count($visibles) > 1 ? 1.2 : 0;
$porcentaje = static fn (int $n): int => $total > 0 ? (int) round($n * 100 / $total) : 0;
$cuantos = static fn (int $n): string => $n . ' ' . ($n === 1 ? $unidad[0] : $unidad[1]);

$resumen = $titulo . ': ' . implode(', ', array_map(
    static fn (array $parte): string => $parte[1] . ' ' . $cuantos($parte[4]),
    $partes,
)) . '.';
?>
<section class="card grafico" aria-labelledby="<?= e($id) ?>">
    <div class="card-header">
        <div>
            <h3 class="card-title" id="<?= e($id) ?>"><?= e($titulo) ?></h3>
            <p class="grafico__texto"><?= e($texto) ?></p>
        </div>
    </div>
    <div class="grafico__cuerpo">
        <figure class="grafico__anillo">
            <svg viewBox="0 0 42 42" role="img" aria-label="<?= e($resumen) ?>">
                <circle class="grafico__fondo" cx="21" cy="21" r="15.915" />
                <?php $inicio = 0.0; ?>
                <?php foreach ($visibles as [$clave, $nombre, , , $cantidad]) : ?>
                    <?php $largo = $cantidad * 100 / $total; ?>
                    <circle class="grafico__parte grafico__parte--<?= e($clave) ?>" cx="21" cy="21" r="15.915"
                        stroke-dasharray="<?= e(round(max($largo - $espacio, 0.4), 3)) ?> <?= e(round(100 - max($largo - $espacio, 0.4), 3)) ?>"
                        stroke-dashoffset="<?= e(round(25 - $inicio - $espacio / 2, 3)) ?>">
                        <title><?= e($nombre . ': ' . $cuantos($cantidad) . ' (' . $porcentaje($cantidad) . ' %)') ?></title>
                    </circle>
                    <?php $inicio += $largo; ?>
                <?php endforeach; ?>
            </svg>
            <figcaption class="grafico__centro" aria-hidden="true">
                <?php if ($total > 0) : ?>
                    <strong><?= e($porcentaje($partes[0][4])) ?> %</strong>
                    <span><?= e($centro) ?></span>
                <?php else : ?>
                    <span>Sin datos todavía</span>
                <?php endif; ?>
            </figcaption>
        </figure>

        <ul class="grafico__leyenda">
            <?php foreach ($partes as [$clave, $nombre, $explicacion, $icono, $cantidad, $enlace]) : ?>
                <li>
                    <<?= $enlace !== null ? 'a href="' . e($enlace) . '"' : 'div' ?> class="grafico__item grafico__item--<?= e($clave) ?>">
                        <span class="grafico__marca"><?= icon($icono) ?></span>
                        <span class="grafico__nombre">
                            <strong><?= e($nombre) ?></strong>
                            <span><?= e($explicacion) ?></span>
                        </span>
                        <span class="grafico__cantidad">
                            <strong><?= e($cantidad) ?></strong>
                            <span><?= e($porcentaje($cantidad)) ?> %</span>
                        </span>
                    </<?= $enlace !== null ? 'a' : 'div' ?>>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <dl class="grafico__datos">
        <?php foreach ($datos as [$etiqueta, $valor]) : ?>
            <div>
                <dt><?= e($etiqueta) ?></dt>
                <dd><?= e($valor) ?></dd>
            </div>
        <?php endforeach; ?>
    </dl>
</section>
