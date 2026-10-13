<?php

declare(strict_types=1);

namespace App\Modules\Retiro;

use App\Core\Database;

defined('APP_ROOT') || exit;

final class EstacionRepository
{
    /** @return list<array<string, mixed>> los equipos habilitados, con quién los habilitó */
    public function activas(): array
    {
        return Database::fetchAll(
            'SELECT e.id, e.nombre, e.ultimo_uso, e.creado_en, u.nombre AS autorizada_por
               FROM estaciones e
               JOIN usuarios u ON u.id = e.autorizada_por
              WHERE e.activa = 1
              ORDER BY e.creado_en'
        );
    }

    /** @return array<string, mixed>|null el equipo activo con ese token (guardado como SHA-256) */
    public function findActivaPorHash(string $hash): ?array
    {
        return Database::fetch('SELECT id, nombre FROM estaciones WHERE token_hash = ? AND activa = 1', [$hash]);
    }

    public function create(string $nombre, string $hash, int $usuarioId): int
    {
        return Database::insert(
            'INSERT INTO estaciones (nombre, token_hash, autorizada_por) VALUES (?, ?, ?)',
            [$nombre, $hash, $usuarioId]
        );
    }

    public function desactivar(int $id): bool
    {
        return Database::execute('UPDATE estaciones SET activa = 0 WHERE id = ? AND activa = 1', [$id]) > 0;
    }

    public function registrarUso(int $id): void
    {
        Database::execute('UPDATE estaciones SET ultimo_uso = NOW() WHERE id = ?', [$id]);
    }
}
