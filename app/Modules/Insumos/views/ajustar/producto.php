<?php

defined('APP_ROOT') || exit;

// Etapa 1, su propia ventana («¿Qué producto vas a actualizar?»): el producto que se va a actualizar. Tocar un producto
// lo elige; «Siguiente» busca lo escrito o escaneado.
?>
<?php if ($actualizado !== null) : ?>
    <?= partial('boleta', [
        'titulo' => '¡Listo! Quedó actualizado',
        'filas' => [
            ['box', '<strong>' . e($actualizado['nombre']) . '</strong>'],
            ['refresh', 'Antes había ' . e(cantidad($actualizado['antes'])) . '.'],
            [$actualizado['diferencia'] > 0 ? 'plus' : 'minus', (abs($actualizado['diferencia']) === 1
                ? ($actualizado['diferencia'] > 0 ? 'Se sumó ' : 'Se restó ')
                : ($actualizado['diferencia'] > 0 ? 'Se sumaron ' : 'Se restaron '))
                . '<strong>' . e(unidades(abs($actualizado['diferencia']))) . '</strong>.'],
            ['info', 'Motivo: ' . e($actualizado['motivo'])],
            ['layers', 'Stock: <strong>' . e(cantidad($actualizado['ahora'])) . '</strong>'],
        ],
    ]) ?>
<?php endif; ?>

<form class="formulario" method="post" action="<?= e(url('ajustar/producto')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <?= partial('Insumos::partials/buscar-producto', [
        'texto' => $texto,
        'resultados' => $resultados,
        'ruta' => 'ajustar',
        'sinEtiqueta' => true,
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
