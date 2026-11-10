<?php

use App\Support\Fecha;

defined('APP_ROOT') || exit;

// Las notificaciones del docente, dentro del panel de su campana: una fila por solicitud (lo que pidió, en qué está y
// la fecha que importa), en el color de su estado; toda la fila lleva a la solicitud. Las nuevas (lo que pasó desde la
// última vez que abrió la campana) van resaltadas con un punto. data-cuenta: cuántas nuevas hay (el número de la
// campana; panel.js lo pone al día y lo borra al abrirla).
// Recibe $notificaciones (PanelRepository::notificacionesDocente).

// estado => [tono, ícono, título]
$tipos = [
    'atrasada' => ['grave', 'clock', 'Devolución atrasada'],
    'pendiente' => ['atencion', 'inbox', 'Por entregar'],
    'prestada' => ['fuera', 'swap', 'Prestada'],
    'rechazada' => ['grave', 'x-circle', 'Rechazada'],
    'devuelta' => ['bien', 'check', 'Recibida en bodega'],
];
$cuenta = count(array_filter($notificaciones, static fn (array $n): bool => (int) $n['nueva'] === 1));
?>
<ul class="notificaciones__lista" data-cuenta="<?= e($cuenta) ?>">
    <?php if ($notificaciones === []) : ?>
        <li class="notificaciones__vacio"><?= icon('check') ?>No tienes notificaciones por ahora.</li>
    <?php endif; ?>
    <?php foreach ($notificaciones as $n) : ?>
        <?php
        $clave = (int) $n['atrasada'] === 1 ? 'atrasada' : $n['estado'];
        [$tono, $icono, $titulo] = $tipos[$clave] ?? ['fuera', 'info', $n['estado']];
        $cuando = match ($clave) {
            'atrasada' => 'Debías devolverla el ' . Fecha::mostrar($n['fecha_devolucion']),
            'pendiente' => 'Retiro el ' . Fecha::mostrar($n['fecha_retiro']),
            'prestada' => 'Devuélvela el ' . Fecha::mostrar($n['fecha_devolucion']),
            'rechazada' => $n['motivo_rechazo'] !== null && $n['motivo_rechazo'] !== '' ? 'Motivo: ' . $n['motivo_rechazo'] : 'Rechazada el ' . Fecha::mostrar(substr((string) $n['cerrada_en'], 0, 10)),
            'devuelta' => 'Recibida el ' . Fecha::mostrar(substr((string) $n['cerrada_en'], 0, 10)),
            default => '',
        };
        ?>
        <li>
            <a class="notificacion notificacion--<?= e($tono) ?><?= (int) $n['nueva'] === 1 ? ' notificacion--nueva' : '' ?>" href="<?= e(url('herramientas/solicitudes/' . $n['id'])) ?>">
                <span class="notificacion__icono" aria-hidden="true"><?= icon($icono) ?></span>
                <span class="notificacion__texto">
                    <strong><?= e($titulo) ?> <span class="notificacion__numero">· N° <?= e($n['id']) ?></span><?php if ((int) $n['nueva'] === 1) : ?><span class="visually-hidden"> (nueva)</span><?php endif; ?></strong>
                    <?php if ($n['herramientas'] !== null) : ?>
                        <span class="notificacion__detalle"><?= e($n['herramientas']) ?></span>
                    <?php endif; ?>
                    <span class="notificacion__cuando"><?= e($cuando) ?></span>
                </span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
