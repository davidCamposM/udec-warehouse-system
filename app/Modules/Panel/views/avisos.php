<?php

use App\Core\Permission;

defined('APP_ROOT') || exit;

$verInsumos = Permission::can('insumos.ver');
// El encargado y las administrativas van a «Préstamos de herramientas»; el docente, a «Mis solicitudes».
$prestamos = static fn (string $filtro): ?string => match (true) {
    Permission::can('prestamos.ver') => url('prestamos', ['ver' => $filtro]),
    Permission::can('herramientas.solicitar') => url('herramientas/mis-solicitudes'),
    default => null,
};

// aviso => [ícono, tono, singular, plural, explicación, a dónde lleva «Ver» (null: su pantalla aún no existe)]
$definiciones = [
    'sin_unidades' => [
        'x-circle', 'danger', 'producto sin unidades', 'productos sin unidades',
        'No queda ninguna en bodega.',
        $verInsumos ? url('insumos', ['ver' => 'no-queda', 'de' => 'consumo']) : null,
    ],
    'por_reponer' => [
        'alert', 'warning', 'producto por reponer', 'productos por reponer',
        'Llegaron a su stock mínimo: hay que pedir más.',
        $verInsumos ? url('insumos', ['ver' => 'reponer', 'de' => 'consumo']) : null,
    ],
    'prestamos_atrasados' => [
        'clock', 'danger', 'préstamo atrasado', 'préstamos atrasados',
        'Herramientas que debieron volver a la bodega.',
        $prestamos('atrasadas'),
    ],
    'herramientas_por_resolver' => [
        'tool', 'danger', 'herramienta dañada o perdida', 'herramientas dañadas o perdidas',
        'Volvieron con daños o no volvieron: falta anotar si se repararon, repusieron o dieron de baja.',
        $prestamos('problemas'),
    ],
    'solicitudes_pendientes' => [
        'inbox', 'warning', 'solicitud por entregar', 'solicitudes por entregar',
        'Herramientas que piden los docentes.',
        $prestamos('pendientes'),
    ],
    'salidas_hoy' => [
        'arrow-out', 'info', 'retiro hoy', 'retiros hoy',
        'Lo que se retiró hoy de la bodega.',
        Permission::can('historial.ver') ? url('movimientos', ['periodo' => 'hoy', 'tipo' => 'salida']) : null,
    ],
];

// Los informativos (como los retiros de hoy) no cuentan como algo por atender: con solo ellos, todo está en orden.
$informativos = ['salidas_hoy'];
$avisos = array_filter($conteos, static fn (int $cantidad): bool => $cantidad > 0);
$urgentes = array_diff_key($avisos, array_flip($informativos));
?>
<?php // Una fila por aviso, dentro de la tarjeta «Avisos»: el número, qué es y qué significa. Si tiene a dónde ir, toda la fila es el enlace. ?>
<ul class="avisos-lista">
    <?php if ($urgentes === []) : ?>
        <li>
            <div class="aviso-fila aviso-fila--ok">
                <span class="aviso-fila__icono"><?= icon('check') ?></span>
                <span class="aviso-fila__texto">
                    <strong>Todo en orden</strong>
                    <span class="aviso-fila__detalle">No hay nada urgente por ahora.</span>
                </span>
            </div>
        </li>
    <?php endif; ?>
    <?php foreach ($avisos as $clave => $cantidad) : ?>
        <?php [$icono, $tono, $singular, $plural, $detalle, $destino] = $definiciones[$clave]; ?>
        <li>
            <<?= $destino !== null ? 'a href="' . e($destino) . '"' : 'div' ?> class="aviso-fila aviso-fila--<?= e($tono) ?>">
                <span class="aviso-fila__icono"><?= icon($icono) ?></span>
                <span class="aviso-fila__texto">
                    <strong><span class="aviso-fila__numero"><?= e($cantidad) ?></span> <?= e($cantidad === 1 ? $singular : $plural) ?></strong>
                    <span class="aviso-fila__detalle"><?= e($detalle) ?></span>
                </span>
                <?php if ($destino !== null) : ?>
                    <span class="aviso-fila__ver">Ver<?= icon('arrow-right') ?></span>
                <?php endif; ?>
            </<?= $destino !== null ? 'a' : 'div' ?>>
        </li>
    <?php endforeach; ?>
</ul>
