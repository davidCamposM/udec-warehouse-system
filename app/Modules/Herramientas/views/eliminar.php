<?php

use App\Modules\Herramientas\HerramientaService;

defined('APP_ROOT') || exit;

// Antes de eliminar una herramienta se pregunta en una pantalla propia. Para las personas desaparece del catálogo; por
// dentro solo se desactiva y los préstamos antiguos la siguen mostrando (HerramientaService::eliminar). Si todavía tiene
// unidades, está prestada, pedida o por resolver, no se puede: se dice por qué y qué hacer.
$motivo = HerramientaService::porQueNoSeElimina($herramienta);
$editar = url('herramientas/' . $herramienta['id'] . '/editar');
?>
<?= partial('encabezado', [
    'volver' => [$editar, 'Volver a Editar herramienta'],
    'color' => 'rojo',
    'icono' => 'alert',
    'titulo' => $motivo === null ? '¿Eliminar esta herramienta?' : 'Todavía no se puede eliminar',
    'texto' => $herramienta['nombre'],
]) ?>

<?php if ($motivo !== null) : ?>
    <div class="alert alert--danger" role="alert">
        <?= icon('alert') ?>
        <p><?= e($motivo) ?></p>
    </div>

    <div class="acciones">
        <a class="btn btn--lg btn--si" href="<?= e($editar) ?>"><?= icon('arrow-left') ?>Volver a Editar herramienta</a>
    </div>
<?php else : ?>
    <div class="confirm">
        <p class="confirm__intro">Si la eliminas:</p>
        <ul class="confirm__list">
            <li>Desaparece del catálogo: ya no se podrá solicitar.</li>
            <li>Los préstamos antiguos la siguen mostrando.</li>
        </ul>
    </div>

    <form method="post" action="<?= e(url('herramientas/' . $herramienta['id'] . '/eliminar')) ?>" data-submit-once>
        <?= csrf_field() ?>
        <?= mensaje_error('eliminar') ?>
        <div class="acciones">
            <a class="btn btn--lg btn--no" href="<?= e($editar) ?>"><?= icon('close') ?>No, volver</a>
            <button class="btn btn--lg btn--si" type="submit" data-loading-text="Eliminando…"><?= icon('ok') ?>Sí, eliminar</button>
        </div>
    </form>
<?php endif; ?>
