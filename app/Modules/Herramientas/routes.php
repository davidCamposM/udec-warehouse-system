<?php

/**
 * Rutas del módulo Herramientas: el catálogo (lo ven todos; lo administra el encargado), lo del docente (solicitar y
 * ver sus solicitudes) y lo del encargado (entregar y recibir los préstamos). Las administrativas pueden mirar los
 * préstamos (prestamos.ver). Las rutas fijas (/herramientas/solicitar) van antes que /herramientas/{id}.
 *
 * @var App\Core\Router $router
 */

declare(strict_types=1);

use App\Modules\Herramientas\HerramientaController;
use App\Modules\Herramientas\PrestamoController;
use App\Modules\Herramientas\SolicitudController;

defined('APP_ROOT') || exit;

// El docente
$router->get('/herramientas/solicitar', [SolicitudController::class, 'formulario'], 'herramientas.solicitar');
$router->post('/herramientas/solicitar', [SolicitudController::class, 'enviar'], 'herramientas.solicitar');
$router->get('/herramientas/mis-solicitudes', [SolicitudController::class, 'mias'], 'herramientas.solicitar');
$router->get('/herramientas/solicitudes/{id}', [SolicitudController::class, 'ver'], 'herramientas.solicitar');
$router->post('/herramientas/solicitudes/{id}/cancelar', [SolicitudController::class, 'cancelar'], 'herramientas.solicitar');

// El encargado
$router->get('/prestamos', [PrestamoController::class, 'index'], 'prestamos.ver');
$router->get('/prestamos/{id}', [PrestamoController::class, 'show'], 'prestamos.ver');
$router->post('/prestamos/{id}/entregar', [PrestamoController::class, 'entregar'], 'prestamos.administrar');
$router->post('/prestamos/{id}/rechazar', [PrestamoController::class, 'rechazar'], 'prestamos.administrar');
$router->get('/prestamos/{id}/recibir', [PrestamoController::class, 'recepcion'], 'prestamos.administrar');
$router->post('/prestamos/{id}/recibir', [PrestamoController::class, 'recibir'], 'prestamos.administrar');
$router->post('/prestamos/{id}/extender', [PrestamoController::class, 'extender'], 'prestamos.administrar');
$router->post('/prestamos/{id}/items/{item}/resolver', [PrestamoController::class, 'resolver'], 'prestamos.administrar');

// El catálogo
$router->get('/herramientas', [HerramientaController::class, 'index'], 'herramientas.ver');
$router->get('/herramientas/nueva', [HerramientaController::class, 'create'], 'herramientas.administrar');
$router->post('/herramientas', [HerramientaController::class, 'store'], 'herramientas.administrar');
$router->get('/herramientas/{id}/editar', [HerramientaController::class, 'edit'], 'herramientas.administrar');
$router->post('/herramientas/{id}', [HerramientaController::class, 'update'], 'herramientas.administrar');
$router->get('/herramientas/{id}/desactivar', [HerramientaController::class, 'confirmDeactivate'], 'herramientas.administrar');
$router->post('/herramientas/{id}/desactivar', [HerramientaController::class, 'deactivate'], 'herramientas.administrar');
$router->post('/herramientas/{id}/activar', [HerramientaController::class, 'activate'], 'herramientas.administrar');
