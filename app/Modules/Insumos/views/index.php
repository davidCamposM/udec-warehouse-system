<?php

use App\Modules\Insumos\InsumoService;

defined('APP_ROOT') || exit;

// Con los componentes básicos (basico.css), como el inicio. Mientras se escribe en la barra, debajo aparecen los
// productos que coinciden (insumos.js) y tocar uno abre su ficha. «Buscar» (o Enter) deja en la lista de abajo todos
// los que coinciden; un código leído con la pistola abre directo la ficha. Debajo, los filtros y una tarjeta por
// producto: nombre y datos, la cantidad con su estado y la foto.
$filtros = ['todos' => 'Todos', 'reponer' => 'Por reponer', 'no-queda' => 'No queda'];

$urlFiltro = static fn (string $ver): string => url('insumos', array_filter([
    'q' => $texto,
    'ver' => $ver === 'todos' ? null : $ver,
]));

// Se ofrece agregar lo buscado como producto nuevo (si parece un código, con ese código ya escrito).
$urlAgregar = url('insumos/nuevo', InsumoService::paraAgregar($texto));
?>
<?= partial('encabezado', [
    'volver' => [url(), 'Volver al inicio'],
    'tarea' => 'buscar-producto',
    'titulo' => 'Buscar producto',
    'texto' => 'Para ver cuánto hay en bodega y dónde está.',
]) ?>

<form class="card card-body buscar" method="get" action="<?= e(url('insumos')) ?>" role="search">
    <?= route_field('insumos') ?>
    <?php if ($filtro !== 'todos') : ?>
        <input type="hidden" name="ver" value="<?= e($filtro) ?>">
    <?php endif; ?>
    <label class="buscar__etiqueta" for="buscar">
        <span class="buscar__icono"><?= icon('barcode') ?></span>
        Escanea el código o escribe el nombre
    </label>
    <div class="input-group input-group-lg sugerencias">
        <input class="form-control" data-lectura id="buscar" name="q" type="search" value="<?= e($texto) ?>"
            placeholder="Ejemplo: cloro o 3001027" autocomplete="off" enterkeyhint="search"
            data-sugerencias="<?= e(url('insumos/sugerencias')) ?>" <?= $texto === '' ? 'autofocus' : '' ?>>
        <button class="btn btn-primary" type="submit"><?= icon('search') ?>Buscar</button>
    </div>
</form>

<div class="buscar__barra">
    <nav class="nav-pills" aria-label="Mostrar">
        <?php foreach ($filtros as $clave => $nombre) : ?>
            <a class="nav-link<?= $clave === $filtro ? ' active' : '' ?>" href="<?= e($urlFiltro($clave)) ?>"
                <?= $clave === $filtro ? 'aria-current="true"' : '' ?>>
                <?= e($nombre) ?>
                <span class="nav-count"><?= e($conteo[$clave]) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if ($texto !== '') : ?>
        <p class="buscar__resultados">
            Resultados para «<?= e($texto) ?>» ·
            <a href="<?= e(url('insumos', array_filter(['ver' => $filtro === 'todos' ? null : $filtro]))) ?>">Ver todos</a>
        </p>
    <?php endif; ?>
</div>

<?php if ($insumos === []) : ?>
    <div class="card card-body vacio-simple">
        <span class="vacio-simple__icono"><?= icon('search') ?></span>
        <p class="vacio-simple__titulo">No hay productos para mostrar</p>
        <p class="text-muted">
            <?= $texto !== ''
                ? 'Revisa lo escrito o busca con otra palabra.'
                : 'En esta lista no hay productos por ahora.' ?>
        </p>
        <?php if ($texto !== '' && $puedeAdministrar) : ?>
            <p class="vacio-simple__pregunta">¿Es un producto que todavía no está en el sistema?</p>
            <a class="btn btn-success" href="<?= e($urlAgregar) ?>"><?= icon('plus') ?>Agregar nuevo producto</a>
        <?php endif; ?>
    </div>
<?php else : ?>
    <ul class="resultados">
        <?php foreach ($insumos as $insumo) : ?>
            <?php
            [$numero, $unidad] = explode(' ', unidades($insumo['stock_actual']), 2);
            $foto = foto_producto($insumo['codigo_udec']);
            ?>
            <li>
                <a class="resultado" href="<?= e(url('insumos/' . $insumo['id'])) ?>">
                    <span class="resultado__texto">
                        <strong class="resultado__nombre"><?= e($insumo['nombre']) ?></strong>
                        <span class="resultado__datos">
                            <span><?= e($insumo['codigo']) ?></span>
                            <span><?= e($insumo['categoria'] ?? 'Sin categoría') ?></span>
                            <span><?= icon('map-pin') ?><?= e($insumo['sector'] ?? 'Sin ubicación') ?></span>
                        </span>
                    </span>
                    <span class="resultado__stock">
                        <span class="resultado__cantidad"><?= e($numero) ?> <small><?= e($unidad) ?></small></span>
                        <?= partial('Insumos::partials/estado', ['insumo' => $insumo]) ?>
                    </span>
                    <span class="resultado__foto">
                        <?php if ($foto !== null) : ?>
                            <img src="<?= e($foto) ?>" alt="" width="88" height="88" loading="lazy" decoding="async">
                        <?php else : ?>
                            <?= icon('box') ?>
                        <?php endif; ?>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
