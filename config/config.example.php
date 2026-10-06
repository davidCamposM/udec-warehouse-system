<?php

/**
 * Plantilla de configuración del sistema de bodega.
 *
 * Copiar este archivo como config/config.php y completar los valores.
 * config.php NO se versiona: contiene credenciales.
 */

declare(strict_types=1);

defined('APP_ROOT') || exit;

return [
    'app' => [
        'name' => 'Bodega FCF UdeC',
        'env' => 'local',                       // local | produccion
        'debug' => false,                       // true solo en desarrollo: muestra errores detallados
        'base_url' => 'http://localhost:8080',  // URL pública del sistema, sin barra final
        'url_mode' => 'rewrite',                // rewrite: /insumos/entradas · query: index.php?r=insumos/entradas
        'timezone' => 'America/Santiago',
        'https' => false,                       // true si el servidor tiene HTTPS: activa la cookie Secure
    ],

    // Valores por defecto del entorno Docker. En el servidor, los que indique la DTI.
    'db' => [
        'host' => 'db',                         // nombre del servicio en docker-compose.yml
        'port' => 3306,
        'name' => 'bodega_fcf',
        'user' => 'bodega',
        'pass' => 'bodega',
        'charset' => 'utf8mb4',
    ],

    'session' => [
        'name' => 'BODEGA_SID',
        'idle_minutes' => 30,                   // cierre de sesión por inactividad
    ],

    'estacion' => [
        'idle_seconds' => 60,                   // la identificación se cierra tras este tiempo sin actividad (por definir)
    ],

    'panel' => [
        'poll_seconds' => 30,                   // cada cuánto se actualiza el panel de inicio
    ],
];
