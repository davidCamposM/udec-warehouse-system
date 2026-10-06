<?php

declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

defined('APP_ROOT') || exit;

/**
 * Manejo global de errores. Los avisos de PHP (warnings, notices) se convierten en excepciones
 * para que no pasen inadvertidos. App atrapa casi todo; esto cubre lo que ocurra fuera de ella.
 */
final class ErrorHandler
{
    public static function register(bool $debug): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');

        set_error_handler(static function (int $nivel, string $mensaje, string $archivo, int $linea): bool {
            if (!(error_reporting() & $nivel)) {
                return false;
            }

            // Lo obsoleto solo se registra: una versión distinta de PHP en el servidor no debe botar el sistema.
            if ($nivel === E_DEPRECATED || $nivel === E_USER_DEPRECATED) {
                Logger::warning("Obsoleto: {$mensaje} en {$archivo}:{$linea}");
                return true;
            }

            throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
        });

        set_exception_handler(static function (Throwable $e) use ($debug): void {
            Logger::exception($e);

            if (PHP_SAPI !== 'cli' && !headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/plain; charset=UTF-8');
            }

            echo $debug ? (string) $e : 'Error interno del servidor.';
        });

        register_shutdown_function(static function (): void {
            $error = error_get_last();

            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                Logger::error("Error fatal: {$error['message']} en {$error['file']}:{$error['line']}");
            }
        });
    }
}
