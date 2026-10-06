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

    /** URL de un archivo de public/assets. Lleva la fecha de modificación para que el navegador no use una copia vieja. */
    public static function asset(string $ruta): string
    {
        $ruta = ltrim($ruta, '/');
        $archivo = APP_ROOT . '/public/assets/' . $ruta;
        $version = is_file($archivo) ? '?v=' . filemtime($archivo) : '';

        return self::base() . '/assets/' . $ruta . $version;
    }
}
