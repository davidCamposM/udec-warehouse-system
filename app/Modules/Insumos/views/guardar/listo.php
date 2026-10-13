<?php

defined('APP_ROOT') || exit;

// Al confirmar, en la ventana: lo que quedó registrado y nada más. «Finalizar» vuelve al inicio y «Registrar otro
// producto», a la etapa 1.
?>
<?= partial('listo', [
    'titulo' => 'Listo, quedó registrado',
    'detalle' => ($registrado['cantidad'] === 1 ? 'Se registró' : 'Se registraron')
        . ' <strong>' . e(unidades($registrado['cantidad'])) . '</strong> de <strong>' . e($registrado['nombre']) . '</strong>'
        . ' en <strong>' . e($registrado['sector']) . '</strong>. Ahora hay ' . e(unidades($registrado['stock'])) . ' en bodega.',
    'enlaces' => [['Ver el producto', url('insumos/' . $registrado['insumo_id'])]],
]) ?>

<div class="formulario__acciones">
    <a class="btn btn--secondary" href="<?= e(url()) ?>"><?= icon('home') ?>Finalizar</a>
    <a class="btn btn--si" href="<?= e(url('guardar')) ?>" autofocus><?= icon('plus') ?>Registrar otro producto</a>
</div>
