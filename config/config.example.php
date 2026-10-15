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
        'idle_minutes' => 20,                   // sin uso por este tiempo, se pregunta «¿Sigues ahí?»
        'respuesta_segundos' => 60,             // para responder; sin respuesta (o con «No») se cierra la sesión
    ],

    // Límite de intentos de inicio de sesión. Si el servidor está detrás de un proxy, todas las
    // peticiones llegan con la misma IP: por eso el límite por IP es más alto.
    'auth' => [
        'max_intentos' => 5,                    // fallos seguidos por correo antes del bloqueo
        'max_intentos_ip' => 30,                // fallos desde una misma IP antes del bloqueo
        'bloqueo_minutos' => 15,                // ventana en que se cuentan los fallos
        'retencion_intentos_dias' => 90,        // el registro de accesos se borra pasado este plazo
    ],

    'panel' => [
        'poll_seconds' => 30,                   // cada cuánto se actualiza el panel de inicio
    ],
];
