<?php

declare(strict_types=1);

namespace App\Modules\Panel;

use App\Core\Auth;
use App\Support\Docx;
use App\Support\Fecha;

defined('APP_ROOT') || exit;

/**
 * El documento «Estado de la bodega» (Word, blanco y negro): lo que muestran los gráficos del inicio, con todos los
 * números, para entregarlo o imprimirlo. 1) Resumen, 2) insumos de consumo (estado, por categoría, por reponer y sin
 * stock) y 3) herramientas, con el equipo de la Práctica de Invierno (estado y préstamos en curso).
 */
final class EstadoBodega
{
    private const CARGOS = ['administrador' => 'Encargado de bodega', 'administrativa' => 'Administrativa'];

    public function __construct(private readonly PanelService $panel = new PanelService())
    {
    }

    public function documento(): string
    {
        $datos = $this->panel->estadoBodega();
        $insumos = $datos['insumos'];
        $herramientas = $datos['herramientas'];
        $productos = $insumos['con_stock'] + $insumos['por_reponer'] + $insumos['sin_stock'];
        $catalogo = $herramientas['en_bodega'] + $herramientas['prestadas'] + $herramientas['atrasadas'] + $herramientas['sin_unidades'];
        $ahora = date('H:i');
        $usuario = Auth::user() ?? [];
        $cargo = self::CARGOS[$usuario['perfil'] ?? ''] ?? 'Bodega';
        $porcentaje = static fn (int $n, int $total): string => ($total > 0 ? (string) round($n * 100 / $total) : '0') . ' %';
        $numero = static fn (int $n): string => number_format($n, 0, ',', '.');

        $doc = (new Docx())
            ->pie('Bodega FCF · Estado de la bodega al ' . date('d/m/Y') . ' a las ' . $ahora)
            ->membrete('Universidad de Concepción · Facultad de Ciencias Forestales')
            ->titulo('Estado de la bodega')
            ->parrafo(mb_ucfirst(Fecha::larga()) . ', a las ' . $ahora . ' horas', ['gris' => true, 'despues' => 0])
            ->parrafo('Emitido por: ' . ($usuario['nombre'] ?? '') . ' (' . $cargo . ')', ['gris' => true, 'linea' => true, 'despues' => 240]);

        // 1. Resumen
        $doc->seccion('1. Resumen')
            ->parrafo(sprintf(
                'La bodega tiene %s productos de consumo: %s con stock, %s por reponer y %s sin stock, con un total de %s en '
                . 'bodega. De las %s herramientas del catálogo (con %s), %s están en bodega, %s prestadas a tiempo, %s '
                . 'atrasadas y %s sin unidades.',
                $numero($productos), $numero($insumos['con_stock']), $numero($insumos['por_reponer']),
                $numero($insumos['sin_stock']), unidades($insumos['unidades']), $numero($catalogo),
                unidades($herramientas['unidades']), $numero($herramientas['en_bodega']), $numero($herramientas['prestadas']),
                $numero($herramientas['atrasadas']), $numero($herramientas['sin_unidades']),
            ))
            ->tabla(['Indicador', 'Valor'], [
                ['Productos de consumo', $numero($productos)],
                ['Unidades en bodega', $numero($insumos['unidades'])],
                ['Productos por reponer', $numero($insumos['por_reponer'])],
                ['Productos sin stock', $numero($insumos['sin_stock'])],
                ['Unidades que entraron este mes', $numero($insumos['entraron_mes'])],
                ['Unidades que salieron este mes', $numero($insumos['salieron_mes'])],
                ['Herramientas en el catálogo', $numero($catalogo)],
                ['Unidades de herramientas', $numero($herramientas['unidades'])],
                ['Unidades de herramientas en bodega', $numero($herramientas['unidades_en_bodega'])],
                ['Unidades de herramientas prestadas ahora', $numero($herramientas['unidades_prestadas'])],
                ['Préstamos atrasados', $numero(count(array_filter($datos['prestamos'], static fn (array $p): bool => (bool) $p['atrasado'])))],
                ['Solicitudes de herramientas por entregar', $numero($datos['solicitudes_pendientes'])],
                ['Herramientas dañadas o perdidas sin resolver', $numero($datos['por_resolver'])],
            ], [7, 2], [1]);

        // 2. Insumos de consumo
        $doc->seccion('2. Insumos de consumo')
            ->subseccion('2.1 Estado general')
            ->tabla(['Estado', 'Significado', 'Productos', '%'], [
                ['Con stock', 'Hay unidades suficientes', $numero($insumos['con_stock']), $porcentaje($insumos['con_stock'], $productos)],
                ['Por reponer', 'Quedan pocas: llegaron a su stock mínimo', $numero($insumos['por_reponer']), $porcentaje($insumos['por_reponer'], $productos)],
                ['Sin stock', 'No queda ninguna unidad', $numero($insumos['sin_stock']), $porcentaje($insumos['sin_stock'], $productos)],
                ['Total', '', $numero($productos), $productos > 0 ? '100 %' : '0 %'],
            ], [3, 6, 2, 1], [2, 3], true);

        $filas = array_map(static fn (array $c): array => [
            $c['categoria'], $numero($c['con_stock']), $numero($c['por_reponer']), $numero($c['sin_stock']), $numero($c['total']),
        ], $datos['categorias']);
        $filas[] = ['Total', $numero($insumos['con_stock']), $numero($insumos['por_reponer']), $numero($insumos['sin_stock']), $numero($productos)];
        $doc->subseccion('2.2 Por categoría')
            ->tabla(['Categoría', 'Con stock', 'Por reponer', 'Sin stock', 'Total'], $filas, [5, 2, 2, 2, 2], [1, 2, 3, 4], true);

        $doc->subseccion('2.3 Productos por reponer');
        $datos['por_reponer'] === []
            ? $doc->parrafo('No hay productos por reponer.', ['gris' => true])
            : $doc->tabla(['Código', 'Producto', 'Categoría', 'Hay', 'Mínimo', 'Ubicación'], array_map(
                static fn (array $p): array => [
                    $p['codigo'], $p['nombre'], $p['categoria'], $numero((int) $p['stock_actual']),
                    $numero((int) $p['stock_minimo']), $p['sector_codigo'] ?? '—',
                ],
                $datos['por_reponer'],
            ), [2, 6, 4, 1, 1, 2], [3, 4]);

        $doc->subseccion('2.4 Productos sin stock');
        $datos['sin_stock'] === []
            ? $doc->parrafo('No hay productos sin stock.', ['gris' => true])
            : $doc->tabla(['Código', 'Producto', 'Categoría', 'Ubicación'], array_map(
                static fn (array $p): array => [$p['codigo'], $p['nombre'], $p['categoria'], $p['sector_codigo'] ?? '—'],
                $datos['sin_stock'],
            ), [2, 7, 4, 2]);

        // 3. Herramientas
        $doc->seccion('3. Herramientas')
            ->subseccion('3.1 Estado general')
            ->tabla(['Estado', 'Significado', 'Herramientas', '%'], [
                ['En bodega', 'Ninguna prestada y hay para prestar', $numero($herramientas['en_bodega']), $porcentaje($herramientas['en_bodega'], $catalogo)],
                ['Prestadas', 'Alguna unidad fuera de la bodega, a tiempo', $numero($herramientas['prestadas']), $porcentaje($herramientas['prestadas'], $catalogo)],
                ['Atrasadas', 'Alguna unidad debió volver a la bodega', $numero($herramientas['atrasadas']), $porcentaje($herramientas['atrasadas'], $catalogo)],
                ['Sin unidades', 'No hay ninguna para prestar', $numero($herramientas['sin_unidades']), $porcentaje($herramientas['sin_unidades'], $catalogo)],
                ['Total', '', $numero($catalogo), $catalogo > 0 ? '100 %' : '0 %'],
            ], [3, 6, 2, 1], [2, 3], true);

        $doc->subseccion('3.2 Préstamos en curso');
        $datos['prestamos'] === []
            ? $doc->parrafo('No hay herramientas prestadas.', ['gris' => true])
            : $doc->tabla(['N°', 'Docente', 'Herramientas', 'Entregada', 'Devolver el', 'Estado'], array_map(
                static fn (array $p): array => [
                    (string) $p['id'], $p['docente'], (string) $p['herramientas'], substr(Fecha::mostrar($p['entregada_en']), 0, 10),
                    Fecha::mostrar($p['fecha_devolucion']), $p['atrasado'] ? 'Atrasado' : 'A tiempo',
                ],
                $datos['prestamos'],
            ), [1, 4, 6, 2, 2, 2]);

        return $doc->contenido();
    }
}
