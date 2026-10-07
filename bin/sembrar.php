<?php

/**
 * Carga los datos iniciales: ejecuta en orden los archivos de database/seeds/.
 * Cada seed se puede ejecutar varias veces: solo agrega lo que falta, nunca duplica.
 *
 *   php bin/sembrar.php
 *
 * En Docker: docker compose exec -u www-data app php bin/sembrar.php
 */

declare(strict_types=1);

use App\Support\Consola;

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';

Consola::soloConsola();

$archivos = glob(APP_ROOT . '/database/seeds/*.php') ?: [];
sort($archivos);

if ($archivos === []) {
    Consola::aviso('No hay seeds en database/seeds/.');
    exit(0);
}

foreach ($archivos as $archivo) {
    Consola::linea('» ' . basename($archivo));

    try {
        // Cada seed corre en su propio ámbito: sus variables no se mezclan con las de otro.
        (static function (string $seed): void {
            require $seed;
        })($archivo);
    } catch (Throwable $e) {
        Consola::error(basename($archivo) . ': ' . $e->getMessage());
        exit(1);
    }
}

Consola::exito('Datos iniciales cargados.');
