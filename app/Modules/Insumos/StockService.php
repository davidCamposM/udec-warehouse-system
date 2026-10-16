<?php

declare(strict_types=1);

namespace App\Modules\Insumos;

use App\Core\Auth;
use App\Core\Database;
use App\Core\ValidationException;
use App\Modules\Sectores\SectorRepository;

defined('APP_ROOT') || exit;

/**
 * El ÚNICO lugar que cambia el stock de los productos: entradas (Registrar producto e Ingresar abastecimiento), ajustes
 * (Actualizar cantidad) y salidas (Retirar producto). Cada cambio queda como un movimiento con su ítem y el stock que resultó, así la suma de
 * los movimientos siempre explica la cantidad actual.
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
     * @return array{insumo_id: int, nombre: string, cantidad: int, stock: int, sector: string, sector_codigo: string}
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
                throw new ValidationException(['codigo' => 'Ese sector ya no existe. Elige otro.']);
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
                'sector_codigo' => $sector['codigo'],
            ];
        });
    }

    /**
     * Abastecimiento: suma todo lo que llegó con una reserva, en una sola entrada con su número. Los productos siguen en
     * su lugar de siempre (sin lugar, si nunca se guardaron): las administrativas no eligen dónde queda.
     *
     * @param array<int, int> $productos insumo_id => cantidad
     * @return list<array{insumo_id: int, nombre: string, cantidad: int, stock: int}>
     * @throws ValidationException si una cantidad no sirve o un producto ya no existe
     */
    public function ingresarReserva(string $reserva, array $productos): array
    {
        if ($productos === []) {
            throw new ValidationException(['codigo' => 'Agrega al menos un producto de los que llegaron.']);
        }
        foreach ($productos as $cantidad) {
            self::validarCantidad($cantidad);
        }

        return Database::transaction(function () use ($reserva, $productos): array {
            $movimiento = $this->stock->crearMovimiento('entrada', (int) Auth::id(), $reserva);
            $ingresados = [];
            foreach ($productos as $insumoId => $cantidad) {
                $insumo = $this->stock->bloquearInsumo($insumoId);
                if ($insumo === null || (int) $insumo['activo'] !== 1) {
                    throw new ValidationException(['codigo' => 'Uno de los productos de la lista ya no existe. Quítalo y vuelve a ingresar.']);
                }
                $stock = (int) $insumo['stock_actual'] + $cantidad;
                $this->stock->agregarItem($movimiento, $insumoId, $cantidad, $stock, null);
                $this->stock->actualizarStock($insumoId, $stock, null);
                $ingresados[] = ['insumo_id' => $insumoId, 'nombre' => (string) $insumo['nombre'], 'cantidad' => $cantidad, 'stock' => $stock];
            }

            return $ingresados;
        });
    }

    /**
     * El N° de reserva como lo muestra SAP: 10 dígitos («18857» → «0000018857»). Acepta lo que se copió del correo
     * («Reserva 0000018857»).
     *
     * @throws ValidationException si no es un número de hasta 10 dígitos
     */
    public static function numeroReserva(mixed $valor): string
    {
        $digitos = preg_replace('/\D/', '', is_string($valor) ? $valor : '') ?? '';
        if ($digitos === '' || strlen(ltrim($digitos, '0')) === 0 || strlen($digitos) > 10) {
            throw new ValidationException(['reserva' => 'Escribe el número de la reserva, solo con números. Ejemplo: 0000018857 (o 18857).']);
        }

        return str_pad(ltrim($digitos, '0'), 10, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{fecha: string, quien: ?string, productos: int}|null la última vez que se ingresó esa reserva
     */
    public function reservaIngresada(string $reserva): ?array
    {
        return $this->stock->reservaIngresada($reserva);
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
     * Salida: descuenta lo que se retira de la bodega. Si no alcanza, no descuenta nada (el stock nunca queda negativo).
     *
     * @return array{insumo_id: int, nombre: string, cantidad: int, stock: int}
     * @throws ValidationException si la cantidad no sirve, el producto no está activo o no alcanza
     */
    public function retirar(int $insumoId, int $cantidad): array
    {
        if ($cantidad < 1 || $cantidad > self::MAXIMO) {
            throw new ValidationException(['cantidad' => 'Escribe cuántas unidades se retiran, con un número entero desde 1. Ejemplo: 2.']);
        }

        return Database::transaction(function () use ($insumoId, $cantidad): array {
            $insumo = $this->stock->bloquearInsumo($insumoId);
            if ($insumo === null || (int) $insumo['activo'] !== 1) {
                throw new ValidationException(['codigo' => 'Ese producto ya no está activo. Empieza de nuevo.']);
            }

            $hay = (int) $insumo['stock_actual'];
            if ($hay < $cantidad) {
                throw new ValidationException(['cantidad' => $hay === 0
                    ? "No quedan unidades de «{$insumo['nombre']}» en bodega."
                    : 'No alcanza: en bodega ' . ($hay === 1 ? 'queda ' : 'quedan ') . unidades($hay) . '. Corrige la cantidad.']);
            }

            $stock = $hay - $cantidad;
            $movimiento = $this->stock->crearMovimiento('salida', (int) Auth::id());
            $this->stock->agregarItem($movimiento, $insumoId, -$cantidad, $stock, null);
            $this->stock->actualizarStock($insumoId, $stock, null);

            return [
                'insumo_id' => $insumoId,
                'nombre' => $insumo['nombre'],
                'cantidad' => $cantidad,
                'stock' => $stock,
            ];
        });
    }

    /**
     * El número que se veía en pantalla justo antes de escanear otra vez el producto (lo deja ui.js en el campo oculto
     * «cantidad_antes», porque la lectura reemplaza el número del campo). Si no llegó o no sirve, la guardada.
     */
    public static function cantidadAntes(mixed $antes, int $guardada): int
    {
        return is_string($antes) && ctype_digit($antes) && (int) $antes >= 1 && (int) $antes <= self::MAXIMO
            ? (int) $antes
            : $guardada;
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
