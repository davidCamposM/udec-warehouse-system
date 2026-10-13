<?php

declare(strict_types=1);

namespace App\Modules\Insumos;

use App\Core\Session;

defined('APP_ROOT') || exit;

/**
 * Lo que se está guardando en bodega, paso a paso: el producto y cuántos llegaron. Vive en la sesión, así no se
 * pierde si la persona recarga la página o va a crear el producto y vuelve.
 */
final class Guardado
{
    private const CLAVE = 'guardar_en_bodega';

    /** Parte con un producto y una unidad (la que se acaba de escanear). */
    public static function empezar(int $insumoId): void
    {
        Session::put(self::CLAVE, ['insumo_id' => $insumoId, 'cantidad' => 1]);
    }

    /** @return array{insumo_id: int, cantidad: int}|null */
    public static function actual(): ?array
    {
        $guardado = Session::get(self::CLAVE);

        return is_array($guardado) ? $guardado : null;
    }

    public static function cambiarCantidad(int $cantidad): void
    {
        $guardado = self::actual();

        if ($guardado !== null) {
            Session::put(self::CLAVE, ['cantidad' => $cantidad] + $guardado);
        }
    }

    public static function terminar(): void
    {
        Session::forget(self::CLAVE);
    }
}
