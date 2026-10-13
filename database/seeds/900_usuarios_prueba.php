<?php

/**
 * Cuentas de prueba, una por perfil. Solo en desarrollo: con app.env = local y el sistema en
 * localhost. En cualquier otro caso este seed no hace nada, así nunca llegan al servidor.
 * Los correos usan .test, un dominio reservado que no existe en internet.
 */

declare(strict_types=1);

use App\Core\Config;
use App\Modules\Usuarios\UsuarioService;
use App\Support\Consola;

defined('APP_ROOT') || exit;

$host = parse_url((string) Config::get('app.base_url', ''), PHP_URL_HOST);

if (Config::get('app.env') !== 'local' || !in_array($host, ['localhost', '127.0.0.1'], true)) {
    Consola::aviso('Cuentas de prueba: omitidas (solo se crean en desarrollo local).');

    return;
}

$clave = 'Bodega2026';

// correo => [nombre, perfil]
$cuentas = [
    'admin@bodega.test' => ['Encargado de Prueba', 'administrador'],
    'administrativa@bodega.test' => ['Administrativa de Prueba', 'administrativa'],
    'docente@bodega.test' => ['Docente de Prueba', 'docente'],
];

$servicio = new UsuarioService();
$nuevas = 0;

foreach ($cuentas as $email => [$nombre, $perfil]) {
    if ($servicio->buscarPorEmail($email) !== null) {
        continue;
    }

    $servicio->crear(['nombre' => $nombre, 'email' => $email, 'perfil' => $perfil, 'password' => $clave]);
    $nuevas++;
}

Consola::exito("Cuentas de prueba: {$nuevas} nuevas de " . count($cuentas) . " (clave {$clave}).");
