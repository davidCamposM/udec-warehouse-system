<?php

declare(strict_types=1);

namespace App\Modules\Insumos;

use App\Core\Session;

defined('APP_ROOT') || exit;

/**
 * La reserva que se está ingresando en «Ingresar abastecimiento», entre una etapa y otra: su número y los productos que
 * llegaron con su cantidad (en el orden en que se agregaron). Vive en la sesión, así no se pierde si la persona recarga
 * la página.
 */
final class Abastecimiento
{
    private const CLAVE = 'abastecimiento';

    public static function empezar(string $reserva): void
    {
        // Cambiar el número no borra lo que ya se agregó.
        Session::put(self::CLAVE, ['reserva' => $reserva, 'productos' => self::actual()['productos'] ?? []]);
    }

    /** @return array{reserva: string, productos: array<int, int>}|null */
    public static function actual(): ?array
    {
        $abastecimiento = Session::get(self::CLAVE);

        return is_array($abastecimiento) ? $abastecimiento : null;
    }

    /** Agrega un producto a la lista; si ya estaba, le suma la cantidad (hasta el máximo de una operación). */
    public static function sumar(int $insumoId, int $cantidad): void
    {
        $actual = self::actual();
        if ($actual !== null) {
            $actual['productos'][$insumoId] = min(($actual['productos'][$insumoId] ?? 0) + $cantidad, StockService::MAXIMO);
            Session::put(self::CLAVE, $actual);
        }
    }

    /** Deja la cantidad de un producto de la lista; con 0, lo quita. */
    public static function cantidad(int $insumoId, int $cantidad): void
    {
        $actual = self::actual();
        if ($actual === null || !isset($actual['productos'][$insumoId])) {
            return;
        }
        if ($cantidad > 0) {
            $actual['productos'][$insumoId] = $cantidad;
        } else {
            unset($actual['productos'][$insumoId]);
        }
        Session::put(self::CLAVE, $actual);
    }

    public static function terminar(): void
    {
        Session::forget(self::CLAVE);
    }
}
