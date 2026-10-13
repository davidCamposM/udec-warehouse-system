<?php

declare(strict_types=1);

namespace App\Modules\Retiro;

use App\Core\Config;
use App\Core\Url;
use App\Core\ValidationException;
use App\Modules\Historial\AuditoriaService;

defined('APP_ROOT') || exit;

/**
 * Equipos habilitados como punto de retiro. El encargado habilita el navegador del computador de la bodega: este
 * guarda una llave (token) en una cookie y en la base queda solo su SHA-256. Sin esa llave el punto de retiro no
 * funciona, así nadie puede retirar a nombre de otra persona desde otro computador.
 */
final class EstacionService
{
    public const COOKIE = 'BODEGA_ESTACION';

    /** La llave dura 400 días, el máximo que aceptan los navegadores; al vencer, el encargado vuelve a habilitar. */
    private const DURACION = 400 * 86400;

    public function __construct(
        private readonly EstacionRepository $estaciones = new EstacionRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService(),
    ) {
    }

    /** @return array{id: int, nombre: string}|null el equipo de esta petición, si está habilitado */
    public function actual(): ?array
    {
        $token = $_COOKIE[self::COOKIE] ?? null;
        if (!is_string($token) || !preg_match('/^[0-9a-f]{64}$/', $token)) {
            return null;
        }

        $estacion = $this->estaciones->findActivaPorHash(hash('sha256', $token));

        return $estacion === null ? null : ['id' => (int) $estacion['id'], 'nombre' => (string) $estacion['nombre']];
    }

    /** @return list<array<string, mixed>> */
    public function activas(): array
    {
        return $this->estaciones->activas();
    }

    /**
     * Habilita este navegador: crea la llave, la guarda en la cookie y registra el equipo.
     *
     * @throws ValidationException
     */
    public function habilitar(mixed $nombre, int $usuarioId): void
    {
        $nombre = is_string($nombre) ? (string) preg_replace('/\s+/u', ' ', trim($nombre)) : '';
        if ($nombre === '' || mb_strlen($nombre) > 80) {
            throw new ValidationException(['nombre' => 'Escribe un nombre corto para este computador. Ejemplo: PC de la bodega.']);
        }

        $token = bin2hex(random_bytes(32));
        $id = $this->estaciones->create($nombre, hash('sha256', $token), $usuarioId);
        $this->auditoria->registrar('habilitar', 'estacion', $id, ['nombre' => $nombre]);

        setcookie(self::COOKIE, $token, [
            'expires' => time() + self::DURACION,
            'path' => Url::basePath() . '/',
            'secure' => (bool) Config::get('app.https', false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public function deshabilitar(int $id): void
    {
        if ($this->estaciones->desactivar($id)) {
            $this->auditoria->registrar('deshabilitar', 'estacion', $id);
        }
    }

    public function registrarUso(int $id): void
    {
        $this->estaciones->registrarUso($id);
    }
}
