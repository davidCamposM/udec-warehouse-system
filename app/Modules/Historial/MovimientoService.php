<?php

declare(strict_types=1);

namespace App\Modules\Historial;

defined('APP_ROOT') || exit;

/**
 * «Entradas y salidas»: lo que entró, salió o se ajustó en la bodega, por período y tipo, por páginas. También la
 * descarga para Excel.
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
     * Todas las filas de los filtros, para la descarga en Excel.
     *
     * @param array{periodo: string, tipo: ?string, pagina: int} $filtros
     * @return list<list<string|int>>
     */
    public function filasCsv(array $filtros): array
    {
        $total = $this->movimientos->contar($filtros['periodo'], $filtros['tipo']);

        return array_map(static fn (array $fila): array => [
            $fila['fecha'],
            self::TIPOS[$fila['tipo']],
            $fila['codigo'],
            $fila['nombre'],
            (int) $fila['cantidad'],
            (int) $fila['stock_resultante'],
            (string) ($fila['quien'] ?? ''),
            (string) ($fila['sector'] ?? ''),
            (string) ($fila['motivo'] ?? ''),
        ], $this->movimientos->buscar($filtros['periodo'], $filtros['tipo'], max(1, $total), 0));
    }
}
