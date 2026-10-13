<?php

/**
 * Rutas del punto de retiro. Las de /retiro son la pantalla de autoservicio: no usan cuenta de usuario (son públicas),
 * pero solo funcionan en un equipo habilitado por el encargado (EstacionService). Las de /punto-de-retiro son las del
 * encargado.
 *
 * @var App\Core\Router $router
 */

declare(strict_types=1);

use App\Core\Permission;
use App\Modules\Retiro\PuntoController;
use App\Modules\Retiro\RetiroController;

defined('APP_ROOT') || exit;

$router->get('/retiro', [RetiroController::class, 'index'], Permission::PUBLICO);
$router->post('/retiro/identificar', [RetiroController::class, 'identificar'], Permission::PUBLICO);
$router->post('/retiro/modo', [RetiroController::class, 'modo'], Permission::PUBLICO);
$router->post('/retiro/leer', [RetiroController::class, 'leer'], Permission::PUBLICO);
$router->post('/retiro/linea', [RetiroController::class, 'cambiar'], Permission::PUBLICO);
$router->post('/retiro/confirmar', [RetiroController::class, 'confirmar'], Permission::PUBLICO);
// Entrada de un auxiliar: tras escanear lo que llegó, dónde quedó.
$router->get('/retiro/sector', [RetiroController::class, 'sector'], Permission::PUBLICO);
$router->post('/retiro/entrada', [RetiroController::class, 'confirmarEntrada'], Permission::PUBLICO);
$router->get('/retiro/listo', [RetiroController::class, 'listo'], Permission::PUBLICO);
$router->post('/retiro/terminar', [RetiroController::class, 'terminar'], Permission::PUBLICO);
$router->post('/retiro/sigo', [RetiroController::class, 'sigo'], Permission::PUBLICO);

$router->get('/punto-de-retiro', [PuntoController::class, 'index'], 'estacion.administrar');
$router->post('/punto-de-retiro/habilitar', [PuntoController::class, 'habilitar'], 'estacion.administrar');
$router->get('/punto-de-retiro/{id}/deshabilitar', [PuntoController::class, 'confirmDisable'], 'estacion.administrar');
$router->post('/punto-de-retiro/{id}/deshabilitar', [PuntoController::class, 'disable'], 'estacion.administrar');
