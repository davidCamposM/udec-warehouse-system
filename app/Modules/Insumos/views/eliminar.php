<?php

use App\Core\Permission;
use App\Modules\Insumos\InsumoService;

defined('APP_ROOT') || exit;

// Antes de eliminar un producto se pregunta en una pantalla propia. Para las personas el producto desaparece del
// sistema; por dentro solo se desactiva y se conserva su historial (InsumoService::eliminar). Si todavía tiene unidades
// en bodega no se puede: se dice por qué y se ofrece «Corregir stock».
$motivo = InsumoService::porQueNoSeElimina($insumo);
?>
<?= partial('encabezado', [
    'volver' => [url('insumos/' . $insumo['id']), 'Volver al producto'],
    'color' => 'rojo',
    'icono' => 'alert',
    'titulo' => $motivo === null ? '¿Eliminar este producto?' : 'Todavía no se puede eliminar',
    'texto' => $insumo['nombre'] . ' · ' . codigo_producto($insumo),
]) ?>

<?php if ($motivo !== null) : ?>
    <div class="alert alert--danger" role="alert">
        <?= icon('alert') ?>
        <p><?= e($motivo) ?></p>
    </div>

    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e(url('insumos/' . $insumo['id'])) ?>"><?= icon('arrow-left') ?>Volver al producto</a>
        <?php if (Permission::can('insumos.ajuste')) : ?>
            <a class="btn btn--lg btn--si" href="<?= e(url('ajustar', ['producto' => $insumo['id']])) ?>"><?= icon('refresh') ?>Corregir stock</a>
        <?php endif; ?>
    </div>
<?php else : ?>
    <div class="confirm">
        <p class="confirm__intro">Si lo eliminas:</p>
        <ul class="confirm__list">
            <li>Desaparece del sistema: ya no se podrá buscar, registrar ni retirar.</li>
            <li>Su código de barras queda libre, por si después se agrega como producto nuevo.</li>
        </ul>
    </div>

    <form method="post" action="<?= e(url('insumos/' . $insumo['id'] . '/eliminar')) ?>" data-submit-once>
        <?= csrf_field() ?>
        <?= mensaje_error('eliminar') ?>
        <div class="acciones">
            <a class="btn btn--lg btn--no" href="<?= e(url('insumos/' . $insumo['id'])) ?>"><?= icon('close') ?>No, volver</a>
            <button class="btn btn--lg btn--si" type="submit" data-loading-text="Eliminando…"><?= icon('ok') ?>Sí, eliminar</button>
        </div>
    </form>
<?php endif; ?>
