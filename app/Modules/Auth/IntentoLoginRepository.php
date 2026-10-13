<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\Database;

defined('APP_ROOT') || exit;

/**
 * Acceso a la tabla `intentos_login`: registro de accesos y límite de intentos fallidos.
 */
final class IntentoLoginRepository
{
    public function registrar(string $email, string $ip, bool $exitoso, ?int $usuarioId): void
    {
        Database::insert(
            'INSERT INTO intentos_login (email, ip, exitoso, usuario_id) VALUES (?, ?, ?, ?)',
            [$email, $ip, (int) $exitoso, $usuarioId]
        );
    }

    /**
     * Segundos que faltan para que el correo pueda volver a intentar, o 0 si no está bloqueado.
     * Solo cuentan los fallos posteriores a su último ingreso exitoso.
     */
    public function esperaPorEmail(string $email, int $maximo, int $minutos): int
    {
        // Por id y no por fecha: un fallo en el mismo segundo que el éxito también cuenta.
        $ultimoExito = Database::fetchValue(
            'SELECT MAX(id) FROM intentos_login WHERE email = ? AND exitoso = 1',
            [$email]
        );

        return $this->espera('email = ? AND id > ?', [$email, (int) $ultimoExito], $maximo, $minutos);
    }

    /** Segundos que faltan para que la IP pueda volver a intentar, o 0 si no está bloqueada. */
    public function esperaPorIp(string $ip, int $maximo, int $minutos): int
    {
        return $this->espera('ip = ?', [$ip], $maximo, $minutos);
    }

    /** Borra los intentos más antiguos que el plazo de retención. Retorna cuántos borró. */
    public function purgarAntiguos(int $dias): int
    {
        return Database::execute('DELETE FROM intentos_login WHERE creado_en < NOW() - INTERVAL ? DAY', [$dias]);
    }

    /**
     * Ventana deslizante: con $maximo fallos dentro de los últimos $minutos, el bloqueo dura hasta
     * que el más antiguo de esos fallos sale de la ventana. Mientras dura, los intentos no se
     * registran (ver LoginService), así que el bloqueo no se alarga.
     *
     * @param array<int, mixed> $parametros los de $filtro
     */
    private function espera(string $filtro, array $parametros, int $maximo, int $minutos): int
    {
        $maximo = max(1, $maximo);

        $segundos = Database::fetchValue(
            "SELECT TIMESTAMPDIFF(SECOND, NOW(), MIN(creado_en) + INTERVAL ? MINUTE)
               FROM (SELECT creado_en
                       FROM intentos_login
                      WHERE {$filtro} AND exitoso = 0 AND creado_en > NOW() - INTERVAL ? MINUTE
                      ORDER BY creado_en DESC
                      LIMIT {$maximo}) AS recientes
             HAVING COUNT(*) >= ?",
            [$minutos, ...$parametros, $minutos, $maximo]
        );

        return $segundos === null ? 0 : max(0, (int) $segundos);
    }
}
