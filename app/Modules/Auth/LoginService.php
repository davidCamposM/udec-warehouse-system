<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\Config;
use App\Modules\Usuarios\UsuarioService;

defined('APP_ROOT') || exit;

/**
 * Verifica correo y contraseña con límite de intentos, y deja registro de cada intento.
 */
final class LoginService
{
    /**
     * Hash de una contraseña al azar (bcrypt de costo 12, el de PASSWORD_DEFAULT en PHP 8.5). Se verifica
     * cuando el correo no existe, para que la demora de la respuesta no revele qué correos tienen cuenta.
     */
    private const HASH_SENUELO = '$2y$12$m2f2Yt031.n38T4D5s/rpufHhwxmG.GjoH1R.cf6OE9vBF3Qd5k2q';

    public function __construct(
        private readonly UsuarioService $usuarios = new UsuarioService(),
        private readonly IntentoLoginRepository $intentos = new IntentoLoginRepository(),
    ) {
    }

    /**
     * @return array{usuario: array{id: int, nombre: string, email: string, perfil: string, docente: bool, huella: string}, acceso_anterior: ?string}
     * @throws LoginException con el mensaje para el usuario
     */
    public function autenticar(string $email, string $password, string $ip): array
    {
        $this->verificarBloqueo($email, $ip);

        $usuario = $this->usuarios->buscarPorEmail($email);

        if ($usuario === null) {
            password_verify($password, self::HASH_SENUELO);
            $this->intentos->registrar($email, $ip, false, null);

            throw new LoginException('Correo o contraseña incorrectos.');
        }

        if (!$this->usuarios->verificarPassword($usuario, $password)) {
            $this->intentos->registrar($email, $ip, false, (int) $usuario['id']);

            // El mismo mensaje que si el correo no existe: no se revela qué correos tienen cuenta.
            throw new LoginException('Correo o contraseña incorrectos.');
        }

        if ((int) $usuario['activo'] !== 1) {
            $this->intentos->registrar($email, $ip, false, (int) $usuario['id']);

            throw new LoginException('Tu cuenta está desactivada. Habla con el encargado de bodega.');
        }

        $this->intentos->registrar($email, $ip, true, (int) $usuario['id']);
        $this->usuarios->registrarAcceso((int) $usuario['id']);
        $this->intentos->purgarAntiguos((int) Config::get('auth.retencion_intentos_dias', 90));

        return [
            // Recargada: verificarPassword() puede haber renovado el hash, y la huella de la sesión debe ser la nueva
            // (con la anterior, la sesión se cerraría como si hubieran cambiado la contraseña).
            'usuario' => $this->usuarios->buscarParaSesion((int) $usuario['id']) ?? UsuarioService::datosSesion($usuario),
            'acceso_anterior' => $usuario['ultimo_acceso'],
        ];
    }

    /**
     * Demasiados fallos recientes con ese correo o desde esa IP: se rechaza sin revisar la contraseña,
     * aunque sea la correcta, y sin registrar el intento.
     *
     * @throws LoginException
     */
    private function verificarBloqueo(string $email, string $ip): void
    {
        $minutos = max(1, (int) Config::get('auth.bloqueo_minutos', 15));

        $segundos = max(
            $this->intentos->esperaPorEmail($email, (int) Config::get('auth.max_intentos', 5), $minutos),
            $this->intentos->esperaPorIp($ip, (int) Config::get('auth.max_intentos_ip', 30), $minutos),
        );

        if ($segundos > 0) {
            $espera = (int) ceil($segundos / 60);

            throw new LoginException(sprintf(
                'Demasiados intentos fallidos. Espera %d %s e inténtalo de nuevo.',
                $espera,
                $espera === 1 ? 'minuto' : 'minutos'
            ));
        }
    }
}
