<?php

defined('APP_ROOT') || exit;

/**
 * Un producto para elegir con un toque: toda la tarjeta es el botón. Recibe $insumo y, opcional, $url: con ella es un
 * enlace; sin ella, un botón que envía el id del producto como insumo_id (dentro de un formulario).
 */
[$numero, $unidad] = explode(' ', unidades($insumo['stock_actual']), 2);
$etiqueta = isset($url) ? 'a' : 'button';
$atributos = isset($url)
    ? 'href="' . e($url) . '"'
    : 'type="submit" name="insumo_id" value="' . e($insumo['id']) . '"';
?>
<<?= $etiqueta ?> class="item-row item-row--elegir" <?= $atributos ?>>
    <span class="item-row__name"><?= e($insumo['nombre']) ?></span>
    <span class="item-row__meta">
        <?= e(codigo_producto($insumo)) ?> · Hay <?= e($numero) ?> <?= e($unidad) ?>
        <?php if ($insumo['sector'] !== null) : ?> · <?= e($insumo['sector']) ?><?php endif; ?>
    </span>
    <span class="item-row__elegir"><?= icon('ok') ?>Elegir</span>
</<?= $etiqueta ?>>
