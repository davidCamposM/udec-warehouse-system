<?php

/**
 * Arranque común: autoload, configuración, errores, permisos y rutas.
 * Lo usan public/index.php y los scripts de bin/. Retorna la App lista para atender peticiones.
 */

declare(strict_types=1);

use App\Core\App;
use App\Core\Config;
use App\Core\ErrorHandler;
use App\Core\Permission;
use App\Core\Router;

defined('APP_ROOT') || exit;

// Autoload estilo PSR-4 sin Composer: App\Core\Router → app/Core/Router.php
spl_autoload_register(static function (string $clase): void {
    if (!str_starts_with($clase, 'App\\')) {
        return;
    }

    $archivo = APP_ROOT . '/app/' . str_replace('\\', '/', substr($clase, 4)) . '.php';

    if (is_file($archivo)) {
        require $archivo;
    }
});

require APP_ROOT . '/app/helpers.php';

try {
    Config::load(APP_ROOT . '/config/config.php');
} catch (RuntimeException $e) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        exit(1);
    }

    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    exit($e->getMessage());
}

ErrorHandler::register((bool) Config::get('app.debug', false));
date_default_timezone_set((string) Config::get('app.timezone', 'America/Santiago'));
mb_internal_encoding('UTF-8');

Permission::load(APP_ROOT . '/config/permisos.php');

// Rutas generales y luego las de cada módulo (app/Modules/<Modulo>/routes.php).
$router = new Router();
require APP_ROOT . '/app/routes.php';

foreach (glob(APP_ROOT . '/app/Modules/*/routes.php') ?: [] as $archivoRutas) {
    require $archivoRutas;
}

return new App($router);
