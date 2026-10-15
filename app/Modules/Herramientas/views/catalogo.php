<?php

defined('APP_ROOT') || exit;

// «Catálogo de herramientas»: lo que tiene la bodega para prestar, en una tarjeta por categoría, con cuántas están
// prestadas ahora. Arriba, el buscador y las categorías (herramientas.js). El encargado además agrega, edita y
// desactiva; abajo ve las desactivadas.
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

<?php if ($grupos === []) : ?>
    <div class="card card-body vacio-simple">
        <span class="vacio-simple__icono"><?= icon('tool') ?></span>
        <p class="vacio-simple__titulo">Todavía no hay herramientas en el catálogo</p>
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
                            <?php if ($prestadas > 0) : ?>
                                <span class="status status--low"><?= e($prestadas === 1 ? '1 prestada' : "{$prestadas} prestadas") ?></span>
                            <?php endif; ?>
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

<?php if ($desactivadas !== []) : ?>
    <section class="card desactivados" aria-labelledby="titulo-desactivadas">
        <div class="card-header">
            <h2 class="card-title" id="titulo-desactivadas">Desactivadas</h2>
            <span class="text-muted desactivados__ayuda">No aparecen al solicitar herramientas</span>
        </div>
        <ul class="list-group">
            <?php foreach ($desactivadas as $herramienta) : ?>
                <li class="list-group-item desactivado">
                    <span class="desactivado__nombre"><?= e($herramienta['nombre']) ?></span>
                    <form method="post" action="<?= e(url('herramientas/' . $herramienta['id'] . '/activar')) ?>" data-submit-once>
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-secondary btn-sm" type="submit" data-loading-text="Activando…">Volver a activar</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
