<?php

use App\Modules\Historial\MovimientoService;
use App\Support\Fecha;

defined('APP_ROOT') || exit;

// «Entradas y salidas», con los componentes básicos (basico.css): los filtros (período y tipo) como pastillas en una
// tarjeta y la tabla por páginas. Cada fila es un producto movido: fecha, tipo, producto, cantidad (+ o −), quién y
// dónde.
$enlace = static fn (array $cambios): string => url('movimientos', array_filter([
    'periodo' => $cambios['periodo'] ?? $filtros['periodo'],
    'tipo' => array_key_exists('tipo', $cambios) ? $cambios['tipo'] : $filtros['tipo'],
    'pagina' => ($cambios['pagina'] ?? 1) > 1 ? $cambios['pagina'] : null,
]));
$csv = url('movimientos/csv', array_filter(['periodo' => $filtros['periodo'], 'tipo' => $filtros['tipo']]));
$tipoActual = $filtros['tipo'] ?? '';
?>
<?= partial('encabezado', [
    'volver' => [url(), 'Volver al inicio'],
    'tarea' => 'historial',
    'titulo' => 'Entradas y salidas',
    'texto' => 'Lo que llegó, lo que se retiró y los ajustes de cantidad, con quién y cuándo.',
    'acciones' => '<a class="btn btn-outline-secondary btn-sm" href="' . e($csv) . '">' . icon('arrow-in') . 'Descargar para Excel</a>',
]) ?>

<div class="card card-body filtros">
    <div class="filtros__fila">
        <span class="filtros__nombre" id="filtro-periodo">Período</span>
        <nav class="nav-pills" aria-labelledby="filtro-periodo">
            <?php foreach (MovimientoService::PERIODOS as $clave => $nombre) : ?>
                <a class="nav-link<?= $clave === $filtros['periodo'] ? ' active' : '' ?>" href="<?= e($enlace(['periodo' => $clave])) ?>"
                    <?= $clave === $filtros['periodo'] ? 'aria-current="true"' : '' ?>><?= e($nombre) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
    <div class="filtros__fila">
        <span class="filtros__nombre" id="filtro-tipo">Tipo</span>
        <nav class="nav-pills" aria-labelledby="filtro-tipo">
            <?php foreach (MovimientoService::TIPOS as $clave => $nombre) : ?>
                <a class="nav-link<?= $clave === $tipoActual ? ' active' : '' ?>" href="<?= e($enlace(['tipo' => $clave === '' ? null : $clave])) ?>"
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
            <table class="table table-hover movimientos__tabla" aria-labelledby="titulo-movimientos">
                <thead>
                    <tr>
                        <th scope="col">Fecha</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Producto</th>
                        <th scope="col" class="text-end">Cantidad</th>
                        <th scope="col" class="text-end">Quedaron</th>
                        <th scope="col">Quién</th>
                        <th scope="col">Sector o motivo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movimientos as $fila) : ?>
                        <?php $cantidad = (int) $fila['cantidad']; ?>
                        <tr>
                            <td class="movimientos__fecha"><?= e(Fecha::mostrar($fila['fecha'])) ?></td>
                            <td><span class="tipo-mov tipo-mov--<?= e($fila['tipo']) ?>"><?= e(rtrim(MovimientoService::TIPOS[$fila['tipo']], 's')) ?></span></td>
                            <td><a class="movimientos__producto" href="<?= e(url('insumos/' . $fila['insumo_id'])) ?>"><?= e($fila['nombre']) ?></a></td>
                            <td class="text-end movimientos__cantidad movimientos__cantidad--<?= $cantidad > 0 ? 'mas' : 'menos' ?>"><?= e(($cantidad > 0 ? '+' : '−') . abs($cantidad)) ?></td>
                            <td class="text-end movimientos__numero"><?= e($fila['stock_resultante']) ?></td>
                            <td><?= e($fila['quien'] ?? '—') ?></td>
                            <td class="text-muted"><?= e($fila['sector'] ?? $fila['motivo'] ?? '—') ?></td>
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
