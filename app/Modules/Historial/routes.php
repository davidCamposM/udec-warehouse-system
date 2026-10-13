<?php

/**
 * Rutas del módulo Historial: por ahora, «Entradas y salidas» de insumos.
 *
 * @var App\Core\Router $router
 */

declare(strict_types=1);

use App\Modules\Historial\MovimientoController;

defined('APP_ROOT') || exit;

$router->get('/movimientos', [MovimientoController::class, 'index'], 'historial.ver');
$router->get('/movimientos/csv', [MovimientoController::class, 'csv'], 'historial.ver');
