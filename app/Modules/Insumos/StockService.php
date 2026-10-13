<?php

declare(strict_types=1);

namespace App\Modules\Insumos;

use App\Core\Auth;
use App\Core\Database;
use App\Core\ValidationException;
use App\Modules\Sectores\SectorRepository;

defined('APP_ROOT') || exit;

/**
 * El ÚNICO lugar que cambia el stock de los productos: entradas (Registrar producto), ajustes y salidas (punto de
 * retiro). Cada cambio queda como un movimiento con su ítem y el stock que resultó, así la suma de los movimientos
 * siempre explica la cantidad actual.
 */
final class StockService
{
    /** Máximo que se acepta en una sola operación: evita errores de tipeo como 1000000. */
    public const MAXIMO = 99999;

    /** Por qué se ajusta una cantidad: clave => texto que queda en el movimiento. */
    public const MOTIVOS_AJUSTE = [
        'carga' => 'Carga inicial',
        'conteo' => 'Conteo físico',
        'danado' => 'Producto dañado o vencido',
        'error' => 'Corrección de un error',
        'otro' => 'Otro motivo',
    ];

    public function __construct(
        private readonly StockRepository $stock = new StockRepository(),
        private readonly SectorRepository $sectores = new SectorRepository(),
    ) {
    }

    /**
     * Entrada: suma lo que llegó y deja el producto en el sector donde se guardó.
     *
     * @return array{insumo_id: int, nombre: string, cantidad: int, stock: int, sector: string}
     * @throws ValidationException si la cantidad, el producto o el sector no sirven
     */
    public function guardarEnBodega(int $insumoId, int $cantidad, int $sectorId): array
    {
        self::validarCantidad($cantidad);

        return Database::transaction(function () use ($insumoId, $cantidad, $sectorId): array {
            $insumo = $this->stock->bloquearInsumo($insumoId);
            if ($insumo === null || (int) $insumo['activo'] !== 1) {
                throw new ValidationException(['codigo' => 'Ese producto ya no está activo. Empieza de nuevo.']);
            }

            $sector = $this->sectores->findById($sectorId);
            if ($sector === null || (int) $sector['activo'] !== 1) {
                throw new ValidationException(['codigo' => 'Ese sector no existe o está desactivado. Elige otro.']);
            }

            $stock = (int) $insumo['stock_actual'] + $cantidad;
            $movimiento = $this->stock->crearMovimiento('entrada', (int) Auth::id());
            $this->stock->agregarItem($movimiento, $insumoId, $cantidad, $stock, $sectorId);
            $this->stock->actualizarStock($insumoId, $stock, $sectorId);

            return [
                'insumo_id' => $insumoId,
                'nombre' => $insumo['nombre'],
                'cantidad' => $cantidad,
                'stock' => $stock,
                'sector' => $sector['nombre'],
            ];
        });
    }

    /**
     * Ajuste: deja el producto con lo que realmente hay en la bodega. $stockVisto es la cantidad que la persona tenía
     * a la vista al contar: si cambió mientras tanto (alguien guardó o retiró), no se ajusta y se avisa.
     *
     * @return array{insumo_id: int, nombre: string, antes: int, ahora: int, diferencia: int, motivo: string}
     * @throws ValidationException si la cantidad no sirve, no cambia nada o el stock cambió mientras se contaba
     */
    public function ajustar(int $insumoId, int $contado, int $stockVisto, string $motivo): array
    {
        if ($contado < 0 || $contado > self::MAXIMO) {
            throw new ValidationException(['contado' => 'Escribe cuántas hay, con un número entero entre 0 y '
                . cantidad(self::MAXIMO) . '.']);
        }

        return Database::transaction(function () use ($insumoId, $contado, $stockVisto, $motivo): array {
            $insumo = $this->stock->bloquearInsumo($insumoId);
            if ($insumo === null || (int) $insumo['activo'] !== 1) {
                throw new ValidationException(['codigo' => 'Ese producto ya no está activo. Empieza de nuevo.']);
            }

            $antes = (int) $insumo['stock_actual'];
            if ($antes !== $stockVisto) {
                throw new ValidationException(['contado' => 'Mientras actualizabas, la cantidad cambió: ahora el sistema dice '
                    . unidades($antes) . '. Revisa y confirma otra vez.']);
            }

            $diferencia = $contado - $antes;
            if ($diferencia === 0) {
                throw new ValidationException(['contado' => 'Es la misma cantidad que dice el sistema: no hay nada que actualizar.']);
            }

            $movimiento = $this->stock->crearMovimiento('ajuste', (int) Auth::id(), motivo: $motivo);
            $this->stock->agregarItem($movimiento, $insumoId, $diferencia, $contado, null);
            $this->stock->actualizarStock($insumoId, $contado, null);

            return [
                'insumo_id' => $insumoId,
                'nombre' => $insumo['nombre'],
                'antes' => $antes,
                'ahora' => $contado,
                'diferencia' => $diferencia,
                'motivo' => $motivo,
            ];
        });
    }

    /**
     * Entrada en el punto de retiro (la registra un auxiliar): suma todo lo que llegó en una sola operación y deja cada
     * producto en el sector elegido.
     *
     * @param array<int, int> $items insumo_id => cantidad, en el orden en que se escanearon
     * @return list<array{insumo_id: int, nombre: string, cantidad: int, stock: int}> lo que se registró
     * @throws ValidationException si no hay nada, una cantidad no sirve, un producto o el sector no está activo
     */
    public function registrarEntradaEnPunto(int $personaId, int $estacionId, array $items, int $sectorId): array
    {
        if ($items === []) {
            throw new ValidationException(['carrito' => 'Escanea lo que llegó.']);
        }
        foreach ($items as $cantidad) {
            self::validarCantidad($cantidad);
        }

        return Database::transaction(function () use ($personaId, $estacionId, $items, $sectorId): array {
            $sector = $this->sectores->findById($sectorId);
            if ($sector === null || (int) $sector['activo'] !== 1) {
                throw new ValidationException(['sector_id' => 'Ese sector no existe o está desactivado. Elige otro.']);
            }

            // Se bloquean en orden de id, como en los retiros.
            $ids = array_keys($items);
            sort($ids);
            $insumos = [];
            foreach ($ids as $id) {
                $insumo = $this->stock->bloquearInsumo($id);
                if ($insumo === null || (int) $insumo['activo'] !== 1) {
                    throw new ValidationException(['carrito' => 'Un producto de la lista ya no está activo. Quítalo y confirma otra vez.']);
                }
                $insumos[$id] = $insumo;
            }

            $movimiento = $this->stock->crearEntradaEnPunto($personaId, $estacionId);
            $registrado = [];

            foreach ($items as $id => $cantidad) {
                $stock = (int) $insumos[$id]['stock_actual'] + $cantidad;
                $this->stock->agregarItem($movimiento, $id, $cantidad, $stock, $sectorId);
                $this->stock->actualizarStock($id, $stock, $sectorId);
                $registrado[] = ['insumo_id' => $id, 'nombre' => $insumos[$id]['nombre'], 'cantidad' => $cantidad, 'stock' => $stock];
            }

            return $registrado;
        });
    }

    /**
     * Salida en el punto de retiro: descuenta todo lo que la persona lleva en una sola operación. Si de algún producto
     * no alcanza, no se descuenta nada (el stock nunca queda negativo).
     *
     * @param array<int, int> $items insumo_id => cantidad, en el orden en que se escanearon
     * @return list<array{insumo_id: int, nombre: string, cantidad: int, stock: int}> lo que se retiró
     * @throws ValidationException si no hay nada, una cantidad no sirve o no alcanza
     */
    public function retirar(int $personaId, int $estacionId, array $items): array
    {
        if ($items === []) {
            throw new ValidationException(['carrito' => 'Escanea lo que vas a retirar.']);
        }
        foreach ($items as $cantidad) {
            if ($cantidad < 1 || $cantidad > self::MAXIMO) {
                throw new ValidationException(['carrito' => 'Revisa las cantidades: cada una debe ser un número entero desde 1.']);
            }
        }

        return Database::transaction(function () use ($personaId, $estacionId, $items): array {
            // Se bloquean en orden de id: dos retiros simultáneos con los mismos productos no se traban entre sí.
            $ids = array_keys($items);
            sort($ids);
            $insumos = [];
            $faltan = [];

            foreach ($ids as $id) {
                $insumo = $this->stock->bloquearInsumo($id);
                if ($insumo === null || (int) $insumo['activo'] !== 1) {
                    $faltan[] = 'un producto ya no está disponible';
                } elseif ((int) $insumo['stock_actual'] < $items[$id]) {
                    $faltan[] = "de «{$insumo['nombre']}» " . ((int) $insumo['stock_actual'] === 1 ? 'queda ' : 'quedan ')
                        . unidades($insumo['stock_actual']);
                }
                $insumos[$id] = $insumo;
            }

            if ($faltan !== []) {
                throw new ValidationException(['carrito' => 'No alcanza: ' . implode('; ', $faltan) . '. Corrige la cantidad.']);
            }

            $movimiento = $this->stock->crearSalida($personaId, $estacionId);
            $retirado = [];

            foreach ($items as $id => $cantidad) {
                $stock = (int) $insumos[$id]['stock_actual'] - $cantidad;
                $this->stock->agregarItem($movimiento, $id, -$cantidad, $stock, null);
                $this->stock->actualizarStock($id, $stock, null);
                $retirado[] = ['insumo_id' => $id, 'nombre' => $insumos[$id]['nombre'], 'cantidad' => $cantidad, 'stock' => $stock];
            }

            return $retirado;
        });
    }

    /** @throws ValidationException */
    public static function validarCantidad(int $cantidad): void
    {
        if ($cantidad < 1 || $cantidad > self::MAXIMO) {
            throw new ValidationException(['cantidad' => 'Escribe cuántas unidades llegaron, con un número entero entre 1 y '
                . cantidad(self::MAXIMO) . '. Ejemplo: 12.']);
        }
    }
}
