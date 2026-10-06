<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

defined('APP_ROOT') || exit;

/**
 * Registro de errores en storage/logs/app-aaaa-mm-dd.log (un archivo por día).
 * No registrar contraseñas ni datos personales en el contexto.
 */
final class Logger
{
    /** @param array<string, mixed> $contexto */
    public static function error(string $mensaje, array $contexto = []): void
    {
        self::escribir('ERROR', $mensaje, $contexto);
    }

    /** @param array<string, mixed> $contexto */
    public static function warning(string $mensaje, array $contexto = []): void
    {
        self::escribir('WARNING', $mensaje, $contexto);
    }

    /** @param array<string, mixed> $contexto */
    public static function info(string $mensaje, array $contexto = []): void
    {
        self::escribir('INFO', $mensaje, $contexto);
    }

    public static function exception(Throwable $e): void
    {
        self::escribir(
            'ERROR',
            sprintf('%s: %s en %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()),
            [],
            $e->getTraceAsString()
        );
    }

    /** @param array<string, mixed> $contexto */
    private static function escribir(string $nivel, string $mensaje, array $contexto, string $traza = ''): void
    {
        $linea = sprintf('[%s] %s %s', date('Y-m-d H:i:s'), $nivel, $mensaje);

        if ($contexto !== []) {
            $linea .= ' ' . json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if ($traza !== '') {
            $linea .= PHP_EOL . $traza;
        }

        $archivo = APP_ROOT . '/storage/logs/app-' . date('Y-m-d') . '.log';

        // Si storage/logs no es escribible, se usa el log del propio PHP para no perder el error.
        if (@file_put_contents($archivo, $linea . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            error_log($linea);
        }
    }
}
