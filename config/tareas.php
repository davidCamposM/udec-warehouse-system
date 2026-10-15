<?php

/**
 * Tareas del sistema: los botones del inicio, en el orden en que aparecen (las del día a día y, aparte, las de administración).
 * Cada tarea exige un permiso de config/permisos.php; quien no lo tiene, no la ve.
 *
 * - 'texto': lo que se hace, con un verbo («Registrar producto»).
 * - 'descripcion': una línea corta que explica para qué sirve; va bajo el nombre en el botón del inicio.
 * - 'color': azul, verde, naranja (terracota), turquesa (petróleo), grafito, ocre o pizarra (salvia),
 *   de los tokens --tarea-* de tokens.css. La pantalla de la tarea usa el mismo color en su encabezado, así la
 *   persona reconoce dónde está.
 * - 'ruta' => null marca una tarea que aún no se construye: no se muestra. Al terminarla se le pone su ruta
 *   y aparece sola en el inicio.
 * - 'boton' => false: no va como botón. «Buscar producto» es la barra de búsqueda de arriba del inicio.
 * - 'perfiles' (opcional): solo esos perfiles la ven, aunque otro tenga el permiso (el encargado tiene todos los
 *   permisos, pero no solicita herramientas: las entrega).
 *
 * Preliminar: se ajusta a medida que se construye cada módulo.
 */

declare(strict_types=1);

defined('APP_ROOT') || exit;

return [
    [
        'clave' => 'buscar-producto',
        'texto' => 'Buscar producto',
        'descripcion' => 'Ver cuánto hay y dónde está',
        'ruta' => '/insumos',
        'icono' => 'search',
        'color' => 'verde',
        'permiso' => 'insumos.ver',
        'boton' => false,
    ],
    [
        'clave' => 'guardar-en-bodega',
        'texto' => 'Registrar producto',
        'descripcion' => 'Anotar lo que llegó y dónde quedó',
        'ruta' => '/guardar',
        'icono' => 'box',
        'color' => 'verde',
        'permiso' => 'insumos.entrada',
    ],
    [
        'clave' => 'retirar-producto',
        'texto' => 'Retirar producto',
        'descripcion' => 'Descontar lo que sale de la bodega',
        'ruta' => '/retirar',
        'icono' => 'arrow-out',
        'color' => 'ocre',
        'permiso' => 'insumos.salida',
    ],
    [
        'clave' => 'ajustar-cantidad',
        'texto' => 'Actualizar cantidad',
        'descripcion' => 'Dejar lo que realmente hay, después de contar',
        'ruta' => '/ajustar',
        'icono' => 'refresh',
        'color' => 'naranja',
        'permiso' => 'insumos.ajuste',
    ],
    [
        'clave' => 'agregar-producto',
        'texto' => 'Agregar nuevo producto',
        'descripcion' => 'Un producto que aún no está en el sistema',
        'ruta' => '/insumos/nuevo',
        'icono' => 'plus',
        'color' => 'turquesa',
        'permiso' => 'insumos.administrar',
    ],
    [
        'clave' => 'contar-bodega',
        'texto' => 'Contar la bodega',
        'descripcion' => 'Revisar sector por sector lo que hay',
        'ruta' => null,
        'icono' => 'layers',
        'color' => 'pizarra',
        'permiso' => 'inventario.contar',
    ],
    [
        'clave' => 'ver-herramientas',
        'texto' => 'Catálogo de herramientas',
        'descripcion' => 'Qué herramientas hay y cuántas están prestadas',
        'ruta' => '/herramientas',
        'icono' => 'tool',
        'color' => 'azul',
        'permiso' => 'herramientas.ver',
    ],
    [
        'clave' => 'solicitar-herramientas',
        'texto' => 'Solicitar herramientas',
        'descripcion' => 'Pedir herramientas para una clase o salida a terreno',
        'ruta' => '/herramientas/solicitar',
        'icono' => 'tool',
        'color' => 'verde',
        'permiso' => 'herramientas.solicitar',
        'perfiles' => ['docente'],
    ],
    [
        'clave' => 'mis-solicitudes',
        'texto' => 'Mis solicitudes',
        'descripcion' => 'Lo que pediste y en qué va cada solicitud',
        'ruta' => '/herramientas/mis-solicitudes',
        'icono' => 'clipboard',
        'color' => 'azul',
        'permiso' => 'herramientas.solicitar',
        'perfiles' => ['docente'],
    ],
    [
        'clave' => 'prestamos',
        'texto' => 'Préstamos de herramientas',
        'descripcion' => 'Entregar lo que piden los docentes y recibir lo que vuelve',
        'ruta' => '/prestamos',
        'icono' => 'swap',
        'color' => 'pizarra',
        'permiso' => 'prestamos.ver',
    ],
    [
        'clave' => 'historial',
        'texto' => 'Entradas y salidas',
        'descripcion' => 'Lo que entró, salió o se corrigió',
        'ruta' => '/movimientos',
        'icono' => 'history',
        'color' => 'azul',
        'permiso' => 'historial.ver',
    ],
    [
        'clave' => 'reportes',
        'texto' => 'Reportes',
        'descripcion' => 'Resúmenes para descargar',
        'ruta' => null,
        'icono' => 'chart',
        'color' => 'azul',
        'permiso' => 'reportes.ver',
    ],
    [
        'clave' => 'sectores',
        'texto' => 'Sectores de bodega',
        'descripcion' => 'Lugares de la bodega y sus códigos QR',
        'ruta' => '/sectores',
        'icono' => 'map-pin',
        'color' => 'grafito',
        'permiso' => 'sectores.administrar',
    ],
    [
        'clave' => 'usuarios',
        'texto' => 'Usuarios',
        'descripcion' => 'Cuentas para entrar al sistema',
        'ruta' => null,
        'icono' => 'users',
        'color' => 'turquesa',
        'permiso' => 'usuarios.administrar',
    ],
    [
        'clave' => 'etiquetas',
        'texto' => 'Imprimir etiquetas',
        'descripcion' => 'Códigos para pegar en estantes y productos',
        'ruta' => null,
        'icono' => 'tag',
        'color' => 'azul',
        'permiso' => 'codigos.etiquetas',
    ],
    [
        'clave' => 'configuracion',
        'texto' => 'Configuración',
        'descripcion' => 'Ajustes generales del sistema',
        'ruta' => null,
        'icono' => 'settings',
        'color' => 'pizarra',
        'permiso' => 'configuracion.administrar',
    ],
];
