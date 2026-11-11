<?php

use App\Support\Fecha;

defined('APP_ROOT') || exit;

// Recién enviada: el aviso y la solicitud tal como quedó, como el formulario en papel.
?>
<?= partial('encabezado', [
    'volver' => [url(), 'Volver al inicio'],
    'tarea' => 'solicitar-herramientas',
    'titulo' => 'Solicitar herramientas',
]) ?>

<div class="pedir pedir--pagina card card-body">
<?= partial('listo', [
    'titulo' => 'Listo, tu solicitud quedó enviada',
    'detalle' => 'La bodega la verá y tendrá las herramientas listas para el <strong>' . e(Fecha::mostrar($solicitud['fecha_retiro']))
        . '</strong>. Puedes ver en qué va en «Mis solicitudes».',
]) ?>

<?= partial('Herramientas::partials/hoja', ['solicitud' => $solicitud, 'items' => $items, 'atrasada' => $atrasada]) ?>

<div class="pedir__acciones">
    <a class="btn btn-outline-secondary" href="<?= e(url()) ?>"><?= icon('home') ?>Finalizar</a>
    <a class="btn btn-primary" href="<?= e(url('herramientas/mis-solicitudes')) ?>" autofocus><?= icon('clipboard') ?>Ver mis solicitudes</a>
</div>
</div>
