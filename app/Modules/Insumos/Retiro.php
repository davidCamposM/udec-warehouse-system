<?php

declare(strict_types=1);

namespace App\Modules\Insumos;

use App\Core\Session;

defined('APP_ROOT') || exit;

/**
 * El producto que se está retirando y cuántas unidades, entre una etapa y otra de «Retirar productos». Vive en la
 * sesión, así no se pierde si la persona recarga la página.
 */
final class Retiro
{
    private const CLAVE = 'retirar_producto';

    /** Parte con un producto y una unidad (la que se acaba de escanear). */
    public static function empezar(int $insumoId): void
    {
        Session::put(self::CLAVE, ['insumo_id' => $insumoId, 'cantidad' => 1]);
    }

    /** @return array{insumo_id: int, cantidad: int}|null */
    public static function actual(): ?array
    {
        $retiro = Session::get(self::CLAVE);

        return is_array($retiro) ? $retiro : null;
    }

    public static function cambiarCantidad(int $cantidad): void
    {
        $retiro = self::actual();

        if ($retiro !== null) {
            Session::put(self::CLAVE, ['cantidad' => $cantidad] + $retiro);
        }
    }

    public static function terminar(): void
    {
        Session::forget(self::CLAVE);
    }
}
