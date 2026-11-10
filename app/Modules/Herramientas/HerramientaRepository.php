<?php

declare(strict_types=1);

namespace App\Modules\Herramientas;

use App\Core\Database;

defined('APP_ROOT') || exit;

/** Catálogo de herramientas: cada tipo («Hipsómetro») con cuántas unidades tiene la bodega. */
final class HerramientaRepository
{
    /** Clave de la categoría principal del equipo de la Práctica de Invierno (migración 017). */
    public const PRACTICA_INVIERNO = 'practica-invierno';

    /**
     * Columnas de cada herramienta:
     * - su categoría principal (la de arriba, si está en una subcategoría) y su subcategoría;
     * - cuántas unidades tiene la bodega;
     * - cuántas están prestadas ahora (en solicitudes prestadas) y cuántas de ellas debieron volver ya (atrasadas);
     * - cuántas volvieron con daños o no volvieron y nadie lo ha resuelto (por resolver);
     * - cuántas solicitudes por entregar la piden.
     */
    private const COLUMNAS = "h.id, h.nombre, h.activo, h.categoria_id, h.unidades,
        COALESCE(cp.nombre, c.nombre) AS categoria, IF(cp.id IS NULL, NULL, c.nombre) AS subcategoria,
        COALESCE(cp.clave, c.clave) AS clave,
        (SELECT COALESCE(SUM(si.cantidad), 0)
           FROM solicitud_items si JOIN solicitudes s ON s.id = si.solicitud_id
          WHERE si.herramienta_id = h.id AND s.estado = 'prestada') AS prestadas,
        (SELECT COALESCE(SUM(si.cantidad), 0)
           FROM solicitud_items si JOIN solicitudes s ON s.id = si.solicitud_id
          WHERE si.herramienta_id = h.id AND s.estado = 'prestada' AND s.fecha_devolucion < CURDATE()) AS atrasadas,
        (SELECT COALESCE(SUM(" . SolicitudRepository::AFECTADAS . "), 0)
           FROM solicitud_items si
          WHERE si.herramienta_id = h.id AND " . SolicitudRepository::PROBLEMA . ") AS por_resolver,
        (SELECT COUNT(*)
           FROM solicitud_items si JOIN solicitudes s ON s.id = si.solicitud_id
          WHERE si.herramienta_id = h.id AND s.estado = 'pendiente') AS pedidas";

    private const DESDE = 'herramientas h
        LEFT JOIN categorias c ON c.id = h.categoria_id
        LEFT JOIN categorias cp ON cp.id = c.padre_id';

    /**
     * Las activas, por categoría, subcategoría y nombre (las sin categoría al final). El equipo de la Práctica de
     * Invierno va aparte (su sección está en preparación): no está en el catálogo, ni se pide, ni cuenta en los gráficos,
     * salvo con $conInvierno.
     *
     * @return list<array<string, mixed>>
     */
    public function activas(bool $conInvierno = false): array
    {
        return Database::fetchAll(
            'SELECT ' . self::COLUMNAS . ' FROM ' . self::DESDE . '
              WHERE h.activo = 1' . ($conInvierno ? '' : ' AND COALESCE(cp.clave, c.clave, \'\') <> ?') . '
              ORDER BY categoria IS NULL, categoria, subcategoria, h.nombre',
            $conInvierno ? [] : [self::PRACTICA_INVIERNO]
        );
    }

    /**
     * @return array<string, mixed>|null solo si no está eliminada: para las personas, una herramienta eliminada no
     *                                   existe (los préstamos antiguos la siguen mostrando por su cuenta)
     */
    public function findById(int $id): ?array
    {
        return Database::fetch('SELECT ' . self::COLUMNAS . ' FROM ' . self::DESDE . ' WHERE h.id = ? AND h.activo = 1', [$id]);
    }

    /**
     * Varias herramientas, bloqueadas hasta que termine la transacción: así nadie más presta las mismas unidades a la
     * vez.
     *
     * @param list<int> $ids
     * @return array<int, array<string, mixed>> id => herramienta
     */
    public function bloquear(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $marcas = implode(', ', array_fill(0, count($ids), '?'));
        Database::fetchAll("SELECT id FROM herramientas WHERE id IN ({$marcas}) FOR UPDATE", $ids);
        $filas = Database::fetchAll('SELECT ' . self::COLUMNAS . ' FROM ' . self::DESDE . " WHERE h.id IN ({$marcas})", $ids);

        return array_column($filas, null, 'id');
    }

    /**
     * Si ya hay otra herramienta con ese nombre (sin distinguir mayúsculas ni tildes, por la intercalación). Las
     * eliminadas no cuentan: su nombre queda libre.
     */
    public function nombreEnUso(string $nombre, ?int $exceptoId = null): bool
    {
        return Database::fetchValue(
            'SELECT 1 FROM herramientas WHERE nombre = ? AND id <> ? AND activo = 1',
            [$nombre, $exceptoId ?? 0]
        ) !== null;
    }

    /**
     * Las categorías donde puede ir una herramienta: las principales sin subcategorías y las subcategorías (con el
     * nombre de su principal), por principal y nombre. Sin las de la Práctica de Invierno (va aparte).
     *
     * @return list<array{id: int, nombre: string, padre: ?string}>
     */
    public function categorias(): array
    {
        return Database::fetchAll(
            "SELECT c.id, c.nombre, p.nombre AS padre
               FROM categorias c LEFT JOIN categorias p ON p.id = c.padre_id
              WHERE c.ambito = 'herramienta' AND c.activo = 1
                AND NOT EXISTS (SELECT 1 FROM categorias h WHERE h.padre_id = c.id AND h.activo = 1)
                AND COALESCE(p.clave, c.clave, '') <> ?
              ORDER BY COALESCE(p.nombre, c.nombre), p.nombre IS NOT NULL, c.nombre",
            [self::PRACTICA_INVIERNO]
        );
    }

    public function create(string $nombre, ?int $categoriaId, int $unidades): int
    {
        return Database::insert(
            'INSERT INTO herramientas (nombre, categoria_id, unidades) VALUES (?, ?, ?)',
            [$nombre, $categoriaId, $unidades]
        );
    }

    public function update(int $id, string $nombre, ?int $categoriaId, int $unidades): void
    {
        Database::execute(
            'UPDATE herramientas SET nombre = ?, categoria_id = ?, unidades = ? WHERE id = ?',
            [$nombre, $categoriaId, $unidades, $id]
        );
    }

    /** Unidades que se dieron de baja: salen de las que tiene la bodega. */
    public function descontarUnidades(int $id, int $cuantas): void
    {
        Database::execute('UPDATE herramientas SET unidades = GREATEST(0, unidades - ?) WHERE id = ?', [$cuantas, $id]);
    }

    /** «Eliminar»: por dentro solo se desactiva, para conservar los préstamos en que aparece. */
    public function eliminar(int $id): void
    {
        Database::execute('UPDATE herramientas SET activo = 0 WHERE id = ?', [$id]);
    }
}
