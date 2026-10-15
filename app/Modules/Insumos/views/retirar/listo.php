<?php

defined('APP_ROOT') || exit;

// Al confirmar, en la ventana: lo que se retiró y nada más. «Finalizar» vuelve al inicio y «Retirar otro producto», a
// la etapa 1.
?>
<?= partial('listo', [
    'titulo' => 'Listo, quedó descontado',
    'detalle' => ($retirado['cantidad'] === 1 ? 'Se retiró' : 'Se retiraron')
        . ' <strong>' . e(unidades($retirado['cantidad'])) . '</strong> de <strong>' . e($retirado['nombre']) . '</strong>.'
        . ' Ahora ' . ($retirado['stock'] === 1 ? 'queda ' : 'quedan ') . e(unidades($retirado['stock'])) . ' en bodega.',
    'enlaces' => [['Ver el producto', url('insumos/' . $retirado['insumo_id'])]],
]) ?>

<div class="formulario__acciones">
    <a class="btn btn--secondary" href="<?= e(url()) ?>"><?= icon('home') ?>Finalizar</a>
    <a class="btn btn--si" href="<?= e(url('retirar')) ?>" autofocus><?= icon('arrow-out') ?>Retirar otro producto</a>
</div>
