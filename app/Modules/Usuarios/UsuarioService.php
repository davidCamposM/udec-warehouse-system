<?php

declare(strict_types=1);

namespace App\Modules\Usuarios;

use App\Core\Permission;
use App\Core\ValidationException;
use App\Core\Validator;
use PDOException;

defined('APP_ROOT') || exit;

/**
 * Cuentas de usuario: creación, contraseñas y datos de sesión. Otros módulos usan este servicio,
 * nunca el repositorio directamente.
 */
final class UsuarioService
{
    public const PASSWORD_MIN = 8;

    /** bcrypt ignora lo que pasa de 72 bytes: una contraseña más larga daría una falsa sensación de seguridad. */
    public const PASSWORD_MAX = 72;

    /** Nombre para mostrar de cada perfil. Un perfil de config/permisos.php sin nombre aquí se muestra tal cual. */
    private const NOMBRES_PERFIL = [
        'administrador' => 'Administrador',
        'administrativa' => 'Administrativa',
        'operador' => 'Operador de bodega',
        'docente' => 'Docente',
    ];

    public function __construct(private readonly UsuarioRepository $usuarios = new UsuarioRepository())
    {
    }

    /**
     * @param array<string, mixed> $datos nombre, email, perfil y password
     * @throws ValidationException
     */
    public function crear(array $datos): int
    {
        $usuario = $this->preparar($datos);

        try {
            return $this->usuarios->create(
                $usuario['nombre'],
                $usuario['email'],
                $usuario['password_hash'],
                $usuario['perfil']
            );
        } catch (PDOException $e) {
            // Otro proceso creó el mismo correo entre la validación y el INSERT.
            if ($e->getCode() === '23000') {
                throw new ValidationException(['email' => 'Ya existe un usuario con ese correo.']);
            }
            throw $e;
        }
    }

    /**
     * Valida los datos de una cuenta nueva y calcula el hash de la contraseña. Con $verificarCorreo = false
     * no consulta la base (bin/crear-usuario.php --sql, que solo imprime el INSERT).
     *
     * @param array<string, mixed> $datos
     * @return array{nombre: string, email: string, perfil: string, password_hash: string}
     * @throws ValidationException
     */
    public function preparar(array $datos, bool $verificarCorreo = true): array
    {
        $errores = [];
        $limpios = [];

        try {
            $limpios = Validator::validate($datos, [
                'nombre' => 'requerido|max:120',
                'email' => 'requerido|email|max:190',
                'perfil' => 'requerido|en:' . implode(',', Permission::profiles()),
            ]);
        } catch (ValidationException $e) {
            $errores = $e->errors();
        }

        $errorPassword = self::validarPassword($datos['password'] ?? null);
        if ($errorPassword !== null) {
            $errores['password'] = $errorPassword;
        }

        if ($verificarCorreo && !isset($errores['email']) && $this->usuarios->findByEmail($limpios['email']) !== null) {
            $errores['email'] = 'Ya existe un usuario con ese correo.';
        }

        if ($errores !== []) {
            throw new ValidationException($errores);
        }

        return [
            'nombre' => $limpios['nombre'],
            'email' => $limpios['email'],
            'perfil' => $limpios['perfil'],
            'password_hash' => self::hash($datos['password']),
        ];
    }

    /**
     * Restablece la contraseña de una cuenta (lo hace el encargado). Retorna la cuenta.
     *
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public function restablecerPassword(string $email, string $password): array
    {
        $error = self::validarPassword($password);
        if ($error !== null) {
            throw new ValidationException(['password' => $error]);
        }

        $usuario = $this->usuarios->findByEmail($email)
            ?? throw new ValidationException(['email' => 'No existe un usuario con ese correo.']);

        $this->usuarios->updatePassword((int) $usuario['id'], self::hash($password));

        return $usuario;
    }

    /** Mensaje de error si la contraseña no sirve para una cuenta, o null si sirve. Los espacios cuentan. */
    public static function validarPassword(mixed $password): ?string
    {
        return match (true) {
            !is_string($password) || $password === '' => 'Este campo es obligatorio.',
            mb_strlen($password) < self::PASSWORD_MIN => 'Debe tener al menos ' . self::PASSWORD_MIN . ' caracteres.',
            strlen($password) > self::PASSWORD_MAX => 'No puede superar los ' . self::PASSWORD_MAX . ' caracteres.',
            str_contains($password, "\0") => 'Contiene caracteres no permitidos.',
            default => null,
        };
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Compara la contraseña con la guardada. Si PHP ahora usa un algoritmo o costo más fuerte,
     * aprovecha que tiene la contraseña a mano para actualizar el hash.
     *
     * @param array<string, mixed> $usuario fila de `usuarios`
     */
    public function verificarPassword(array $usuario, string $password): bool
    {
        // Sin contraseña todavía (la persona no ha usado su enlace): no hay con qué comparar.
        if ($usuario['password_hash'] === null) {
            return false;
        }
        $hash = (string) $usuario['password_hash'];

        if (!password_verify($password, $hash)) {
            return false;
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $this->usuarios->updatePassword((int) $usuario['id'], self::hash($password));
        }

        return true;
    }

    /** @return array<string, mixed>|null */
    public function buscarPorEmail(string $email): ?array
    {
        return $this->usuarios->findByEmail($email);
    }

    public function registrarAcceso(int $id): void
    {
        $this->usuarios->registrarAcceso($id);
    }

    /**
     * Datos vigentes para la sesión, o null si la cuenta ya no puede ingresar.
     *
     * @return array{id: int, nombre: string, email: string, perfil: string}|null
     */
    public function buscarParaSesion(int $id): ?array
    {
        $usuario = $this->usuarios->findById($id);

        return $usuario !== null && (int) $usuario['activo'] === 1 ? self::datosSesion($usuario) : null;
    }

    /**
     * Lo que se guarda del usuario en la sesión: nunca el hash ni otros datos que no se usen.
     *
     * @param array<string, mixed> $usuario fila de `usuarios`
     * @return array{id: int, nombre: string, email: string, perfil: string}
     */
    public static function datosSesion(array $usuario): array
    {
        return [
            'id' => (int) $usuario['id'],
            'nombre' => (string) $usuario['nombre'],
            'email' => (string) $usuario['email'],
            'perfil' => (string) $usuario['perfil'],
        ];
    }

    public static function nombrePerfil(string $perfil): string
    {
        return self::NOMBRES_PERFIL[$perfil] ?? $perfil;
    }
}
