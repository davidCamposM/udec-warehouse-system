<?php

defined('APP_ROOT') || exit;

// «Agregar código de barras» de un producto, en un formulario compacto: el código (se escanea) y el envase, opcional.

/** Marca en rojo la pregunta con error. */
$claseError = static fn (string $campo): string => error_de($campo) !== null ? ' has-error' : '';
?>
<?= partial('encabezado', [
    'volver' => [url('insumos/' . $insumo['id']), 'Volver al producto'],
    'color' => 'turquesa',
    'icono' => 'barcode',
    'titulo' => 'Agregar otro código de barras',
    'texto' => $insumo['nombre'],
]) ?>

<?php if (errores() !== []) : ?>
    <div class="alert alert--danger flash" role="alert">
        <?= icon('alert') ?>
        <p>Hay que corregir algo. Revisa lo marcado en rojo.</p>
    </div>
<?php endif; ?>

<form class="form-simple form-simple--compacto" method="post" action="<?= e(url('insumos/' . $insumo['id'] . '/codigos')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <div class="question<?= $claseError('codigo_barras') ?>">
        <label class="question__label" for="codigo_barras">Código de barras</label>
        <div class="field__control field__control--with-icon">
            <span class="field__icon"><?= icon('barcode') ?></span>
            <input class="input input--code" id="codigo_barras" name="codigo_barras" type="text" maxlength="64"
                autocomplete="off" autofocus required data-enter-next placeholder="Escanéalo con la pistola"
                value="<?= e(old('codigo_barras')) ?>" <?= aria_error('codigo_barras') ?>>
        </div>
        <?= mensaje_error('codigo_barras') ?>
    </div>

    <div class="question<?= $claseError('descripcion') ?>">
        <label class="question__label" for="descripcion">Envase <span class="question__optional">(opcional)</span></label>
        <input class="input" id="descripcion" name="descripcion" type="text" maxlength="80" placeholder="Ejemplo: Caja de 100"
            value="<?= e(old('descripcion')) ?>" <?= aria_error('descripcion') ?>>
        <?= mensaje_error('descripcion') ?>
    </div>

    <div class="form-simple__acciones">
        <a class="btn btn--secondary" href="<?= e(url('insumos/' . $insumo['id'])) ?>">Cancelar</a>
        <button class="btn btn--si" type="submit" data-loading-text="Guardando…"><?= icon('ok') ?>Guardar</button>
    </div>
</form>
