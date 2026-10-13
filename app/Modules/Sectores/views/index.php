<?php

use App\Support\Tareas;

defined('APP_ROOT') || exit;

// Sectores de la bodega, con los componentes básicos (basico.css): una tarjeta por sector (nombre, cuántos productos
// guarda, su código como la etiqueta que se pega en el lugar y sus botones) y, aparte, la lista de los desactivados.

$activos = array_values(array_filter($sectores, static fn (array $s): bool => (int) $s['activo'] === 1));
$desactivados = array_values(array_filter($sectores, static fn (array $s): bool => (int) $s['activo'] !== 1));

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
    'texto' => 'Cada parte de la bodega tiene un código. Al registrar un producto se escanea el código del sector donde queda.',
    'acciones' => '<a class="btn btn-success" href="' . e(url('sectores/nuevo')) . '">' . icon('plus') . 'Agregar sector</a>',
]) ?>

<?php if ($activos === []) : ?>
    <div class="card card-body vacio-simple">
        <span class="vacio-simple__icono"><?= icon('map-pin') ?></span>
        <p class="vacio-simple__titulo">Todavía no hay sectores</p>
        <p class="text-muted">Agrega el primero con el botón «Agregar sector». Por ejemplo: Estante A, Repisa de líquidos.</p>
    </div>
<?php else : ?>
    <ul class="registros tono-<?= e(Tareas::tarea('sectores')['color']) ?>">
        <?php foreach ($activos as $sector) : ?>
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
                    <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('sectores/' . $sector['id'] . '/editar')) ?>"><?= icon('edit') ?>Cambiar nombre</a>
                    <a class="btn btn-outline-danger btn-sm" href="<?= e(url('sectores/' . $sector['id'] . '/desactivar')) ?>">Desactivar</a>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    <p class="nota-info">
        <?= icon('info') ?>
        <span>
            Pronto podrás imprimir el código QR de cada sector para pegarlo en su lugar. Mientras tanto, al registrar un
            producto puedes tocar el sector en la lista o escribir su código.
        </span>
    </p>
<?php endif; ?>

<?php if ($desactivados !== []) : ?>
    <section class="card desactivados" aria-labelledby="titulo-desactivados">
        <div class="card-header">
            <h2 class="card-title" id="titulo-desactivados">Desactivados</h2>
            <span class="text-muted desactivados__ayuda">No aparecen al registrar productos</span>
        </div>
        <ul class="list-group">
            <?php foreach ($desactivados as $sector) : ?>
                <li class="list-group-item desactivado">
                    <span class="desactivado__codigo"><?= e($sector['codigo']) ?></span>
                    <span class="desactivado__nombre"><?= e($sector['nombre']) ?></span>
                    <form method="post" action="<?= e(url('sectores/' . $sector['id'] . '/activar')) ?>" data-submit-once>
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-secondary btn-sm" type="submit" data-loading-text="Activando…">Volver a activar</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
