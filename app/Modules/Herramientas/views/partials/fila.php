<?php

use App\Modules\Herramientas\SolicitudService;
use App\Support\Fecha;

defined('APP_ROOT') || exit;

/**
 * Una solicitud en una lista: toda la tarjeta abre su hoja ($url). Para qué es, de quién (si $conDocente), las fechas,
 * cuántas unidades y su estado (escrito, no solo el color).
 */
$atrasada = SolicitudService::atrasada($solicitud);
[$estadoTexto, $estadoClase] = SolicitudService::ESTADOS[$solicitud['estado']];
$unidades = (int) $solicitud['unidades'];
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
    <span class="status status--<?= e($atrasada ? 'out' : $estadoClase) ?>"><?= e($atrasada ? 'Atrasada' : $estadoTexto) ?></span>
    <span class="solicitud-fila__flecha" aria-hidden="true"><?= icon('arrow-right') ?></span>
</a>
