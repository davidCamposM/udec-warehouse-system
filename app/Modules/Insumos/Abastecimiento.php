<?php

declare(strict_types=1);

namespace App\Modules\Insumos;

use App\Core\Session;

defined('APP_ROOT') || exit;

/**
 * La lista de lo que va llegando en «Ingresar abastecimiento»: cada producto con su cantidad (en el orden en que se
 * agregaron). Vive en la sesión, así no se pierde si la persona recarga la página.
 */
final class Abastecimiento
{
    private const CLAVE = 'abastecimiento-lista';

    /** @return array<int, int> insumo_id => cantidad */
    public static function productos(): array
    {
        $productos = Session::get(self::CLAVE);

        return is_array($productos) ? $productos : [];
    }

    /** Agrega un producto a la lista; si ya estaba, le suma la cantidad (hasta el máximo de una operación). */
    public static function sumar(int $insumoId, int $cantidad): void
    {
        $productos = self::productos();
        $productos[$insumoId] = min(($productos[$insumoId] ?? 0) + $cantidad, StockService::MAXIMO);
        Session::put(self::CLAVE, $productos);
    }

    /** Deja la cantidad de un producto de la lista; con 0, lo quita. */
    public static function cantidad(int $insumoId, int $cantidad): void
    {
        $productos = self::productos();
        if (!isset($productos[$insumoId])) {
            return;
        }
        if ($cantidad > 0) {
            $productos[$insumoId] = $cantidad;
        } else {
            unset($productos[$insumoId]);
        }
        Session::put(self::CLAVE, $productos);
    }

    public static function terminar(): void
    {
        Session::forget(self::CLAVE);
    }
}
