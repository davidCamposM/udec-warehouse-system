<?php

use App\Modules\Herramientas\HerramientaService;

defined('APP_ROOT') || exit;

// «Insumos Práctica de Invierno», con los componentes básicos (basico.css): arriba los filtros, los mismos del catálogo
// de herramientas y con sus colores (en bodega, prestados, atrasados, sin unidades), y una tarjeta por tipo de equipo, en
// dos columnas que se llenan de arriba abajo (sin huecos entre tarjetas de distinto largo). En cada fila el equipo y, a
// la derecha, cuántos hay en bodega en grande; debajo, los prestados o atrasados, si hay.
$vacios = [
    'en-bodega' => 'No hay equipo en bodega ahora',
    'prestadas' => 'No hay equipo prestado ahora',
    'atrasadas' => 'No hay equipo atrasado',
    'sin-unidades' => 'Todo el equipo tiene unidades',
];
?>
<?= partial('encabezado', [
    'volver' => [url(), 'Volver al inicio'],
    'tarea' => 'practica-invierno',
    'titulo' => 'Insumos Práctica de Invierno',
    'texto' => 'El equipo de montaña de la práctica: se presta y se devuelve. Cuántos hay en la bodega.',
]) ?>

<nav class="nav-pills invierno__filtros" aria-label="Mostrar">
    <?php foreach (HerramientaService::ESTADOS as $clave => $nombre) : ?>
        <?php $color = HerramientaService::COLORES[$clave] ?? null; ?>
        <a class="nav-link<?= $color !== null ? ' nav-link--' . e($color) : '' ?><?= $clave === $estado ? ' active' : '' ?>"
            href="<?= e(url('practica-invierno', $clave === 'todas' ? [] : ['ver' => $clave])) ?>" <?= $clave === $estado ? 'aria-current="true"' : '' ?>>
            <?= e($clave === 'todas' ? 'Todos' : $nombre) ?> <span class="nav-count"><?= e($conteo[$clave]) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<?php if ($grupos === []) : ?>
    <div class="card card-body vacio-simple">
        <span class="vacio-simple__icono"><?= icon('tree') ?></span>
        <p class="vacio-simple__titulo"><?= e($conteo['todas'] === 0
            ? 'Todavía no hay equipo de la Práctica de Invierno'
            : $vacios[$estado]) ?></p>
    </div>
<?php else : ?>
    <div class="invierno">
        <?php foreach ($grupos as $grupo => $equipo) : ?>
            <section class="card invierno__grupo" aria-label="<?= e($grupo) ?>">
                <div class="card-header">
                    <h2 class="card-title"><?= e($grupo) ?></h2>
                    <span class="text-muted"><?= e(count($equipo)) ?></span>
                </div>
                <ul class="invierno__lista">
                    <?php foreach ($equipo as $herramienta) : ?>
                        <?php
                        $hay = (int) $herramienta['en_bodega'];
                        $atrasadas = (int) $herramienta['atrasadas'];
                        $aTiempo = (int) $herramienta['prestadas'] - $atrasadas;
                        ?>
                        <li class="invierno__fila<?= $hay > 0 ? '' : ' invierno__fila--sin' ?>">
                            <span class="invierno__nombre">
                                <?= e($herramienta['nombre']) ?>
                                <?php if ($atrasadas > 0) : ?>
                                    <span class="status status--out"><?= e($atrasadas === 1 ? '1 atrasado' : "{$atrasadas} atrasados") ?></span>
                                <?php endif; ?>
                                <?php if ($aTiempo > 0) : ?>
                                    <span class="status status--low"><?= e($aTiempo === 1 ? '1 prestado' : "{$aTiempo} prestados") ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="invierno__hay">
                                <strong><?= e(cantidad($hay)) ?></strong>
                                <span><?= $hay > 0 ? 'en bodega' : ((int) $herramienta['unidades'] > 0 ? 'Ninguno en bodega' : 'Sin unidades') ?></span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
