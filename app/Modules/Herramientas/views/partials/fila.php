<?php

use App\Modules\Herramientas\SolicitudService;
use App\Support\Fecha;

defined('APP_ROOT') || exit;

/**
 * Una solicitud en una lista: toda la tarjeta abre su hoja ($url). Para qué es, de quién (si $conDocente), las fechas,
 * cuántas unidades y su estado (escrito, no solo el color): los días de atraso o lo que falta resolver, si hay.
 */
$diasAtraso = SolicitudService::diasDeAtraso($solicitud);
$problemas = (int) $solicitud['problemas'];
[$estadoTexto, $estadoClase] = SolicitudService::ESTADOS[$solicitud['estado']];
$unidades = (int) $solicitud['unidades'];

// Lo más urgente manda en la etiqueta: el atraso, lo que falta resolver y, si no, el estado.
[$etiqueta, $clase] = match (true) {
    $diasAtraso > 0 => ['Atrasada ' . ($diasAtraso === 1 ? '1 día' : "{$diasAtraso} días"), 'out'],
    $problemas > 0 => [$problemas === 1 ? '1 por resolver' : "{$problemas} por resolver", 'out'],
    default => [$estadoTexto, $estadoClase],
};
?>
<a class="solicitud-fila" href="<?= e($url) ?>">
    <span class="solicitud-fila__texto">
        <strong class="solicitud-fila__titulo"><?= e($solicitud['tipo_actividad']) ?></strong>
        <span class="solicitud-fila__datos">
            <?php if (!empty($conDocente)) : ?><?= e($solicitud['docente']) ?> · <?php endif; ?>
            Retiro <?= e(Fecha::mostrar($solicitud['fecha_retiro'])) ?> · Recepción <?= e(Fecha::mostrar($solicitud['fecha_devolucion'])) ?>
            · <?= e($unidades === 1 ? '1 unidad' : "{$unidades} unidades") ?>
        </span>
    </span>
    <span class="status status--<?= e($clase) ?>"><?= e($etiqueta) ?></span>
    <span class="solicitud-fila__flecha" aria-hidden="true"><?= icon('arrow-right') ?></span>
</a>
