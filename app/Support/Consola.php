<?php

declare(strict_types=1);

namespace App\Support;

defined('APP_ROOT') || exit;

/**
 * Utilidades para los scripts de bin/: mensajes, preguntas y opciones de la línea de comandos.
 */
final class Consola
{
    /** Corta el script si no se ejecuta desde la consola (nunca desde el navegador). */
    public static function soloConsola(): void
    {
        if (PHP_SAPI !== 'cli') {
            http_response_code(404);
            exit;
        }
    }

    public static function linea(string $texto = ''): void
    {
        fwrite(STDOUT, $texto . PHP_EOL);
    }

    public static function exito(string $texto): void
    {
        self::linea('✔ ' . $texto);
    }

    public static function aviso(string $texto): void
    {
        self::linea('• ' . $texto);
    }

    public static function error(string $texto): void
    {
        fwrite(STDERR, '✘ ' . $texto . PHP_EOL);
    }

    public static function preguntar(string $pregunta, string $porDefecto = ''): string
    {
        $sufijo = $porDefecto !== '' ? " [{$porDefecto}]" : '';
        fwrite(STDOUT, $pregunta . $sufijo . ': ');
        $respuesta = trim((string) fgets(STDIN));

        return $respuesta !== '' ? $respuesta : $porDefecto;
    }

    /** Pregunta sin mostrar lo que se escribe (contraseñas). Sin terminal, lo muestra y avisa. */
    public static function preguntarOculto(string $pregunta): string
    {
        $hayTerminal = function_exists('posix_isatty') ? posix_isatty(STDIN) : true;

        if ($hayTerminal && shell_exec('stty -echo 2>/dev/null') !== null) {
            fwrite(STDOUT, $pregunta . ': ');
            $respuesta = trim((string) fgets(STDIN));
            shell_exec('stty echo 2>/dev/null');
            fwrite(STDOUT, PHP_EOL);

            return $respuesta;
        }

        self::aviso('No hay terminal: lo que escribas se verá en pantalla.');

        return self::preguntar($pregunta);
    }

    /**
     * Opciones de la línea de comandos: --estado, --email=x@y.cl
     *
     * @param list<string> $argumentos
     * @return array<string, string|true>
     */
    public static function opciones(array $argumentos): array
    {
        $opciones = [];

        foreach ($argumentos as $argumento) {
            if (preg_match('/^--([a-z0-9-]+)(?:=(.*))?$/', $argumento, $m)) {
                $opciones[$m[1]] = $m[2] ?? true;
            }
        }

        return $opciones;
    }
}
