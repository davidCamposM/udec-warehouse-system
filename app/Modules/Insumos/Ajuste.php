<?php

declare(strict_types=1);

namespace App\Modules\Insumos;

use App\Core\Session;

defined('APP_ROOT') || exit;

/**
 * El ajuste en curso, paso a paso: el producto, lo que se contó, con qué stock a la vista y por qué. Vive en la sesión
 * hasta que se confirma.
 */
final class Ajuste
{
    private const CLAVE = 'ajustar_cantidad';

    public static function empezar(int $insumoId): void
    {
        Session::put(self::CLAVE, ['insumo_id' => $insumoId]);
    }

    /** @return array{insumo_id: int, contado?: int, stock_visto?: int, motivo?: string, detalle?: string}|null */
    public static function actual(): ?array
    {
        $ajuste = Session::get(self::CLAVE);

        return is_array($ajuste) ? $ajuste : null;
    }

    public static function contar(int $contado, int $stockVisto, string $motivo, string $detalle): void
    {
        $ajuste = self::actual();

        if ($ajuste !== null) {
            Session::put(self::CLAVE, [
                'contado' => $contado,
                'stock_visto' => $stockVisto,
                'motivo' => $motivo,
                'detalle' => $detalle,
            ] + $ajuste);
        }
    }

    /** El stock cambió mientras se contaba: se confirma de nuevo contra el stock nuevo. */
    public static function actualizarStockVisto(int $stock): void
    {
        $ajuste = self::actual();

        if ($ajuste !== null) {
            Session::put(self::CLAVE, ['stock_visto' => $stock] + $ajuste);
        }
    }

    public static function terminar(): void
    {
        Session::forget(self::CLAVE);
    }
}
