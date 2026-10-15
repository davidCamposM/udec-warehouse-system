<?php

defined('APP_ROOT') || exit;

// Antes de eliminar un producto se pregunta en una pantalla propia. Para las personas el producto desaparece del
// sistema; por dentro solo se desactiva y se conserva su historial (InsumoService::eliminar).
?>
<?= partial('encabezado', [
    'volver' => [url('insumos/' . $insumo['id']), 'Volver al producto'],
    'color' => 'rojo',
    'icono' => 'alert',
    'titulo' => '¿Eliminar este producto?',
    'texto' => $insumo['nombre'] . ' · ' . $insumo['codigo'],
]) ?>

<div class="confirm">
    <p class="confirm__intro">Si lo eliminas:</p>
    <ul class="confirm__list">
        <li>Desaparece del sistema: ya no se podrá buscar, registrar ni retirar.</li>
        <li>Su código de barras queda libre, por si después se agrega como producto nuevo.</li>
    </ul>

    <?php if ((int) $insumo['stock_actual'] > 0) : ?>
        <div class="alert alert--warning" role="status">
            <?= icon('alert') ?>
            <p>Según el sistema, todavía hay <?= e(unidades($insumo['stock_actual'])) ?> en bodega.</p>
        </div>
    <?php endif; ?>
</div>

<form method="post" action="<?= e(url('insumos/' . $insumo['id'] . '/eliminar')) ?>" data-submit-once>
    <?= csrf_field() ?>
    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e(url('insumos/' . $insumo['id'])) ?>"><?= icon('close') ?>No, volver</a>
        <button class="btn btn--lg btn--si" type="submit" data-loading-text="Eliminando…"><?= icon('ok') ?>Sí, eliminar</button>
    </div>
</form>
