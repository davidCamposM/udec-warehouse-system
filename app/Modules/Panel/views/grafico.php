<?php

defined('APP_ROOT') || exit;

// Un estado de la bodega (insumos o herramientas), la vista rápida de cómo está:
// 1) Arriba, el título. 2) La torta (un anillo en SVG: circunferencia 100, así el largo de cada arco es su porcentaje),
//    con el total al centro.
//    Colores lisos, arcos de puntas redondeadas, una graduación fina alrededor y un disco al centro; los arcos entran
//    girando al cargar. Al pasar el mouse sobre un arco o su fila, ese arco se engrosa, los demás se apagan y el
//    centro muestra su porcentaje.
// 3) Al lado de la torta, compacto: la cifra en una línea y una fila por parte (ícono, nombre, una barra fina con lo
//    que pesa en el total y la cantidad; el color nunca va solo); si tiene enlace, lleva a su lista. Al pasar sobre una
//    fila se resalta su arco. 4) Abajo, una franja delgada con unos datos más.
// Cada número aparece una sola vez: el porcentaje de cada parte queda en su arco (al pasar el mouse) y para lectores
// de pantalla.
// Recibe lo de PanelService::graficos(): $titulo, $icono, $unidad, $frase, $partes, $datos y $id.

$total = array_sum(array_column($partes, 4));
$porcentaje = static fn (int $n): int => $total > 0 ? (int) round($n * 100 / $total) : 0;
$cuantos = static fn (int $n): string => $n . ' ' . ($n === 1 ? $unidad[0] : $unidad[1]);
$principal = $partes[0];
// En la torta, solo las partes con algo. Los arcos tienen los extremos redondeados: cada punta suma medio grosor
// ($grosor, el mismo que stroke-width en panel.css), así que el trazo se acorta en un grosor más el espacio entre arcos.
// Con una sola parte, el anillo va entero.
$visibles = array_values(array_filter($partes, static fn (array $parte): bool => $parte[4] > 0));
$grosor = 3.2;
$espacio = 1.8;
$solo = count($visibles) === 1;
// El largo de cada arco en el anillo (de 100). Una parte chica necesita al menos sus dos puntas, su espacio y un trazo
// corto ($minimo): si no, su punto se sale de su lugar y toca al color vecino. Las chicas reciben ese mínimo y el resto
// del anillo se reparte entre las demás, en proporción (se repite hasta que ninguna quede bajo el mínimo).
$minimo = $grosor + $espacio + 0.8;
$largos = array_map(static fn (array $parte): float => $parte[4] * 100 / $total, $visibles);
if (!$solo) {
    $fijas = [];
    do {
        $antes = count($fijas);
        $libres = array_diff_key($largos, $fijas);
        $sumaLibres = array_sum(array_map(static fn (int $i): int => $visibles[$i][4], array_keys($libres)));
        $resto = 100 - count($fijas) * $minimo;
        foreach (array_keys($libres) as $i) {
            $largos[$i] = $visibles[$i][4] * $resto / $sumaLibres;
            if ($largos[$i] < $minimo) {
                $fijas[$i] = true;
                $largos[$i] = $minimo;
            }
        }
    } while (count($fijas) !== $antes);
}
// La graduación de afuera: 60 marcas finas, más largas en cada cuarto.
$marcas = [];
for ($i = 0; $i < 60; $i++) {
    $angulo = deg2rad($i * 6 - 90);
    $largoMarca = $i % 15 === 0 ? 1.4 : 0.7;
    $marcas[] = [
        round(21 + 19.2 * cos($angulo), 3), round(21 + 19.2 * sin($angulo), 3),
        round(21 + (19.2 + $largoMarca) * cos($angulo), 3), round(21 + (19.2 + $largoMarca) * sin($angulo), 3),
        $i % 15 === 0,
    ];
}
?>
<section class="estado" aria-labelledby="<?= e($id) ?>">
    <header class="estado__cabecera">
        <span class="estado__icono" aria-hidden="true"><?= icon($icono) ?></span>
        <div class="estado__titulos">
            <h3 class="estado__titulo" id="<?= e($id) ?>"><?= e($titulo) ?></h3>
        </div>
    </header>

    <?php if ($total === 0) : ?>
        <p class="estado__vacio">Todavía no hay <?= e($unidad[1]) ?> para mostrar.</p>
    <?php else : ?>
        <div class="estado__vista">
            <figure class="estado__torta">
                <svg viewBox="0 0 42 42" role="img" aria-label="<?= e($titulo . ': ' . implode(', ', array_map(
                    static fn (array $parte): string => $parte[1] . ' ' . $cuantos($parte[4]) . ' (' . $porcentaje($parte[4]) . ' %)',
                    $partes,
                ))) ?>">
                    <g class="estado__graduacion" aria-hidden="true">
                        <?php foreach ($marcas as [$x1, $y1, $x2, $y2, $cuarto]) : ?>
                            <line x1="<?= e($x1) ?>" y1="<?= e($y1) ?>" x2="<?= e($x2) ?>" y2="<?= e($y2) ?>"<?= $cuarto ? ' class="estado__cuarto"' : '' ?> />
                        <?php endforeach; ?>
                    </g>
                    <circle class="estado__disco" cx="21" cy="21" r="12.6" />
                    <circle class="estado__pista" cx="21" cy="21" r="15.915" />
                    <g class="estado__arcos">
                        <?php $inicio = 0.0; ?>
                        <?php foreach ($visibles as $n => [$clave, $nombre, , , $cantidad]) : ?>
                            <?php
                            $largo = $largos[$n];
                            $trazo = $solo ? 100 : $largo - $grosor - $espacio;
                            $desde = $solo ? 0 : $inicio + ($grosor + $espacio) / 2;
                            ?>
                            <circle class="estado__tramo estado__tramo--<?= e($clave) ?><?= $solo ? ' estado__tramo--entero' : '' ?>" cx="21" cy="21" r="15.915"
                                stroke-dasharray="<?= e(round($trazo, 3)) ?> <?= e(round(100 - $trazo, 3)) ?>"
                                stroke-dashoffset="<?= e(round(25 - $desde, 3)) ?>">
                                <title><?= e($nombre . ': ' . $cuantos($cantidad) . ' (' . $porcentaje($cantidad) . ' %)') ?></title>
                            </circle>
                            <?php $inicio += $largo; ?>
                        <?php endforeach; ?>
                    </g>
                </svg>
                <?php // Al centro, el total; al pasar sobre un arco o su tarjeta, lo de esa parte. ?>
                <figcaption class="estado__centro" aria-hidden="true">
                    <span class="estado__lectura estado__lectura--total">
                        <strong><?= e($total) ?></strong>
                        <span><?= e($total === 1 ? $unidad[0] : $unidad[1]) ?></span>
                    </span>
                    <?php foreach ($visibles as [$clave, $nombre, , , $cantidad]) : ?>
                        <span class="estado__lectura estado__lectura--<?= e($clave) ?>">
                            <strong><?= e($porcentaje($cantidad)) ?><small>%</small></strong>
                            <span><?= e($nombre) ?></span>
                        </span>
                    <?php endforeach; ?>
                </figcaption>
            </figure>

            <?php // Al lado de la torta: la cifra principal en una línea y, debajo, una fila por parte (la leyenda). ?>
            <div class="estado__lado">
                <p class="estado__cifra">
                    <strong><?= e($porcentaje($principal[4])) ?><small>%</small></strong>
                    <span><?= e($frase) ?></span>
                </p>
                <ul class="estado__partes estado__partes--<?= e(count($partes)) ?>">
                    <?php foreach ($partes as [$clave, $nombre, $explicacion, $iconoParte, $cantidad, $enlace]) : ?>
                        <li>
                            <<?= $enlace !== null ? 'a href="' . e($enlace) . '"' : 'div' ?>
                                class="estado__parte estado__parte--<?= e($clave) ?><?= $cantidad === 0 ? ' estado__parte--cero' : '' ?>"
                                title="<?= e($explicacion) ?>">
                                <span class="estado__marca" aria-hidden="true"><?= icon($iconoParte) ?></span>
                                <strong class="estado__cantidad"><?= e($cantidad) ?></strong>
                                <span class="estado__proporcion" aria-hidden="true"><span style="width: <?= e($porcentaje($cantidad)) ?>%"></span></span>
                                <span class="estado__nombre">
                                    <?= e($nombre) ?>
                                    <span class="visually-hidden">: <?= e($porcentaje($cantidad)) ?> %. <?= e($explicacion) ?>.</span>
                                </span>
                                <?php if ($enlace !== null) : ?>
                                    <span class="estado__ir" aria-hidden="true"><?= icon('arrow-right') ?></span>
                                <?php endif; ?>
                            </<?= $enlace !== null ? 'a' : 'div' ?>>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <dl class="estado__datos">
        <?php foreach ($datos as [$etiqueta, $valor]) : ?>
            <div>
                <dt><?= e($etiqueta) ?></dt>
                <dd><?= e($valor) ?></dd>
            </div>
        <?php endforeach; ?>
    </dl>
</section>
