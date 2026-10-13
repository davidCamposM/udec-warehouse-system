<?php

/**
 * Rutas del módulo Personas: quienes retiran en el punto de retiro con su credencial o RUT.
 *
 * @var App\Core\Router $router
 */

declare(strict_types=1);

use App\Modules\Personas\PersonaController;

defined('APP_ROOT') || exit;

$router->get('/personas', [PersonaController::class, 'index'], 'personas.administrar');
$router->get('/personas/nueva', [PersonaController::class, 'create'], 'personas.administrar');
$router->post('/personas', [PersonaController::class, 'store'], 'personas.administrar');

$router->get('/personas/{id}/editar', [PersonaController::class, 'edit'], 'personas.administrar');
$router->post('/personas/{id}', [PersonaController::class, 'update'], 'personas.administrar');
$router->get('/personas/{id}/desactivar', [PersonaController::class, 'confirmDeactivate'], 'personas.administrar');
$router->post('/personas/{id}/desactivar', [PersonaController::class, 'deactivate'], 'personas.administrar');
$router->post('/personas/{id}/activar', [PersonaController::class, 'activate'], 'personas.administrar');
