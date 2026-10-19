<?php

use App\Modules\Herramientas\HerramientaService;

defined('APP_ROOT') || exit;

// «Catálogo de herramientas»: lo que tiene la bodega para prestar, en una tarjeta por categoría, con cuántas hay en
// bodega y cuántas están prestadas ahora. Arriba, los filtros por dónde están (todas, en bodega, prestadas, atrasadas,
// sin unidades; ?ver=) y debajo el buscador y las categorías (herramientas.js). El encargado además agrega, edita (y
// anota cuántas hay) y elimina (desde Editar). Cada estado con su color, el mismo del gráfico del inicio.
$acciones = match (true) {
    $puedeAdministrar => '<a class="btn btn-success" href="' . e(url('herramientas/nueva')) . '">' . icon('plus') . 'Agregar herramienta</a>',
    $puedeSolicitar => '<a class="btn btn-success" href="' . e(url('herramientas/solicitar')) . '">' . icon('tool') . 'Solicitar herramientas</a>',
    default => '',
};
?>
<?= partial('encabezado', [
    'volver' => [url(), 'Volver al inicio'],
    'tarea' => 'ver-herramientas',
    'titulo' => 'Catálogo de herramientas',
    'texto' => 'Las herramientas e instrumentos de terreno que la bodega presta.',
    'acciones' => $acciones,
]) ?>

<?php // Arriba, dónde están: todas, en bodega, prestadas, atrasadas o sin unidades, con los colores del gráfico del inicio. ?>
<nav class="nav-pills catalogo__estados" aria-label="Mostrar">
    <?php foreach (HerramientaService::ESTADOS as $clave => $nombre) : ?>
        <a class="nav-link<?= isset(HerramientaService::COLORES[$clave]) ? ' nav-link--' . e(HerramientaService::COLORES[$clave]) : '' ?><?= $clave === $estado ? ' active' : '' ?>"
            href="<?= e(url('herramientas', $clave === 'todas' ? [] : ['ver' => $clave])) ?>"
            <?= $clave === $estado ? 'aria-current="true"' : '' ?>>
            <?= e($nombre) ?> <span class="nav-count"><?= e($conteo[$clave]) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<?php if ($grupos === []) : ?>
    <div class="card card-body vacio-simple">
        <span class="vacio-simple__icono"><?= icon('tool') ?></span>
        <p class="vacio-simple__titulo"><?= $estado === 'todas'
            ? 'Todavía no hay herramientas en el catálogo'
            : 'No hay herramientas ' . e(mb_strtolower(HerramientaService::ESTADOS[$estado])) . ' ahora' ?></p>
    </div>
<?php else : ?>
    <?= partial('Herramientas::partials/buscador', ['grupos' => $grupos]) ?>

    <div class="catalogo">
        <?php foreach (array_values($grupos) as $i => $herramientas) : ?>
            <section class="card catalogo__grupo" data-grupo="<?= e($i) ?>" aria-labelledby="grupo-<?= e($i) ?>">
                <div class="card-header">
                    <h2 class="card-title" id="grupo-<?= e($i) ?>"><?= e(array_keys($grupos)[$i]) ?></h2>
                    <span class="text-muted"><?= e(count($herramientas)) ?></span>
                </div>
                <ul class="catalogo__lista">
                    <?php foreach ($herramientas as $herramienta) : ?>
                        <?php $prestadas = (int) $herramienta['prestadas']; ?>
                        <li class="catalogo__herramienta" data-herramienta data-nombre="<?= e($herramienta['nombre']) ?>">
                            <span class="catalogo__nombre"><?= e($herramienta['nombre']) ?></span>
                            <?php if ((int) $herramienta['atrasadas'] > 0) : ?>
                                <span class="status status--out"><?= e((int) $herramienta['atrasadas'] === 1 ? '1 atrasada' : "{$herramienta['atrasadas']} atrasadas") ?></span>
                            <?php endif; ?>
                            <?php if ($prestadas - (int) $herramienta['atrasadas'] > 0) : ?>
                                <?php $aTiempo = $prestadas - (int) $herramienta['atrasadas']; ?>
                                <span class="status status--low"><?= e($aTiempo === 1 ? '1 prestada' : "{$aTiempo} prestadas") ?></span>
                            <?php endif; ?>
                            <?php $hay = (int) $herramienta['en_bodega']; ?>
                            <span class="catalogo__hay<?= $hay > 0 ? '' : ' catalogo__hay--sin' ?>"><?= e(match (true) {
                                $hay > 0 => "{$hay} en bodega",
                                (int) $herramienta['unidades'] > 0 => 'Ninguna en bodega',
                                default => 'Sin unidades',
                            }) ?></span>
                            <?php if ($puedeAdministrar) : ?>
                                <a class="catalogo__editar" href="<?= e(url('herramientas/' . $herramienta['id'] . '/editar')) ?>"
                                    title="Editar <?= e($herramienta['nombre']) ?>"><?= icon('edit') ?><span class="visually-hidden">Editar <?= e($herramienta['nombre']) ?></span></a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

