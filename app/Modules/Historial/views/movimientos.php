<?php

use App\Modules\Historial\MovimientoService;
use App\Support\Fecha;

defined('APP_ROOT') || exit;

// «Entradas y salidas», con los componentes básicos (basico.css): a la derecha del título, las descargas (Excel y PDF);
// debajo, los filtros (período y tipo) en una fila y la tabla por páginas. Cada fila es un producto movido: fecha, tipo, producto, cantidad (+ o −), quién y
// dónde.
$enlace = static fn (array $cambios): string => url('movimientos', array_filter([
    'periodo' => $cambios['periodo'] ?? $filtros['periodo'],
    'tipo' => array_key_exists('tipo', $cambios) ? $cambios['tipo'] : $filtros['tipo'],
    'pagina' => ($cambios['pagina'] ?? 1) > 1 ? $cambios['pagina'] : null,
]));
// Las descargas (Excel y PDF) bajan lo mismo que muestran los filtros elegidos (período y tipo).
$excel = url('movimientos/excel', array_filter(['periodo' => $filtros['periodo'], 'tipo' => $filtros['tipo']]));
$pdf = url('movimientos/pdf', array_filter(['periodo' => $filtros['periodo'], 'tipo' => $filtros['tipo']]));
$tipoActual = $filtros['tipo'] ?? '';
?>
<?php
// Las descargas van a la derecha del título: dos botones chicos con lo mismo que muestran los filtros elegidos.
$descargas = '<div class="descargas"><span class="descargas__titulo">Descargar</span>'
    . '<a class="descargas__boton" href="' . e($excel) . '" download title="Resumen, por producto y detalle editable">'
    . icon('chart') . 'Excel' . icon('arrow-in') . '</a>'
    . '<a class="descargas__boton" href="' . e($pdf) . '" download title="Informe formal en blanco y negro">'
    . icon('printer') . 'PDF' . icon('arrow-in') . '</a></div>';
?>
<?= partial('encabezado', [
    'volver' => [url(), 'Volver al inicio'],
    'tarea' => 'historial',
    'titulo' => 'Entradas y salidas',
    'texto' => 'Lo que llegó, lo que se retiró y los ajustes de cantidad, con quién y cuándo.',
    'lateral' => $descargas,
]) ?>

<?php // Los filtros, compactos y en una fila: el período y el tipo como selectores de pocas opciones. ?>
<div class="filtros">
    <div class="filtros__grupo">
        <span class="filtros__nombre" id="filtro-periodo">Período</span>
        <nav class="segmento" aria-labelledby="filtro-periodo">
            <?php foreach (MovimientoService::PERIODOS as $clave => $nombre) : ?>
                <a class="segmento__opcion<?= $clave === $filtros['periodo'] ? ' is-activa' : '' ?>" href="<?= e($enlace(['periodo' => $clave])) ?>"
                    <?= $clave === $filtros['periodo'] ? 'aria-current="true"' : '' ?>><?= e($nombre) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
    <div class="filtros__grupo">
        <span class="filtros__nombre" id="filtro-tipo">Tipo</span>
        <nav class="segmento" aria-labelledby="filtro-tipo">
            <?php foreach (MovimientoService::TIPOS as $clave => $nombre) : ?>
                <a class="segmento__opcion<?= $clave === $tipoActual ? ' is-activa' : '' ?>" href="<?= e($enlace(['tipo' => $clave === '' ? null : $clave])) ?>"
                    <?= $clave === $tipoActual ? 'aria-current="true"' : '' ?>><?= e($nombre) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
</div>

<?php if ($movimientos === []) : ?>
    <div class="card card-body vacio-simple">
        <span class="vacio-simple__icono"><?= icon('history') ?></span>
        <p class="vacio-simple__titulo">No hay movimientos para mostrar</p>
        <p class="text-muted">Prueba con otro período o tipo.</p>
    </div>
<?php else : ?>
    <section class="card" aria-labelledby="titulo-movimientos">
        <div class="card-header">
            <h2 class="card-title" id="titulo-movimientos">Movimientos</h2>
            <span class="text-muted movimientos__total"><?= e($total) ?> <?= $total === 1 ? 'producto movido' : 'productos movidos' ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover tabla-apilada movimientos__tabla" aria-labelledby="titulo-movimientos">
                <thead>
                    <tr>
                        <th scope="col">Fecha</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Producto</th>
                        <th scope="col" class="text-end">Cantidad</th>
                        <th scope="col" class="text-end">Quedaron</th>
                        <th scope="col">Quién</th>
                        <th scope="col">Sector, reserva o motivo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movimientos as $fila) : ?>
                        <?php $cantidad = (int) $fila['cantidad']; ?>
                        <tr>
                            <td class="movimientos__fecha" data-label="Fecha"><?= e(Fecha::mostrar($fila['fecha'])) ?></td>
                            <td data-label="Tipo"><span class="tipo-mov tipo-mov--<?= e($fila['tipo']) ?>"><?= e(rtrim(MovimientoService::TIPOS[$fila['tipo']], 's')) ?></span></td>
                            <td class="tabla-apilada__titulo">
                                <?php if ((int) $fila['insumo_activo'] === 1) : ?>
                                    <a class="movimientos__producto" href="<?= e(url('insumos/' . $fila['insumo_id'])) ?>"><?= e($fila['nombre']) ?></a>
                                <?php else : ?>
                                    <?php // Un producto eliminado: su historial queda, pero ya no tiene ficha. ?>
                                    <span class="movimientos__producto"><?= e($fila['nombre']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end movimientos__cantidad movimientos__cantidad--<?= $cantidad > 0 ? 'mas' : 'menos' ?>" data-label="Cantidad"><?= e(($cantidad > 0 ? '+' : '−') . abs($cantidad)) ?></td>
                            <td class="text-end movimientos__numero" data-label="Quedaron"><?= e($fila['stock_resultante']) ?></td>
                            <td data-label="Quién"><?= e($fila['quien'] ?? '—') ?></td>
                            <td class="text-muted" data-label="Detalle"><?= e($fila['sector'] ?? $fila['motivo'] ?? ($fila['reserva'] !== null ? 'Reserva ' . $fila['reserva'] : '—')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($paginas > 1) : ?>
            <nav class="pagination" aria-label="Páginas de la lista">
                <?php if ($pagina > 1) : ?>
                    <a class="page-link" href="<?= e($enlace(['pagina' => $pagina - 1])) ?>"><?= icon('arrow-left') ?>Anterior</a>
                <?php else : ?>
                    <span class="page-link disabled" aria-hidden="true"><?= icon('arrow-left') ?>Anterior</span>
                <?php endif; ?>
                <p class="page-info">
                    Página <strong><?= e($pagina) ?></strong> de <strong><?= e($paginas) ?></strong>
                    · <?= e($desde + 1) ?> a <?= e($desde + count($movimientos)) ?> de <?= e($total) ?>
                </p>
                <?php if ($pagina < $paginas) : ?>
                    <a class="page-link" href="<?= e($enlace(['pagina' => $pagina + 1])) ?>">Siguiente<?= icon('arrow-right') ?></a>
                <?php else : ?>
                    <span class="page-link disabled" aria-hidden="true">Siguiente<?= icon('arrow-right') ?></span>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>
<?php endif; ?>
