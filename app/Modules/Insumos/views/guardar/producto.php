<?php

defined('APP_ROOT') || exit;

// Etapa 1, su propia ventana («¿Qué producto llegó?»): el producto que llegó, y nada más. Se escanea (con la pistola
// salta solo a «Cuántos llegaron») o se escribe y se elige en la lista que aparece al escribir. Si se acaba de
// registrar uno, arriba va la boleta de lo registrado (qué, cuántas, dónde y el stock) y el campo queda listo para el
// siguiente.
?>
<?php if ($registrado !== null) : ?>
    <?= partial('boleta', [
        'titulo' => '¡Listo! Quedó guardado',
        'filas' => [
            ['box', '<strong>' . e($registrado['nombre']) . '</strong>'],
            ['plus', ($registrado['cantidad'] === 1 ? 'Llegó ' : 'Llegaron ') . '<strong>' . e(unidades($registrado['cantidad'])) . '</strong>.'],
            ['lugar', ['codigo' => $registrado['sector_codigo'], 'nombre' => $registrado['sector']]],
            ['layers', 'Stock: <strong>' . e(cantidad($registrado['stock'])) . '</strong>'],
        ],
    ]) ?>
<?php endif; ?>

<form class="formulario" method="post" action="<?= e(url('guardar/producto')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <?= partial('Insumos::partials/buscar-producto', [
        'texto' => $texto,
        'resultados' => $resultados,
        'ruta' => 'guardar',
        'sinEtiqueta' => true,
        'ayuda' => $registrado !== null
            ? 'Escanea el siguiente producto, o escribe su nombre y elígelo en la lista.'
            : 'Escanea su código, o escribe su nombre y elígelo en la lista.',
        'agregar' => $puedeAdministrar ? url('insumos/nuevo', ['para' => 'guardar']) : null,
    ]) ?>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url()) ?>"><?= icon($registrado !== null ? 'home' : 'close') ?><?= $registrado !== null ? 'Finalizar' : 'Cancelar' ?></a>
        <button class="btn btn--si" type="submit" data-loading-text="Buscando…">Siguiente<?= icon('arrow-right') ?></button>
    </div>
</form>
