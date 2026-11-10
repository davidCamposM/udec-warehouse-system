<?php

/**
 * Ruta del catálogo de Difusión: artículos y servicios con cantidad aún no verificada.
 *
 * @var App\Core\Router $router
 */

declare(strict_types=1);

use App\Modules\Difusion\DifusionController;

defined('APP_ROOT') || exit;

$router->get('/difusion', [DifusionController::class, 'index'], 'difusion.ver');
