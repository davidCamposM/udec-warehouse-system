<?php

/**
 * Rutas del módulo Difusión: los pedidos a proveedores fuera de abasto (las administrativas). Pendiente: por ahora
 * solo la pantalla que avisa que está en preparación.
 *
 * @var App\Core\Router $router
 */

declare(strict_types=1);

use App\Modules\Difusion\DifusionController;

defined('APP_ROOT') || exit;

$router->get('/difusion', [DifusionController::class, 'index'], 'difusion.ver');
