<?php

/**
 * Rutas del módulo Sectores: las partes de la bodega y sus códigos.
 *
 * @var App\Core\Router $router
 */

declare(strict_types=1);

use App\Modules\Sectores\SectorController;

defined('APP_ROOT') || exit;

$router->get('/sectores', [SectorController::class, 'index'], 'sectores.administrar');
$router->get('/sectores/nuevo', [SectorController::class, 'create'], 'sectores.administrar');
$router->post('/sectores', [SectorController::class, 'store'], 'sectores.administrar');

$router->get('/sectores/{id}/editar', [SectorController::class, 'edit'], 'sectores.administrar');
$router->post('/sectores/{id}', [SectorController::class, 'update'], 'sectores.administrar');
$router->get('/sectores/{id}/desactivar', [SectorController::class, 'confirmDeactivate'], 'sectores.administrar');
$router->post('/sectores/{id}/desactivar', [SectorController::class, 'deactivate'], 'sectores.administrar');
$router->post('/sectores/{id}/activar', [SectorController::class, 'activate'], 'sectores.administrar');
