<?php

declare(strict_types=1);

namespace App\Modules\Usuarios;

use App\Core\Database;

defined('APP_ROOT') || exit;

/**
 * Acceso a la tabla `usuarios`.
 */
final class UsuarioRepository
{
    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        return Database::fetch('SELECT * FROM usuarios WHERE id = ?', [$id]);
    }

    /**
     * El correo se compara sin distinguir mayúsculas (lo hace la collation de la columna).
     *
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        return Database::fetch('SELECT * FROM usuarios WHERE email = ?', [$email]);
    }

    public function create(string $nombre, string $email, string $passwordHash, string $perfil): int
    {
        return Database::insert(
            'INSERT INTO usuarios (nombre, email, password_hash, perfil) VALUES (?, ?, ?, ?)',
            [$nombre, $email, $passwordHash, $perfil]
        );
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        Database::execute('UPDATE usuarios SET password_hash = ? WHERE id = ?', [$passwordHash, $id]);
    }

    public function registrarAcceso(int $id): void
    {
        Database::execute('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?', [$id]);
    }
}
