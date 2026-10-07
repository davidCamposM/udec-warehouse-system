<?php

/**
 * Rutas del módulo Auth: inicio y cierre de sesión.
 *
 * @var App\Core\Router $router
 */

declare(strict_types=1);

use App\Core\Permission;
use App\Modules\Auth\LoginController;

defined('APP_ROOT') || exit;

$router->get('/login', [LoginController::class, 'show'], Permission::PUBLICO);
