<?php

declare(strict_types=1);

namespace App\Core;

defined('APP_ROOT') || exit;

/**
 * Sesión PHP con cookies seguras, cierre por inactividad y mensajes flash.
 *
 * La inactividad la maneja la página (sesion.js): tras session.idle_minutes sin uso pregunta «¿Sigues ahí?» y, sin
 * respuesta en session.respuesta_segundos, cierra la sesión. El servidor la cierra por su cuenta un poco después, por si
 * la página no pudo hacerlo (sin JavaScript, el computador suspendido o el navegador cerrado).
 */
final class Session
{
    /**
     * Segundos de holgura del servidor sobre lo que espera la página: sesion.js avisa al servidor que la persona sigue
     * usando el sistema como mucho cada 2 minutos, así que su última actividad puede ir hasta ese tanto atrasada.
     */
    private const HOLGURA = 180;

    private const ACTIVIDAD = '_ultima_actividad';
    private const FLASH = '_flash';
    private const FLASH_NUEVO = '_flash_nuevo';

    private static bool $expirada = false;

    /**
     * Las peticiones en segundo plano ($enPrimerPlano = false, como el polling del panel) no
     * renuevan la actividad, para que un panel abierto no mantenga la sesión viva para siempre,
     * y tampoco consumen los mensajes flash pendientes.
     */
    public static function start(bool $enPrimerPlano = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $limite = (int) Config::get('session.idle_minutes', 20) * 60 + (int) Config::get('session.respuesta_segundos', 60)
            + self::HOLGURA;

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.gc_maxlifetime', (string) $limite);

        // Sesiones en storage/sessions: así ningún otro sistema del servidor las borra antes de tiempo.
        $carpeta = APP_ROOT . '/storage/sessions';
        if (is_dir($carpeta) && is_writable($carpeta)) {
            session_save_path($carpeta);
            ini_set('session.gc_probability', '1');
            ini_set('session.gc_divisor', '100');
        }

        session_name((string) Config::get('session.name', 'BODEGA_SID'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => Url::basePath() . '/',
            'secure' => (bool) Config::get('app.https', false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();

        self::controlarInactividad($limite, $enPrimerPlano);

        if ($enPrimerPlano) {
            $_SESSION[self::FLASH] = $_SESSION[self::FLASH_NUEVO] ?? [];
            unset($_SESSION[self::FLASH_NUEVO]);
        }
    }

    private static function controlarInactividad(int $limite, bool $enPrimerPlano): void
    {
        $ahora = time();
        $ultima = $_SESSION[self::ACTIVIDAD] ?? null;

        if (is_int($ultima) && $ahora - $ultima > $limite) {
            self::$expirada = isset($_SESSION[Auth::CLAVE]);
            self::reiniciar();
        }

        if ($enPrimerPlano || !isset($_SESSION[self::ACTIVIDAD])) {
            $_SESSION[self::ACTIVIDAD] = $ahora;
        }
    }

    /** En esta petición se cerró una sesión de usuario por inactividad. */
    public static function expired(): bool
    {
        return self::$expirada;
    }

    /** Cambia el id de sesión conservando los datos (al iniciar sesión). */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    /** Vacía la sesión y cambia su id (al cerrar sesión o por inactividad). */
    public static function reiniciar(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public static function get(string $clave, mixed $porDefecto = null): mixed
    {
        return $_SESSION[$clave] ?? $porDefecto;
    }

    public static function put(string $clave, mixed $valor): void
    {
        $_SESSION[$clave] = $valor;
    }

    public static function forget(string $clave): void
    {
        unset($_SESSION[$clave]);
    }

    public static function pull(string $clave, mixed $porDefecto = null): mixed
    {
        $valor = self::get($clave, $porDefecto);
        self::forget($clave);

        return $valor;
    }

    /** Guarda un dato que estará disponible solo en la próxima página (por ejemplo, tras una redirección). */
    public static function flash(string $clave, mixed $valor): void
    {
        $_SESSION[self::FLASH_NUEVO][$clave] = $valor;
    }

    /**
     * Guarda lo escrito en un formulario para rellenarlo en la próxima página (helper old()).
     *
     * @param array<string, mixed> $datos
     */
    public static function flashInput(array $datos): void
    {
        self::flash('_old', $datos);
    }

    public static function getFlash(string $clave, mixed $porDefecto = null): mixed
    {
        return $_SESSION[self::FLASH][$clave] ?? $porDefecto;
    }
}
