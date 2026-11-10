<?php

declare(strict_types=1);

namespace App\Modules\Sectores;

use App\Core\Database;
use App\Modules\Insumos\InsumoRepository;

defined('APP_ROOT') || exit;

final class SectorRepository
{
    /*
     * Para las personas, un sector eliminado ya no existe: todas las lecturas son solo de los que no se eliminaron (por
     * dentro, eliminar solo desactiva y el historial conserva dónde quedó cada cosa).
     */

    /** @return list<array<string, mixed>> los sectores, por nombre, con cuántos productos hay en cada uno */
    public function activos(): array
    {
        return Database::fetchAll(
            'SELECT s.id, s.codigo, s.nombre, s.activo,
                    (SELECT COUNT(*) FROM insumos i WHERE i.sector_id = s.id AND i.activo = 1) AS productos
               FROM sectores s
              WHERE s.activo = 1
              ORDER BY s.nombre'
        );
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        return Database::fetch(
            'SELECT s.id, s.codigo, s.nombre, s.activo,
                    (SELECT COUNT(*) FROM insumos i WHERE i.sector_id = s.id AND i.activo = 1) AS productos
               FROM sectores s
              WHERE s.id = ? AND s.activo = 1',
            [$id]
        );
    }

    /** @return array<string, mixed>|null el sector de ese código (sin distinguir mayúsculas) */
    public function findPorCodigo(string $codigo): ?array
    {
        return Database::fetch('SELECT id, codigo, nombre, activo FROM sectores WHERE codigo = ? AND activo = 1', [$codigo]);
    }

    /**
     * Siguiente código libre (SEC001…). Se llama dentro de una transacción: el FOR UPDATE hace que dos personas
     * creando sectores al mismo tiempo no reciban el mismo código.
     */
    public function siguienteCodigo(): string
    {
        $ultimo = (int) Database::fetchValue(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(codigo, 4) AS UNSIGNED)), 0) FROM sectores WHERE codigo LIKE 'SEC%' FOR UPDATE"
        );

        return sprintf('SEC%03d', $ultimo + 1);
    }

    public function create(string $codigo, string $nombre): int
    {
        return Database::insert('INSERT INTO sectores (codigo, nombre) VALUES (?, ?)', [$codigo, $nombre]);
    }

    public function update(int $id, string $codigo, string $nombre): void
    {
        Database::execute('UPDATE sectores SET codigo = ?, nombre = ? WHERE id = ?', [$codigo, $nombre, $id]);
    }

    /**
     * «Eliminar» un sector: por dentro solo se desactiva (el historial conserva dónde quedó cada cosa), y los productos
     * que estaban ahí quedan sin ubicación.
     */
    public function eliminar(int $id): void
    {
        Database::execute('UPDATE sectores SET activo = 0 WHERE id = ?', [$id]);
        Database::execute('UPDATE insumos SET sector_id = NULL WHERE sector_id = ?', [$id]);
    }

    /**
     * Si un sector eliminado tiene ese nombre, se le cambia por dentro («Estante A (eliminado 7)»), para usarlo en otro:
     * el nombre no se puede repetir y, para las personas, ese sector ya no existe.
     */
    public function liberarNombre(string $nombre): void
    {
        Database::execute(
            "UPDATE sectores SET nombre = LEFT(CONCAT(nombre, ' (eliminado ', id, ')'), 80) WHERE nombre = ? AND activo = 0",
            [$nombre]
        );
    }

    /**
     * Si un sector eliminado tiene ese código, se le cambia por dentro («~7»), para usarlo en otro: el código no se
     * puede repetir y, para las personas, ese sector ya no existe. Ningún código escaneado empieza con «~».
     */
    public function liberarCodigo(string $codigo): void
    {
        Database::execute("UPDATE sectores SET codigo = CONCAT('~', id) WHERE codigo = ? AND activo = 0", [$codigo]);
    }

    /** Id de otro sector con ese código (sin distinguir mayúsculas), o null. */
    public function idPorCodigo(string $codigo, ?int $excepto): ?int
    {
        $id = Database::fetchValue(
            'SELECT id FROM sectores WHERE codigo = ? AND id <> ? AND activo = 1 LIMIT 1',
            [$codigo, $excepto ?? 0]
        );

        return $id === null ? null : (int) $id;
    }

    /**
     * El producto que ya usa ese código (su código interno, el UdeC o un código de barras), o null: un código lleva
     * siempre a una sola cosa (RF17), así que un sector no puede tener el código de un producto.
     */
    public function productoConCodigo(string $codigo): ?string
    {
        $nombre = Database::fetchValue(
            'SELECT i.nombre FROM insumo_codigos ic JOIN insumos i ON i.id = ic.insumo_id
              WHERE ic.codigo = ? AND ic.activo = 1 AND i.activo = 1
             UNION ALL
             SELECT nombre FROM insumos
              WHERE (codigo = ? OR codigo_udec = ? OR (sku = ? AND sku <> ?)) AND activo = 1
             LIMIT 1',
            [$codigo, $codigo, $codigo, $codigo, InsumoRepository::SIN_CODIGO_BARRAS]
        );

        return $nombre === null ? null : (string) $nombre;
    }

    /** Id de otro sector con ese nombre (sin distinguir mayúsculas ni tildes), o null. */
    public function idPorNombre(string $nombre, ?int $excepto): ?int
    {
        $id = Database::fetchValue(
            'SELECT id FROM sectores WHERE nombre = ? AND id <> ? AND activo = 1 LIMIT 1',
            [$nombre, $excepto ?? 0]
        );

        return $id === null ? null : (int) $id;
    }
}
