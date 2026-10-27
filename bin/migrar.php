<?php

/**
 * Aplica las migraciones pendientes de database/migrations/ en orden y las registra en la
 * tabla `migraciones`. Una migración aplicada nunca se vuelve a ejecutar ni se edita: los
 * cambios van en una migración nueva.
 *
 *   php bin/migrar.php            aplica las pendientes
 *   php bin/migrar.php --estado   muestra cuáles están aplicadas y cuáles pendientes
 *
 * En Docker: docker compose exec -u www-data app php bin/migrar.php
 */

declare(strict_types=1);

use App\Core\Database;
use App\Support\Consola;
use App\Support\Migraciones;

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';

Consola::soloConsola();

$opciones = Consola::opciones(array_slice($argv, 1));

Database::query(
    'CREATE TABLE IF NOT EXISTS migraciones (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        archivo VARCHAR(190) NOT NULL,
        aplicada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_migraciones_archivo (archivo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$aplicadas = array_column(Database::fetchAll('SELECT archivo, aplicada_en FROM migraciones'), 'aplicada_en', 'archivo');
$archivos = Migraciones::archivos();

if (isset($opciones['estado'])) {
    foreach ($archivos as $ruta) {
        $nombre = basename($ruta);
        isset($aplicadas[$nombre])
            ? Consola::exito("{$nombre}  (aplicada el {$aplicadas[$nombre]})")
            : Consola::aviso("{$nombre}  PENDIENTE");
    }
    exit(0);
}

$pendientes = array_filter($archivos, static fn (string $ruta): bool => !isset($aplicadas[basename($ruta)]));

if ($pendientes === []) {
    Consola::exito('No hay migraciones pendientes.');
    exit(0);
}

foreach ($pendientes as $ruta) {
    $nombre = basename($ruta);

    try {
        Migraciones::aplicar($ruta);
        Database::insert('INSERT INTO migraciones (archivo) VALUES (?)', [$nombre]);
        Consola::exito($nombre);
    } catch (Throwable $e) {
        // MySQL confirma cada CREATE/ALTER por separado: lo ya creado de esta migración queda.
        // Hay que revisar el error, corregir la base a mano o la migración, y volver a ejecutar.
        Consola::error("{$nombre}: {$e->getMessage()}");
        exit(1);
    }
}

Consola::exito(count($pendientes) . ' migración(es) aplicada(s).');
