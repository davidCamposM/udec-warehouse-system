<?php

defined('APP_ROOT') || exit;

// Sectores de la bodega: una lista de filas compactas (nombre, cuántos productos guarda, el código de su etiqueta y los
// botones chicos «Editar» y «Eliminar»); «Agregar sector», arriba a la derecha. Los eliminados no aparecen: para las
// personas ya no existen.

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
    'lateral' => '<a class="boton-nuevo" href="' . e(url('sectores/nuevo')) . '">' . icon('plus') . 'Agregar sector</a>',
]) ?>

<?php if ($sectores === []) : ?>
    <div class="card card-body vacio-simple">
        <span class="vacio-simple__icono"><?= icon('map-pin') ?></span>
        <p class="vacio-simple__titulo">Todavía no hay sectores</p>
        <p class="text-muted">Toca «Agregar sector» y escanea la etiqueta impresa de una repisa o cubículo.</p>
    </div>
<?php else : ?>
    <ul class="sectores">
        <?php foreach ($sectores as $sector) : ?>
            <li class="sector">
                <span class="sector__icono" aria-hidden="true"><?= icon('map-pin') ?></span>
                <span class="sector__texto">
                    <strong><?= e($sector['nombre']) ?></strong>
                    <span><?= e($productos((int) $sector['productos'])) ?></span>
                </span>
                <span class="sector__codigo" title="Código de su etiqueta">
                    <span class="visually-hidden">Código del sector:</span><?= e($sector['codigo']) ?>
                </span>
                <span class="sector__acciones">
                    <a class="boton-fila" href="<?= e(url('sectores/' . $sector['id'] . '/editar')) ?>"><?= icon('edit') ?>Editar</a>
                    <a class="boton-fila boton-fila--peligro" href="<?= e(url('sectores/' . $sector['id'] . '/eliminar')) ?>"><?= icon('close') ?>Eliminar</a>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
    <p class="nota-sutil">
        <?= icon('info') ?>
        <span>
            El código de cada sector es el de su etiqueta impresa. Si uno quedó con otro código (por ejemplo SEC001),
            tócalo en «Editar» y escanea su etiqueta.
        </span>
    </p>
<?php endif; ?>
