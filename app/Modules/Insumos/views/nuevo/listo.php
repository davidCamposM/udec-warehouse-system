<?php

defined('APP_ROOT') || exit;

// Al agregar, en la ventana: lo que quedó agregado y nada más. «Finalizar» vuelve al inicio y «Agregar otro producto»,
// a la etapa 1.
?>
<?= partial('listo', [
    'titulo' => 'Listo, quedó agregado',
    'detalle' => 'Se agregó <strong>' . e($agregado['nombre']) . '</strong> con el código <strong>' . e($agregado['codigo'])
        . '</strong>, con <strong>' . e(unidades($agregado['cantidad'])) . '</strong> en <strong>' . e($agregado['sector']) . '</strong>.',
    'enlaces' => [['Ver el producto', url('insumos/' . $agregado['id'])]],
]) ?>

<div class="formulario__acciones">
    <a class="btn btn--secondary" href="<?= e(url()) ?>"><?= icon('home') ?>Finalizar</a>
    <a class="btn btn--si" href="<?= e(url('insumos/nuevo/producto')) ?>" autofocus><?= icon('plus') ?>Agregar otro producto</a>
</div>
