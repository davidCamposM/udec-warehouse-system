<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use PHPUnit\Framework\TestCase;

/**
 * Prueba que usa la base de pruebas: cada prueba corre dentro de una transacción que se deshace al terminar, así
 * ninguna deja datos y el orden en que corren no importa. Trae ayudas para crear cuentas e intentos de ingreso.
 */
abstract class PruebaConBase extends TestCase
{
    /** La contraseña de las cuentas de prueba. */
    protected const CLAVE = 'Clave-segura-2026';

    protected function setUp(): void
    {
        Database::connection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $pdo = Database::connection();
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

    /**
     * Una cuenta de docente. Con $clave null, la cuenta todavía no tiene contraseña (no ha usado su enlace). Con
     * $costo, el hash bcrypt se calcula con ese costo en vez del actual (para probar que se actualiza).
     *
     * @return int el id de la cuenta
     */
    protected function crearUsuario(string $email, ?string $clave = self::CLAVE, bool $activo = true, ?int $costo = null): int
    {
        $hash = match (true) {
            $clave === null => null,
            $costo !== null => password_hash($clave, PASSWORD_BCRYPT, ['cost' => $costo]),
            default => password_hash($clave, PASSWORD_DEFAULT),
        };

        return Database::insert(
            'INSERT INTO usuarios (nombre, email, password_hash, perfil, activo) VALUES (?, ?, ?, ?, ?)',
            ['Persona de Prueba', $email, $hash, 'docente', (int) $activo]
        );
    }

    /** Registra $cantidad intentos fallidos de un correo desde una IP, hace $haceMinutos minutos. */
    protected function fallos(int $cantidad, string $email, string $ip = '10.0.0.1', int $haceMinutos = 0): void
    {
        for ($i = 0; $i < $cantidad; $i++) {
            Database::insert(
                'INSERT INTO intentos_login (email, ip, exitoso, creado_en) VALUES (?, ?, 0, NOW() - INTERVAL ? MINUTE)',
                [$email, $ip, $haceMinutos]
            );
        }
    }

    /** Cuántos intentos hay registrados (de un correo, si se indica). */
    protected function contarIntentos(?string $email = null): int
    {
        return (int) ($email === null
            ? Database::fetchValue('SELECT COUNT(*) FROM intentos_login')
            : Database::fetchValue('SELECT COUNT(*) FROM intentos_login WHERE email = ?', [$email]));
    }
}
