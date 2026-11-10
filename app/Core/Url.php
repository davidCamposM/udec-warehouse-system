<?php

declare(strict_types=1);

namespace App\Core;

defined('APP_ROOT') || exit;

/**
 * Construcción de URLs. Todas las URLs del sistema se generan aquí, nunca a mano, para que
 * funcionen con o sin reescritura de URLs (app.url_mode) y en cualquier subcarpeta (app.base_url).
 */
final class Url
{
    /** URL base sin barra final: 'http://localhost:8080' o 'https://servidor/bodega'. */
    public static function base(): string
    {
        return rtrim((string) Config::get('app.base_url', ''), '/');
    }

    /** Parte de la ruta de base_url: '' si el sistema está en la raíz, '/bodega' si está en una subcarpeta. */
    public static function basePath(): string
    {
        return rtrim((string) parse_url(self::base(), PHP_URL_PATH), '/');
    }

    /**
     * URL de una ruta del sistema según app.url_mode:
     *   rewrite → /insumos/entradas?id=5
     *   query   → /index.php?r=insumos/entradas&id=5
     *
     * @param array<string, mixed> $parametros
     */
    public static function to(string $ruta = '', array $parametros = []): string
    {
        $ruta = trim($ruta, '/');
        $consulta = http_build_query($parametros);

        if (Config::get('app.url_mode', 'rewrite') === 'query') {
            $url = self::base() . '/index.php';
            $partes = array_filter([$ruta !== '' ? 'r=' . $ruta : '', $consulta]);
        } else {
            $url = self::base() . '/' . $ruta;
            $partes = array_filter([$consulta]);
        }

        return $partes === [] ? $url : $url . '?' . implode('&', $partes);
    }

    /** La URL apunta a este sistema: evita redirigir a otro sitio con una URL manipulada. */
    public static function isInternal(string $url): bool
    {
        return $url === self::base() || str_starts_with($url, self::base() . '/');
    }

    /** URL de un archivo de public/assets. Lleva la fecha de modificación para que el navegador no use una copia vieja. */
    public static function asset(string $ruta): string
    {
        $ruta = ltrim($ruta, '/');
        $archivo = APP_ROOT . '/public/assets/' . $ruta;
        $version = is_file($archivo) ? '?v=' . filemtime($archivo) : '';

        return self::base() . '/assets/' . $ruta . $version;
    }

    /**
     * Un script del sistema, que se carga como módulo ES (<script type="module">), con la versión en la ruta:
     * /assets/v1731283200/js/core/ui.js (public/.htaccess la quita).
     *
     * Por qué en la ruta y no como «?v=»: un módulo importa a otro con una ruta relativa (import "../core/api.js"), que
     * hereda la carpeta pero no la consulta. Con «?v=» solo en la etiqueta, el mismo archivo tendría dos URL y el
     * navegador lo ejecutaría dos veces; y el importado, sin versión, podría quedar viejo en caché. Con la versión en
     * la ruta, todos los módulos comparten una sola, que cambia apenas cambia cualquiera de ellos.
     * Sin reescritura de URLs (url_mode = query) no hay quien quite la versión: va la ruta real.
     */
    public static function modulo(string $ruta): string
    {
        $ruta = ltrim($ruta, '/');
        if (Config::get('app.url_mode', 'rewrite') === 'query') {
            return self::base() . '/assets/' . $ruta;
        }

        return self::base() . '/assets/v' . self::versionDeModulos() . '/' . $ruta;
    }

    /** La fecha del script más reciente de public/assets/js: cambia cada vez que se actualiza cualquiera. */
    private static function versionDeModulos(): int
    {
        static $version = null;
        if ($version !== null) {
            return $version;
        }

        $version = 0;
        $archivos = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            APP_ROOT . '/public/assets/js',
            \FilesystemIterator::SKIP_DOTS
        ));
        foreach ($archivos as $archivo) {
            if ($archivo->getExtension() === 'js') {
                $version = max($version, $archivo->getMTime());
            }
        }

        return $version;
    }
}
