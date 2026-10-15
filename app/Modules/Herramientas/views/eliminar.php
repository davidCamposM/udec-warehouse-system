<?php

defined('APP_ROOT') || exit;

// Antes de eliminar una herramienta se pregunta en una pantalla propia. Para las personas desaparece del catálogo; por
// dentro solo se desactiva y los préstamos antiguos la siguen mostrando (HerramientaService::eliminar).
?>
<?= partial('encabezado', [
    'volver' => [url('herramientas/' . $herramienta['id'] . '/editar'), 'Volver a Editar herramienta'],
    'color' => 'rojo',
    'icono' => 'alert',
    'titulo' => '¿Eliminar esta herramienta?',
    'texto' => $herramienta['nombre'],
]) ?>

<div class="confirm">
    <p class="confirm__intro">Si la eliminas:</p>
    <ul class="confirm__list">
        <li>Desaparece del catálogo: ya no se podrá solicitar.</li>
        <?php if ((int) $herramienta['prestadas'] > 0) : ?>
            <li>Las <?= e($herramienta['prestadas']) ?> que están prestadas se reciben igual cuando vuelvan.</li>
        <?php endif; ?>
    </ul>
</div>

<form method="post" action="<?= e(url('herramientas/' . $herramienta['id'] . '/eliminar')) ?>" data-submit-once>
    <?= csrf_field() ?>
    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e(url('herramientas/' . $herramienta['id'] . '/editar')) ?>"><?= icon('close') ?>No, volver</a>
        <button class="btn btn--lg btn--si" type="submit" data-loading-text="Eliminando…"><?= icon('ok') ?>Sí, eliminar</button>
    </div>
</form>
