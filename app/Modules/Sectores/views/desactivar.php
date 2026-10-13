<?php defined('APP_ROOT') || exit; ?>
<?= partial('encabezado', [
    'volver' => [url('sectores'), 'Volver a Sectores'],
    'color' => 'rojo',
    'icono' => 'alert',
    'titulo' => '¿Desactivar este sector?',
    'texto' => $sector['nombre'] . ' · ' . $sector['codigo'],
]) ?>

<div class="confirm">
    <p class="confirm__intro">Si lo desactivas:</p>
    <ul class="confirm__list">
        <li>Ya no aparecerá al registrar productos, y su código no se aceptará.</li>
        <?php if ((int) $sector['productos'] > 0) : ?>
            <li>
                Los <?= e($sector['productos']) ?> productos que están aquí siguen mostrando este sector hasta que se
                guarden en otro.
            </li>
        <?php endif; ?>
        <li>Puedes volver a activarlo cuando quieras.</li>
    </ul>
</div>

<form method="post" action="<?= e(url('sectores/' . $sector['id'] . '/desactivar')) ?>" data-submit-once>
    <?= csrf_field() ?>
    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e(url('sectores')) ?>"><?= icon('close') ?>No, volver</a>
        <button class="btn btn--lg btn--si" type="submit" data-loading-text="Desactivando…"><?= icon('ok') ?>Sí, desactivar</button>
    </div>
</form>
