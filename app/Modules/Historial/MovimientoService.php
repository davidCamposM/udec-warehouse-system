<?php

declare(strict_types=1);

namespace App\Modules\Historial;

use App\Support\Xlsx;
use DateTimeImmutable;

defined('APP_ROOT') || exit;

/**
 * «Entradas y salidas»: lo que entró, salió o se ajustó en la bodega, por período y tipo, por páginas. También la
 * descarga para Excel (resumen, por producto y detalle).
 */
final class MovimientoService
{
    public const POR_PAGINA = 20;

    /** Período => cómo se muestra. */
    public const PERIODOS = [
        'hoy' => 'Hoy',
        'semana' => 'Últimos 7 días',
        'mes' => 'Este mes',
        'todo' => 'Todo',
    ];

    /** Tipo => cómo se muestra (la clave vacía: todos). */
    public const TIPOS = [
        '' => 'Todos',
        'entrada' => 'Entradas',
        'salida' => 'Salidas',
        'ajuste' => 'Ajustes',
    ];

    /** Cómo se llama cada tipo en una fila de la descarga. */
    private const TIPO_SINGULAR = ['entrada' => 'Entrada', 'salida' => 'Salida', 'ajuste' => 'Ajuste'];

    public function __construct(
        private readonly MovimientoRepository $movimientos = new MovimientoRepository(),
    ) {
    }

    /**
     * Filtros válidos: cualquier otro valor se trata como el de por defecto (hoy, todos, página 1).
     *
     * @return array{periodo: string, tipo: ?string, pagina: int}
     */
    public static function filtros(mixed $periodo, mixed $tipo, mixed $pagina): array
    {
        return [
            'periodo' => is_string($periodo) && isset(self::PERIODOS[$periodo]) ? $periodo : 'hoy',
            'tipo' => is_string($tipo) && in_array($tipo, MovimientoRepository::TIPOS, true) ? $tipo : null,
            'pagina' => is_string($pagina) && ctype_digit($pagina) && strlen($pagina) < 6 ? max(1, (int) $pagina) : 1,
        ];
    }

    /**
     * @param array{periodo: string, tipo: ?string, pagina: int} $filtros
     * @return array{movimientos: list<array<string, mixed>>, total: int, pagina: int, paginas: int, desde: int}
     */
    public function listar(array $filtros): array
    {
        $total = $this->movimientos->contar($filtros['periodo'], $filtros['tipo']);
        $paginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $pagina = min($filtros['pagina'], $paginas);
        $desde = ($pagina - 1) * self::POR_PAGINA;

        return [
            'movimientos' => $this->movimientos->buscar($filtros['periodo'], $filtros['tipo'], self::POR_PAGINA, $desde),
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
            'desde' => $desde,
        ];
    }

    /**
     * «Descargar para Excel»: un libro con tres hojas, de lo que muestran los filtros (período y tipo).
     * 1) Resumen: el período, los totales, los productos más retirados y las unidades por categoría.
     * 2) Por producto: una fila por producto movido (cuántas había al comenzar, entraron, salieron, ajustes, quedan).
     * 3) Detalle: cada movimiento, agrupado por producto y por fecha, con Entró / Salió / Ajuste en columnas aparte.
     *
     * @param array{periodo: string, tipo: ?string, pagina: int} $filtros
     */
    public function libroExcel(array $filtros, string $quien): string
    {
        [$periodo, $tipo] = [$filtros['periodo'], $filtros['tipo']];
        $productos = $this->movimientos->porProducto($periodo, $tipo);
        $total = $this->movimientos->contar($periodo, $tipo);
        $detalle = $total === 0 ? [] : $this->movimientos->buscar($periodo, $tipo, $total, 0, true);

        $entraron = array_sum(array_map(static fn (array $p): int => (int) $p['entraron'], $productos));
        $salieron = array_sum(array_map(static fn (array $p): int => (int) $p['salieron'], $productos));
        $ajustes = array_sum(array_map(static fn (array $p): int => (int) $p['ajustes'], $productos));
        $inicio = $this->movimientos->inicioDelPeriodo($periodo);
        $hoy = date('d/m/Y');
        $rango = match (true) {
            $inicio === null => 'todo el historial, hasta el ' . $hoy,
            $inicio === date('Y-m-d') => 'el ' . $hoy,
            default => 'del ' . date('d/m/Y', (int) strtotime($inicio)) . ' al ' . $hoy,
        };
        $encabezado = static function (Xlsx $libro, string $titulo) use ($periodo, $tipo, $rango, $quien): void {
            $libro->fila([$titulo], 'titulo')
                ->fila(['Período: ' . self::PERIODOS[$periodo] . ' (' . $rango . ') · Tipo: ' . self::TIPOS[$tipo ?? '']], 'gris')
                ->fila(['Descargado el ' . date('d/m/Y') . ' a las ' . date('H:i') . ' por ' . $quien], 'gris')
                ->fila();
        };

        $libro = new Xlsx();

        // 1) Resumen
        $libro->hoja('Resumen', [34, 14, 40, 14]);
        $encabezado($libro, 'Entradas y salidas de la bodega');
        $libro->fila(['Totales'], 'subtitulo')
            ->tabla(['Concepto', 'Cantidad'], [
                ['Unidades que entraron', $entraron],
                ['Unidades que salieron', $salieron],
                ['Ajustes de cantidad (neto)', $ajustes],
                ['Productos con movimiento', count($productos)],
            ]);

        $retirados = array_values(array_filter($productos, static fn (array $p): bool => (int) $p['salieron'] > 0));
        usort($retirados, static fn (array $a, array $b): int => [(int) $b['salieron'], $a['nombre']] <=> [(int) $a['salieron'], $b['nombre']]);
        $libro->fila(['Lo más retirado'], 'subtitulo');
        $retirados === []
            ? $libro->fila(['No salió ningún producto en este período.'], 'gris')->fila()
            : $libro->tabla(['N°', 'Código', 'Producto', 'Salieron'], array_map(
                static fn (int $n, array $p): array => [$n + 1, $p['codigo'], $p['nombre'], (int) $p['salieron']],
                array_keys(array_slice($retirados, 0, 10)),
                array_slice($retirados, 0, 10),
            ));

        $categorias = [];
        foreach ($productos as $p) {
            $categorias[$p['categoria']] ??= [$p['categoria'], 0, 0];
            $categorias[$p['categoria']][1] += (int) $p['entraron'];
            $categorias[$p['categoria']][2] += (int) $p['salieron'];
        }
        $libro->fila(['Por categoría'], 'subtitulo');
        $categorias === []
            ? $libro->fila(['No hubo movimientos en este período.'], 'gris')
            : $libro->tabla(['Categoría', 'Entraron', 'Salieron'], [...array_values($categorias), ['Total', $entraron, $salieron]], true);

        // 2) Por producto
        $libro->hoja('Por producto', [12, 40, 28, 14, 11, 11, 11, 13]);
        $encabezado($libro, 'Entradas y salidas por producto');
        $filas = array_map(static fn (array $p): array => [
            $p['codigo'], $p['nombre'], $p['categoria'],
            (int) $p['stock_actual'] - (int) $p['neto'], (int) $p['entraron'], (int) $p['salieron'], (int) $p['ajustes'],
            (int) $p['stock_actual'],
        ], $productos);
        $filas === []
            ? $libro->fila(['No hubo movimientos en este período.'], 'gris')
            : $libro->tabla(
                ['Código', 'Producto', 'Categoría', 'Había al inicio', 'Entraron', 'Salieron', 'Ajustes', 'Quedan ahora'],
                [...$filas, ['Total', '', '', null, $entraron, $salieron, $ajustes, null]],
                true,
            );

        // 3) Detalle
        $libro->hoja('Detalle', [12, 8, 12, 40, 10, 9, 9, 9, 11, 13, 34, 30]);
        $encabezado($libro, 'Detalle de entradas y salidas');
        $filas = array_map(static function (array $m): array {
            $cantidad = (int) $m['cantidad'];

            return [
                new DateTimeImmutable(substr((string) $m['fecha'], 0, 10)),
                substr((string) $m['fecha'], 11, 5),
                $m['codigo'], $m['nombre'],
                self::TIPO_SINGULAR[$m['tipo']] ?? (string) $m['tipo'],
                $m['tipo'] === 'entrada' ? abs($cantidad) : null,
                $m['tipo'] === 'salida' ? abs($cantidad) : null,
                $m['tipo'] === 'ajuste' ? $cantidad : null,
                (int) $m['stock_resultante'],
                (string) ($m['reserva'] ?? ''),
                $m['sector'] !== null ? $m['sector_codigo'] . ' · ' . $m['sector'] : '',
                (string) ($m['motivo'] ?? ''),
            ];
        }, $detalle);
        $filas === []
            ? $libro->fila(['No hubo movimientos en este período.'], 'gris')
            : $libro->tabla(
                ['Fecha', 'Hora', 'Código', 'Producto', 'Tipo', 'Entró', 'Salió', 'Ajuste', 'Quedaron', 'Reserva', 'Ubicación', 'Motivo'],
                $filas,
            );

        return $libro->contenido();
    }
}
