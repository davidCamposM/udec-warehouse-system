<?php defined('APP_ROOT') || exit; ?>
<?= partial('encabezado', [
    'volver' => [url('insumos/' . $insumo['id']), 'Volver al producto'],
    'color' => 'rojo',
    'icono' => 'alert',
    'titulo' => '¿Desactivar este producto?',
    'texto' => $insumo['nombre'] . ' · ' . $insumo['codigo'],
]) ?>

<div class="confirm">
    <p class="confirm__intro">Si lo desactivas:</p>
    <ul class="confirm__list">
        <li>Ya no aparecerá en la lista de productos.</li>
        <li>No se podrá retirar ni registrar más unidades.</li>
        <li>Su historial se conserva, y puedes volver a activarlo cuando quieras.</li>
    </ul>

    <?php if ((int) $insumo['stock_actual'] > 0) : ?>
        <div class="alert alert--warning" role="status">
            <?= icon('alert') ?>
            <p>Según el sistema, todavía hay <?= e(unidades($insumo['stock_actual'])) ?> en bodega.</p>
        </div>
    <?php endif; ?>
</div>

<form method="post" action="<?= e(url('insumos/' . $insumo['id'] . '/desactivar')) ?>" data-submit-once>
    <?= csrf_field() ?>
    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e(url('insumos/' . $insumo['id'])) ?>"><?= icon('close') ?>No, volver</a>
        <button class="btn btn--lg btn--si" type="submit" data-loading-text="Desactivando…"><?= icon('ok') ?>Sí, desactivar</button>
    </div>
</form>
