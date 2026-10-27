<?php

use App\Modules\Insumos\InsumoRepository;
use App\Support\Fecha;

defined('APP_ROOT') || exit;

[$numero, $palabra] = explode(' ', unidades($insumo['stock_actual']), 2);
$categoria = $insumo['categoria'] === null
    ? 'Sin categoría'
    : ($insumo['categoria_padre'] !== null ? $insumo['categoria_padre'] . ' › ' : '') . $insumo['categoria'];
$base = 'insumos/' . $insumo['id'];

/** "Entraron 10 unidades", "Salió 1 unidad", "Cantidad actualizada: se restaron 2 unidades" */
$frase = static function (array $movimiento): string {
    $cantidad = (int) $movimiento['cantidad'];
    $texto = unidades(abs($cantidad));
    $una = abs($cantidad) === 1;

    return match ($movimiento['tipo']) {
        'entrada' => ($una ? 'Entró ' : 'Entraron ') . $texto,
        'salida' => ($una ? 'Salió ' : 'Salieron ') . $texto,
        default => 'Cantidad actualizada: ' . ($cantidad > 0 ? ($una ? 'se sumó ' : 'se sumaron ') : ($una ? 'se restó ' : 'se restaron ')) . $texto,
    };
};

// Lo que se puede hacer con este producto, según los permisos: [texto, descripción, ícono, color, URL]
$tareas = [];
if ($puedeGuardar) {
    $tareas[] = ['Registrar producto', 'Anotar lo que llegó y dónde quedó', 'box', 'verde', url('guardar', ['producto' => $insumo['id']])];
}
if ($puedeRetirar && (int) $insumo['stock_actual'] > 0) {
    $tareas[] = ['Retirar producto', 'Descontar lo que sale de la bodega', 'arrow-out', 'ocre', url('retirar', ['producto' => $insumo['id']])];
}
if ($puedeAjustar) {
    $tareas[] = ['Actualizar cantidad', 'Dejar lo que realmente hay, después de contar', 'refresh', 'naranja', url('ajustar', ['producto' => $insumo['id']])];
}
if ($puedeAdministrar) {
    $tareas[] = ['Editar datos', 'Nombre, tipo, stock mínimo, código UdeC o código de barras', 'edit', 'pizarra', url("{$base}/editar")];
    $tareas[] = $insumo['sku'] === InsumoRepository::SIN_CODIGO_BARRAS
        ? ['Agregar código de barras', 'Escanear el código del envase', 'barcode', 'turquesa', url("{$base}/codigos/nuevo")]
        : ['Agregar otro código de barras', 'Otra marca o la caja de este mismo producto', 'barcode', 'turquesa', url("{$base}/codigos/nuevo")];
}
?>
<?= partial('encabezado', [
    'volver' => [url('insumos'), 'Volver a Buscar producto'],
    'tarea' => 'buscar-producto',
    'icono' => 'box',
    'titulo' => $insumo['nombre'],
    'texto' => $insumo['codigo'] . ' · ' . $categoria,
]) ?>

<?php $foto = foto_producto($insumo['codigo_udec'], $insumo['codigo']); ?>
<section class="producto" aria-label="El producto">
    <div class="producto__info">
        <div class="producto__cantidad">
            <p class="producto__etiqueta">Hay en bodega</p>
            <p class="producto__numero"><?= e($numero) ?> <span><?= e($palabra) ?></span></p>
            <?= partial('Insumos::partials/estado', ['insumo' => $insumo]) ?>
            <?php if ((int) $insumo['stock_minimo'] > 0) : ?>
                <p class="producto__minimo">Aviso cuando queden <?= e(unidades($insumo['stock_minimo'])) ?> o menos</p>
            <?php endif; ?>
        </div>
        <dl class="producto__datos">
            <div>
                <dt><?= icon('map-pin') ?>Dónde está en la bodega</dt>
                <dd>
                    <?= $insumo['sector'] !== null
                        ? e($insumo['sector']) . ' <span class="producto__codigo">' . e($insumo['sector_codigo']) . '</span>'
                        : 'Sin sector asignado' ?>
                </dd>
            </div>
            <div>
                <dt><?= icon('layers') ?>Tipo</dt>
                <dd><?= e($categoria) ?></dd>
            </div>
            <div>
                <dt><?= icon('clipboard') ?>Código UdeC</dt>
                <dd><?= e($insumo['codigo_udec'] ?? 'Sin código') ?></dd>
            </div>
            <div>
                <dt><?= icon('barcode') ?>Código de barras</dt>
                <dd><?= e($insumo['sku'] === InsumoRepository::SIN_CODIGO_BARRAS ? 'Sin anotar' : $insumo['sku']) ?></dd>
            </div>
        </dl>
    </div>
    <div class="producto__foto">
        <?php if ($foto !== null) : ?>
            <img src="<?= e($foto) ?>" alt="Foto de <?= e($insumo['nombre']) ?>" width="300" height="300">
        <?php else : ?>
            <p class="producto__sin-foto"><?= icon('box') ?>Sin foto</p>
        <?php endif; ?>
    </div>
</section>

<?php if ($tareas !== []) : ?>
    <nav class="ficha__bloque" aria-labelledby="titulo-tareas">
        <h2 class="titulo-seccion" id="titulo-tareas">¿Qué quieres hacer con este producto?</h2>
        <ul class="lista-acciones">
            <?php foreach ($tareas as [$texto, $descripcion, $icono, $color, $destino]) : ?>
                <li>
                    <a class="accion accion--<?= e($color) ?>" href="<?= e($destino) ?>">
                        <span class="accion__icono"><?= icon($icono) ?></span>
                        <span class="accion__texto">
                            <strong class="accion__titulo"><?= e($texto) ?></strong>
                            <span class="accion__detalle"><?= e($descripcion) ?></span>
                        </span>
                        <span class="accion__flecha" aria-hidden="true"><?= icon('arrow-right') ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
<?php endif; ?>

<div class="ficha__columnas">
    <section class="card" aria-labelledby="titulo-movimientos">
        <div class="card-header">
            <h2 class="card-title" id="titulo-movimientos">Últimos movimientos</h2>
        </div>
        <?php if ($movimientos === []) : ?>
            <p class="card-body text-muted">Todavía no hay entradas ni salidas de este producto.</p>
        <?php else : ?>
            <ul class="movimientos-lista">
                <?php foreach ($movimientos as $movimiento) : ?>
                    <?php $tipo = $movimiento['tipo']; ?>
                    <li class="mov mov--<?= e($tipo) ?>">
                        <span class="mov__icono"><?= icon(['entrada' => 'arrow-in', 'salida' => 'arrow-out'][$tipo] ?? 'refresh') ?></span>
                        <span class="mov__texto">
                            <strong><?= e($frase($movimiento)) ?></strong>
                            <span class="mov__datos">
                                <?= e(Fecha::mostrar($movimiento['fecha'])) ?>
                                <?php if ($movimiento['quien'] !== null) : ?> · <?= e($movimiento['quien']) ?><?php endif; ?>
                                <?php if ($movimiento['sector'] !== null) : ?> · se guardó en <?= e($movimiento['sector']) ?><?php endif; ?>
                            </span>
                            <?php if ($movimiento['motivo'] !== null || $movimiento['reserva'] !== null) : ?>
                                <span class="mov__datos"><?= e($movimiento['motivo'] ?? 'Reserva ' . $movimiento['reserva']) ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="mov__saldo">
                            <span class="mov__saldo-numero"><?= e(explode(' ', unidades($movimiento['stock_resultante']), 2)[0]) ?></span>
                            quedaron
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="card" aria-labelledby="titulo-codigos">
        <div class="card-header">
            <h2 class="card-title" id="titulo-codigos">Otros códigos de barras</h2>
        </div>
        <?php if ($codigos === []) : ?>
            <p class="card-body text-muted">
                Este producto no tiene otros códigos de barras. Se puede escanear su código interno
                <strong><?= e($insumo['codigo']) ?></strong> desde su etiqueta.
            </p>
        <?php else : ?>
            <ul class="list-group">
                <?php foreach ($codigos as $codigo) : ?>
                    <li class="list-group-item codigo-fila">
                        <span class="codigo-fila__icono"><?= icon('barcode') ?></span>
                        <span class="codigo-fila__texto">
                            <span class="codigo-fila__valor"><?= e($codigo['codigo']) ?></span>
                            <?php if ($codigo['descripcion'] !== null) : ?>
                                <span class="text-muted"><?= e($codigo['descripcion']) ?></span>
                            <?php endif; ?>
                        </span>
                        <?php if ($puedeAdministrar) : ?>
                            <a class="btn btn-outline-secondary btn-sm"
                                href="<?= e(url($base . '/codigos/' . $codigo['id'] . '/quitar')) ?>"><?= icon('close') ?>Quitar</a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<?php if ($puedeAdministrar) : ?>
    <section class="card card-body ficha__eliminar">
        <p class="text-muted">¿Ya no se usa este producto?</p>
        <a class="btn btn-outline-danger btn-sm" href="<?= e(url($base . '/eliminar')) ?>">Eliminar este producto</a>
    </section>
<?php endif; ?>
