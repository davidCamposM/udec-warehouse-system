<?php

use App\Modules\Insumos\AjusteController;

defined('APP_ROOT') || exit;

// Etapa 3, en la ventana: el producto, «antes → ahora» y el motivo, cada uno con «Cambiar». «Confirmar cantidad»
// deja la cantidad nueva.
$diferencia = $ahora - $antes;
$cambio = $diferencia > 0
    ? (abs($diferencia) === 1 ? 'Se suma ' : 'Se suman ') . unidades(abs($diferencia))
    : (abs($diferencia) === 1 ? 'Se resta ' : 'Se restan ') . unidades(abs($diferencia));
?>
<?= partial('Insumos::partials/pasos', ['paso' => 3, 'pasos' => AjusteController::PASOS, 'color' => 'naranja']) ?>

<form class="formulario" method="post" action="<?= e(url('ajustar/confirmar')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <?php if (error_de('contado') !== null) : ?>
        <div class="alert alert--warning" role="alert">
            <?= icon('alert') ?>
            <p><?= e(error_de('contado')) ?></p>
        </div>
    <?php endif; ?>

    <?= partial('campo-fijo', [
        'etiqueta' => 'Producto',
        'icono' => 'box',
        'valor' => $insumo['nombre'],
        'detalle' => $insumo['codigo'],
        'cambiar' => url('ajustar'),
    ]) ?>

    <div class="campo">
        <p class="campo__etiqueta">Cantidad</p>
        <div class="cambio">
            <div class="cambio__numeros">
                <div class="cambio__lado">
                    <span class="cambio__etiqueta">Antes</span>
                    <span class="cambio__numero"><?= e(cantidad($antes)) ?></span>
                    <span class="cambio__unidad"><?= $antes === 1 ? 'unidad' : 'unidades' ?></span>
                </div>
                <span class="cambio__flecha"><?= icon('arrow-right') ?></span>
                <div class="cambio__lado cambio__lado--ahora">
                    <span class="cambio__etiqueta">Ahora</span>
                    <span class="cambio__numero"><?= e(cantidad($ahora)) ?></span>
                    <span class="cambio__unidad"><?= $ahora === 1 ? 'unidad' : 'unidades' ?></span>
                </div>
            </div>
            <p class="cambio__diferencia cambio__diferencia--<?= $diferencia > 0 ? 'suma' : 'resta' ?>">
                <?= icon($diferencia > 0 ? 'plus' : 'minus') ?><?= e($cambio) ?>
            </p>
            <a class="campo__cambiar" href="<?= e(url('ajustar/contar')) ?>"><?= icon('edit') ?>Cambiar</a>
        </div>
    </div>

    <?= partial('campo-fijo', [
        'etiqueta' => 'Motivo',
        'icono' => 'info',
        'valor' => $motivo,
        'cambiar' => url('ajustar/contar'),
    ]) ?>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url('ajustar/contar')) ?>"><?= icon('arrow-left') ?>Atrás</a>
        <button class="btn btn--si" type="submit" data-loading-text="Actualizando…"><?= icon('ok') ?>Confirmar cantidad</button>
    </div>
</form>
