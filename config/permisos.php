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
    // Encargado de bodega (administrador): fiscaliza y administra todo el sistema; no opera el día a día (eso es del
    // operador), pero conserva todos los permisos como respaldo. Acceso total, incluidos insumos.administrar (editar,
    // eliminar y los códigos de los productos), insumos.crear, insumos.entrada (ingresar productos), insumos.salida
    // (retirar productos), insumos.ajuste (corregir stock), sectores.administrar, usuarios.administrar,
    // herramientas.administrar (el catálogo) y prestamos.administrar (entregar y recibir herramientas).
    'administrador' => ['*'],

    // Consultan sin registrar ni modificar (salvo el abastecimiento): el stock en tiempo real, el catálogo de
    // herramientas, las entradas y salidas y, en el inicio, los gráficos de la bodega. No ven los préstamos ni las
    // solicitudes de herramientas de los docentes: eso es del encargado y del operador.
    'administrativa' => [
        'panel.ver',
        'panel.bodega',     // avisos y gráficos de toda la bodega; sin él, el inicio muestra solo lo propio
        'insumos.ver',
        'insumos.abastecimiento', // «Ingresar abastecimiento»: lo que llega, por código SAP, código de barras o nombre
        'herramientas.ver',
        'historial.ver',    // «Entradas y salidas», con su descarga para Excel
        'difusion.ver',     // «Difusión»: catálogo de artículos y servicios
        'reportes.ver',
        'avisos.ver',
    ],

    // Operador de bodega: quien opera la bodega todos los días. Ingresa lo que llega (y crea el producto si no existe),
    // retira lo que sale, corrige el stock después de contar, entrega y recibe las herramientas que se prestan, imprime
    // etiquetas, ve Entradas y salidas, administra los sectores de bodega e imprime el estado de la bodega; en el inicio,
    // la campana le avisa de las solicitudes de herramientas de los docentes. No edita ni elimina productos, ni
    // administra herramientas o cuentas: eso es del encargado (administrador), que fiscaliza todo el sistema.
    'operador' => [
        'panel.ver',
        'panel.bodega',     // «Imprimir estado» de la bodega, en el inicio
        'sectores.administrar', // «Sectores de bodega»: los lugares de la bodega y sus etiquetas
        'insumos.ver',
        'insumos.entrada',  // «Ingresar productos»
        'insumos.salida',   // «Retirar productos»
        'insumos.ajuste',   // «Corregir stock»
        'insumos.crear',    // «Producto nuevo» (cuando llega algo que no está en el sistema) e «Imprimir etiqueta»
        'historial.ver',    // «Entradas y salidas» (solo ver), con sus descargas
        'herramientas.ver',
        'prestamos.ver',
        'prestamos.administrar', // entregar, recibir, extender y resolver préstamos
    ],

    // Módulo de herramientas: solicita con el formulario («Solicitar herramientas») y ve las suyas.
    'docente' => [
        'panel.ver',
        'herramientas.ver',
        'herramientas.solicitar',
        'avisos.ver',
    ],
];
