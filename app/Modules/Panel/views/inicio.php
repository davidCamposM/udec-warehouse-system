<?php

use App\Core\Auth;
use App\Support\Fecha;

defined('APP_ROOT') || exit;

// Inicio con los componentes básicos (basico.css, al estilo de Bootstrap), en el orden en que se lee: el saludo (quién
// y qué día), la barra para buscar productos (mientras se escribe, debajo aparecen los que coinciden, insumos.js; la
// lupa lleva a «Buscar producto»), el tablero (las tareas y, al lado, los avisos y la administración) y el
// inventario (el detalle). Bajo las tareas, para las administrativas, los gráficos de la bodega; sobre los avisos,
// «Imprimir estado de bodega».
// $deFondo: va detrás de una ventana (como «Registrar producto»), así que la barra no toma el foco.

$usuario = Auth::user();
$hora = (int) date('G');
$saludo = match (true) {
    $hora < 12 => 'Buenos días',
    $hora < 20 => 'Buenas tardes',
    default => 'Buenas noches',
};
$nombre = explode(' ', trim((string) ($usuario['nombre'] ?? '')))[0];

// Las tareas de administración (se usan poco) van aparte, debajo de las del día a día
$administracion = ['sectores', 'usuarios', 'etiquetas', 'configuracion'];
$grupos = ['Tareas' => [], 'Administración' => []];
foreach ($tareas as $tarea) {
    $grupos[in_array($tarea['clave'], $administracion, true) ? 'Administración' : 'Tareas'][] = $tarea;
}
?>
<div class="inicio" data-panel data-panel-url="<?= e(url('panel/resumen')) ?>" data-panel-interval="<?= e($intervalo) ?>">
    <?php // Arriba, la cabecera clara: el saludo y, debajo, la barra para buscar ?>
    <header class="inicio__hero">
        <div class="inicio__encabezado">
            <h1 class="inicio__saludo"><?= e($saludo) ?><?= $nombre !== '' ? ', ' . e($nombre) : '' ?></h1>
            <p class="inicio__fecha"><?= e(mb_ucfirst(Fecha::larga())) ?></p>
        </div>
        <?php if ($buscar) : ?>
            <form class="input-group input-group-lg sugerencias" method="get" action="<?= e(url('insumos')) ?>" role="search">
                <a class="btn btn-primary btn-icon" href="<?= e(url('insumos')) ?>" title="Ir a Buscar producto"
                    aria-label="Ir a Buscar producto"><?= icon('search') ?></a>
                <label class="visually-hidden" for="buscar">Buscar producto</label>
                <input class="form-control" id="buscar" name="q" type="search" placeholder="Buscar producto por nombre o código"
                    autocomplete="off" enterkeyhint="search" data-sugerencias="<?= e(url('insumos/sugerencias')) ?>"
                    <?= $deFondo ? '' : 'autofocus' ?>>
            </form>
        <?php endif; ?>
    </header>

    <?php if ($bienvenida !== null) : ?>
        <div class="alert alert--info inicio__bienvenida" role="status">
            <?= icon('info') ?>
            <p>
                <?php if ($bienvenida['acceso_anterior'] === null) : ?>
                    Este es tu primer ingreso al sistema.
                <?php else : ?>
                    Tu ingreso anterior fue el <?= e(Fecha::mostrar($bienvenida['acceso_anterior'])) ?>.
                    Si no fuiste tú, avisa al encargado de bodega.
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>

    <?php // Tablero: a la izquierda lo que se hace (tareas); a la derecha lo que pasa (avisos) y lo que se configura (administración). ?>
    <div class="tablero<?= $grupos['Tareas'] === [] && $bodega === null ? ' tablero--una-columna' : '' ?>">
        <?php if ($grupos['Tareas'] !== [] || $bodega !== null) : ?>
            <div class="tablero__tareas">
            <?php if ($grupos['Tareas'] !== []) : ?>
            <nav aria-labelledby="titulo-tareas">
                <h2 class="titulo-seccion" id="titulo-tareas">¿Qué quieres hacer?</h2>
                <ul class="lista-acciones">
                    <?php foreach ($grupos['Tareas'] as $tarea) : ?>
                        <li>
                            <a class="accion accion--<?= e($tarea['color']) ?>" href="<?= e($tarea['url']) ?>">
                                <span class="accion__icono"><?= icon($tarea['icono']) ?></span>
                                <span class="accion__texto">
                                    <strong class="accion__titulo"><?= e($tarea['texto']) ?></strong>
                                    <span class="accion__detalle"><?= e($tarea['descripcion']) ?></span>
                                </span>
                                <span class="accion__flecha" aria-hidden="true"><?= icon('arrow-right') ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <?php endif; ?>

            <?php // Debajo de las tareas, cómo está la bodega: para responder «¿cuál es el estado de la bodega ahora mismo?». ?>
            <?php if ($bodega !== null) : ?>
                <section class="graficos" aria-labelledby="titulo-graficos">
                    <h2 class="titulo-seccion" id="titulo-graficos">¿Cómo está la bodega?</h2>
                    <div class="graficos__lista">
                        <?php foreach ($bodega['graficos'] as $i => $grafico) : ?>
                            <?= partial('Panel::grafico', $grafico + ['id' => 'grafico-' . $i]) ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="tablero__avisos">
            <?php // Sobre los avisos, el documento Word con el estado completo de la bodega (encargado y administrativas). ?>
            <?php if ($imprimir) : ?>
                <a class="btn btn-outline-secondary tablero__imprimir" href="<?= e(url('panel/estado-bodega')) ?>" download>
                    <?= icon('printer') ?>Imprimir estado de bodega
                </a>
            <?php endif; ?>
            <section class="card" aria-labelledby="titulo-avisos">
                <div class="card-header">
                    <h2 class="card-title" id="titulo-avisos">Avisos</h2>
                    <p class="inicio__estado" data-panel-status>Actualizado a las <?= e(date('H:i')) ?></p>
                </div>
                <div data-panel-content>
                    <?= $avisos ?>
                </div>
            </section>
        </div>

        <?php if ($grupos['Administración'] !== []) : ?>
            <nav class="card tablero__admin" aria-labelledby="titulo-administracion">
                <div class="card-header">
                    <h2 class="card-title" id="titulo-administracion">Administración</h2>
                </div>
                <ul class="list-group">
                    <?php foreach ($grupos['Administración'] as $tarea) : ?>
                        <li>
                            <a class="list-group-item ajuste accion--<?= e($tarea['color']) ?>" href="<?= e($tarea['url']) ?>">
                                <span class="ajuste__icono"><?= icon($tarea['icono']) ?></span>
                                <span class="ajuste__texto">
                                    <strong><?= e($tarea['texto']) ?></strong>
                                    <span class="ajuste__detalle"><?= e($tarea['descripcion']) ?></span>
                                </span>
                                <span class="ajuste__flecha" aria-hidden="true"><?= icon('arrow-right') ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <?php if ($tareas === [] && !$buscar) : ?>
        <p class="card card-body text-muted">Pronto podrás hacer tus trámites aquí.</p>
    <?php endif; ?>

    <?php if ($inventario !== null) : ?>
        <?= partial('Panel::inventario', $inventario) ?>
    <?php endif; ?>
</div>
