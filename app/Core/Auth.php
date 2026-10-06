<?php

declare(strict_types=1);

namespace App\Core;

defined('APP_ROOT') || exit;

/**
 * Usuario con cuenta que tiene la sesión iniciada. La verificación de correo y contraseña la
 * hace el módulo Auth; esta clase solo guarda y entrega al usuario ya verificado.
 *
 * Las personas que se identifican en la estación de retiro NO pasan por aquí: tienen su propia
 * identificación, separada y de corta duración.
 */
final class Auth
{
    public const CLAVE = '_usuario';

    /**
     * Cambia el id de sesión y el token CSRF para evitar la fijación de sesión.
     *
     * @param array{id: int, nombre: string, email: string, perfil: string} $usuario
     */
    public static function login(array $usuario): void
    {
        Session::regenerate();
        Csrf::regenerate();
        Session::put(self::CLAVE, [
            'id' => (int) $usuario['id'],
            'nombre' => (string) $usuario['nombre'],
            'email' => (string) $usuario['email'],
            'perfil' => (string) $usuario['perfil'],
        ]);
    }

    public static function logout(): void
    {
        Session::reiniciar();
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** @return array{id: int, nombre: string, email: string, perfil: string}|null */
    public static function user(): ?array
    {
        $usuario = Session::get(self::CLAVE);

        return is_array($usuario) ? $usuario : null;
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function perfil(): ?string
    {
        return self::user()['perfil'] ?? null;
    }
}
