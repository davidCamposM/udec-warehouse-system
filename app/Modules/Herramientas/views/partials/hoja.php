<?php

use App\Modules\Herramientas\SolicitudService;
use App\Support\Fecha;

defined('APP_ROOT') || exit;

/**
 * Una solicitud dibujada como el formulario en papel «Solicitud de instrumentos y herramientas»: los datos arriba y el
 * detalle (descripción, unidades y estado en la recepción) abajo. Recibe $solicitud, $items y $atrasada. Muestra los
 * días de atraso, si se extendió el plazo y, en cada línea que volvió con daños o no volvió, qué pasó y si ya se
 * resolvió.
 */
[$estadoTexto, $estadoClase] = SolicitudService::ESTADOS[$solicitud['estado']];
$diasAtraso = SolicitudService::diasDeAtraso($solicitud);

/** Lo que va en «Estado en la recepción» de cada línea: cómo volvió y, si hubo daños o faltas, qué pasó. */
$recepcion = static function (array $item) use ($solicitud): string {
    if ($solicitud['estado'] !== 'devuelta') {
        return '';
    }
    $volvieron = (int) $item['cantidad_devuelta'];
    $estado = match (true) {
        $volvieron === 0 => 'No volvió',
        $item['estado_recepcion'] === 'danada' => 'Con daños',
        default => 'Bien',
    };
    if ($volvieron > 0 && $volvieron < (int) $item['cantidad']) {
        $estado .= " · volvieron {$volvieron} de {$item['cantidad']}";
    }

    return $item['detalle_recepcion'] !== null ? "{$estado}: {$item['detalle_recepcion']}" : $estado;
};
?>
<article class="hoja">
    <?= partial('Herramientas::partials/membrete') ?>

    <p class="hoja__estado">
        <span class="status status--<?= e($atrasada ? 'out' : $estadoClase) ?>">
            <?= e($atrasada ? 'Atrasada ' . ($diasAtraso === 1 ? '1 día' : "{$diasAtraso} días") : $estadoTexto) ?>
        </span>
        <span class="text-muted">N° <?= e($solicitud['id']) ?> · enviada el <?= e(Fecha::mostrar($solicitud['creado_en'])) ?></span>
    </p>

    <dl class="hoja__datos">
        <div class="hoja__dato">
            <dt>Departamento</dt>
            <dd><?= e($solicitud['departamento']) ?></dd>
        </div>
        <div class="hoja__dato">
            <dt>Nombre del funcionario</dt>
            <dd><?= e($solicitud['docente']) ?></dd>
        </div>
        <div class="hoja__dato hoja__dato--ancho">
            <dt>Tipo de actividad</dt>
            <dd><?= e($solicitud['tipo_actividad']) ?></dd>
        </div>
        <div class="hoja__dato hoja__dato--ancho">
            <dt>Autoriza el retiro a</dt>
            <dd><?= e($solicitud['autoriza_retiro'] ?? 'El mismo funcionario') ?></dd>
        </div>
        <div class="hoja__dato">
            <dt>Fecha de retiro</dt>
            <dd><?= e(Fecha::mostrar($solicitud['fecha_retiro'])) ?></dd>
        </div>
        <div class="hoja__dato">
            <dt>Fecha de recepción</dt>
            <dd<?= $atrasada ? ' class="hoja__atrasada"' : '' ?>>
                <?= e(Fecha::mostrar($solicitud['fecha_devolucion'])) ?>
                <?php if ($solicitud['plazo_original'] !== null) : ?>
                    <span class="hoja__extendido">Plazo extendido · antes era el <?= e(Fecha::mostrar($solicitud['plazo_original'])) ?></span>
                <?php endif; ?>
            </dd>
        </div>
    </dl>

    <h3 class="hoja__subtitulo">Detalles</h3>
    <div class="table-responsive">
        <table class="hoja__tabla">
            <thead>
                <tr>
                    <th scope="col">Descripción</th>
                    <th scope="col" class="text-end">Unidades</th>
                    <th scope="col">Estado en la recepción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item) : ?>
                    <tr>
                        <td><?= e($item['nombre']) ?></td>
                        <td class="text-end hoja__unidades"><?= e($item['cantidad']) ?></td>
                        <td class="<?= (int) $item['problema'] === 1 ? 'hoja__problema' : '' ?>">
                            <?= e($recepcion($item)) ?>
                            <?php if ($item['resolucion'] !== null) : ?>
                                <span class="hoja__resuelto">
                                    <?= icon('check') ?><?= e(SolicitudService::RESOLUCIONES[$item['resolucion']]) ?>
                                    el <?= e(Fecha::mostrar($item['resuelta_en'])) ?><?= $item['resuelta_por_nombre'] !== null ? ' · ' . e($item['resuelta_por_nombre']) : '' ?>
                                </span>
                            <?php elseif ((int) $item['problema'] === 1) : ?>
                                <span class="hoja__pendiente-resolver">Por resolver</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($solicitud['estado'] === 'rechazada' && $solicitud['motivo_rechazo'] !== null) : ?>
        <p class="hoja__nota hoja__nota--problema"><strong>Motivo del rechazo:</strong> <?= e($solicitud['motivo_rechazo']) ?></p>
    <?php endif; ?>
    <?php if ($solicitud['entregada_en'] !== null) : ?>
        <p class="hoja__pie">Entregada el <?= e(Fecha::mostrar($solicitud['entregada_en'])) ?>.
            <?php if ($solicitud['recibida_en'] !== null) : ?>Recibida el <?= e(Fecha::mostrar($solicitud['recibida_en'])) ?>.<?php endif; ?>
        </p>
    <?php endif; ?>
</article>
