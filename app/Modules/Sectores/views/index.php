<?php

use App\Support\Tareas;

defined('APP_ROOT') || exit;

// Sectores de la bodega, con los componentes básicos (basico.css): una tarjeta por sector (nombre, cuántos productos
// guarda, su código como la etiqueta que se pega en el lugar y sus botones «Editar» y «Eliminar»). Los
// eliminados no aparecen: para las personas ya no existen.

/** "Sin productos", "1 producto guardado aquí", "4 productos guardados aquí" */
$productos = static fn (int $n): string => match (true) {
    $n === 0 => 'Sin productos todavía',
    $n === 1 => '1 producto guardado aquí',
    default => "{$n} productos guardados aquí",
};
?>
<?= partial('encabezado', [
    'volver' => [url(), 'Volver al inicio'],
    'tarea' => 'sectores',
    'titulo' => 'Sectores de bodega',
    'texto' => 'Cada parte de la bodega tiene su etiqueta. Al registrar un producto se escanea la etiqueta del lugar donde queda.',
    'acciones' => '<a class="btn btn-success" href="' . e(url('sectores/nuevo')) . '">' . icon('plus') . 'Agregar sector</a>',
]) ?>

<?php if ($sectores === []) : ?>
    <div class="card card-body vacio-simple">
        <span class="vacio-simple__icono"><?= icon('map-pin') ?></span>
        <p class="vacio-simple__titulo">Todavía no hay sectores</p>
        <p class="text-muted">Toca «Agregar sector» y escanea la etiqueta impresa de una repisa o cubículo.</p>
    </div>
<?php else : ?>
    <ul class="registros tono-<?= e(Tareas::tarea('sectores')['color']) ?>">
        <?php foreach ($sectores as $sector) : ?>
            <li class="registro">
                <div class="registro__cabecera">
                    <span class="registro__icono"><?= icon('map-pin') ?></span>
                    <div>
                        <p class="registro__nombre"><?= e($sector['nombre']) ?></p>
                        <p class="registro__detalle"><?= e($productos((int) $sector['productos'])) ?></p>
                    </div>
                </div>
                <span class="registro__etiqueta" title="Código del sector">
                    <?= icon('scan') ?><span class="visually-hidden">Código del sector:</span><span class="registro__codigo"><?= e($sector['codigo']) ?></span>
                </span>
                <div class="registro__acciones">
                    <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('sectores/' . $sector['id'] . '/editar')) ?>"><?= icon('edit') ?>Editar</a>
                    <a class="btn btn-outline-danger btn-sm" href="<?= e(url('sectores/' . $sector['id'] . '/eliminar')) ?>">Eliminar</a>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    <p class="nota-info">
        <?= icon('info') ?>
        <span>
            El código de cada sector es el de su etiqueta impresa. Si un sector quedó con un código que no es el de su
            etiqueta (por ejemplo SEC001), tócalo en «Editar» y escanea su etiqueta.
        </span>
    </p>
<?php endif; ?>
