<?php

/**
 * Rutas del módulo Auth: inicio y cierre de sesión, crear o recuperar la contraseña con un enlace al correo, y el
 * aviso de que la persona sigue usando el sistema.
 *
 * @var App\Core\Router $router
 */

declare(strict_types=1);

use App\Core\Permission;
use App\Modules\Auth\ClaveController;
use App\Modules\Auth\LoginController;

defined('APP_ROOT') || exit;

$router->get('/login', [LoginController::class, 'show'], Permission::PUBLICO);
$router->post('/login', [LoginController::class, 'login'], Permission::PUBLICO);
// Crear la contraseña la primera vez, o recuperarla: se pide el enlace con el correo y se usa el que llega.
$router->get('/clave', [ClaveController::class, 'pedir'], Permission::PUBLICO);
$router->post('/clave', [ClaveController::class, 'enviar'], Permission::PUBLICO);
$router->get('/clave/{codigo}', [ClaveController::class, 'formulario'], Permission::PUBLICO);
$router->post('/clave/{codigo}', [ClaveController::class, 'guardar'], Permission::PUBLICO);
$router->post('/logout', [LoginController::class, 'logout'], Permission::AUTENTICADO);
$router->post('/sesion/seguir', [LoginController::class, 'seguir'], Permission::AUTENTICADO);
$router->get('/sesion/estado', [LoginController::class, 'estado'], Permission::AUTENTICADO);
