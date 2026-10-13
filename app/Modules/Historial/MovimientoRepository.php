<?php

declare(strict_types=1);

namespace App\Modules\Historial;

use App\Core\Database;

defined('APP_ROOT') || exit;

/**
 * Lectura de los movimientos de stock de insumos (entradas, salidas y ajustes), una fila por producto movido.
 * Los filtros llegan ya validados por MovimientoService: el período y el tipo salen de listas fijas.
 */
final class MovimientoRepository
{
    /** Desde cuándo, según el período. Null: sin límite. */
    public const PERIODOS = [
        'hoy' => 'CURDATE()',
        'semana' => 'CURDATE() - INTERVAL 6 DAY',
        'mes' => "DATE_FORMAT(CURDATE(), '%Y-%m-01')",
        'todo' => null,
    ];

    public const TIPOS = ['entrada', 'salida', 'ajuste'];

    private const DESDE = 'movimiento_items mi
        JOIN movimientos m ON m.id = mi.movimiento_id
        JOIN insumos i ON i.id = mi.insumo_id
        LEFT JOIN personas pe ON pe.id = m.persona_id
        LEFT JOIN usuarios us ON us.id = m.usuario_id
        LEFT JOIN sectores se ON se.id = mi.sector_id';

    /**
     * Una página de movimientos, del más reciente al más antiguo.
     *
     * @return list<array<string, mixed>>
     */
    public function buscar(string $periodo, ?string $tipo, int $limite, int $desde): array
    {
        [$condicion, $parametros] = self::condicion($periodo, $tipo);

        return Database::fetchAll(
            'SELECT m.id, m.tipo, m.fecha, m.motivo, mi.cantidad, mi.stock_resultante,
                    i.id AS insumo_id, i.codigo, i.nombre,
                    COALESCE(pe.nombre, us.nombre) AS quien, m.persona_id IS NOT NULL AS en_punto,
                    se.nombre AS sector
               FROM ' . self::DESDE . "
              WHERE {$condicion}
              ORDER BY m.fecha DESC, m.id DESC, mi.id
              LIMIT " . max(1, $limite) . ' OFFSET ' . max(0, $desde),
            $parametros
        );
    }

    public function contar(string $periodo, ?string $tipo): int
    {
        [$condicion, $parametros] = self::condicion($periodo, $tipo);

        return (int) Database::fetchValue('SELECT COUNT(*) FROM ' . self::DESDE . " WHERE {$condicion}", $parametros);
    }

    /**
     * Las salidas de hoy por persona: cuántos retiros hizo y cuántas unidades se llevó.
     *
     * @return list<array{quien: string, retiros: int, unidades: int}>
     */
    public function salidasDeHoyPorPersona(): array
    {
        return Database::fetchAll(
            "SELECT COALESCE(pe.nombre, us.nombre, 'Sin nombre') AS quien,
                    COUNT(DISTINCT m.id) AS retiros, SUM(-mi.cantidad) AS unidades
               FROM " . self::DESDE . "
              WHERE m.tipo = 'salida' AND m.fecha >= CURDATE()
              GROUP BY quien
              ORDER BY unidades DESC, quien"
        );
    }

    /** @return array{0: string, 1: list<string>} */
    private static function condicion(string $periodo, ?string $tipo): array
    {
        $partes = ['1 = 1'];
        $parametros = [];

        if ((self::PERIODOS[$periodo] ?? null) !== null) {
            $partes[] = 'm.fecha >= ' . self::PERIODOS[$periodo];
        }
        if ($tipo !== null) {
            $partes[] = 'm.tipo = ?';
            $parametros[] = $tipo;
        }

        return [implode(' AND ', $partes), $parametros];
    }
}
