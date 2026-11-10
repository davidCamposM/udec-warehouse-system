<?php

/**
 * Rutas del módulo Panel: página de inicio y su actualización automática.
 *
 * @var App\Core\Router $router
 */

declare(strict_types=1);

use App\Modules\Panel\PanelController;

defined('APP_ROOT') || exit;

$router->get('/', [PanelController::class, 'index'], 'panel.ver');
$router->get('/inventario', [PanelController::class, 'inventario'], 'insumos.ver');
$router->get('/panel/resumen',[PanelController::class, 'actualizar'], 'panel.ver');
$router->get('/panel/herramientas', [PanelController::class, 'herramientas'], 'herramientas.ver');
// La campana: el número del encargado y del operador (panel.js), su enlace (marca como vistas y lleva a los préstamos)
// y, para el docente, «ya las vi» al abrirla.
$router->get('/panel/campana', [PanelController::class, 'campana'], 'prestamos.administrar');
$router->get('/panel/campana/ver', [PanelController::class, 'verSolicitudes'], 'prestamos.administrar');
$router->post('/panel/notificaciones/vistas', [PanelController::class, 'notificacionesVistas'], 'herramientas.solicitar');
$router->get('/panel/estado-bodega', [PanelController::class, 'estadoBodega'], 'panel.bodega');
