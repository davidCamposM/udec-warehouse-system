<?php

defined('APP_ROOT') || exit;

// «Difusión»: por ahora solo avisa que la sección está en preparación (DifusionController).
?>
<?= partial('encabezado', [
    'volver' => [url(), 'Volver al inicio'],
    'tarea' => 'difusion',
    'titulo' => 'Difusión',
    'texto' => 'Los insumos que se piden fuera de abasto, a distintos proveedores.',
]) ?>

<div class="card card-body vacio-simple">
    <span class="vacio-simple__icono"><?= icon('clock') ?></span>
    <p class="vacio-simple__titulo">Esta sección está en preparación</p>
    <p class="text-muted">Pronto podrás registrar aquí los pedidos a proveedores fuera de abasto y seguir lo que llega.</p>
    <a class="btn btn-outline-secondary" href="<?= e(url()) ?>"><?= icon('arrow-left') ?>Volver al inicio</a>
</div>
