<?php

declare(strict_types=1);

namespace App\Core;

defined('APP_ROOT') || exit;

/**
 * Respuesta HTTP. Los controladores siempre retornan una: HTML, JSON, redirección o un archivo para descargar.
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

    /**
     * Respuesta exitosa de la API, siempre con el mismo sobre: {"ok": true, "data": …}. Así el JavaScript (core/api.js)
     * lee todas las respuestas igual, sin conocer cada endpoint.
     */
    public static function ok(mixed $datos = null, int $estado = 200): self
    {
        return self::json(['ok' => true, 'data' => $datos], $estado);
    }

    /**
     * Respuesta de error de la API, con el mismo sobre: {"ok": false, "error": {"code", "message", …}}. El código es
     * estable y en inglés (para el programa); el mensaje, en español y para la persona. $detalles agrega datos al error,
     * como la dirección del login o los campos con errores.
     *
     * @param array<string, mixed> $detalles
     */
    public static function error(string $codigo, string $mensaje, int $estado, array $detalles = []): self
    {
        return self::json(['ok' => false, 'error' => ['code' => $codigo, 'message' => $mensaje] + $detalles], $estado);
    }

    private static function json(mixed $datos, int $estado): self
    {
        $json = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return new self($json, $estado, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public static function redirect(string $url, int $estado = 302): self
    {
        return new self('', $estado, ['Location' => $url]);
    }

    /** Un archivo para descargar (por ejemplo, un .docx), con su tipo y su nombre. */
    public static function descarga(string $nombreArchivo, string $contenido, string $tipo): self
    {
        $nombre = preg_replace('/[^A-Za-z0-9._-]/', '_', $nombreArchivo);

        return new self($contenido, 200, [
            'Content-Type' => $tipo,
            'Content-Disposition' => 'attachment; filename="' . $nombre . '"',
            'Content-Length' => (string) strlen($contenido),
        ]);
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
