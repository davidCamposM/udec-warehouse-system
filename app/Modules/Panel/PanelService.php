<?php

declare(strict_types=1);

namespace App\Modules\Panel;

use App\Core\Permission;
use App\Modules\Insumos\InsumoService;

defined('APP_ROOT') || exit;

/**
 * Avisos del inicio: solo lo que necesita atención. El de toda la bodega (administrador y administrativas)
 * y el propio de cada docente.
 */
final class PanelService
{
    public function __construct(
        private readonly PanelRepository $panel = new PanelRepository(),
        private readonly InsumoService $insumos = new InsumoService(),
    ) {
    }

    /**
     * Cuántos hay de cada cosa por atender, de lo más grave a lo menos. Los avisos en 0 los omite la vista.
     *
     * @return array<string, int> aviso => cantidad
     */
    public function avisosBodega(): array
    {
        // Los mismos números que los filtros de la lista de insumos.
        $insumos = $this->insumos->contar('');

        return [
            'sin_unidades' => $insumos['no-queda'],
            'por_reponer' => $insumos['reponer'],
            'prestamos_atrasados' => $this->panel->contarPrestamosAtrasados(),
            'herramientas_por_resolver' => $this->panel->contarHerramientasPorResolver(),
            'solicitudes_pendientes' => $this->panel->contarSolicitudesPendientes(),
            // Informativo (no urgente): se muestra aunque todo esté en orden.
            'salidas_hoy' => $this->panel->contarSalidasDeHoy(),
        ];
    }

    /**
     * Los dos gráficos de torta del inicio: los insumos de consumo y las herramientas, cada uno partido en lo que está en
     * bodega y lo que no, con unos datos más debajo. Cada parte: [clave, nombre, explicación, ícono, cantidad, enlace].
     *
     * @return list<array{titulo: string, texto: string, unidad: array{0: string, 1: string}, centro: string,
     *     partes: list<array{0: string, 1: string, 2: string, 3: string, 4: int, 5: ?string}>, datos: list<array{0: string, 1: string}>}>
     */
    public function graficos(): array
    {
        $insumos = $this->panel->resumenInsumos();
        $herramientas = $this->panel->resumenHerramientas();
        $verInsumos = Permission::can('insumos.ver');
        $enlace = static fn (string $ver): ?string => $verInsumos ? url('insumos', ['ver' => $ver]) : null;
        $verHerramientas = Permission::can('herramientas.ver');
        $catalogo = static fn (string $ver): ?string => $verHerramientas ? url('herramientas', ['ver' => $ver]) : null;
        $unidades = static fn (int $n): string => unidades($n);

        return [
            [
                'titulo' => 'Insumos de consumo',
                'texto' => 'Los productos según lo que queda en bodega',
                'unidad' => ['producto', 'productos'],
                'centro' => 'con unidades',
                'partes' => [
                    ['bien', 'Con stock', 'Hay unidades suficientes', 'check', $insumos['con_stock'], $enlace('con-stock')],
                    ['atencion', 'Por reponer', 'Quedan pocas: hay que pedir más', 'alert', $insumos['por_reponer'], $enlace('reponer')],
                    ['grave', 'Sin stock', 'No queda ninguna en bodega', 'x-circle', $insumos['sin_stock'], $enlace('no-queda')],
                ],
                'datos' => [
                    ['Unidades en bodega', $unidades($insumos['unidades'])],
                    ['Salieron este mes', $unidades($insumos['salieron_mes'])],
                    ['Entraron este mes', $unidades($insumos['entraron_mes'])],
                ],
            ],
            [
                'titulo' => 'Herramientas',
                'texto' => 'Las herramientas del catálogo según dónde están',
                'unidad' => ['herramienta', 'herramientas'],
                'centro' => 'en bodega',
                'partes' => [
                    ['bien', 'En bodega', 'Ninguna unidad prestada', 'check', $herramientas['en_bodega'], $catalogo('en-bodega')],
                    ['fuera', 'Prestadas', 'Fuera de la bodega, a tiempo', 'swap', $herramientas['prestadas'], $catalogo('prestadas')],
                    ['grave', 'Atrasadas', 'Debieron volver a la bodega', 'clock', $herramientas['atrasadas'], $catalogo('atrasadas')],
                ],
                'datos' => [
                    ['Unidades prestadas ahora', $unidades($herramientas['unidades_prestadas'])],
                    ['Solicitudes por entregar', (string) $this->panel->contarSolicitudesPendientes()],
                    ['Dañadas o perdidas sin resolver', (string) $this->panel->contarHerramientasPorResolver()],
                ],
            ],
        ];
    }

    /**
     * Los insumos por categoría (para el documento del estado de la bodega): cada categoría principal con sus productos
     * con stock, por reponer y sin stock, y el total.
     *
     * @return list<array{categoria: string, con_stock: int, por_reponer: int, sin_stock: int, total: int}>
     */
    public function categorias(): array
    {
        return array_map(
            static fn (array $fila): array => $fila + ['total' => $fila['con_stock'] + $fila['por_reponer'] + $fila['sin_stock']],
            $this->panel->insumosPorCategoria(),
        );
    }

    /**
     * Todo lo del documento «Estado de la bodega»: los resúmenes de los gráficos, las categorías, los productos por
     * reponer y sin stock, y los préstamos en curso.
     *
     * @return array<string, mixed>
     */
    public function estadoBodega(): array
    {
        return [
            'insumos' => $this->panel->resumenInsumos(),
            'herramientas' => $this->panel->resumenHerramientas(),
            'categorias' => $this->categorias(),
            'por_reponer' => $this->panel->productos('por_reponer'),
            'sin_stock' => $this->panel->productos('sin_stock'),
            'prestamos' => $this->panel->prestamosEnCurso(),
            'solicitudes_pendientes' => $this->panel->contarSolicitudesPendientes(),
            'por_resolver' => $this->panel->contarHerramientasPorResolver(),
        ];
    }

    /** @return array<string, int> aviso => cantidad */
    public function avisosDocente(int $usuarioId): array
    {
        return [
            'prestamos_atrasados' => $this->panel->contarPrestamosAtrasadosDe($usuarioId),
        ];
    }
}
