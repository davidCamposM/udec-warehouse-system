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
use App\Core\Url;

defined('APP_ROOT') || exit;

// Provisional: mientras no exista el panel de inicio, la raíz lleva al login.
$router->get('/', static fn (): Response => Response::redirect(Url::to('login')), Permission::PUBLICO);

// Estado del sistema: tras una instalación, confirma que PHP y la base de datos responden.
$router->get('/estado', static function (): Response {
    try {
        Database::fetchValue('SELECT 1');
        $bd = 'ok';
    } catch (Throwable $e) {
        Logger::exception($e);
        $bd = 'error';
    }

    return Response::json(['php' => 'ok', 'bd' => $bd], $bd === 'ok' ? 200 : 503);
}, Permission::PUBLICO);
