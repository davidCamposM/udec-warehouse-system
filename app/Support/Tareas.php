<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Auth;
use App\Core\Permission;
use App\Core\Url;
use InvalidArgumentException;

defined('APP_ROOT') || exit;

/**
 * Tareas definidas en config/tareas.php: los botones del inicio y el color e ícono del encabezado
 * de cada pantalla.
 */
final class Tareas
{
    /** @var list<array{clave: string, texto: string, descripcion: string, ruta: ?string, icono: string, color: string, permiso: string, boton?: bool}>|null */
    private static ?array $config = null;

    /**
     * Los botones del inicio: las tareas que el usuario puede hacer y que ya existen (las que tienen ruta), en el
     * orden de la configuración. No incluye las que no van como botón (Buscar producto es la barra de búsqueda).
     *
     * @return list<array{clave: string, texto: string, descripcion: string, url: string, icono: string, color: string}>
     */
    public static function botones(): array
    {
        $botones = [];

        foreach (self::config() as $tarea) {
            if (($tarea['boton'] ?? true) === false || !self::disponible($tarea)) {
                continue;
            }

            $botones[] = [
                'clave' => $tarea['clave'],
                'texto' => $tarea['texto'],
                'descripcion' => $tarea['descripcion'],
                'url' => Url::to($tarea['ruta']),
                'icono' => $tarea['icono'],
                'color' => $tarea['color'],
            ];
        }

        return $botones;
    }

    /** Si el usuario puede hacer esta tarea (existe y tiene el permiso). */
    public static function puede(string $clave): bool
    {
        return self::disponible(self::tarea($clave));
    }

    /**
     * Una tarea por su clave, para pintar el encabezado de sus pantallas con su color e ícono.
     *
     * @return array{clave: string, texto: string, descripcion: string, ruta: ?string, icono: string, color: string, permiso: string, boton?: bool}
     */
    public static function tarea(string $clave): array
    {
        foreach (self::config() as $tarea) {
            if ($tarea['clave'] === $clave) {
                return $tarea;
            }
        }

        throw new InvalidArgumentException("Tarea desconocida: {$clave}");
    }

    /** @param array{ruta: ?string, permiso: string, perfiles?: list<string>} $tarea */
    private static function disponible(array $tarea): bool
    {
        return $tarea['ruta'] !== null
            && Permission::can($tarea['permiso'])
            && (!isset($tarea['perfiles']) || in_array(Auth::perfil(), $tarea['perfiles'], true));
    }

    /** @return list<array{clave: string, texto: string, descripcion: string, ruta: ?string, icono: string, color: string, permiso: string, boton?: bool}> */
    private static function config(): array
    {
        return self::$config ??= require APP_ROOT . '/config/tareas.php';
    }
}
