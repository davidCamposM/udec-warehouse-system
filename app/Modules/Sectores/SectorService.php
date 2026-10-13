<?php

declare(strict_types=1);

namespace App\Modules\Sectores;

use App\Core\Database;
use App\Core\ValidationException;
use App\Core\Validator;
use App\Modules\Historial\AuditoriaService;

defined('APP_ROOT') || exit;

/**
 * Sectores de bodega: cada parte de la bodega (un estante, una repisa) con su código, que se imprime como QR
 * y se escanea al guardar un producto.
 */
final class SectorService
{
    public function __construct(
        private readonly SectorRepository $sectores = new SectorRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService(),
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function todos(): array
    {
        return $this->sectores->todos();
    }

    /** @return list<array<string, mixed>> */
    public function activos(): array
    {
        return $this->sectores->activos();
    }

    /** @return array<string, mixed>|null */
    public function buscarPorId(int $id): ?array
    {
        return $this->sectores->findById($id);
    }

    /** @return array<string, mixed>|null el sector de un código leído o escrito, esté activo o no */
    public function buscarPorCodigo(string $codigo): ?array
    {
        $codigo = trim($codigo);

        return $codigo === '' ? null : $this->sectores->findPorCodigo($codigo);
    }

    /**
     * Crea el sector con el siguiente código (SEC001…).
     *
     * @param array<string, mixed> $datos
     * @return array{id: int, codigo: string, nombre: string}
     * @throws ValidationException
     */
    public function crear(array $datos): array
    {
        $nombre = $this->validarNombre($datos, null);

        return Database::transaction(function () use ($nombre): array {
            $codigo = $this->sectores->siguienteCodigo();
            $id = $this->sectores->create($codigo, $nombre);
            $this->auditoria->registrar('crear', 'sector', $id, ['codigo' => $codigo, 'nombre' => $nombre]);

            return ['id' => $id, 'codigo' => $codigo, 'nombre' => $nombre];
        });
    }

    /**
     * @param array<string, mixed> $datos
     * @throws ValidationException
     */
    public function renombrar(int $id, array $datos): void
    {
        $actual = $this->sectores->findById($id) ?? throw new ValidationException(['nombre' => 'Ese sector no existe.']);
        $nombre = $this->validarNombre($datos, $id);

        if ($nombre === $actual['nombre']) {
            return;
        }

        Database::transaction(function () use ($id, $nombre, $actual): void {
            $this->sectores->rename($id, $nombre);
            $this->auditoria->registrar('editar', 'sector', $id, ['cambios' => ['nombre' => [$actual['nombre'], $nombre]]]);
        });
    }

    public function cambiarActivo(int $id, bool $activo): void
    {
        $sector = $this->sectores->findById($id);

        if ($sector === null || (int) $sector['activo'] === (int) $activo) {
            return;
        }

        Database::transaction(function () use ($id, $activo, $sector): void {
            $this->sectores->setActivo($id, $activo);
            $this->auditoria->registrar($activo ? 'activar' : 'desactivar', 'sector', $id, [
                'codigo' => $sector['codigo'],
                'nombre' => $sector['nombre'],
            ]);
        });
    }

    /**
     * @param array<string, mixed> $datos
     * @throws ValidationException
     */
    private function validarNombre(array $datos, ?int $id): string
    {
        // Espacios repetidos fuera: "Estante  A" y "Estante A" son el mismo nombre.
        if (is_string($datos['nombre'] ?? null)) {
            $datos['nombre'] = preg_replace('/\s+/u', ' ', trim($datos['nombre']));
        }

        try {
            $limpios = Validator::validate($datos, ['nombre' => 'requerido|max:80']);
        } catch (ValidationException) {
            throw new ValidationException(['nombre' => ($datos['nombre'] ?? '') === ''
                ? 'Escribe el nombre del sector. Ejemplo: Estante A.'
                : 'El nombre es muy largo: usa 80 letras como máximo.']);
        }

        if ($this->sectores->idPorNombre($limpios['nombre'], $id) !== null) {
            throw new ValidationException(['nombre' => 'Ya hay un sector con ese nombre. Usa otro, por ejemplo agregando un número.']);
        }

        return $limpios['nombre'];
    }
}
