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
    // Encargado de bodega: acceso total.
    'administrador' => ['*'],

    // Ven todo, pero no registran ni modifican (por ahora).
    'administrativa' => [
        'panel.ver',
        'insumos.ver',
        'herramientas.ver',
        'historial.ver',
        'reportes.ver',
        'avisos.ver',
    ],

    // Módulo de herramientas: catálogo, sus solicitudes y sus préstamos.
    'docente' => [
        'panel.ver',
        'herramientas.ver',
        'herramientas.solicitar',
        'avisos.ver',
    ],
];
