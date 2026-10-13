<?php

use App\Modules\Insumos\AjusteController;

defined('APP_ROOT') || exit;

// Etapa 1, en la ventana: el producto que se va a actualizar. Tocar un producto lo elige; «Siguiente» busca lo escrito
// o escaneado.
?>
<?php if ($actualizado !== null) : ?>
    <?php
    $diferencia = abs($actualizado['diferencia']);
    $cambio = $actualizado['diferencia'] > 0
        ? ($diferencia === 1 ? 'se sumó ' : 'se sumaron ') . unidades($diferencia)
        : ($diferencia === 1 ? 'se restó ' : 'se restaron ') . unidades($diferencia);
    ?>
    <?= partial('listo', [
        'titulo' => 'Listo, quedó actualizado',
        'detalle' => 'Ahora hay <strong>' . e(unidades($actualizado['ahora'])) . '</strong> de <strong>' . e($actualizado['nombre'])
            . '</strong>. Antes había ' . e(unidades($actualizado['antes'])) . ': ' . e($cambio) . '. Motivo: ' . e($actualizado['motivo']) . '.',
        'enlaces' => [['Ver el producto', url('insumos/' . $actualizado['insumo_id'])]],
    ]) ?>
<?php endif; ?>

<?= partial('Insumos::partials/pasos', ['paso' => 1, 'pasos' => AjusteController::PASOS, 'color' => 'naranja']) ?>

<form class="formulario" method="post" action="<?= e(url('ajustar/producto')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <?= partial('Insumos::partials/buscar-producto', [
        'texto' => $texto,
        'resultados' => $resultados,
        'ruta' => 'ajustar',
        'ayuda' => ($actualizado !== null ? '¿Otro producto? ' : '') . 'Escanea su código, o escribe su nombre y elígelo en la lista.',
    ]) ?>

    <div class="formulario__acciones">
        <?php if ($actualizado !== null) : ?>
            <a class="btn btn--secondary" href="<?= e(url()) ?>"><?= icon('ok') ?>Terminar</a>
        <?php else : ?>
            <a class="btn btn--secondary" href="<?= e(url()) ?>"><?= icon('close') ?>Cancelar</a>
        <?php endif; ?>
        <button class="btn btn--si" type="submit" data-loading-text="Buscando…">Siguiente<?= icon('arrow-right') ?></button>
    </div>
</form>
