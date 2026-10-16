<?php

/**
 * Permisos por perfil. Cada ruta exige un permiso ('modulo.accion'); aquí se define qué perfil
 * lo tiene. Comodines: '*' = todo; 'insumos.*' = todo lo del módulo de insumos.
 *
 * Preliminar: se ajusta a medida que se construye cada módulo.
 */

declare(strict_types=1);

defined('APP_ROOT') || exit;

return [
    // Encargado de bodega: la cuenta que maneja todo el sistema. Acceso total, incluidos insumos.administrar
    // (crear, editar, desactivar), insumos.entrada (registrar producto), insumos.salida (retirar producto),
    // insumos.ajuste (actualizar cantidad), sectores.administrar, herramientas.administrar (el catálogo) y
    // prestamos.administrar (entregar y recibir herramientas).
    'administrador' => ['*'],

    // Consultan sin registrar ni modificar: los productos, el catálogo de herramientas, las entradas y salidas y, en el
    // inicio, los avisos y los gráficos de la bodega. No ven los préstamos de herramientas: eso es del encargado.
    'administrativa' => [
        'panel.ver',
        'panel.bodega',     // avisos y gráficos de toda la bodega; sin él, el inicio muestra solo lo propio
        'insumos.ver',
        'herramientas.ver',
        'historial.ver',    // «Entradas y salidas», con su descarga para Excel
        'reportes.ver',
        'avisos.ver',
    ],

    // Módulo de herramientas: solicita con el formulario («Solicitar herramientas») y ve las suyas.
    'docente' => [
        'panel.ver',
        'herramientas.ver',
        'herramientas.solicitar',
        'avisos.ver',
    ],
];
