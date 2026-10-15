<?php

declare(strict_types=1);

namespace App\Modules\Herramientas;

use App\Core\Database;

defined('APP_ROOT') || exit;

/** Catálogo de herramientas (los tipos, como «Hipsómetro»; las unidades físicas van aparte). */
final class HerramientaRepository
{
    /**
     * Columnas de cada herramienta: su categoría y cuántas unidades están prestadas ahora (en solicitudes prestadas).
     */
    private const COLUMNAS = "h.id, h.nombre, h.descripcion, h.activo, h.categoria_id, c.nombre AS categoria,
        (SELECT COALESCE(SUM(si.cantidad), 0)
           FROM solicitud_items si JOIN solicitudes s ON s.id = si.solicitud_id
          WHERE si.herramienta_id = h.id AND s.estado = 'prestada') AS prestadas";

    /** @return list<array<string, mixed>> las activas, por categoría y nombre (las sin categoría al final) */
    public function activas(): array
    {
        return Database::fetchAll(
            'SELECT ' . self::COLUMNAS . '
               FROM herramientas h LEFT JOIN categorias c ON c.id = h.categoria_id
              WHERE h.activo = 1
              ORDER BY c.nombre IS NULL, c.nombre, h.nombre'
        );
    }

    /**
     * @return array<string, mixed>|null solo si no está eliminada: para las personas, una herramienta eliminada no
     *                                   existe (los préstamos antiguos la siguen mostrando por su cuenta)
     */
    public function findById(int $id): ?array
    {
        return Database::fetch(
            'SELECT ' . self::COLUMNAS . '
               FROM herramientas h LEFT JOIN categorias c ON c.id = h.categoria_id
              WHERE h.id = ? AND h.activo = 1',
            [$id]
        );
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

    /** @return list<array{id: int, nombre: string}> las categorías de herramientas activas, por nombre */
    public function categorias(): array
    {
        return Database::fetchAll(
            "SELECT id, nombre FROM categorias WHERE ambito = 'herramienta' AND activo = 1 ORDER BY nombre"
        );
    }

    public function create(string $nombre, ?int $categoriaId): int
    {
        return Database::insert('INSERT INTO herramientas (nombre, categoria_id) VALUES (?, ?)', [$nombre, $categoriaId]);
    }

    public function update(int $id, string $nombre, ?int $categoriaId): void
    {
        Database::execute('UPDATE herramientas SET nombre = ?, categoria_id = ? WHERE id = ?', [$nombre, $categoriaId, $id]);
    }

    /** «Eliminar»: por dentro solo se desactiva, para conservar los préstamos en que aparece. */
    public function eliminar(int $id): void
    {
        Database::execute('UPDATE herramientas SET activo = 0 WHERE id = ?', [$id]);
    }
}
