<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

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

    /** @var (Closure(int): ?array{id: int, nombre: string, email: string, perfil: string})|null */
    private static ?Closure $resolver = null;

    private static bool $revocada = false;

    /**
     * Función que entrega los datos vigentes del usuario por su id, o null si ya no puede ingresar
     * (desactivado). La enlaza app/bootstrap.php, así el núcleo no depende del módulo Usuarios.
     *
     * @param Closure(int): ?array{id: int, nombre: string, email: string, perfil: string} $resolver
     */
    public static function resolveUsing(Closure $resolver): void
    {
        self::$resolver = $resolver;
    }

    /**
     * Recarga al usuario de la sesión en cada petición: si lo desactivaron, su sesión se cierra en
     * ese momento; si le cambiaron el perfil o el nombre, se aplica sin que tenga que volver a ingresar.
     */
    public static function refresh(): void
    {
        $usuario = self::user();

        if ($usuario === null || self::$resolver === null) {
            return;
        }

        $vigente = (self::$resolver)($usuario['id']);

        if ($vigente === null) {
            Session::reiniciar();
            self::$revocada = true;

            return;
        }

        if ($vigente !== $usuario) {
            Session::put(self::CLAVE, $vigente);
        }
    }

    /** En esta petición se cerró la sesión porque la cuenta fue desactivada. */
    public static function revoked(): bool
    {
        return self::$revocada;
    }

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
