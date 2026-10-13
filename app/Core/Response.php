<?php

declare(strict_types=1);

namespace App\Core;

defined('APP_ROOT') || exit;

/**
 * Respuesta HTTP. Los controladores siempre retornan una: HTML, JSON, redirección o CSV.
 */
final class Response
{
    /** @param array<string, string> $cabeceras */
    public function __construct(
        private readonly string $cuerpo = '',
        private readonly int $estado = 200,
        private array $cabeceras = [],
    ) {
    }

    public static function html(string $html, int $estado = 200): self
    {
        return new self($html, $estado, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /** @param array<string, mixed> $datos */
    public static function view(string $plantilla, array $datos = [], ?string $layout = 'app', int $estado = 200): self
    {
        return self::html(View::render($plantilla, $datos, $layout), $estado);
    }

    public static function json(mixed $datos, int $estado = 200): self
    {
        $json = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return new self($json, $estado, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public static function redirect(string $url, int $estado = 302): self
    {
        return new self('', $estado, ['Location' => $url]);
    }

    /**
     * Descarga CSV que abre directo en Excel: separador ";" y BOM UTF-8 para tildes y ñ.
     *
     * @param list<string>                         $encabezados
     * @param iterable<array<int|string, mixed>>   $filas
     */
    public static function csv(string $nombreArchivo, array $encabezados, iterable $filas): self
    {
        $flujo = fopen('php://temp', 'r+');
        fwrite($flujo, "\xEF\xBB\xBF");
        fputcsv($flujo, $encabezados, ';', '"', '');

        foreach ($filas as $fila) {
            fputcsv($flujo, array_map(self::celdaSegura(...), array_values($fila)), ';', '"', '');
        }

        rewind($flujo);
        $contenido = (string) stream_get_contents($flujo);
        fclose($flujo);

        $nombre = preg_replace('/[^A-Za-z0-9._-]/', '_', $nombreArchivo);

        return new self($contenido, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $nombre . '"',
        ]);
    }

    /** Evita que Excel ejecute como fórmula un texto ingresado por un usuario ("=...", "+...", "@..."). */
    private static function celdaSegura(mixed $valor): string
    {
        $texto = (string) $valor;

        if ($texto !== '' && !is_numeric($texto) && str_contains("=+-@\t\r", $texto[0])) {
            return "'" . $texto;
        }

        return $texto;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->estado);
            header_remove('X-Powered-By');

            foreach ($this->cabeceras + self::cabecerasSeguridad() as $nombre => $valor) {
                header($nombre . ': ' . $valor);
            }
        }

        echo $this->cuerpo;
    }

    /** @return array<string, string> */
    private static function cabecerasSeguridad(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'same-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
            'Content-Security-Policy' => "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; "
                . "script-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'",
            // Las páginas muestran datos de personas y stock: no deben quedar en caché.
            'Cache-Control' => 'no-store',
        ];
    }
}
