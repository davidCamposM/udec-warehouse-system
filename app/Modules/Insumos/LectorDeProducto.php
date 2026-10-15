<?php

declare(strict_types=1);

namespace App\Modules\Insumos;

use App\Core\ValidationException;
use App\Modules\Sectores\SectorService;

defined('APP_ROOT') || exit;

/**
 * Interpreta lo que llega del cuadro «Escanea el código del producto o escribe su nombre» (Registrar producto,
 * Actualizar cantidad, Retirar producto): un código de producto, un código que nadie tiene o un nombre para buscar.
 */
final class LectorDeProducto
{
    public function __construct(
        private readonly InsumoService $insumos = new InsumoService(),
        private readonly SectorService $sectores = new SectorService(),
    ) {
    }

    /**
     * @return array{tipo: 'producto', id: int}|array{tipo: 'desconocido', codigo: string}|array{tipo: 'nombre', texto: string}
     * @throws ValidationException si viene vacío o es el código de un sector
     */
    public function leer(mixed $entrada): array
    {
        $texto = is_string($entrada) ? trim($entrada) : '';
        if ($texto === '') {
            throw new ValidationException(['codigo' => 'Escanea el código del producto o escribe su nombre.']);
        }

        $id = $this->insumos->idPorCodigo($texto);
        if ($id !== null) {
            return ['tipo' => 'producto', 'id' => $id];
        }

        if ($this->sectores->buscarPorCodigo($texto) !== null) {
            throw new ValidationException(['codigo' => 'Ese es el código de un sector. Escanea el código del producto.']);
        }

        // Sin espacios, con números y sin productos que coincidan: es un código que nadie tiene.
        if (!preg_match('/\s/', $texto) && preg_match('/\d/', $texto) === 1 && $this->insumos->buscar($texto, 'todos') === []) {
            return ['tipo' => 'desconocido', 'codigo' => $texto];
        }

        return ['tipo' => 'nombre', 'texto' => $texto];
    }

    /**
     * El producto elegido o leído, si se puede usar.
     *
     * @return array<string, mixed>
     * @throws ValidationException si no existe o está desactivado
     */
    public function productoActivo(int $id): array
    {
        $insumo = $this->insumos->buscarPorId($id) ?? throw new ValidationException(['codigo' => 'Ese producto no existe.']);

        if ((int) $insumo['activo'] !== 1) {
            throw new ValidationException(['codigo' => "«{$insumo['nombre']}» está desactivado. Primero actívalo desde su ficha."]);
        }

        return $insumo;
    }
}
