<?php

use App\Support\Tareas;

defined('APP_ROOT') || exit;

/**
 * Ventana que se abre sobre la página, con el fondo difuminado: un formulario por etapas. Va en $ventana del layout.
 *
 * - $tarea: clave de config/tareas.php (de ahí salen el color y el ícono). $titulo y $texto (opcional): la cabecera.
 * - $cerrar: URL del botón «Cerrar» (también la usa la tecla Esc).
 * - $cuerpo: HTML de la etapa.
 * - $ancha (opcional): más ancha, para un formulario con varias columnas (como «Solicitar herramientas»).
 * Los mensajes que deja la acción anterior (flash) se muestran dentro de la ventana, no detrás.
 */
$datosTarea = Tareas::tarea($tarea);
?>
<div class="ventana tono-<?= e($datosTarea['color']) ?><?= !empty($ancha) ? ' ventana--ancha' : '' ?>" data-ventana data-cerrar="<?= e($cerrar) ?>">
    <div class="ventana__fondo"></div>
    <section class="ventana__caja" role="dialog" aria-modal="true" aria-labelledby="ventana-titulo">
        <header class="ventana__cabecera">
            <span class="ventana__icono"><?= icon($datosTarea['icono']) ?></span>
            <div class="ventana__textos">
                <h2 class="ventana__titulo" id="ventana-titulo"><?= e($titulo) ?></h2>
                <?php if (($texto ?? '') !== '') : ?>
                    <p class="ventana__texto"><?= e($texto) ?></p>
                <?php endif; ?>
            </div>
            <a class="ventana__cerrar" href="<?= e($cerrar) ?>"><?= icon('close') ?><span>Cerrar</span></a>
        </header>

        <div class="ventana__cuerpo">
            <?= partial('flash') ?>
            <?= $cuerpo ?>
        </div>
    </section>
</div>
