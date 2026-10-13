<?php

use App\Support\Tareas;

defined('APP_ROOT') || exit;

/**
 * Encabezado de cada pantalla interna: «← Volver a …», el ícono en el color de la tarea, el título y una
 * línea de instrucción.
 *
 * - $volver:  [url, texto] del botón para salir («Volver al inicio»).
 * - $tarea:   clave de config/tareas.php; de ahí salen el color y el ícono. O bien $color e $icono sueltos
 *             (pantallas que no son una tarea del inicio, como «Editar producto»). $color también puede ser
 *             'rojo', para las confirmaciones de algo delicado.
 * - $titulo:  qué se hace en esta pantalla. $texto (opcional): la instrucción.
 * - $acciones (opcional): HTML de botones que van bajo el título.
 */
if (isset($tarea)) {
    $datosTarea = Tareas::tarea($tarea);
    $color ??= $datosTarea['color'];
    $icono ??= $datosTarea['icono'];
}
?>
<header class="encabezado tono-<?= e($color) ?>">
    <a class="back-link" href="<?= e($volver[0]) ?>"><?= icon('arrow-left') ?><?= e($volver[1]) ?></a>
    <div class="encabezado__fila">
        <span class="encabezado__icono"><?= icon($icono) ?></span>
        <div class="encabezado__textos">
            <h1 class="encabezado__titulo"><?= e($titulo) ?></h1>
            <?php if (($texto ?? '') !== '') : ?>
                <p class="encabezado__texto"><?= e($texto) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php if (($acciones ?? '') !== '') : ?>
        <div class="encabezado__acciones"><?= $acciones ?></div>
    <?php endif; ?>
</header>
