<?php

declare(strict_types=1);

namespace App\Core;

defined('APP_ROOT') || exit;

/**
 * Petición HTTP actual: método, ruta interna, datos GET/POST y cabeceras.
 */
final class Request
{
    /**
     * @param array<string, mixed>  $query      datos GET
     * @param array<string, mixed>  $post       datos POST
     * @param array<string, mixed>  $servidor   $_SERVER
     * @param array<string, string> $parametros parámetros de la ruta ({id})
     */
    public function __construct(
        private readonly string $metodo,
        private readonly string $ruta,
        private readonly array $query = [],
        private readonly array $post = [],
        private readonly array $servidor = [],
        private readonly array $parametros = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            self::resolverRuta($_GET, $_SERVER),
            $_GET,
            $_POST,
            $_SERVER,
        );
    }

    /**
     * Ruta interna ('/insumos/entradas'). El parámetro ?r= se acepta siempre, así el sistema
     * responde aunque el servidor no tenga reescritura de URLs.
     *
     * @param array<string, mixed> $query
     * @param array<string, mixed> $servidor
     */
    private static function resolverRuta(array $query, array $servidor): string
    {
        if (isset($query['r']) && is_string($query['r'])) {
            $ruta = $query['r'];
        } else {
            $ruta = rawurldecode((string) parse_url((string) ($servidor['REQUEST_URI'] ?? '/'), PHP_URL_PATH));
            $base = Url::basePath();

            if ($base !== '' && str_starts_with($ruta, $base)) {
                $ruta = substr($ruta, strlen($base));
            }
            if (str_starts_with($ruta, '/index.php')) {
                $ruta = substr($ruta, strlen('/index.php'));
            }
        }

        return '/' . trim($ruta, '/');
    }

    public function method(): string
    {
        return $this->metodo;
    }

    public function path(): string
    {
        return $this->ruta;
    }

    /** URL completa de esta petición (para volver a ella después del login). */
    public function fullUrl(): string
    {
        $query = $this->query;
        unset($query['r']);

        return Url::to($this->ruta, $query);
    }

    public function query(string $clave, mixed $porDefecto = null): mixed
    {
        return $this->query[$clave] ?? $porDefecto;
    }

    public function input(string $clave, mixed $porDefecto = null): mixed
    {
        return $this->post[$clave] ?? $porDefecto;
    }

    /** @return array<string, mixed> */
    public function post(): array
    {
        return $this->post;
    }

    public function param(string $nombre): ?string
    {
        return $this->parametros[$nombre] ?? null;
    }

    public function header(string $nombre): ?string
    {
        $valor = $this->servidor['HTTP_' . strtoupper(str_replace('-', '_', $nombre))] ?? null;

        return is_string($valor) ? $valor : null;
    }

    /** La petición viene de JavaScript (fetch) y espera JSON en vez de HTML. */
    public function wantsJson(): bool
    {
        return str_contains((string) $this->header('Accept'), 'application/json')
            || $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /** Petición automática del navegador (polling del panel): no cuenta como actividad del usuario. */
    public function isBackground(): bool
    {
        return $this->header('X-Bodega-Background') === '1';
    }

    public function referer(): ?string
    {
        return $this->header('Referer');
    }

    public function ip(): string
    {
        return (string) ($this->servidor['REMOTE_ADDR'] ?? '');
    }

    /** @param array<string, string> $parametros */
    public function withParams(array $parametros): self
    {
        return new self($this->metodo, $this->ruta, $this->query, $this->post, $this->servidor, $parametros);
    }
}
