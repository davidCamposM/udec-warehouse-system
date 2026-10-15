<?php

defined('APP_ROOT') || exit;

// Etapa 1, su propia ventana («¿Qué producto es?»). Primero se escanea su código de barras (la pistola manda Enter, que
// pasa al nombre); después se escribe el nombre. El código UdeC va plegado. Si el código ya viene (se llegó desde
// «Registrar producto» con un código desconocido), el cursor parte en el nombre. Si se acaba de agregar uno, arriba va
// el aviso. Si falta el nombre, el cursor parte ahí.
$nombre = old('nombre', $nuevo['nombre']);
$codigo = old('codigo_barras', $nuevo['codigo_barras']);
$udec = old('codigo_udec', $nuevo['codigo_udec']);
$focoEnNombre = error_de('codigo_barras') === null && ($codigo !== '' || error_de('nombre') !== null);
?>
<?php if ($agregado !== null) : ?>
    <?= partial('boleta', [
        'titulo' => '¡Listo! Quedó agregado',
        'filas' => [
            ['box', '<strong>' . e($agregado['nombre']) . '</strong> · código ' . e($agregado['codigo'])],
            ['plus', ($agregado['cantidad'] === 1 ? 'Llegó ' : 'Llegaron ') . '<strong>' . e(unidades($agregado['cantidad'])) . '</strong>.'],
            ['lugar', ['codigo' => $agregado['sector_codigo'], 'nombre' => $agregado['sector']]],
            ['layers', 'Stock: <strong>' . e(cantidad($agregado['stock'])) . '</strong>'],
        ],
    ]) ?>
<?php endif; ?>

<form class="formulario" method="post" action="<?= e(url('insumos/nuevo/producto')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <div class="escaneo-foco escaneo-foco--compacto<?= error_de('codigo_barras') !== null ? ' has-error' : '' ?>">
        <span class="escaneo-foco__icono" aria-hidden="true"><?= icon('barcode') ?></span>
        <label class="escaneo-foco__pregunta" for="codigo_barras">Escanea el código de barras del producto</label>
        <input class="escaneo-foco__campo" id="codigo_barras" name="codigo_barras" type="text" maxlength="64" autocomplete="off"
            placeholder="Código de barras" aria-describedby="codigo-ayuda" value="<?= e($codigo) ?>" data-enter-next data-lectura
            <?= $focoEnNombre ? '' : 'autofocus' ?> <?= aria_error('codigo_barras') ?>>
        <?= mensaje_error('codigo_barras') ?>
        <p class="escaneo-foco__ayuda" id="codigo-ayuda">¿No tiene código? Déjalo en blanco: el sistema le asigna uno propio.</p>
    </div>

    <div class="campo">
        <label class="campo__etiqueta" for="nombre">Nombre del producto<span class="campo__obligatorio" aria-hidden="true">*</span></label>
        <input class="input input--relleno" id="nombre" name="nombre" type="text" maxlength="150" autocomplete="off"
            aria-required="true" placeholder="Como aparece en el envase. Ejemplo: Cloro envase 1 litro" value="<?= e($nombre) ?>"
            <?= $focoEnNombre ? 'autofocus' : '' ?> <?= aria_error('nombre') ?>>
        <?= mensaje_error('nombre') ?>
    </div>

    <details class="lista-plegada"<?= $udec !== '' || error_de('codigo_udec') !== null ? ' open' : '' ?>>
        <summary>Código del catálogo UdeC (opcional)</summary>
        <div class="campo">
            <label class="visually-hidden" for="codigo_udec">Código del catálogo UdeC</label>
            <input class="input input--relleno" id="codigo_udec" name="codigo_udec" type="text" maxlength="20" inputmode="numeric"
                autocomplete="off" placeholder="El número del pedido de materiales. Ejemplo: 3001027" value="<?= e($udec) ?>"
                <?= aria_error('codigo_udec') ?>>
            <?= mensaje_error('codigo_udec') ?>
        </div>
    </details>

    <div class="formulario__acciones">
        <?php if ($nuevo['para_guardar']) : ?>
            <a class="btn btn--secondary" href="<?= e(url('guardar')) ?>"><?= icon('arrow-left') ?>Atrás</a>
        <?php else : ?>
            <a class="btn btn--secondary" href="<?= e(url()) ?>"><?= icon($agregado !== null ? 'home' : 'close') ?><?= $agregado !== null ? 'Finalizar' : 'Cancelar' ?></a>
        <?php endif; ?>
        <button class="btn btn--si" type="submit" data-loading-text="Un momento…">Siguiente<?= icon('arrow-right') ?></button>
    </div>
</form>
