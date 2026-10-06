<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

defined('APP_ROOT') || exit;

/**
 * Plantillas PHP. Nombres:
 *   'Insumos::index' → app/Modules/Insumos/views/index.php
 *   'errores/http'   → app/views/errores/http.php
 */
final class View
{
    /**
     * Renderiza una plantilla y, si se indica, la envuelve en un layout de app/views/layouts/.
     * El layout recibe el HTML de la plantilla en $contenido, además de los mismos $datos.
     *
     * @param array<string, mixed> $datos
     */
    public static function render(string $plantilla, array $datos = [], ?string $layout = 'app'): string
    {
        $contenido = self::renderArchivo(self::ruta($plantilla), $datos);

        if ($layout === null) {
            return $contenido;
        }

        return self::renderArchivo(self::ruta('layouts/' . $layout), ['contenido' => $contenido] + $datos);
    }

    /**
     * Fragmento reutilizable de app/views/partials/.
     *
     * @param array<string, mixed> $datos
     */
    public static function partial(string $nombre, array $datos = []): string
    {
        return self::renderArchivo(self::ruta('partials/' . $nombre), $datos);
    }

    private static function ruta(string $plantilla): string
    {
        if (!preg_match('#^(?:([A-Z][A-Za-z]*)::)?([a-z0-9_-]+(?:/[a-z0-9_-]+)*)$#', $plantilla, $m)) {
            throw new InvalidArgumentException("Nombre de plantilla inválido: {$plantilla}");
        }

        $archivo = $m[1] !== ''
            ? APP_ROOT . "/app/Modules/{$m[1]}/views/{$m[2]}.php"
            : APP_ROOT . "/app/views/{$m[2]}.php";

        if (!is_file($archivo)) {
            throw new RuntimeException("No existe la plantilla: {$plantilla}");
        }

        return $archivo;
    }

    /**
     * La plantilla solo ve las variables de $datos.
     *
     * @param array<string, mixed> $__datos
     */
    private static function renderArchivo(string $__archivo, array $__datos): string
    {
        extract($__datos, EXTR_SKIP);
        ob_start();

        try {
            require $__archivo;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }
}
