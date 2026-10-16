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
        LEFT JOIN usuarios us ON us.id = m.usuario_id
        LEFT JOIN sectores se ON se.id = mi.sector_id';

    /**
     * Una página de movimientos, del más reciente al más antiguo (o, para la descarga, $porProducto: agrupados por
     * producto y, dentro de cada uno, del más antiguo al más reciente).
     *
     * @return list<array<string, mixed>>
     */
    public function buscar(string $periodo, ?string $tipo, int $limite, int $desde, bool $porProducto = false): array
    {
        $orden = $porProducto ? 'i.nombre, i.id, m.fecha, m.id, mi.id' : 'm.fecha DESC, m.id DESC, mi.id';

        [$condicion, $parametros] = self::condicion($periodo, $tipo);

        return Database::fetchAll(
            'SELECT m.id, m.tipo, m.fecha, m.motivo, m.reserva, mi.cantidad, mi.stock_resultante,
                    i.id AS insumo_id, i.codigo, i.nombre, i.activo AS insumo_activo,
                    us.nombre AS quien, se.nombre AS sector, se.codigo AS sector_codigo
               FROM ' . self::DESDE . "
              WHERE {$condicion}
              ORDER BY {$orden}
              LIMIT " . max(1, $limite) . ' OFFSET ' . max(0, $desde),
            $parametros
        );
    }

    /**
     * Por cada producto que se movió en el período (y del tipo, si se eligió uno): las unidades que entraron, las que
     * salieron, el neto de los ajustes, cuántas hay ahora y el neto de todo lo que se movió desde el comienzo del
     * período (sin importar el tipo), para saber cuántas había al comenzar. Por categoría y nombre.
     *
     * @return list<array<string, mixed>>
     */
    public function porProducto(string $periodo, ?string $tipo): array
    {
        [$condicion, $parametros] = self::condicion($periodo, $tipo);
        $desdeCuando = self::PERIODOS[$periodo] ?? null;

        return Database::fetchAll(
            "SELECT i.id, i.codigo, i.nombre, i.stock_actual,
                    COALESCE(cp.nombre, c.nombre, 'Sin categoría') AS categoria,
                    COALESCE(SUM(CASE WHEN m.tipo = 'entrada' THEN ABS(mi.cantidad) END), 0) AS entraron,
                    COALESCE(SUM(CASE WHEN m.tipo = 'salida' THEN ABS(mi.cantidad) END), 0) AS salieron,
                    COALESCE(SUM(CASE WHEN m.tipo = 'ajuste' THEN mi.cantidad END), 0) AS ajustes,
                    (SELECT COALESCE(SUM(mi2.cantidad), 0)
                       FROM movimiento_items mi2 JOIN movimientos m2 ON m2.id = mi2.movimiento_id
                      WHERE mi2.insumo_id = i.id" . ($desdeCuando !== null ? " AND m2.fecha >= {$desdeCuando}" : '') . ') AS neto
               FROM ' . self::DESDE . "
               LEFT JOIN categorias c ON c.id = i.categoria_id
               LEFT JOIN categorias cp ON cp.id = c.padre_id
              WHERE {$condicion}
              GROUP BY i.id, i.codigo, i.nombre, i.stock_actual, categoria
              ORDER BY categoria = 'Sin categoría', categoria, i.nombre",
            $parametros
        );
    }

    /** La fecha en que empieza el período (aaaa-mm-dd); null en «todo». */
    public function inicioDelPeriodo(string $periodo): ?string
    {
        $desde = self::PERIODOS[$periodo] ?? null;

        return $desde === null ? null : (string) Database::fetchValue("SELECT DATE({$desde})");
    }

    public function contar(string $periodo, ?string $tipo): int
    {
        [$condicion, $parametros] = self::condicion($periodo, $tipo);

        return (int) Database::fetchValue('SELECT COUNT(*) FROM ' . self::DESDE . " WHERE {$condicion}", $parametros);
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
