<?php defined('APP_ROOT') || exit; ?>
<?= partial('encabezado', [
    'volver' => [url('insumos/' . $insumo['id']), 'Volver al producto'],
    'color' => 'rojo',
    'icono' => 'alert',
    'titulo' => '¿Quitar este código de barras?',
    'texto' => $insumo['nombre'],
]) ?>

<div class="confirm">
    <p class="codes__value"><?= icon('barcode') ?><?= e($codigo['codigo']) ?></p>
    <ul class="confirm__list">
        <li>Al escanear este código, el sistema ya no reconocerá este producto.</li>
        <li>Puedes volver a agregarlo cuando quieras.</li>
    </ul>
</div>

<form method="post" action="<?= e(url('insumos/' . $insumo['id'] . '/codigos/' . $codigo['id'] . '/quitar')) ?>"
    data-submit-once>
    <?= csrf_field() ?>
    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e(url('insumos/' . $insumo['id'])) ?>"><?= icon('close') ?>No, volver</a>
        <button class="btn btn--lg btn--si" type="submit" data-loading-text="Quitando…"><?= icon('ok') ?>Sí, quitar</button>
    </div>
</form>
