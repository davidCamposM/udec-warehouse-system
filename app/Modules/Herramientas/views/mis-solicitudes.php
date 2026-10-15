<?php

defined('APP_ROOT') || exit;

// «Mis solicitudes» del docente: cada una con su estado. Tocar una la muestra como el formulario en papel.
?>
<?= partial('encabezado', [
    'volver' => [url(), 'Volver al inicio'],
    'tarea' => 'mis-solicitudes',
    'titulo' => 'Mis solicitudes',
    'texto' => 'Las herramientas que pediste y en qué va cada solicitud.',
    'acciones' => '<a class="btn btn-success" href="' . e(url('herramientas/solicitar')) . '">' . icon('plus') . 'Solicitar herramientas</a>',
]) ?>

<?php if ($solicitudes === []) : ?>
    <div class="card card-body vacio-simple">
        <span class="vacio-simple__icono"><?= icon('clipboard') ?></span>
        <p class="vacio-simple__titulo">Todavía no has pedido herramientas</p>
        <p class="text-muted">Usa «Solicitar herramientas» para pedir lo que necesitas para una clase o salida a terreno.</p>
    </div>
<?php else : ?>
    <ul class="solicitudes">
        <?php foreach ($solicitudes as $solicitud) : ?>
            <li><?= partial('Herramientas::partials/fila', [
                'solicitud' => $solicitud,
                'url' => url('herramientas/solicitudes/' . $solicitud['id']),
            ]) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
