<?php

/**
 * Punto de entrada único: toda petición al sistema pasa por aquí.
 */

declare(strict_types=1);

// Raíz del proyecto. Si en el servidor public/ queda en otra carpeta que el resto del sistema,
// basta con ajustar esta línea.
define('APP_ROOT', dirname(__DIR__));

/** @var App\Core\App $app */
$app = require APP_ROOT . '/app/bootstrap.php';

$app->handle(App\Core\Request::fromGlobals())->send();
