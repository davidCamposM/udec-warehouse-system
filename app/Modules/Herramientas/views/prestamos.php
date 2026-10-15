<?php

use App\Modules\Herramientas\SolicitudService;

defined('APP_ROOT') || exit;

// «Préstamos de herramientas» del encargado: las solicitudes de los docentes por estado, como pastillas con su
// cantidad. Tocar una la abre como el formulario en papel, con «Entregar» o «Recibir devolución».
$vacios = [
    'pendientes' => 'No hay solicitudes por entregar.',
    'prestadas' => 'No hay herramientas prestadas.',
    'atrasadas' => 'No hay préstamos atrasados.',
    'problemas' => 'No hay herramientas dañadas ni perdidas por resolver.',
    'devueltas' => 'Todavía no se ha devuelto ningún préstamo.',
    'todas' => 'Todavía no hay solicitudes de herramientas.',
];
?>
<?= partial('encabezado', [
    'volver' => [url(), 'Volver al inicio'],
    'tarea' => 'prestamos',
    'titulo' => 'Préstamos de herramientas',
    'texto' => 'Lo que piden los docentes: entrégalo el día del retiro y recíbelo cuando vuelva.',
]) ?>

<nav class="card card-body nav-pills prestamos__filtros" aria-label="Ver solicitudes">
    <?php foreach (SolicitudService::FILTROS as $clave => $nombre) : ?>
        <a class="nav-link<?= $clave === $filtro ? ' active' : '' ?>" href="<?= e(url('prestamos', ['ver' => $clave])) ?>"
            <?= $clave === $filtro ? 'aria-current="true"' : '' ?>>
            <?= e($nombre) ?> <span class="nav-count"><?= e($conteo[$clave]) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<?php if ($solicitudes === []) : ?>
    <div class="card card-body vacio-simple">
        <span class="vacio-simple__icono"><?= icon('swap') ?></span>
        <p class="vacio-simple__titulo"><?= e($vacios[$filtro]) ?></p>
        <p class="text-muted">Los docentes piden herramientas con «Solicitar herramientas» desde su inicio.</p>
    </div>
<?php else : ?>
    <ul class="solicitudes">
        <?php foreach ($solicitudes as $solicitud) : ?>
            <li><?= partial('Herramientas::partials/fila', [
                'solicitud' => $solicitud,
                'url' => url('prestamos/' . $solicitud['id']),
                'conDocente' => true,
            ]) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
