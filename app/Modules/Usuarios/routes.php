<?php

/**
 * Rutas del módulo Usuarios: las cuentas de docentes y administrativas (solo el encargado). Las rutas fijas
 * (/usuarios/agregar) van antes que /usuarios/{id}.
 *
 * @var App\Core\Router $router
 */

declare(strict_types=1);

use App\Modules\Usuarios\UsuarioController;

defined('APP_ROOT') || exit;

$router->get('/usuarios', [UsuarioController::class, 'index'], 'usuarios.administrar');
$router->get('/usuarios/agregar', [UsuarioController::class, 'agregar'], 'usuarios.administrar');
$router->post('/usuarios/agregar', [UsuarioController::class, 'guardar'], 'usuarios.administrar');
$router->get('/usuarios/enlace', [UsuarioController::class, 'mostrarEnlace'], 'usuarios.administrar');
$router->get('/usuarios/{id}/editar', [UsuarioController::class, 'edit'], 'usuarios.administrar');
$router->post('/usuarios/{id}', [UsuarioController::class, 'update'], 'usuarios.administrar');
$router->post('/usuarios/{id}/enlace', [UsuarioController::class, 'enlace'], 'usuarios.administrar');
$router->get('/usuarios/{id}/eliminar', [UsuarioController::class, 'confirmDelete'], 'usuarios.administrar');
$router->post('/usuarios/{id}/eliminar', [UsuarioController::class, 'delete'], 'usuarios.administrar');
