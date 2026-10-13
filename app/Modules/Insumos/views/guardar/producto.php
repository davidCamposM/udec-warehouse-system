<?php

use App\Modules\Insumos\GuardarController;

defined('APP_ROOT') || exit;

// Etapa 1, en la ventana: el producto que llegó. Con el campo vacío se ven los últimos registrados. Tocar un producto
// lo elige; «Siguiente» busca lo escrito o escaneado.
?>

<?= partial('Insumos::partials/pasos', ['paso' => 1, 'pasos' => GuardarController::PASOS, 'color' => 'verde']) ?>

<form class="formulario" method="post" action="<?= e(url('guardar/producto')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <?= partial('Insumos::partials/buscar-producto', [
        'texto' => $texto,
        'resultados' => $resultados,
        'ruta' => 'guardar',
        'ayuda' => 'Escanea su código, o escribe su nombre y elígelo en la lista.',
        'recientes' => $recientes,
        'tituloRecientes' => 'Registrados hace poco',
        'agregar' => $puedeAdministrar ? url('insumos/nuevo', ['para' => 'guardar']) : null,
    ]) ?>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url()) ?>"><?= icon('close') ?>Cancelar</a>
        <button class="btn btn--si" type="submit" data-loading-text="Buscando…">Siguiente<?= icon('arrow-right') ?></button>
    </div>
</form>
