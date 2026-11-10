<?php

defined('APP_ROOT') || exit;

// Inventario (su página, Panel::inventario-pagina): una tarjeta con la lista por páginas; en su cabecera, las categorías
// como pestañas (tocar una deja en la lista solo sus productos). Recibe $categorias, $categoria (la elegida o null),
// $productos (los de esta página), $pagina, $paginas, $total y $desde (InsumoService::inventario).
$enlace = static fn (?int $id, int $numero = 1): string => url('inventario', array_filter([
    'categoria' => $id,
    'pagina' => $numero > 1 ? $numero : null,
])) . '#inventario';
?>
<section class="inventario" id="inventario" aria-label="Inventario">
    <div class="card">
        <?php // Una sola cabecera: las categorías como pestañas chicas y el total (el título va arriba, en la página). ?>
        <div class="card-header inventario__cabecera">
            <h2 class="visually-hidden" id="titulo-inventario">Lista de productos</h2>
            <nav class="inventario__categorias" aria-label="Categorías">
                <a class="inventario__categoria<?= $categoria === null ? ' is-activa' : '' ?>" href="<?= e($enlace(null)) ?>"
                    <?= $categoria === null ? 'aria-current="true"' : '' ?>>Todas</a>
                <?php foreach ($categorias as $fila) : ?>
                    <?php $activa = (int) $fila['id'] === $categoria; ?>
                    <a class="inventario__categoria<?= $activa ? ' is-activa' : '' ?>" href="<?= e($enlace((int) $fila['id'])) ?>"
                        <?= $activa ? 'aria-current="true"' : '' ?>><?= e($fila['nombre']) ?></a>
                <?php endforeach; ?>
            </nav>
            <span class="text-muted inventario__total"><?= e($total) ?> <?= $total === 1 ? 'producto' : 'productos' ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover tabla-apilada inventario__tabla" aria-labelledby="titulo-inventario">
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
                        <tr id="inventario-insumo-<?= e($insumo['id']) ?>">
                            <td class="inventario__codigo" data-label="Código UdeC"><?= e($insumo['codigo_udec'] ?? '—') ?></td>
                            <?php // La foto del producto (la misma de «Buscar producto»); sin foto, el ícono de la caja. ?>
                            <?php $foto = foto_producto($insumo['codigo_udec'] ?? null, $insumo['codigo'] ?? null); ?>
                            <td class="tabla-apilada__titulo">
                                <span class="inventario__producto">
                                    <span class="inventario__foto">
                                        <?php if ($foto !== null) : ?>
                                            <img src="<?= e($foto) ?>" alt="" width="44" height="44" loading="lazy" decoding="async">
                                        <?php else : ?>
                                            <?= icon('box') ?>
                                        <?php endif; ?>
                                    </span>
                                    <a class="inventario__nombre" href="<?= e(url('insumos/' . $insumo['id'])) ?>"><?= e($insumo['nombre']) ?></a>
                                </span>
                            </td>
                            <td class="text-muted" data-label="Categoría"><?= e($insumo['categoria'] ?? 'Sin categoría') ?></td>
                            <td class="inventario__numero text-end" data-label="Cantidad"><?= e(explode(' ', unidades($insumo['stock_actual']), 2)[0]) ?></td>
                            <td class="text-muted" data-label="Ubicación"><?= e($insumo['sector'] ?? 'Sin ubicación') ?></td>
                            <td data-label="Estado"><?= partial('Insumos::partials/estado', ['insumo' => $insumo]) ?></td>
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
