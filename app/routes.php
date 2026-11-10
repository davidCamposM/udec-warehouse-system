<?php

/**
 * Rutas generales, que no pertenecen a un módulo. Cada módulo declara las suyas en
 * app/Modules/<Modulo>/routes.php.
 *
 * @var App\Core\Router $router
 */

declare(strict_types=1);

use App\Core\Database;
use App\Core\Logger;
use App\Core\Permission;
use App\Core\Response;

defined('APP_ROOT') || exit;

// Estado del sistema: tras una instalación, confirma que PHP y la base de datos responden.
$router->get('/estado', static function (): Response {
    try {
        Database::fetchValue('SELECT 1');
        $bd = 'ok';
    } catch (Throwable $e) {
        Logger::exception($e);
        $bd = 'error';
    }

    return $bd === 'ok'
        ? Response::ok(['php' => 'ok', 'bd' => 'ok'])
        : Response::error('service_unavailable', 'La base de datos no responde.', 503, ['php' => 'ok', 'bd' => 'error']);
}, Permission::PUBLICO);
