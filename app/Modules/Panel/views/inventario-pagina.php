<?php

defined('APP_ROOT') || exit;

// «Inventario» (el botón de «¿Cómo está la bodega?» en el inicio): todos los productos en una lista por páginas, con las
// categorías como pestañas. Tocar un nombre abre su ficha, y «Volver al inventario» regresa aquí (insumos-regreso.js).
?>
<?= partial('encabezado', [
    'volver' => [url(), 'Volver al inicio'],
    'color' => 'verde',
    'icono' => 'layers',
    'titulo' => 'Inventario',
    'texto' => 'Todos los productos de la bodega: cuántos hay, dónde están y cómo está su stock.',
]) ?>

<?= partial('Panel::inventario', $inventario) ?>
