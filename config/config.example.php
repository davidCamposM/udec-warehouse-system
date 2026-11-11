<?php

/**
 * Plantilla de configuración del sistema de bodega.
 *
 * Copiar este archivo como config/config.php y completar los valores.
 * config.php NO se versiona: contiene credenciales.
 */

declare(strict_types=1);

defined('APP_ROOT') || exit;

// Solo en desarrollo, pruebas E2E (e2e/): entrando por http://e2e.localhost:8080 el sistema usa la base de pruebas
// (bodega_fcf_pruebas), que las pruebas reinician en cada corrida. En el servidor, dejar 'bodega_fcf' fijo abajo.
$e2e = strtolower((string) ($_SERVER['HTTP_HOST'] ?? '')) === 'e2e.localhost:8080';

return [
    'app' => [
        'name' => 'Bodega FCF UdeC',
        'env' => 'local',                       // local | produccion
        'debug' => false,                       // true solo en desarrollo: muestra errores detallados
        'base_url' => $e2e ? 'http://e2e.localhost:8080' : 'http://localhost:8080',  // URL pública del sistema, sin barra final
        'url_mode' => 'rewrite',                // rewrite: /insumos/entradas · query: index.php?r=insumos/entradas
        'timezone' => 'America/Santiago',
        'https' => false,                       // true si el servidor tiene HTTPS: activa la cookie Secure
    ],

    // Valores por defecto del entorno Docker. En el servidor, los que indique la DTI.
    'db' => [
        'host' => 'db',                         // nombre del servicio en docker-compose.yml
        'port' => 3306,
        'name' => $e2e ? 'bodega_fcf_pruebas' : 'bodega_fcf',
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
        'dominios' => ['udec.cl', 'gmail.com'], // solo se agregan cuentas con correo de estos dominios ([]: cualquiera)
        'enlace_compartir_minutos' => 15,       // el enlace que da el administrador («Dar enlace»): único y corto
        'enlace_activar_dias' => 7,             // el enlace para crear la contraseña la primera vez (pedido por correo)
        'enlace_recuperar_minutos' => 60,       // el enlace para recuperar una contraseña olvidada
        'enlaces_por_hora' => 3,                // enlaces que se pueden pedir por correo en una hora
    ],

    // Envío de correos (enlaces para crear o recuperar la contraseña).
    //   'archivo': no envía; los guarda en storage/logs/correos-aaaa-mm-dd.log (desarrollo).
    //   'smtp': los envía con la casilla indicada. Gmail: smtp.gmail.com, 587, tls, con una «contraseña de
    //   aplicación». Microsoft 365: smtp.office365.com, 587, tls (la casilla debe tener permitido «SMTP AUTH»).
    'correo' => [
        'modo' => 'archivo',                    // archivo | smtp
        'host' => '',
        'puerto' => 587,
        'seguridad' => 'tls',                   // tls (STARTTLS, puerto 587) | ssl (puerto 465) | '' (sin cifrar)
        'usuario' => '',
        'clave' => '',
        'remitente' => '',                      // la dirección que envía: bodega.fcf@udec.cl
        'nombre_remitente' => 'Bodega FCF UdeC',
    ],

    'panel' => [
        'poll_seconds' => 30,                   // cada cuánto se actualiza el panel de inicio
    ],
];
