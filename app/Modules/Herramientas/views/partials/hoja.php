<?php

use App\Modules\Herramientas\SolicitudService;
use App\Support\Fecha;

defined('APP_ROOT') || exit;

/**
 * Una solicitud dibujada como el formulario en papel «Solicitud de instrumentos y herramientas»: los datos arriba y el
 * detalle (descripción, unidades y estado en la recepción) abajo. Recibe $solicitud, $items y $atrasada.
 */
[$estadoTexto, $estadoClase] = SolicitudService::ESTADOS[$solicitud['estado']];

/** Lo que va en «Estado en la recepción» de cada línea. */
$recepcion = static function (array $item) use ($solicitud): string {
    if ($solicitud['estado'] !== 'devuelta') {
        return '';
    }
    $volvieron = (int) $item['cantidad_devuelta'];
    if ($volvieron === 0) {
        return 'No volvió';
    }
    $estado = $item['estado_recepcion'] === 'danada' ? 'Con daños' : 'Bien';

    return $volvieron === (int) $item['cantidad'] ? $estado : "{$estado} · volvieron {$volvieron} de {$item['cantidad']}";
};
?>
<article class="hoja">
    <?= partial('Herramientas::partials/membrete') ?>

    <p class="hoja__estado">
        <span class="status status--<?= e($atrasada ? 'out' : $estadoClase) ?>"><?= e($atrasada ? 'Atrasada' : $estadoTexto) ?></span>
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
            <dd<?= $atrasada ? ' class="hoja__atrasada"' : '' ?>><?= e(Fecha::mostrar($solicitud['fecha_devolucion'])) ?></dd>
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
                        <td class="<?= $item['estado_recepcion'] === 'danada' || ($solicitud['estado'] === 'devuelta' && (int) $item['cantidad_devuelta'] < (int) $item['cantidad']) ? 'hoja__problema' : '' ?>">
                            <?= e($recepcion($item)) ?>
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
    <?php if ($solicitud['observacion_recepcion'] !== null) : ?>
        <p class="hoja__nota"><strong>Nota de la bodega:</strong> <?= e($solicitud['observacion_recepcion']) ?></p>
    <?php endif; ?>
</article>
