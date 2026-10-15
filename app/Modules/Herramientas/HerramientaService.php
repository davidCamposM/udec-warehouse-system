<?php

declare(strict_types=1);

namespace App\Modules\Herramientas;

use App\Core\ValidationException;
use App\Modules\Historial\AuditoriaService;

defined('APP_ROOT') || exit;

/**
 * Catálogo de herramientas: lo que se puede pedir prestado, agrupado por categoría. El encargado agrega, renombra,
 * cambia de categoría y elimina. Para las personas, eliminar la saca del sistema; por dentro solo se desactiva (los
 * préstamos antiguos la siguen mostrando).
 */
final class HerramientaService
{
    /** Nombre del grupo para las herramientas que no tienen categoría. */
    public const SIN_CATEGORIA = 'Otras';

    public function __construct(
        private readonly HerramientaRepository $herramientas = new HerramientaRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService(),
    ) {
    }

    /**
     * Las activas agrupadas por categoría: categoría => herramientas (las sin categoría al final, en «Otras»).
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function porCategoria(): array
    {
        $grupos = [];
        foreach ($this->herramientas->activas() as $herramienta) {
            $grupos[$herramienta['categoria'] ?? self::SIN_CATEGORIA][] = $herramienta;
        }

        return $grupos;
    }

    /** @return list<array{id: int, nombre: string}> */
    public function categorias(): array
    {
        return $this->herramientas->categorias();
    }

    /** @return array<string, mixed>|null */
    public function buscarPorId(int $id): ?array
    {
        return $this->herramientas->findById($id);
    }

    /**
     * @param array<string, mixed> $datos nombre y categoria_id
     * @return string el nombre con que quedó
     * @throws ValidationException
     */
    public function crear(array $datos): string
    {
        [$nombre, $categoriaId] = $this->validar($datos, null);
        $id = $this->herramientas->create($nombre, $categoriaId);
        $this->auditoria->registrar('crear', 'herramienta', $id, ['nombre' => $nombre, 'categoria_id' => $categoriaId]);

        return $nombre;
    }

    /**
     * @param array<string, mixed> $datos nombre y categoria_id
     * @throws ValidationException
     */
    public function editar(int $id, array $datos): void
    {
        $actual = $this->herramientas->findById($id) ?? throw new ValidationException(['nombre' => 'Esa herramienta no existe.']);
        [$nombre, $categoriaId] = $this->validar($datos, $id);
        $this->herramientas->update($id, $nombre, $categoriaId);
        $this->auditoria->registrar('editar', 'herramienta', $id, AuditoriaService::diferencias(
            ['nombre' => $actual['nombre'], 'categoria_id' => $actual['categoria_id']],
            ['nombre' => $nombre, 'categoria_id' => $categoriaId],
        ));
    }

    public function eliminar(int $id): void
    {
        $this->herramientas->eliminar($id);
        $this->auditoria->registrar('eliminar', 'herramienta', $id);
    }

    /**
     * @param array<string, mixed> $datos
     * @return array{0: string, 1: ?int} el nombre y la categoría
     * @throws ValidationException
     */
    private function validar(array $datos, ?int $id): array
    {
        $errores = [];
        $nombre = preg_replace('/\s+/', ' ', trim((string) ($datos['nombre'] ?? ''))) ?? '';

        if ($nombre === '') {
            $errores['nombre'] = 'Escribe el nombre de la herramienta. Ejemplo: Hipsómetro.';
        } elseif (mb_strlen($nombre) > 150) {
            $errores['nombre'] = 'El nombre puede tener hasta 150 letras.';
        } elseif ($this->herramientas->nombreEnUso($nombre, $id)) {
            $errores['nombre'] = 'Ya hay una herramienta con ese nombre.';
        }

        $categoria = (string) ($datos['categoria_id'] ?? '');
        $categoriaId = null;
        if ($categoria !== '') {
            $validas = array_column($this->herramientas->categorias(), 'id');
            $categoriaId = ctype_digit($categoria) && in_array((int) $categoria, array_map('intval', $validas), true)
                ? (int) $categoria
                : null;
            if ($categoriaId === null) {
                $errores['categoria_id'] = 'Elige una de las categorías.';
            }
        }

        if ($errores !== []) {
            throw new ValidationException($errores);
        }

        return [$nombre, $categoriaId];
    }
}
