<?php

declare(strict_types=1);

namespace App\Modules\Panel;

use App\Core\Database;
use App\Modules\Herramientas\SolicitudRepository;
use App\Modules\Insumos\InsumoRepository;

defined('APP_ROOT') || exit;

/**
 * Conteos de solo lectura para los avisos del inicio que no son de insumos (los de insumos los da su
 * propio servicio, con las mismas reglas que la lista). Las reglas que modifican estos datos siguen en
 * el servicio de cada módulo.
 */
final class PanelRepository
{
    /** Retiros de insumos registrados hoy (cada «Retirar producto» confirmado cuenta como uno). */
    public function contarSalidasDeHoy(): int
    {
        return (int) Database::fetchValue("SELECT COUNT(*) FROM movimientos WHERE tipo = 'salida' AND fecha >= CURDATE()");
    }

    /** Herramientas prestadas cuya fecha de recepción ya pasó. */
    public function contarPrestamosAtrasados(): int
    {
        return (int) Database::fetchValue(
            "SELECT COUNT(*) FROM solicitudes WHERE estado = 'prestada' AND fecha_devolucion < CURDATE()"
        );
    }

    /** Herramientas que volvieron con daños o no volvieron, y aún no se resuelven (una por línea de solicitud). */
    public function contarHerramientasPorResolver(): int
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM solicitud_items si WHERE ' . SolicitudRepository::PROBLEMA
        );
    }

    public function contarSolicitudesPendientes(): int
    {
        return (int) Database::fetchValue("SELECT COUNT(*) FROM solicitudes WHERE estado = 'pendiente'");
    }

    /**
     * Los insumos de consumo activos (sin el equipo de la Práctica de Invierno) según lo que queda: sin unidades, por
     * reponer (quedan, pero llegaron a su mínimo) y con stock; cada uno cuenta en un solo grupo. Más las unidades en bodega y las que entraron y salieron este mes.
     *
     * @return array{con_stock: int, por_reponer: int, sin_stock: int, unidades: int, salieron_mes: int, entraron_mes: int}
     */
    public function resumenInsumos(): array
    {
        $productos = Database::fetch(
            'SELECT COALESCE(SUM(i.stock_actual <= 0), 0) AS sin_stock,
                    COALESCE(SUM(i.stock_actual > 0 AND i.stock_minimo > 0 AND i.stock_actual <= i.stock_minimo), 0) AS por_reponer,
                    COALESCE(SUM(i.stock_actual > 0 AND (i.stock_minimo = 0 OR i.stock_actual > i.stock_minimo)), 0) AS con_stock,
                    COALESCE(SUM(GREATEST(i.stock_actual, 0)), 0) AS unidades
               FROM ' . self::INSUMOS_CON_CATEGORIA . '
              WHERE i.activo = 1 AND ' . InsumoRepository::SOLO_CONSUMO
        ) ?? [];
        $mes = Database::fetch(
            "SELECT COALESCE(SUM(CASE WHEN m.tipo = 'salida' THEN ABS(mi.cantidad) END), 0) AS salieron_mes,
                    COALESCE(SUM(CASE WHEN m.tipo = 'entrada' THEN ABS(mi.cantidad) END), 0) AS entraron_mes
               FROM movimientos m JOIN movimiento_items mi ON mi.movimiento_id = m.id
              WHERE m.fecha >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
        ) ?? [];

        return array_map('intval', $productos + $mes) + [
            'con_stock' => 0, 'por_reponer' => 0, 'sin_stock' => 0, 'unidades' => 0, 'salieron_mes' => 0, 'entraron_mes' => 0,
        ];
    }

    /**
     * Las herramientas activas del catálogo según dónde están: en bodega (ninguna unidad prestada), prestadas (alguna
     * unidad fuera, a tiempo) y atrasadas (alguna unidad en un préstamo que debió volver). Cada una cuenta en un solo
     * grupo. Más las unidades prestadas ahora.
     *
     * @return array{en_bodega: int, prestadas: int, atrasadas: int, unidades_prestadas: int}
     */
    public function resumenHerramientas(): array
    {
        $fila = Database::fetch(
            "SELECT COALESCE(SUM(t.atrasada = 1), 0) AS atrasadas,
                    COALESCE(SUM(t.atrasada = 0 AND t.prestadas > 0), 0) AS prestadas,
                    COALESCE(SUM(t.prestadas = 0), 0) AS en_bodega,
                    COALESCE(SUM(t.prestadas), 0) AS unidades_prestadas
               FROM (SELECT h.id,
                            COALESCE(SUM(si.cantidad), 0) AS prestadas,
                            COALESCE(MAX(s.fecha_devolucion < CURDATE()), 0) AS atrasada
                       FROM herramientas h
                       LEFT JOIN solicitud_items si ON si.herramienta_id = h.id
                            AND si.solicitud_id IN (SELECT id FROM solicitudes WHERE estado = 'prestada')
                       LEFT JOIN solicitudes s ON s.id = si.solicitud_id
                      WHERE h.activo = 1
                      GROUP BY h.id) t"
        ) ?? [];

        return array_map('intval', $fila) + ['en_bodega' => 0, 'prestadas' => 0, 'atrasadas' => 0, 'unidades_prestadas' => 0];
    }

    /** La categoría principal de un producto (o la suya, si no tiene padre); sin categoría: «Sin categoría». */
    private const CATEGORIA_PRINCIPAL = "COALESCE(cp.nombre, c.nombre, 'Sin categoría')";

    private const INSUMOS_CON_CATEGORIA = 'insumos i
        LEFT JOIN categorias c ON c.id = i.categoria_id
        LEFT JOIN categorias cp ON cp.id = c.padre_id
        LEFT JOIN sectores s ON s.id = i.sector_id';

    /**
     * Por cada categoría principal, cuántos productos activos hay con stock, por reponer y sin stock (los mismos grupos
     * de resumenInsumos), por nombre de categoría.
     *
     * @return list<array{categoria: string, con_stock: int, por_reponer: int, sin_stock: int}>
     */
    public function insumosPorCategoria(): array
    {
        $filas = Database::fetchAll(
            'SELECT ' . self::CATEGORIA_PRINCIPAL . ' AS categoria,
                    SUM(i.stock_actual > 0 AND (i.stock_minimo = 0 OR i.stock_actual > i.stock_minimo)) AS con_stock,
                    SUM(i.stock_actual > 0 AND i.stock_minimo > 0 AND i.stock_actual <= i.stock_minimo) AS por_reponer,
                    SUM(i.stock_actual <= 0) AS sin_stock
               FROM ' . self::INSUMOS_CON_CATEGORIA . '
              WHERE i.activo = 1 AND ' . InsumoRepository::SOLO_CONSUMO . '
              GROUP BY categoria
              ORDER BY categoria = \'Sin categoría\', categoria'
        );

        return array_map(static fn (array $fila): array => [
            'categoria' => (string) $fila['categoria'],
            'con_stock' => (int) $fila['con_stock'],
            'por_reponer' => (int) $fila['por_reponer'],
            'sin_stock' => (int) $fila['sin_stock'],
        ], $filas);
    }

    /**
     * Los productos activos de un grupo, para el documento del estado de la bodega: 'por_reponer' (quedan, pero
     * llegaron a su mínimo) o 'sin_stock'. Por categoría y nombre.
     *
     * @param 'por_reponer'|'sin_stock' $grupo
     * @return list<array<string, mixed>>
     */
    public function productos(string $grupo): array
    {
        $condicion = $grupo === 'por_reponer'
            ? 'i.stock_actual > 0 AND i.stock_minimo > 0 AND i.stock_actual <= i.stock_minimo'
            : 'i.stock_actual <= 0';

        return Database::fetchAll(
            'SELECT i.codigo, i.nombre, i.stock_actual, i.stock_minimo, ' . self::CATEGORIA_PRINCIPAL . ' AS categoria,
                    s.codigo AS sector_codigo
               FROM ' . self::INSUMOS_CON_CATEGORIA . "
              WHERE i.activo = 1 AND {$condicion} AND " . InsumoRepository::SOLO_CONSUMO . "
              ORDER BY categoria, i.nombre"
        );
    }

    /**
     * Los préstamos de herramientas en curso (entregados y aún no recibidos), del que vence primero al último, con el
     * docente y lo que se llevó («2 × Hipsómetro, 1 × Brújula»).
     *
     * @return list<array<string, mixed>>
     */
    public function prestamosEnCurso(): array
    {
        return Database::fetchAll(
            "SELECT s.id, u.nombre AS docente, s.entregada_en, s.fecha_devolucion,
                    s.fecha_devolucion < CURDATE() AS atrasado,
                    (SELECT GROUP_CONCAT(CONCAT(si.cantidad, ' × ', h.nombre) ORDER BY h.nombre SEPARATOR ', ')
                       FROM solicitud_items si JOIN herramientas h ON h.id = si.herramienta_id
                      WHERE si.solicitud_id = s.id) AS herramientas
               FROM solicitudes s JOIN usuarios u ON u.id = s.docente_id
              WHERE s.estado = 'prestada'
              ORDER BY s.fecha_devolucion, s.id"
        );
    }

    /** Préstamos atrasados del docente: lo que pidió, le entregaron y no ha vuelto a tiempo. */
    public function contarPrestamosAtrasadosDe(int $usuarioId): int
    {
        return (int) Database::fetchValue(
            "SELECT COUNT(*) FROM solicitudes WHERE docente_id = ? AND estado = 'prestada' AND fecha_devolucion < CURDATE()",
            [$usuarioId]
        );
    }
}
