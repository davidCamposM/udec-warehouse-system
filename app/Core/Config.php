<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

defined('APP_ROOT') || exit;

/**
 * Configuración del sistema (config/config.php). Se lee con notación de puntos: 'db.host'.
 */
final class Config
{
    /** @var array<string, mixed> */
    private static array $valores = [];

    public static function load(string $archivo): void
    {
        if (!is_file($archivo)) {
            throw new RuntimeException(
                'Falta config/config.php. Copia config/config.example.php como config/config.php y complétalo.'
            );
        }

        $valores = require $archivo;

        if (!is_array($valores)) {
            throw new RuntimeException('config/config.php debe retornar un arreglo.');
        }

        self::$valores = $valores;
    }

    public static function get(string $clave, mixed $porDefecto = null): mixed
    {
        $valor = self::$valores;

        foreach (explode('.', $clave) as $parte) {
            if (!is_array($valor) || !array_key_exists($parte, $valor)) {
                return $porDefecto;
            }
            $valor = $valor[$parte];
        }

        return $valor;
    }

    public static function set(string $clave, mixed $valor): void
    {
        $destino = &self::$valores;

        foreach (explode('.', $clave) as $parte) {
            if (!isset($destino[$parte]) || !is_array($destino[$parte])) {
                $destino[$parte] = [];
            }
            $destino = &$destino[$parte];
        }

        $destino = $valor;
    }
}
