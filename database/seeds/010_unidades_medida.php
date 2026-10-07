<?php

/**
 * Unidades de medida base. El encargado puede agregar o desactivar unidades después.
 */

declare(strict_types=1);

use App\Core\Database;
use App\Support\Consola;

defined('APP_ROOT') || exit;

// nombre => [abreviatura, permite decimales]
$unidades = [
    'Unidad' => ['u', false],
    'Caja' => ['caja', false],
    'Paquete' => ['paq', false],
    'Resma' => ['resma', false],
    'Rollo' => ['rollo', false],
    'Par' => ['par', false],
    'Litro' => ['L', true],
    'Kilogramo' => ['kg', true],
];

$nuevas = 0;

foreach ($unidades as $nombre => [$abreviatura, $decimales]) {
    if (Database::fetchValue('SELECT id FROM unidades_medida WHERE nombre = ?', [$nombre]) !== null) {
        continue;
    }

    Database::insert(
        'INSERT INTO unidades_medida (nombre, abreviatura, permite_decimales) VALUES (?, ?, ?)',
        [$nombre, $abreviatura, (int) $decimales]
    );
    $nuevas++;
}

Consola::exito("Unidades de medida: {$nuevas} nuevas de " . count($unidades) . '.');
