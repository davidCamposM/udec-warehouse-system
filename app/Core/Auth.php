<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

defined('APP_ROOT') || exit;

/**
 * Usuario con cuenta que tiene la sesión iniciada. La verificación de correo y contraseña la
 * hace el módulo Auth; esta clase solo guarda y entrega al usuario ya verificado.
 */
final class Auth
{
    public const CLAVE = '_usuario';

    /** @var (Closure(int): ?array{id: int, nombre: string, email: string, perfil: string, docente: bool, huella: string})|null */
    private static ?Closure $resolver = null;

    private static bool $revocada = false;

    private static bool $claveCambiada = false;

    /**
     * Función que entrega los datos vigentes del usuario por su id, o null si ya no puede ingresar
     * (desactivado). La enlaza app/bootstrap.php, así el núcleo no depende del módulo Usuarios.
     *
     * @param Closure(int): ?array{id: int, nombre: string, email: string, perfil: string, docente: bool, huella: string} $resolver
     */
    public static function resolveUsing(Closure $resolver): void
    {
        self::$resolver = $resolver;
    }

    /**
     * Recarga al usuario de la sesión en cada petición: si lo desactivaron o le cambiaron la contraseña, su sesión se
     * cierra en ese momento; si le cambiaron el perfil o el nombre, se aplica sin que tenga que volver a ingresar.
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

        // Sesión iniciada con la contraseña anterior. Una sesión de antes de que existiera la huella no se cierra:
        // solo la toma.
        if (isset($usuario['huella']) && !hash_equals((string) $usuario['huella'], $vigente['huella'])) {
            Session::reiniciar();
            self::$claveCambiada = true;

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

    /** En esta petición se cerró la sesión porque la contraseña cambió desde que se inició. */
    public static function passwordChanged(): bool
    {
        return self::$claveCambiada;
    }

    /**
     * Cambia el id de sesión y el token CSRF para evitar la fijación de sesión.
     *
     * @param array{id: int, nombre: string, email: string, perfil: string, docente: bool, huella: string} $usuario
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
            'docente' => (bool) $usuario['docente'],
            'huella' => (string) $usuario['huella'],
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

    /** @return array{id: int, nombre: string, email: string, perfil: string, docente?: bool, huella?: string}|null */
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

    /** Solicita herramientas como docente: los docentes y el administrador marcado con tambien_docente. */
    public static function esDocente(): bool
    {
        return (bool) (self::user()['docente'] ?? self::perfil() === 'docente');
    }
}
