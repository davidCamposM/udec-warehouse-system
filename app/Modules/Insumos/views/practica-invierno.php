<?php

use App\Modules\Insumos\PracticaInviernoController;

defined('APP_ROOT') || exit;

// «Insumos Práctica de Invierno», con los componentes básicos (basico.css): arriba los filtros (Todos, Con stock en
// verde, Sin stock en rojo, los colores de los gráficos del inicio) y una tarjeta por tipo de equipo, en dos columnas
// que se llenan de arriba abajo (sin huecos entre tarjetas de distinto largo); en cada fila el producto y, a la derecha,
// cuántos hay en grande, con «en bodega» o «Sin stock». Tocar uno abre su ficha (?desde=invierno: su «Volver» regresa
// aquí, con el mismo filtro).
$colores = ['con-stock' => 'bien', 'sin-stock' => 'grave'];
?>
<?= partial('encabezado', [
    'volver' => [url(), 'Volver al inicio'],
    'tarea' => 'practica-invierno',
    'titulo' => 'Insumos Práctica de Invierno',
    'texto' => 'El equipo de montaña de la práctica y cuántos hay en la bodega.',
]) ?>

<nav class="nav-pills invierno__filtros" aria-label="Mostrar">
    <?php foreach (PracticaInviernoController::FILTROS as $clave => $nombre) : ?>
        <a class="nav-link<?= isset($colores[$clave]) ? ' nav-link--' . e($colores[$clave]) : '' ?><?= $clave === $filtro ? ' active' : '' ?>"
            href="<?= e(url('practica-invierno', $clave === 'todos' ? [] : ['ver' => $clave])) ?>" <?= $clave === $filtro ? 'aria-current="true"' : '' ?>>
            <?= e($nombre) ?> <span class="nav-count"><?= e($conteo[$clave]) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<?php if ($grupos === []) : ?>
    <div class="card card-body vacio-simple">
        <span class="vacio-simple__icono"><?= icon('box') ?></span>
        <p class="vacio-simple__titulo"><?= e(match (true) {
            $conteo['todos'] === 0 => 'Todavía no hay insumos de la Práctica de Invierno',
            $filtro === 'con-stock' => 'Todavía no hay equipo con stock',
            default => 'Todo el equipo tiene stock',
        }) ?></p>
    </div>
<?php else : ?>
    <div class="invierno">
        <?php foreach ($grupos as $grupo => $productos) : ?>
            <section class="card invierno__grupo" aria-label="<?= e($grupo) ?>">
                <div class="card-header">
                    <h2 class="card-title"><?= e($grupo) ?></h2>
                    <span class="text-muted"><?= e(count($productos)) ?></span>
                </div>
                <ul class="invierno__lista">
                    <?php foreach ($productos as $insumo) : ?>
                        <?php $hay = (int) $insumo['stock_actual']; ?>
                        <li>
                            <a class="invierno__fila<?= $hay > 0 ? '' : ' invierno__fila--sin' ?>" href="<?= e(url('insumos/' . $insumo['id'], array_filter(['desde' => 'invierno', 'ver' => $filtro === 'todos' ? null : $filtro]))) ?>">
                                <span class="invierno__nombre"><?= e($insumo['nombre']) ?></span>
                                <span class="invierno__hay">
                                    <strong><?= e(cantidad($hay)) ?></strong>
                                    <span><?= $hay > 0 ? 'en bodega' : 'Sin stock' ?></span>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
