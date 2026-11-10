<?php

declare(strict_types=1);

namespace App\Modules\Historial;

use App\Support\Pdf;

defined('APP_ROOT') || exit;

/** Informe formal A4 apaisado, monocromático y paginado, con las mismas tres vistas del Excel. */
final class MovimientoPdf
{
    private Pdf $pdf;
    private float $y = 0;
    private string $seccion = '';
    private string $seccionBase = '';

    /**
     * @param array{productos: list<array<string, mixed>>, detalle: list<array<string, mixed>>, entraron: int, salieron: int, ajustes: int, rango: string} $datos
     * @param array{periodo: string, tipo: ?string, pagina: int} $filtros
     */
    public function __construct(private readonly array $datos, private readonly array $filtros, private readonly string $quien)
    {
        $this->pdf = new Pdf();
    }

    public function contenido(): string
    {
        $productos = $this->datos['productos'];
        $this->pagina('Resumen general');
        $this->separador('Totales del período');
        $this->tabla(['Concepto', 'Cantidad'], [
            ['Unidades que entraron', $this->datos['entraron']],
            ['Unidades que salieron', $this->datos['salieron']],
            ['Ajustes de cantidad (neto)', $this->datos['ajustes']],
            ['Productos con movimiento', count($productos)],
        ], [400, 130], [1]);

        $retirados = array_values(array_filter($productos, static fn (array $p): bool => (int) $p['salieron'] > 0));
        usort($retirados, static fn (array $a, array $b): int => [(int) $b['salieron'], $a['nombre']] <=> [(int) $a['salieron'], $b['nombre']]);
        $this->separador('Productos más retirados');
        $this->tabla(['N°', 'Código', 'Producto', 'Salieron'], array_map(
            static fn (int $n, array $p): array => [$n + 1, $p['codigo'], $p['nombre'], (int) $p['salieron']],
            array_keys(array_slice($retirados, 0, 10)), array_slice($retirados, 0, 10),
        ), [45, 100, 420, 90], [0, 3], false, 'No salió ningún producto en este período.');

        $categorias = [];
        foreach ($productos as $p) {
            $categorias[$p['categoria']] ??= [$p['categoria'], 0, 0];
            $categorias[$p['categoria']][1] += (int) $p['entraron'];
            $categorias[$p['categoria']][2] += (int) $p['salieron'];
        }
        $this->separador('Movimientos por categoría');
        $this->tabla(['Categoría', 'Entraron', 'Salieron'],
            $categorias === [] ? [] : [...array_values($categorias), ['Total', $this->datos['entraron'], $this->datos['salieron']]],
            [420, 120, 120], [1, 2], true);

        if ($productos === []) {
            return $this->pdf->contenido('Bodega FCF UdeC | Entradas y salidas');
        }

        $this->pagina('Movimientos por producto');
        $porProducto = array_map(static fn (array $p): array => [
            $p['codigo'], $p['nombre'], $p['categoria'],
            (int) $p['stock_actual'] - (int) $p['neto'], (int) $p['entraron'], (int) $p['salieron'],
            (int) $p['ajustes'], (int) $p['stock_actual'],
        ], $productos);
        if ($porProducto !== []) {
            $porProducto[] = ['Total', '', '', null, $this->datos['entraron'], $this->datos['salieron'], $this->datos['ajustes'], null];
        }
        $this->tabla(['Código', 'Producto', 'Categoría', 'Inicial', 'Entraron', 'Salieron', 'Ajustes', 'Actual'],
            $porProducto, [66, 216, 160, 65, 65, 65, 65, 60], [3, 4, 5, 6, 7], true);

        $this->pagina('Detalle de movimientos');
        $filas = array_map(static function (array $m): array {
            $contexto = [];
            if (($m['reserva'] ?? null) !== null) {
                $contexto[] = 'Reserva ' . $m['reserva'];
            }
            if (($m['sector'] ?? null) !== null) {
                $contexto[] = 'Sector ' . $m['sector_codigo'] . ' - ' . $m['sector'];
            }
            if (($m['motivo'] ?? null) !== null && $m['motivo'] !== '') {
                $contexto[] = 'Motivo: ' . $m['motivo'];
            }
            $cantidad = (int) $m['cantidad'];
            return [
                date('d/m/Y H:i', (int) strtotime((string) $m['fecha'])),
                ['entrada' => 'Entrada', 'salida' => 'Salida', 'ajuste' => 'Ajuste'][$m['tipo']] ?? $m['tipo'],
                $m['codigo'] . ' - ' . $m['nombre'],
                ($cantidad > 0 ? '+' : '') . $cantidad,
                (int) $m['stock_resultante'],
                $m['quien'] ?? '-',
                $contexto === [] ? '-' : implode(' | ', $contexto),
            ];
        }, $this->datos['detalle']);
        $this->tabla(['Fecha y hora', 'Tipo', 'Producto / código', 'Cambio', 'Saldo', 'Quién', 'Contexto'],
            $filas, [88, 59, 176, 56, 50, 105, 228], [3, 4]);

        return $this->pdf->contenido('Bodega FCF UdeC | Entradas y salidas');
    }

    private function pagina(string $seccion, bool $continuacion = false): void
    {
        $this->seccion = $seccion;
        if (!$continuacion) {
            $this->seccionBase = $seccion;
        }
        $this->pdf->pagina();
        $this->pdf->texto(40, 41, 'UNIVERSIDAD DE CONCEPCIÓN', 9, true);
        $this->pdf->texto(40, 55, 'FACULTAD DE CIENCIAS FORESTALES', 8);
        $this->pdf->texto(802, 48, 'BODEGA FCF', 9, true, 'derecha');
        $this->pdf->linea(40, 67, 802, 67, 1);
        $this->pdf->texto(40, 93, 'INFORME DE ENTRADAS Y SALIDAS', 16, true);
        $this->pdf->texto(40, 113, $seccion, 11, true);
        $this->pdf->texto(802, 93, 'Emitido: ' . date('d/m/Y H:i'), 8, false, 'derecha');
        $this->pdf->texto(40, 135, 'Período: ' . MovimientoService::PERIODOS[$this->filtros['periodo']]
            . ' (' . $this->datos['rango'] . ')  |  Tipo: ' . MovimientoService::TIPOS[$this->filtros['tipo'] ?? ''], 8.5);
        $this->pdf->texto(40, 150, 'Preparado por: ' . ($this->quien !== '' ? $this->quien : 'No indicado'), 8.5);
        $this->y = 158;
    }

    private function separador(string $titulo): void
    {
        if ($this->y > 492) {
            $this->pagina($this->seccionBase . ' (continuación)', true);
        }
        $this->y += 6;
        $this->pdf->texto(40, $this->y + 9, $titulo, 10, true);
        $this->pdf->linea(40, $this->y + 15, 802, $this->y + 15, 0.4);
        $this->y += 22;
    }

    /**
     * @param list<string> $cabeceras
     * @param list<list<string|int|float|null>> $filas
     * @param list<int> $anchos
     * @param list<int> $numericas columnas alineadas a la derecha
     */
    private function tabla(array $cabeceras, array $filas, array $anchos, array $numericas = [], bool $totalFinal = false, string $mensajeVacio = 'No hubo movimientos en este período.'): void
    {
        $this->cabeceraTabla($cabeceras, $anchos);
        if ($filas === []) {
            $this->pdf->texto(47, $this->y + 17, $mensajeVacio, 9);
            $this->y += 25;
            return;
        }
        foreach ($filas as $indice => $fila) {
            $lineas = [];
            foreach ($fila as $columna => $valor) {
                $lineas[] = Pdf::partir((string) ($valor ?? ''), $anchos[$columna] - 14, 8.2);
            }
            $alto = max(25, max(array_map('count', $lineas)) * 11 + 10);
            if ($this->y + $alto > 539) {
                $this->pagina($this->seccionBase . ' (continuación)', true);
                $this->cabeceraTabla($cabeceras, $anchos);
            }
            $esTotal = $totalFinal && $indice === count($filas) - 1;
            if ($esTotal) {
                $this->pdf->fondo(40, $this->y, array_sum($anchos), $alto, 0.88);
                $this->pdf->linea(40, $this->y, 40 + array_sum($anchos), $this->y, 0.8);
            } elseif ($indice % 2 === 1) {
                $this->pdf->fondo(40, $this->y, array_sum($anchos), $alto, 0.965);
            }
            $x = 40;
            foreach ($lineas as $columna => $partes) {
                foreach ($partes as $n => $linea) {
                    $derecha = in_array($columna, $numericas, true);
                    $this->pdf->texto($derecha ? $x + $anchos[$columna] - 7 : $x + 7,
                        $this->y + 16 + $n * 11, $linea, 8.2, $esTotal, $derecha ? 'derecha' : 'izquierda');
                }
                $x += $anchos[$columna];
            }
            $this->pdf->linea(40, $this->y + $alto, 40 + array_sum($anchos), $this->y + $alto, 0.25);
            $this->y += $alto;
        }
        $this->y += 7;
    }

    /** @param list<string> $cabeceras @param list<int> $anchos */
    private function cabeceraTabla(array $cabeceras, array $anchos): void
    {
        if ($this->y + 24 > 539) {
            $this->pagina($this->seccionBase . ' (continuación)', true);
        }
        $this->pdf->fondo(40, $this->y, array_sum($anchos), 24, 0.87);
        $x = 40;
        foreach ($cabeceras as $columna => $cabecera) {
            $this->pdf->texto($x + 7, $this->y + 16, $cabecera, 8.2, true);
            $x += $anchos[$columna];
        }
        $this->pdf->linea(40, $this->y + 24, 40 + array_sum($anchos), $this->y + 24, 0.7);
        $this->y += 24;
    }
}
