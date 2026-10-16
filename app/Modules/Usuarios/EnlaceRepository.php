<?php

declare(strict_types=1);

namespace App\Modules\Usuarios;

use App\Core\Database;

defined('APP_ROOT') || exit;

/** Acceso a la tabla `usuario_enlaces`: los enlaces para crear o recuperar la contraseña (se guarda su hash). */
final class EnlaceRepository
{
    public function crear(int $usuarioId, string $tokenHash, string $expiraEn): void
    {
        Database::insert(
            'INSERT INTO usuario_enlaces (usuario_id, token_hash, expira_en) VALUES (?, ?, ?)',
            [$usuarioId, $tokenHash, $expiraEn]
        );
    }

    /** @return array<string, mixed>|null el enlace sin usar y vigente, con su cuenta activa */
    public function vigente(string $tokenHash): ?array
    {
        return Database::fetch(
            'SELECT e.id, e.usuario_id, u.nombre, u.email, u.password_hash IS NOT NULL AS con_clave
               FROM usuario_enlaces e JOIN usuarios u ON u.id = e.usuario_id
              WHERE e.token_hash = ? AND e.usado_en IS NULL AND e.expira_en > NOW() AND u.activo = 1',
            [$tokenHash]
        );
    }

    /** Usado uno, ninguno de los enlaces anteriores de esa cuenta sirve: solo vale el último que se usó. */
    public function usarTodos(int $usuarioId): void
    {
        Database::execute('UPDATE usuario_enlaces SET usado_en = NOW() WHERE usuario_id = ? AND usado_en IS NULL', [$usuarioId]);
    }

    /** Cuántos enlaces se crearon para esa cuenta en los últimos $minutos (para no llenar su correo). */
    public function recientes(int $usuarioId, int $minutos): int
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM usuario_enlaces WHERE usuario_id = ? AND creado_en > NOW() - INTERVAL ? MINUTE',
            [$usuarioId, $minutos]
        );
    }
}
