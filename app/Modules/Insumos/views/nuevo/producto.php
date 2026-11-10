<?php

defined('APP_ROOT') || exit;

// Etapa 1, su propia ventana («¿Qué producto es?»), con campos simples: primero el código de barras (la pistola manda
// Enter, que pasa al nombre); después el nombre y, a la vista, el código UdeC (opcional). Si el código ya viene (se llegó desde
// «Ingresar productos» con un código desconocido), el cursor parte en el nombre. Si se acaba de agregar uno, arriba va
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
            ['box', '<strong>' . e($agregado['nombre']) . '</strong> · código ' . e(codigo_producto($agregado))],
            ['plus', ($agregado['cantidad'] === 1 ? 'Llegó ' : 'Llegaron ') . '<strong>' . e(unidades($agregado['cantidad'])) . '</strong>.'],
            $agregado['sector_codigo'] !== ''
                ? ['lugar', ['codigo' => $agregado['sector_codigo'], 'nombre' => $agregado['sector']]]
                : ['map-pin', 'Sin ubicación por ahora.'],
            ['layers', 'Stock: <strong>' . e(cantidad($agregado['stock'])) . '</strong>'],
        ],
    ]) ?>
<?php endif; ?>

<form class="formulario" method="post" action="<?= e(url('insumos/nuevo/producto')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <div class="campo<?= error_de('codigo_barras') !== null ? ' has-error' : '' ?>">
        <label class="campo__etiqueta" for="codigo_barras">Código de barras</label>
        <div class="field__control field__control--with-icon">
            <span class="field__icon"><?= icon('barcode') ?></span>
            <input class="input input--relleno input--code" id="codigo_barras" name="codigo_barras" type="text" maxlength="64"
                autocomplete="off" placeholder="Escanéalo con la pistola" aria-describedby="codigo-ayuda" value="<?= e($codigo) ?>"
                data-enter-next data-lectura <?= $focoEnNombre ? '' : 'autofocus' ?> <?= aria_error('codigo_barras') ?>>
        </div>
        <?= mensaje_error('codigo_barras') ?>
        <p class="campo__ayuda" id="codigo-ayuda">¿No tiene? Déjalo en blanco: el sistema le asigna uno propio.</p>
    </div>

    <div class="campo">
        <label class="campo__etiqueta" for="nombre">Nombre<span class="campo__obligatorio" aria-hidden="true">*</span></label>
        <input class="input input--relleno" id="nombre" name="nombre" type="text" maxlength="150" autocomplete="off"
            aria-required="true" placeholder="Como aparece en el envase. Ejemplo: Cloro envase 1 litro" value="<?= e($nombre) ?>"
            <?= $focoEnNombre ? 'autofocus' : '' ?> <?= aria_error('nombre') ?>>
        <?= mensaje_error('nombre') ?>
    </div>

    <?php // El código UdeC: el del catálogo de abasto, el mismo que trae la reserva de SAP. ?>
    <div class="campo<?= error_de('codigo_udec') !== null ? ' has-error' : '' ?>">
        <label class="campo__etiqueta" for="codigo_udec">Código UdeC <span class="campo__opcional">(opcional)</span></label>
        <input class="input input--relleno input--code" id="codigo_udec" name="codigo_udec" type="text" maxlength="20" inputmode="numeric"
            autocomplete="off" placeholder="El del catálogo de abasto. Ejemplo: 3001027" value="<?= e($udec) ?>"
            <?= aria_error('codigo_udec') ?>>
        <?= mensaje_error('codigo_udec') ?>
    </div>

    <div class="formulario__acciones">
        <?php if ($nuevo['para_guardar']) : ?>
            <a class="btn btn--secondary" href="<?= e(url('guardar')) ?>"><?= icon('arrow-left') ?>Atrás</a>
        <?php else : ?>
            <a class="btn btn--secondary" href="<?= e(url()) ?>"><?= icon($agregado !== null ? 'home' : 'close') ?><?= $agregado !== null ? 'Finalizar' : 'Cancelar' ?></a>
        <?php endif; ?>
        <button class="btn btn--si" type="submit" data-loading-text="Un momento…">Siguiente<?= icon('arrow-right') ?></button>
    </div>
</form>
