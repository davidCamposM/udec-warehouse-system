<?php

declare(strict_types=1);

namespace App\Modules\Insumos;

use App\Core\Database;

defined('APP_ROOT') || exit;

/** Movimientos de stock. Solo lo usa StockService, dentro de sus transacciones. */
final class StockRepository
{
    /**
     * El producto, bloqueado hasta que termine la transacción: si dos personas mueven el mismo producto a la vez,
     * la segunda espera y parte del stock que dejó la primera.
     *
     * @return array<string, mixed>|null
     */
    public function bloquearInsumo(int $id): ?array
    {
        return Database::fetch('SELECT id, codigo, nombre, stock_actual, activo FROM insumos WHERE id = ? FOR UPDATE', [$id]);
    }

    /**
     * @param 'entrada'|'salida'|'ajuste' $tipo
     * @param ?string $reserva el N° de reserva SAP (solo en entradas antiguas de abastecimiento)
     */
    public function crearMovimiento(string $tipo, int $usuarioId, ?string $reserva = null, ?string $motivo = null): int
    {
        return Database::insert(
            'INSERT INTO movimientos (tipo, usuario_id, reserva, motivo) VALUES (?, ?, ?, ?)',
            [$tipo, $usuarioId, $reserva, $motivo]
        );
    }

    public function agregarItem(int $movimientoId, int $insumoId, int $cantidad, int $stockResultante, ?int $sectorId): void
    {
        Database::insert(
            'INSERT INTO movimiento_items (movimiento_id, insumo_id, cantidad, stock_resultante, sector_id) VALUES (?, ?, ?, ?, ?)',
            [$movimientoId, $insumoId, $cantidad, $stockResultante, $sectorId]
        );
    }

    /** Deja el stock nuevo y, si se indica, el sector donde quedó el producto. */
    public function actualizarStock(int $insumoId, int $stock, ?int $sectorId): void
    {
        Database::execute(
            'UPDATE insumos SET stock_actual = ?, sector_id = COALESCE(?, sector_id) WHERE id = ?',
            [$stock, $sectorId, $insumoId]
        );
    }
}
