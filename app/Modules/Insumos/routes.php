<?php

/**
 * Rutas del módulo Insumos. Las rutas fijas (/insumos/nuevo) van antes que /insumos/{id}.
 *
 * @var App\Core\Router $router
 */

declare(strict_types=1);

use App\Modules\Insumos\AjusteController;
use App\Modules\Insumos\GuardarController;
use App\Modules\Insumos\InsumoController;
use App\Modules\Insumos\NuevoProductoController;
use App\Modules\Insumos\RetirarController;

defined('APP_ROOT') || exit;

$router->get('/insumos', [InsumoController::class, 'index'], 'insumos.ver');

// La lista que aparece bajo una barra de búsqueda mientras se escribe.
$router->get('/insumos/sugerencias', [InsumoController::class, 'sugerencias'], 'insumos.ver');

// Agregar nuevo producto: 1) el producto, 2) el tipo, 3) cuántos llegaron, 4) dónde quedó.
$router->get('/insumos/nuevo', [NuevoProductoController::class, 'empezar'], 'insumos.administrar');
$router->get('/insumos/nuevo/producto', [NuevoProductoController::class, 'producto'], 'insumos.administrar');
$router->post('/insumos/nuevo/producto', [NuevoProductoController::class, 'leerProducto'], 'insumos.administrar');
$router->get('/insumos/nuevo/tipo', [NuevoProductoController::class, 'tipo'], 'insumos.administrar');
$router->post('/insumos/nuevo/tipo', [NuevoProductoController::class, 'leerTipo'], 'insumos.administrar');
$router->get('/insumos/nuevo/cantidad', [NuevoProductoController::class, 'cantidad'], 'insumos.administrar');
$router->post('/insumos/nuevo/cantidad', [NuevoProductoController::class, 'leerCantidad'], 'insumos.administrar');
$router->get('/insumos/nuevo/sector', [NuevoProductoController::class, 'sector'], 'insumos.administrar');
$router->post('/insumos/nuevo/sector', [NuevoProductoController::class, 'agregar'], 'insumos.administrar');

$router->get('/insumos/{id}', [InsumoController::class, 'show'], 'insumos.ver');
$router->get('/insumos/{id}/editar', [InsumoController::class, 'edit'], 'insumos.administrar');
$router->post('/insumos/{id}', [InsumoController::class, 'update'], 'insumos.administrar');
$router->get('/insumos/{id}/eliminar', [InsumoController::class, 'confirmDelete'], 'insumos.administrar');
$router->post('/insumos/{id}/eliminar', [InsumoController::class, 'delete'], 'insumos.administrar');

$router->get('/insumos/{id}/codigos/nuevo', [InsumoController::class, 'createCode'], 'insumos.administrar');
$router->post('/insumos/{id}/codigos', [InsumoController::class, 'storeCode'], 'insumos.administrar');
$router->get('/insumos/{id}/codigos/{codigo}/quitar', [InsumoController::class, 'confirmRemoveCode'], 'insumos.administrar');
$router->post('/insumos/{id}/codigos/{codigo}/quitar', [InsumoController::class, 'removeCode'], 'insumos.administrar');

// Registrar producto: 1) el producto, 2) cuántos llegaron, 3) el sector donde quedó.
$router->get('/guardar', [GuardarController::class, 'producto'], 'insumos.entrada');
$router->post('/guardar/producto', [GuardarController::class, 'leerProducto'], 'insumos.entrada');
$router->get('/guardar/desconocido', [GuardarController::class, 'desconocido'], 'insumos.entrada');
$router->get('/guardar/envase', [GuardarController::class, 'envase'], 'insumos.administrar');
$router->post('/guardar/envase', [GuardarController::class, 'guardarEnvase'], 'insumos.administrar');
$router->get('/guardar/cantidad', [GuardarController::class, 'cantidad'], 'insumos.entrada');
$router->post('/guardar/cantidad', [GuardarController::class, 'leerCantidad'], 'insumos.entrada');
$router->get('/guardar/sector', [GuardarController::class, 'sector'], 'insumos.entrada');
$router->post('/guardar/sector', [GuardarController::class, 'guardarSector'], 'insumos.entrada');

// Actualizar cantidad: 1) el producto, 2) cuántos hay realmente y por qué (y se actualiza).
$router->get('/ajustar', [AjusteController::class, 'producto'], 'insumos.ajuste');
$router->post('/ajustar/producto', [AjusteController::class, 'leerProducto'], 'insumos.ajuste');
$router->get('/ajustar/contar', [AjusteController::class, 'contar'], 'insumos.ajuste');
$router->post('/ajustar/contar', [AjusteController::class, 'leerConteo'], 'insumos.ajuste');

// Retirar producto: 1) el producto, 2) cuántos se retiran (y se descuenta).
$router->get('/retirar', [RetirarController::class, 'producto'], 'insumos.salida');
$router->post('/retirar/producto', [RetirarController::class, 'leerProducto'], 'insumos.salida');
$router->get('/retirar/cantidad', [RetirarController::class, 'cantidad'], 'insumos.salida');
$router->post('/retirar/cantidad', [RetirarController::class, 'retirar'], 'insumos.salida');
