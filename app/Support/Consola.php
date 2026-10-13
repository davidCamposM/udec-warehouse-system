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

    /**
     * Pregunta sin mostrar lo que se escribe (contraseñas). Sin terminal, lo muestra y avisa.
     * La respuesta no se recorta: en una contraseña, los espacios cuentan.
     */
    public static function preguntarOculto(string $pregunta): string
    {
        $hayTerminal = function_exists('posix_isatty') ? posix_isatty(STDIN) : true;

        // stty no imprime nada si funciona: se revisa su código de salida.
        $oculto = $hayTerminal && exec('stty -echo 2>/dev/null', $salida, $codigo) !== false && $codigo === 0;

        if (!$oculto) {
            self::aviso('No hay terminal: lo que escribas se verá en pantalla.');
        }

        fwrite(STDOUT, $pregunta . ': ');
        $respuesta = rtrim((string) fgets(STDIN), "\r\n");

        if ($oculto) {
            exec('stty echo 2>/dev/null');
            fwrite(STDOUT, PHP_EOL);
        }

        return $respuesta;
    }

    /**
     * Pregunta con alternativas numeradas. Retorna la clave de la alternativa elegida.
     *
     * @param array<string, string> $alternativas clave => texto
     */
    public static function elegir(string $pregunta, array $alternativas): string
    {
        $claves = array_keys($alternativas);

        foreach ($claves as $i => $clave) {
            self::linea(sprintf('  %d) %s', $i + 1, $alternativas[$clave]));
        }

        while (true) {
            $respuesta = self::preguntar($pregunta . ' (1-' . count($claves) . ')');

            if (ctype_digit($respuesta) && isset($claves[(int) $respuesta - 1])) {
                return $claves[(int) $respuesta - 1];
            }
            if (feof(STDIN)) {
                return '';
            }
            self::error('Elige un número de la lista.');
        }
    }

    /** Texto como literal SQL ('O''Higgins'), para los scripts que imprimen sentencias en vez de ejecutarlas. */
    public static function sql(string $texto): string
    {
        return "'" . str_replace(['\\', "'"], ['\\\\', "''"], $texto) . "'";
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
