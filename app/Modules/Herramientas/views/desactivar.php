<?php defined('APP_ROOT') || exit; ?>
<?= partial('encabezado', [
    'volver' => [url('herramientas/' . $herramienta['id'] . '/editar'), 'Volver a Editar herramienta'],
    'color' => 'rojo',
    'icono' => 'alert',
    'titulo' => '¿Desactivar esta herramienta?',
    'texto' => $herramienta['nombre'],
]) ?>

<div class="confirm">
    <p class="confirm__intro">Si la desactivas:</p>
    <ul class="confirm__list">
        <li>Ya no aparecerá al solicitar herramientas.</li>
        <?php if ((int) $herramienta['prestadas'] > 0) : ?>
            <li>Las <?= e($herramienta['prestadas']) ?> que están prestadas se reciben igual cuando vuelvan.</li>
        <?php endif; ?>
        <li>Las solicitudes anteriores la siguen mostrando, y puedes volver a activarla cuando quieras.</li>
    </ul>
</div>

<form method="post" action="<?= e(url('herramientas/' . $herramienta['id'] . '/desactivar')) ?>" data-submit-once>
    <?= csrf_field() ?>
    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e(url('herramientas/' . $herramienta['id'] . '/editar')) ?>"><?= icon('close') ?>No, volver</a>
        <button class="btn btn--lg btn--si" type="submit" data-loading-text="Desactivando…"><?= icon('ok') ?>Sí, desactivar</button>
    </div>
</form>
