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

    /**
     * Las cuentas activas de esos perfiles, por nombre, con si ya crearon su contraseña.
     *
     * @param list<string> $perfiles
     * @return list<array<string, mixed>>
     */
    public function listar(array $perfiles): array
    {
        if ($perfiles === []) {
            return [];
        }

        return Database::fetchAll(
            'SELECT id, nombre, email, perfil, ultimo_acceso, password_hash IS NOT NULL AS con_clave
               FROM usuarios
              WHERE activo = 1 AND perfil IN (' . implode(', ', array_fill(0, count($perfiles), '?')) . ')
              ORDER BY nombre',
            $perfiles
        );
    }

    /** Una cuenta cargada por el encargado: sin contraseña, hasta que la persona la cree con el enlace. */
    public function crearSinClave(string $nombre, string $email, string $perfil): int
    {
        return Database::insert('INSERT INTO usuarios (nombre, email, perfil) VALUES (?, ?, ?)', [$nombre, $email, $perfil]);
    }

    /** Vuelve a dar acceso a una cuenta eliminada (se cargó de nuevo su correo): con sus datos nuevos y sin contraseña. */
    public function reactivar(int $id, string $nombre, string $perfil): void
    {
        Database::execute(
            'UPDATE usuarios SET nombre = ?, perfil = ?, activo = 1, password_hash = NULL WHERE id = ?',
            [$nombre, $perfil, $id]
        );
    }

    public function update(int $id, string $nombre, string $perfil): void
    {
        Database::execute('UPDATE usuarios SET nombre = ?, perfil = ? WHERE id = ?', [$nombre, $perfil, $id]);
    }

    /** «Eliminar»: la persona ya no puede entrar; por dentro solo se desactiva (sus solicitudes y registros quedan). */
    public function eliminar(int $id): void
    {
        Database::execute('UPDATE usuarios SET activo = 0 WHERE id = ?', [$id]);
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
