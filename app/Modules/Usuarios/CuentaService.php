<?php

declare(strict_types=1);

namespace App\Modules\Usuarios;

use App\Core\Config;
use App\Core\Database;
use App\Core\Url;
use App\Core\ValidationException;
use App\Modules\Historial\AuditoriaService;
use App\Support\Correo;
use DateTimeImmutable;

defined('APP_ROOT') || exit;

/**
 * Las cuentas de las personas que usan el sistema (docentes y administrativas), sin que nadie más conozca su
 * contraseña: el encargado agrega a cada persona (nombre, correo UdeC o Gmail, perfil) y el sistema le muestra un enlace
 * para que la persona cree su contraseña, que él le comparte (y, si hay casilla de correo, también le llega por correo).
 * Si olvida su contraseña, el encargado le da un enlace nuevo. También corrige el nombre o el perfil y elimina cuentas
 * (por dentro, se desactivan).
 */
final class CuentaService
{
    /** Los perfiles que se cargan desde aquí (la cuenta del encargado se crea aparte, con bin/crear-usuario.php). */
    public const PERFILES = ['docente' => 'Docente', 'administrativa' => 'Administrativa'];

    /** Filtros de la lista de cuentas: filtro => nombre. */
    public const FILTROS = [
        'todas' => 'Todas',
        'docente' => 'Docentes',
        'administrativa' => 'Administrativas',
        'sin-clave' => 'Falta crear contraseña',
    ];

    public function __construct(
        private readonly UsuarioRepository $usuarios = new UsuarioRepository(),
        private readonly EnlaceRepository $enlaces = new EnlaceRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService(),
    ) {
    }

    // ---------- La lista ----------

    /** @return list<array<string, mixed>> las cuentas del filtro (docentes y administrativas), por nombre */
    public function listar(string $filtro): array
    {
        $perfiles = isset(self::PERFILES[$filtro]) ? [$filtro] : array_keys(self::PERFILES);
        $cuentas = $this->usuarios->listar($perfiles);

        return $filtro === 'sin-clave'
            ? array_values(array_filter($cuentas, static fn (array $c): bool => !(bool) $c['con_clave']))
            : $cuentas;
    }

    /** @return array<string, int> filtro => cuántas cuentas hay */
    public function contar(): array
    {
        $cuentas = $this->usuarios->listar(array_keys(self::PERFILES));
        $conteo = ['todas' => count($cuentas), 'sin-clave' => 0] + array_fill_keys(array_keys(self::PERFILES), 0);
        foreach ($cuentas as $cuenta) {
            $conteo[$cuenta['perfil']]++;
            $conteo['sin-clave'] += (bool) $cuenta['con_clave'] ? 0 : 1;
        }

        return $conteo;
    }

    /** @return array<string, mixed>|null una cuenta activa de docente o administrativa */
    public function buscar(int $id): ?array
    {
        $cuenta = $this->usuarios->findById($id);

        return $cuenta !== null && (int) $cuenta['activo'] === 1 && isset(self::PERFILES[$cuenta['perfil']]) ? $cuenta : null;
    }

    // ---------- Agregar, editar, eliminar ----------

    /**
     * Agrega a una persona (nombre, correo UdeC o Gmail y perfil) y crea el enlace para que cree su contraseña, que el
     * encargado le comparte. Si su correo era de una cuenta eliminada, la cuenta vuelve, con estos datos.
     *
     * @param array<string, mixed> $datos nombre, email y perfil
     * @return array<string, mixed> el enlace para compartir (enlaceParaCompartir)
     * @throws ValidationException
     */
    public function agregar(array $datos): array
    {
        [$nombre, $perfil] = $this->validar($datos);
        $email = mb_strtolower(trim((string) ($datos['email'] ?? '')));
        $dominios = array_map('strtolower', (array) Config::get('auth.dominios', ['udec.cl', 'gmail.com']));
        $dominio = substr((string) strrchr($email, '@'), 1);

        $cuenta = null;
        $error = match (true) {
            $email === '' => 'Escribe su correo. Ejemplo: nombre@udec.cl o nombre@gmail.com',
            filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 190 => 'Ese correo no está bien escrito. Ejemplo: nombre@udec.cl o nombre@gmail.com',
            $dominios !== [] && array_filter($dominios, static fn (string $d): bool => $dominio === $d || str_ends_with($dominio, '.' . $d)) === []
                => 'Debe ser un correo ' . implode(' o ', array_map(static fn (string $d): string => '@' . $d, $dominios)) . '.',
            (int) (($cuenta = $this->usuarios->findByEmail($email))['activo'] ?? 0) === 1 => 'Esa persona ya tiene cuenta.',
            default => null,
        };
        if ($error !== null) {
            throw new ValidationException(['email' => $error]);
        }

        $id = Database::transaction(function () use ($cuenta, $nombre, $email, $perfil): int {
            if ($cuenta === null) {
                $id = $this->usuarios->crearSinClave($nombre, $email, $perfil);
            } else {
                $id = (int) $cuenta['id'];
                $this->usuarios->reactivar($id, $nombre, $perfil);
            }
            $this->auditoria->registrar('crear', 'usuario', $id, ['email' => $email, 'perfil' => $perfil]);

            return $id;
        });

        return $this->enlaceParaCompartir($id);
    }

    /**
     * @param array<string, mixed> $datos nombre y perfil
     * @throws ValidationException
     */
    public function editar(int $id, array $datos): void
    {
        $cuenta = $this->buscar($id) ?? throw new ValidationException(['nombre' => 'Esa cuenta no existe.']);
        [$nombre, $perfil] = $this->validar($datos);

        $this->usuarios->update($id, $nombre, $perfil);
        $this->auditoria->registrar('editar', 'usuario', $id, AuditoriaService::diferencias(
            ['nombre' => $cuenta['nombre'], 'perfil' => $cuenta['perfil']],
            ['nombre' => $nombre, 'perfil' => $perfil],
        ));
    }

    /** «Eliminar»: la persona ya no puede entrar; por dentro solo se desactiva. Sus enlaces dejan de servir. */
    public function eliminar(int $id): void
    {
        if ($this->buscar($id) === null) {
            return;
        }

        Database::transaction(function () use ($id): void {
            $this->usuarios->eliminar($id);
            $this->enlaces->usarTodos($id);
            $this->auditoria->registrar('eliminar', 'usuario', $id);
        });
    }

    // ---------- Enlaces para crear o recuperar la contraseña ----------

    /**
     * Un enlace nuevo para que la persona cree su contraseña (o una nueva, si la olvidó), para que el encargado se lo
     * comparta (por WhatsApp, Teams o su propio correo). Si el sistema tiene casilla de correo (correo.modo = smtp),
     * además se le envía por correo.
     *
     * @return array{nombre: string, email: string, enlace: string, vence: string, primera_vez: bool, enviado: ?bool}
     *     enviado: null si el sistema no envía correos
     * @throws ValidationException si la cuenta no existe
     */
    public function enlaceParaCompartir(int $id): array
    {
        $cuenta = $this->buscar($id) ?? throw new ValidationException(['enlace' => 'Esa cuenta no existe.']);
        $enlace = $this->crearEnlace($cuenta, true);
        $vence = $enlace['vence']->format('d/m/Y') . ' a las ' . $enlace['vence']->format('H:i');

        return [
            'nombre' => (string) $cuenta['nombre'],
            'email' => (string) $cuenta['email'],
            'enlace' => $enlace['enlace'],
            'vence' => $vence,
            'primera_vez' => $enlace['primera_vez'],
            'enviado' => Correo::envia() ? Correo::enviar((string) $cuenta['email'], $enlace['asunto'], $enlace['texto']) : null,
        ];
    }

    /**
     * Desde el login («Primera vez o olvidé mi contraseña»): si el correo es de una cuenta activa, le llega el enlace. No
     * dice si el correo tiene cuenta o no (a quien lo pide se le responde siempre lo mismo).
     */
    public function pedirEnlace(string $email): void
    {
        $cuenta = $this->usuarios->findByEmail(trim($email));
        if ($cuenta === null || (int) $cuenta['activo'] !== 1) {
            return;
        }
        if ($this->enlaces->recientes((int) $cuenta['id'], 60) >= self::enlacesPorHora()) {
            return;
        }

        $enlace = $this->crearEnlace($cuenta, false);
        Correo::enviar((string) $cuenta['email'], $enlace['asunto'], $enlace['texto']);
    }

    /** @return array<string, mixed>|null la cuenta del enlace, si todavía sirve (sin usar, sin vencer, cuenta activa) */
    public function cuentaDelEnlace(string $codigo): ?array
    {
        return preg_match('/^[a-f0-9]{64}$/', $codigo) === 1 ? $this->enlaces->vigente(hash('sha256', $codigo)) : null;
    }

    /**
     * Crea la contraseña con el enlace. Después, ese enlace y los anteriores ya no sirven.
     *
     * @return string el correo de la cuenta (para dejarlo escrito en el login)
     * @throws ValidationException
     */
    public function crearClave(string $codigo, mixed $clave, mixed $repetida): string
    {
        $cuenta = $this->cuentaDelEnlace($codigo)
            ?? throw new ValidationException(['password' => 'Este enlace ya no sirve. Pide uno nuevo.']);

        $error = UsuarioService::validarPassword($clave);
        if ($error !== null) {
            throw new ValidationException(['password' => $error]);
        }
        if ($clave !== $repetida) {
            throw new ValidationException(['password_repetida' => 'No es igual a la primera. Escríbela de nuevo.']);
        }

        Database::transaction(function () use ($cuenta, $clave): void {
            $this->usuarios->updatePassword((int) $cuenta['usuario_id'], UsuarioService::hash((string) $clave));
            $this->enlaces->usarTodos((int) $cuenta['usuario_id']);
            $this->auditoria->registrar('crear_clave', 'usuario', (int) $cuenta['usuario_id']);
        });

        return (string) $cuenta['email'];
    }

    /**
     * Crea un enlace para la cuenta, con el correo que lo lleva. Sin contraseña todavía, o si lo pide el encargado
     * ($largo, para compartirlo con calma), vence en días; si la persona lo pide desde el login para recuperar su
     * contraseña, en minutos.
     *
     * @param array<string, mixed> $cuenta fila de usuarios
     * @return array{enlace: string, vence: DateTimeImmutable, primera_vez: bool, asunto: string, texto: string}
     */
    private function crearEnlace(array $cuenta, bool $largo): array
    {
        $primeraVez = $cuenta['password_hash'] === null;
        $vence = $primeraVez || $largo
            ? new DateTimeImmutable('+' . max(1, (int) Config::get('auth.enlace_activar_dias', 7)) . ' days')
            : new DateTimeImmutable('+' . max(5, (int) Config::get('auth.enlace_recuperar_minutos', 60)) . ' minutes');

        $codigo = bin2hex(random_bytes(32));
        $this->enlaces->crear((int) $cuenta['id'], hash('sha256', $codigo), $vence->format('Y-m-d H:i:s'));
        $enlace = Url::to('clave/' . $codigo);
        [$asunto, $texto] = self::mensaje($cuenta, $enlace, $vence, $primeraVez);

        return ['enlace' => $enlace, 'vence' => $vence, 'primera_vez' => $primeraVez, 'asunto' => $asunto, 'texto' => $texto];
    }

    /**
     * @param array<string, mixed> $cuenta
     * @return array{0: string, 1: string} el asunto y el texto
     */
    private static function mensaje(array $cuenta, string $enlace, DateTimeImmutable $vence, bool $primeraVez): array
    {
        $nombre = explode(' ', trim((string) $cuenta['nombre']))[0];
        $login = Url::to('login');

        if ($primeraVez) {
            return ['Crea tu contraseña para Bodega FCF', implode("\n", [
                "Hola, {$nombre}:",
                '',
                'Te dieron acceso al sistema de la Bodega de la Facultad de Ciencias Forestales (UdeC).',
                '',
                'Para entrar por primera vez, crea tu contraseña con este enlace:',
                $enlace,
                '',
                'El enlace sirve una sola vez y vence el ' . $vence->format('d/m/Y') . ' a las ' . $vence->format('H:i') . '.',
                "Después, entra en {$login} con tu correo ({$cuenta['email']}) y tu contraseña.",
                '',
                'Si no esperabas este correo, puedes ignorarlo.',
                '',
                'Bodega FCF · Universidad de Concepción',
            ])];
        }

        return ['Recupera tu contraseña de Bodega FCF', implode("\n", [
            "Hola, {$nombre}:",
            '',
            'Pediste una contraseña nueva para el sistema de la Bodega FCF. Créala con este enlace:',
            $enlace,
            '',
            'El enlace sirve una sola vez y vence el ' . $vence->format('d/m/Y') . ' a las ' . $vence->format('H:i') . '.',
            'Si no lo pediste tú, ignora este correo: tu contraseña sigue igual.',
            '',
            'Bodega FCF · Universidad de Concepción',
        ])];
    }

    /**
     * @param array<string, mixed> $datos nombre y perfil
     * @return array{0: string, 1: string} el nombre (sin espacios de más) y el perfil
     * @throws ValidationException
     */
    private function validar(array $datos): array
    {
        $nombre = preg_replace('/\s+/u', ' ', trim((string) ($datos['nombre'] ?? ''))) ?? '';
        $perfil = (string) ($datos['perfil'] ?? '');

        $errores = [];
        if ($nombre === '') {
            $errores['nombre'] = 'Escribe su nombre y apellido. Ejemplo: Juan Pérez';
        } elseif (mb_strlen($nombre) > 120) {
            $errores['nombre'] = 'El nombre es muy largo: usa 120 letras como máximo.';
        }
        if (!isset(self::PERFILES[$perfil])) {
            $errores['perfil'] = 'Elige si es docente o administrativa.';
        }
        if ($errores !== []) {
            throw new ValidationException($errores);
        }

        return [$nombre, $perfil];
    }

    private static function enlacesPorHora(): int
    {
        return max(1, (int) Config::get('auth.enlaces_por_hora', 3));
    }
}
