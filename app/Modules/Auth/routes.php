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
$router->post('/login', [LoginController::class, 'login'], Permission::PUBLICO);
$router->post('/logout', [LoginController::class, 'logout'], Permission::AUTENTICADO);
