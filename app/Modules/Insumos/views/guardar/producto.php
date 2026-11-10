<?php

use App\Modules\Insumos\InsumoService;

defined('APP_ROOT') || exit;

// Etapa 1, su propia ventana («¿Qué llegó?»): el producto que llegó, y nada más. Se escanea (con la pistola
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
            $registrado['sector_codigo'] !== ''
                ? ['lugar', ['codigo' => $registrado['sector_codigo'], 'nombre' => $registrado['sector']]]
                : ['map-pin', 'Sin ubicación por ahora.'],
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
    ]) ?>

    <?php
    // Las otras formas de ingresar productos, a la vista bajo la búsqueda (así el inicio solo muestra «Registrar
    // producto»): un producto que todavía no existe (se crea y se vuelve aquí con lo registrado) y el
    // abastecimiento SAP.
    ?>
    <?php if ($puedeCrear || $puedeAbastecer) : ?>
        <nav class="guardar__otros" aria-label="Otras formas de ingresar productos">
            <?php if ($puedeCrear) : ?>
                <a class="guardar__otro" href="<?= e(url('insumos/nuevo', ['para' => 'guardar'] + ($texto !== '' ? InsumoService::paraAgregar($texto) : []))) ?>">
                    <span class="guardar__otro-icono"><?= icon('plus') ?></span>
                    <span>Producto nuevo</span>
                </a>
            <?php endif; ?>
            <?php if ($puedeAbastecer) : ?>
                <a class="guardar__otro" href="<?= e(url('abastecimiento')) ?>">
                    <span class="guardar__otro-icono"><?= icon('clipboard') ?></span>
                    <span>Abastecimiento SAP</span>
                </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url()) ?>"><?= icon($registrado !== null ? 'home' : 'close') ?><?= $registrado !== null ? 'Finalizar' : 'Cancelar' ?></a>
        <button class="btn btn--si" type="submit" data-loading-text="Buscando…">Siguiente<?= icon('arrow-right') ?></button>
    </div>

</form>
