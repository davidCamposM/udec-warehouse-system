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
$router->get('/panel/resumen', [PanelController::class, 'actualizar'], 'panel.ver');
$router->get('/panel/estado-bodega', [PanelController::class, 'estadoBodega'], 'panel.bodega');
