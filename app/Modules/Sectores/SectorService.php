<?php

declare(strict_types=1);

namespace App\Modules\Sectores;

use App\Core\Database;
use App\Core\ValidationException;
use App\Modules\Historial\AuditoriaService;

defined('APP_ROOT') || exit;

/**
 * Sectores de bodega: cada parte de la bodega (una repisa, un cubículo) con su código, el de la etiqueta impresa
 * pegada en el lugar (Code 128, docs/etiquetas). Al agregar un sector se escanea esa etiqueta; al registrar un producto
 * se vuelve a escanear y el producto queda en ese sector.
 */
final class SectorService
{
    public function __construct(
        private readonly SectorRepository $sectores = new SectorRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService(),
    ) {
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

    /** @return array<string, mixed>|null el sector de un código leído o escrito (los eliminados ya no existen) */
    public function buscarPorCodigo(string $codigo): ?array
    {
        $codigo = trim($codigo);

        return $codigo === '' ? null : $this->sectores->findPorCodigo($codigo);
    }

    /**
     * Crea el sector con el código de su etiqueta (escaneado). Sin código, el sistema le da el siguiente (SEC001…); sin
     * nombre, se arma del código de la etiqueta (M12 → «Mueble de madera · Cubículo 12»).
     *
     * @param array<string, mixed> $datos codigo y nombre
     * @return array{id: int, codigo: string, nombre: string}
     * @throws ValidationException
     */
    public function crear(array $datos): array
    {
        [$codigo, $nombre] = $this->validar($datos, null);

        return Database::transaction(function () use ($codigo, $nombre): array {
            $codigo ??= $this->sectores->siguienteCodigo();
            $this->sectores->liberarCodigo($codigo);
            $this->sectores->liberarNombre($nombre);
            $id = $this->sectores->create($codigo, $nombre);
            $this->auditoria->registrar('crear', 'sector', $id, ['codigo' => $codigo, 'nombre' => $nombre]);

            return ['id' => $id, 'codigo' => $codigo, 'nombre' => $nombre];
        });
    }

    /**
     * Cambia el código (se escanea otra etiqueta) y el nombre. Sin código, se mantiene el que tenía.
     *
     * @param array<string, mixed> $datos codigo y nombre
     * @throws ValidationException
     */
    public function editar(int $id, array $datos): void
    {
        $actual = $this->sectores->findById($id) ?? throw new ValidationException(['nombre' => 'Ese sector no existe.']);
        [$codigo, $nombre] = $this->validar($datos, $id);
        $codigo ??= $actual['codigo'];

        if ($codigo === $actual['codigo'] && $nombre === $actual['nombre']) {
            return;
        }

        Database::transaction(function () use ($id, $codigo, $nombre, $actual): void {
            $this->sectores->liberarCodigo($codigo);
            $this->sectores->liberarNombre($nombre);
            $this->sectores->update($id, $codigo, $nombre);
            $this->auditoria->registrar('editar', 'sector', $id, AuditoriaService::diferencias(
                ['codigo' => $actual['codigo'], 'nombre' => $actual['nombre']],
                ['codigo' => $codigo, 'nombre' => $nombre],
            ));
        });
    }

    /**
     * El nombre que corresponde al código de una etiqueta impresa (docs/etiquetas/generar.py): A03 → «Estante metálico
     * A · Repisa 3», M12 → «Mueble de madera · Cubículo 12». Un código de otra forma se usa tal cual.
     */
    public static function nombreDeEtiqueta(string $codigo): string
    {
        if (preg_match('/^([AB])0*(\d{1,3})$/', $codigo, $m) === 1) {
            return "Estante metálico {$m[1]} · Repisa {$m[2]}";
        }
        if (preg_match('/^M0*(\d{1,3})$/', $codigo, $m) === 1) {
            return "Mueble de madera · Cubículo {$m[1]}";
        }

        return $codigo;
    }

    /**
     * «Eliminar»: el sector desaparece del sistema para las personas y sus productos quedan sin ubicación; por dentro
     * solo se desactiva (SectorRepository::eliminar).
     */
    public function eliminar(int $id): void
    {
        $sector = $this->sectores->findById($id);
        if ($sector === null) {
            return;
        }

        Database::transaction(function () use ($id, $sector): void {
            $this->sectores->eliminar($id);
            $this->auditoria->registrar('eliminar', 'sector', $id, [
                'codigo' => $sector['codigo'],
                'nombre' => $sector['nombre'],
                'productos' => (int) $sector['productos'],
            ]);
        });
    }

    /**
     * @param array<string, mixed> $datos codigo y nombre
     * @return array{0: ?string, 1: string} el código (null: sin código) y el nombre
     * @throws ValidationException
     */
    private function validar(array $datos, ?int $id): array
    {
        $errores = [];

        // El código, como lo manda la pistola: sin espacios y en mayúsculas («m12» y «M12» son la misma etiqueta).
        $codigo = strtoupper(preg_replace('/\s+/u', '', (string) ($datos['codigo'] ?? '')) ?? '');
        if ($codigo === '') {
            $codigo = null;
        } elseif (preg_match('/^[A-Z0-9][A-Z0-9-]{0,19}$/', $codigo) !== 1) {
            $errores['codigo'] = 'Ese código no sirve: usa solo letras y números, hasta 20. Ejemplo: M12.';
        } elseif ($this->sectores->idPorCodigo($codigo, $id) !== null) {
            $errores['codigo'] = 'Esa etiqueta ya es de otro sector. Escanea otra.';
        } elseif (($producto = $this->sectores->productoConCodigo($codigo)) !== null) {
            $errores['codigo'] = "Ese código es del producto «{$producto}», no de una etiqueta de sector.";
        }

        // Espacios repetidos fuera: "Estante  A" y "Estante A" son el mismo nombre. Sin nombre, el de la etiqueta.
        $nombre = preg_replace('/\s+/u', ' ', trim((string) ($datos['nombre'] ?? ''))) ?? '';
        if ($nombre === '') {
            if ($codigo === null) {
                $errores['codigo'] ??= 'Escanea la etiqueta del sector, o escribe su nombre.';
            } else {
                $nombre = self::nombreDeEtiqueta($codigo);
            }
        }
        if (mb_strlen($nombre) > 80) {
            $errores['nombre'] = 'El nombre es muy largo: usa 80 letras como máximo.';
        } elseif ($nombre !== '' && $this->sectores->idPorNombre($nombre, $id) !== null) {
            $errores['nombre'] = 'Ya hay un sector con ese nombre. Usa otro, por ejemplo agregando un número.';
        }

        if ($errores !== []) {
            throw new ValidationException($errores);
        }

        return [$codigo, $nombre];
    }
}
