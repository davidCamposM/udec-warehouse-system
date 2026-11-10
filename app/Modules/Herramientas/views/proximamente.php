<?php

defined('APP_ROOT') || exit;

// «Insumos Práctica de Invierno», mientras se prepara: el ícono de la montaña y el aviso (PracticaInviernoController).
?>
<?= partial('encabezado', [
    'volver' => [url(), 'Volver al inicio'],
    'tarea' => 'practica-invierno',
    'titulo' => 'Insumos Práctica de Invierno',
]) ?>

<section class="proximamente" aria-labelledby="proximamente-titulo">
    <span class="proximamente__icono" aria-hidden="true"><?= icon('mountain') ?></span>
    <h2 class="proximamente__titulo" id="proximamente-titulo">A la brevedad estará disponible esta sección</h2>
    <p class="proximamente__texto">¡Estamos trabajando en ello!</p>
</section>
