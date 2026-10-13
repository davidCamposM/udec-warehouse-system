<?php

defined('APP_ROOT') || exit;

/**
 * La lista bajo una barra de búsqueda, que pide insumos.js mientras se escribe (InsumoController::sugerencias).
 * - $productos: los primeros que coinciden con $texto. $total: cuántos coinciden en total; si son más, un botón envía
 *   la búsqueda para verlos todos.
 * - $para: qué hace tocar uno. ficha, guardar y ajustar: es un enlace. envase: es un botón del formulario
 *   #envase-elegir (en guardar/envase), que lo elige como el producto del envase.
 * - $agregar: URL de «Agregar nuevo producto», que se ofrece cuando ninguno coincide (o null).
 * El nombre y los datos llevan data-resaltar: insumos.js marca en ellos lo escrito.
 */
$elegir = match ($para) {
    'guardar' => static fn (array $insumo): string => 'href="' . e(url('guardar', ['producto' => $insumo['id']])) . '"',
    'ajustar' => static fn (array $insumo): string => 'href="' . e(url('ajustar', ['producto' => $insumo['id']])) . '"',
    'envase' => static fn (array $insumo): string => 'type="submit" form="envase-elegir" name="insumo_id" value="' . e($insumo['id']) . '"',
    default => static fn (array $insumo): string => 'href="' . e(url('insumos/' . $insumo['id'])) . '"',
};
$etiqueta = $para === 'envase' ? 'button' : 'a';
$anuncio = match ($total) {
    0 => 'Ningún producto coincide.',
    1 => 'Coincide 1 producto.',
    default => "Coinciden {$total} productos.",
};
?>
<div data-anuncio="<?= e($anuncio) ?>">
    <?php if ($productos !== []) : ?>
        <ul class="sugerencias__lista" role="listbox" aria-label="Productos que coinciden">
            <?php foreach ($productos as $insumo) : ?>
                <?php [$numero, $unidad] = explode(' ', unidades($insumo['stock_actual']), 2); ?>
                <li role="presentation">
                    <<?= $etiqueta ?> class="sugerencia" role="option" tabindex="-1" <?= $elegir($insumo) ?>>
                        <span class="sugerencia__nombre" data-resaltar><?= e($insumo['nombre']) ?></span>
                        <span class="sugerencia__cantidad"><?= e($numero) ?> <small><?= e($unidad) ?></small></span>
                        <span class="sugerencia__meta" data-resaltar><?= e(implode(' · ', array_filter([
                            $insumo['codigo'],
                            $insumo['categoria'] ?? 'Sin categoría',
                            $insumo['sector'],
                        ]))) ?></span>
                        <?= partial('Insumos::partials/estado', ['insumo' => $insumo]) ?>
                    </<?= $etiqueta ?>>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($total > count($productos)) : ?>
            <button class="sugerencias__todos" type="submit">Ver los <?= e($total) ?> que coinciden<?= icon('arrow-right') ?></button>
        <?php endif; ?>
    <?php else : ?>
        <div class="sugerencias__vacio">
            <p class="sugerencias__titulo">No encontramos «<?= e($texto) ?>»</p>
            <p>
                <?= $para === 'envase'
                    ? 'Prueba con otra palabra. Si no está, vuelve atrás y elige «Es un producto nuevo».'
                    : 'Revisa lo escrito, busca con otra palabra o escanea el código del producto.' ?>
            </p>
            <?php if ($agregar !== null) : ?>
                <a class="btn btn--tarea tono-turquesa" href="<?= e($agregar) ?>"><?= icon('plus') ?>Agregar nuevo producto</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
