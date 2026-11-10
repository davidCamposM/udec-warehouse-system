<?php

defined('APP_ROOT') || exit;

// Difusión, minimalista: el encabezado con el resumen a la derecha; los grupos como selector compacto (lleva a cada
// uno); y cada grupo, una tarjeta con filas delgadas (nombre y detalle; a la derecha, la existencia).
// Las tarjetas van en dos columnas parejas: cada grupo, en orden, va a la columna más corta (así no quedan huecos).
$columnas = [[], []];
$filas = [0, 0];
foreach ($grupos as $nombre => $grupo) {
    $i = $filas[0] <= $filas[1] ? 0 : 1;
    $columnas[$i][$nombre] = $grupo;
    $filas[$i] += count($grupo['items']) + 1;
}
$resumen = '<span class="difusion__resumen"><strong>' . e($conteo['articulo']) . '</strong> artículos · <strong>'
    . e($conteo['servicio']) . '</strong> servicios</span>';
?>
<?= partial('encabezado', [
    'volver' => [url(), 'Volver al inicio'],
    'tarea' => 'difusion',
    'titulo' => 'Difusión',
    'lateral' => $grupos === [] ? '' : $resumen,
]) ?>

<div class="difusion">
    <?php if ($grupos === []) : ?>
        <div class="card card-body vacio-simple"><p class="vacio-simple__titulo">Todavía no hay artículos de difusión</p></div>
    <?php else : ?>
        <div class="difusion__barra">
            <nav class="segmento" aria-label="Ir a un grupo">
                <?php foreach ($grupos as $nombre => $grupo) : ?>
                    <a class="segmento__opcion" href="#<?= e($grupo['id']) ?>"><?= e($nombre) ?><span class="segmento__cuenta"><?= e(count($grupo['items'])) ?></span></a>
                <?php endforeach; ?>
            </nav>
        </div>

        <div class="difusion__grupos">
            <?php foreach ($columnas as $columna) : ?>
            <div class="difusion__columna">
            <?php foreach ($columna as $nombre => $grupo) : ?>
                <section class="difusion__grupo" id="<?= e($grupo['id']) ?>" aria-labelledby="titulo-<?= e($grupo['id']) ?>">
                    <h2 class="difusion__titulo" id="titulo-<?= e($grupo['id']) ?>">
                        <?= icon($grupo['icono']) ?><?= e($nombre) ?>
                    </h2>
                    <ul class="difusion__lista">
                        <?php foreach ($grupo['items'] as $item) : ?>
                            <li class="difusion__item">
                                <span class="difusion__identidad">
                                    <strong><?= e($item['nombre']) ?></strong>
                                    <?php if ($item['detalle'] !== null) : ?><span><?= e($item['detalle']) ?></span><?php endif; ?>
                                </span>
                                <?php if ($item['cantidad_actual'] === null) : ?>
                                    <span class="difusion__valor difusion__valor--sin">Sin contar</span>
                                <?php else : ?>
                                    <span class="difusion__valor"><?= e(cantidad((int) $item['cantidad_actual'])) ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
