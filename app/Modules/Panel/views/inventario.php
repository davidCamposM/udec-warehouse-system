<?php

defined('APP_ROOT') || exit;

// Inventario del inicio: a la izquierda las categorías principales (tocar una deja en la lista solo sus productos,
// incluidos los de sus subcategorías) y a la derecha la lista de inventario, por páginas. Recibe $categorias, $categoria
// (la elegida o null), $productos (los de esta página), $pagina, $paginas, $total y $desde (InsumoService::inventario).
$enlace = static fn (?int $id, int $numero = 1): string => url('', array_filter([
    'categoria' => $id,
    'pagina' => $numero > 1 ? $numero : null,
])) . '#inventario';
?>
<section class="inventario" id="inventario" aria-label="Inventario">
    <nav class="card" aria-labelledby="titulo-categorias">
        <div class="card-header">
            <h2 class="card-title" id="titulo-categorias">Categorías</h2>
        </div>
        <ul class="list-group">
            <li>
                <a class="list-group-item<?= $categoria === null ? ' active' : '' ?>" href="<?= e($enlace(null)) ?>"
                    <?= $categoria === null ? 'aria-current="true"' : '' ?>>Todas</a>
            </li>
            <?php foreach ($categorias as $fila) : ?>
                <?php $activa = (int) $fila['id'] === $categoria; ?>
                <li>
                    <a class="list-group-item<?= $activa ? ' active' : '' ?>"
                        href="<?= e($enlace((int) $fila['id'])) ?>" <?= $activa ? 'aria-current="true"' : '' ?>><?= e($fila['nombre']) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title" id="titulo-inventario">Inventario</h2>
            <span class="text-muted inventario__total"><?= e($total) ?> <?= $total === 1 ? 'producto' : 'productos' ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover inventario__tabla" aria-labelledby="titulo-inventario">
                <thead>
                    <tr>
                        <th scope="col">Código UdeC</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Categoría</th>
                        <th scope="col" class="text-end">Cantidad</th>
                        <th scope="col">Ubicación</th>
                        <th scope="col">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $insumo) : ?>
                        <tr>
                            <td class="inventario__codigo"><?= e($insumo['codigo_udec'] ?? '—') ?></td>
                            <td><a class="inventario__nombre" href="<?= e(url('insumos/' . $insumo['id'])) ?>"><?= e($insumo['nombre']) ?></a></td>
                            <td class="text-muted"><?= e($insumo['categoria'] ?? 'Sin categoría') ?></td>
                            <td class="inventario__numero text-end"><?= e(explode(' ', unidades($insumo['stock_actual']), 2)[0]) ?></td>
                            <td class="text-muted"><?= e($insumo['sector'] ?? 'Sin ubicación') ?></td>
                            <td><?= partial('Insumos::partials/estado', ['insumo' => $insumo]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($productos === []) : ?>
                        <tr>
                            <td class="inventario__vacio text-muted" colspan="6">No hay productos en esta categoría.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($paginas > 1) : ?>
            <nav class="pagination" aria-label="Páginas de la lista">
                <?php if ($pagina > 1) : ?>
                    <a class="page-link" href="<?= e($enlace($categoria, $pagina - 1)) ?>"><?= icon('arrow-left') ?>Anterior</a>
                <?php else : ?>
                    <span class="page-link disabled" aria-hidden="true"><?= icon('arrow-left') ?>Anterior</span>
                <?php endif; ?>
                <p class="page-info">
                    Página <strong><?= e($pagina) ?></strong> de <strong><?= e($paginas) ?></strong>
                    · productos <?= e($desde + 1) ?> a <?= e($desde + count($productos)) ?>
                </p>
                <?php if ($pagina < $paginas) : ?>
                    <a class="page-link" href="<?= e($enlace($categoria, $pagina + 1)) ?>">Siguiente<?= icon('arrow-right') ?></a>
                <?php else : ?>
                    <span class="page-link disabled" aria-hidden="true">Siguiente<?= icon('arrow-right') ?></span>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </div>
</section>
