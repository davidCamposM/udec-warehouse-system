<?php

defined('APP_ROOT') || exit;

// Etapa 1, su propia ventana («¿Qué se retira?»): el producto, y nada más. Se escanea (con la pistola salta
// sola a «¿Cuántas unidades se retiran?») o se escribe y se elige en la lista que aparece al escribir. Si se acaba de
// retirar uno, arriba va la boleta de lo retirado (qué, cuántas y el stock) y el campo queda listo para el siguiente.
?>
<?php if ($retirado !== null) : ?>
    <?= partial('boleta', [
        'titulo' => '¡Listo! Quedó descontado',
        'filas' => [
            ['box', '<strong>' . e($retirado['nombre']) . '</strong>'],
            ['minus', ($retirado['cantidad'] === 1 ? 'Salió ' : 'Salieron ') . '<strong>' . e(unidades($retirado['cantidad'])) . '</strong>.'],
            ['layers', 'Stock: <strong>' . e(cantidad($retirado['stock'])) . '</strong>'],
        ],
    ]) ?>
<?php endif; ?>

<form class="formulario" method="post" action="<?= e(url('retirar/producto')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <?= partial('Insumos::partials/buscar-producto', [
        'texto' => $texto,
        'resultados' => $resultados,
        'ruta' => 'retirar',
        'sinEtiqueta' => true,
        'ayuda' => $retirado !== null
            ? 'Escanea el siguiente producto, o escribe su nombre y elígelo en la lista.'
            : 'Escanea su código, o escribe su nombre y elígelo en la lista.',
    ]) ?>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url()) ?>"><?= icon($retirado !== null ? 'home' : 'close') ?><?= $retirado !== null ? 'Finalizar' : 'Cancelar' ?></a>
        <button class="btn btn--si" type="submit" data-loading-text="Buscando…">Siguiente<?= icon('arrow-right') ?></button>
    </div>
</form>
