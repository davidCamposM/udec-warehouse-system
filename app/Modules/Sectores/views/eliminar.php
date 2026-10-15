<?php

defined('APP_ROOT') || exit;

// Antes de eliminar un sector se pregunta en una pantalla propia. Para las personas el sector desaparece del sistema;
// por dentro solo se desactiva y el historial conserva dónde quedó cada cosa (SectorService::eliminar).
?>
<?= partial('encabezado', [
    'volver' => [url('sectores'), 'Volver a Sectores'],
    'color' => 'rojo',
    'icono' => 'alert',
    'titulo' => '¿Eliminar este sector?',
    'texto' => $sector['nombre'] . ' · ' . $sector['codigo'],
]) ?>

<div class="confirm">
    <p class="confirm__intro">Si lo eliminas:</p>
    <ul class="confirm__list">
        <li>Desaparece del sistema: ya no aparecerá al registrar productos, y su etiqueta dejará de servir.</li>
        <?php if ((int) $sector['productos'] > 0) : ?>
            <li>
                <?= (int) $sector['productos'] === 1 ? 'El producto que está aquí queda' : 'Los ' . e($sector['productos']) . ' productos que están aquí quedan' ?>
                sin ubicación, hasta que se guarden en otro sector.
            </li>
        <?php endif; ?>
    </ul>
</div>

<form method="post" action="<?= e(url('sectores/' . $sector['id'] . '/eliminar')) ?>" data-submit-once>
    <?= csrf_field() ?>
    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e(url('sectores')) ?>"><?= icon('close') ?>No, volver</a>
        <button class="btn btn--lg btn--si" type="submit" data-loading-text="Eliminando…"><?= icon('ok') ?>Sí, eliminar</button>
    </div>
</form>
