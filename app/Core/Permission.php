<?php

declare(strict_types=1);

namespace App\Core;

defined('APP_ROOT') || exit;

/**
 * Permisos por perfil, definidos en config/permisos.php. Se validan siempre en el servidor.
 */
final class Permission
{
    /** Ruta accesible sin iniciar sesión. */
    public const PUBLICO = 'publico';

    /** Ruta accesible para cualquier usuario con sesión, sea cual sea su perfil. */
    public const AUTENTICADO = 'autenticado';

    /** @var array<string, list<string>> perfil => permisos */
    private static array $matriz = [];

    public static function load(string $archivo): void
    {
        self::$matriz = require $archivo;
    }

    /**
     * ¿El perfil (por defecto, el del usuario en sesión) tiene el permiso?
     * Comodines en la matriz: '*' = todo; 'insumos.*' = todo lo del módulo de insumos.
     */
    public static function can(string $permiso, ?string $perfil = null): bool
    {
        if ($permiso === self::PUBLICO) {
            return true;
        }

        $perfil ??= Auth::perfil();

        if ($perfil === null) {
            return false;
        }

        if ($permiso === self::AUTENTICADO) {
            return true;
        }

        $modulo = explode('.', $permiso)[0];

        foreach (self::$matriz[$perfil] ?? [] as $otorgado) {
            if ($otorgado === '*' || $otorgado === $permiso || $otorgado === $modulo . '.*') {
                return true;
            }
        }

        return false;
    }

    /**
     * Para chequeos dentro de un controlador o servicio, además del permiso de la ruta.
     *
     * @throws HttpException 403
     */
    public static function authorize(string $permiso): void
    {
        if (!self::can($permiso)) {
            throw new HttpException(403);
        }
    }
}
