<?php

use App\Modules\Insumos\RetirarController;

defined('APP_ROOT') || exit;

// Etapa 1, en la ventana: el producto que se retira. Con el campo vacío se ven los últimos retirados. Tocar un producto
// lo elige; «Siguiente» busca lo escrito o escaneado.
?>

<?= partial('Insumos::partials/pasos', ['paso' => 1, 'pasos' => RetirarController::PASOS, 'color' => 'ocre']) ?>

<form class="formulario" method="post" action="<?= e(url('retirar/producto')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <?= partial('Insumos::partials/buscar-producto', [
        'texto' => $texto,
        'resultados' => $resultados,
        'ruta' => 'retirar',
        'ayuda' => 'Escanea su código, o escribe su nombre y elígelo en la lista.',
        'recientes' => $recientes,
        'tituloRecientes' => 'Retirados hace poco',
    ]) ?>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url()) ?>"><?= icon('close') ?>Cancelar</a>
        <button class="btn btn--si" type="submit" data-loading-text="Buscando…">Siguiente<?= icon('arrow-right') ?></button>
    </div>
</form>
