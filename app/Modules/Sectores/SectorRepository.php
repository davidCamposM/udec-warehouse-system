<?php

declare(strict_types=1);

namespace App\Modules\Sectores;

use App\Core\Database;

defined('APP_ROOT') || exit;

final class SectorRepository
{
    /** @return list<array<string, mixed>> todos los sectores, con cuántos productos activos hay en cada uno */
    public function todos(): array
    {
        return Database::fetchAll(
            'SELECT s.id, s.codigo, s.nombre, s.activo,
                    (SELECT COUNT(*) FROM insumos i WHERE i.sector_id = s.id AND i.activo = 1) AS productos
               FROM sectores s
              ORDER BY s.activo DESC, s.nombre'
        );
    }

    /** @return list<array<string, mixed>> sectores donde se puede guardar */
    public function activos(): array
    {
        return Database::fetchAll('SELECT id, codigo, nombre FROM sectores WHERE activo = 1 ORDER BY nombre');
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        return Database::fetch(
            'SELECT s.id, s.codigo, s.nombre, s.activo,
                    (SELECT COUNT(*) FROM insumos i WHERE i.sector_id = s.id AND i.activo = 1) AS productos
               FROM sectores s
              WHERE s.id = ?',
            [$id]
        );
    }

    /** @return array<string, mixed>|null el sector de ese código (sin distinguir mayúsculas), esté activo o no */
    public function findPorCodigo(string $codigo): ?array
    {
        return Database::fetch('SELECT id, codigo, nombre, activo FROM sectores WHERE codigo = ?', [$codigo]);
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

    public function rename(int $id, string $nombre): void
    {
        Database::execute('UPDATE sectores SET nombre = ? WHERE id = ?', [$nombre, $id]);
    }

    public function setActivo(int $id, bool $activo): void
    {
        Database::execute('UPDATE sectores SET activo = ? WHERE id = ?', [(int) $activo, $id]);
    }

    /** Id de otro sector con ese nombre (sin distinguir mayúsculas ni tildes), o null. */
    public function idPorNombre(string $nombre, ?int $excepto): ?int
    {
        $id = Database::fetchValue('SELECT id FROM sectores WHERE nombre = ? AND id <> ? LIMIT 1', [$nombre, $excepto ?? 0]);

        return $id === null ? null : (int) $id;
    }
}
