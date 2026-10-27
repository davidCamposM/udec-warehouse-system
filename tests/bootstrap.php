<?php

/**
 * Arranque de las pruebas (phpunit.xml.dist): el mismo del sistema, pero con su propia base y con los límites del
 * login fijos, para no depender de config/config.php. La base de pruebas se arma desde cero en cada corrida con las
 * migraciones de database/migrations/, así siempre tiene el esquema actual. La base de desarrollo no se toca.
 *
 * Preparación (una sola vez): crear bodega_fcf_pruebas y dar permisos al usuario de la app (ver README, «Pruebas»).
 */

declare(strict_types=1);

use App\Core\Config;
use App\Core\Database;
use App\Support\Migraciones;

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';

// Las clases de apoyo de las pruebas: Tests\PruebaConBase → tests/PruebaConBase.php
spl_autoload_register(static function (string $clase): void {
    if (str_starts_with($clase, 'Tests\\')) {
        $archivo = __DIR__ . '/' . str_replace('\\', '/', substr($clase, 6)) . '.php';
        if (is_file($archivo)) {
            require $archivo;
        }
    }
});

Config::set('db.name', 'bodega_fcf_pruebas');
Config::set('app.base_url', 'http://localhost:8080');
Config::set('auth.max_intentos', 5);
Config::set('auth.max_intentos_ip', 30);
Config::set('auth.bloqueo_minutos', 15);
Config::set('auth.retencion_intentos_dias', 90);

// Freno: se borran todas las tablas, así que solo en una base cuyo nombre termine en _pruebas.
$base = (string) Database::fetchValue('SELECT DATABASE()');
if (!str_ends_with($base, '_pruebas')) {
    fwrite(STDERR, "Las pruebas solo corren en una base que termine en _pruebas (conectado a «{$base}»).\n");
    exit(1);
}

Database::query('SET FOREIGN_KEY_CHECKS = 0');
foreach (Database::fetchAll('SELECT TABLE_NAME AS tabla FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()') as $fila) {
    Database::query('DROP TABLE `' . str_replace('`', '', $fila['tabla']) . '`');
}
Database::query('SET FOREIGN_KEY_CHECKS = 1');

foreach (Migraciones::archivos() as $ruta) {
    Migraciones::aplicar($ruta);
}
