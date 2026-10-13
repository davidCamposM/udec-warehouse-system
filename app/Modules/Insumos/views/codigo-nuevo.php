<?php

defined('APP_ROOT') || exit;

/** Marca en rojo la pregunta con error. */
$claseError = static fn (string $campo): string => error_de($campo) !== null ? ' has-error' : '';
?>
<?= partial('encabezado', [
    'volver' => [url('insumos/' . $insumo['id']), 'Volver al producto'],
    'color' => 'turquesa',
    'icono' => 'barcode',
    'titulo' => 'Agregar código de barras',
    'texto' => 'Para «' . $insumo['nombre'] . '». Así, al escanear este código, el sistema sabrá que es este producto.',
]) ?>

<?php if (errores() !== []) : ?>
    <div class="alert alert--danger flash" role="alert">
        <?= icon('alert') ?>
        <p>Hay que corregir algo. Revisa lo marcado en rojo.</p>
    </div>
<?php endif; ?>

<form class="form-simple" method="post" action="<?= e(url('insumos/' . $insumo['id'] . '/codigos')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <div class="question<?= $claseError('codigo_barras') ?>">
        <label class="question__label" for="codigo_barras">
            <span class="question__number">1</span>Escanea el código del producto
        </label>
        <p class="question__hint">
            Haz clic en el cuadro y escanea con la pistola. También puedes escribir el número que aparece bajo las barras.
        </p>
        <input class="input input--code" id="codigo_barras" name="codigo_barras" type="text" maxlength="64"
            autocomplete="off" autofocus required data-enter-next
            value="<?= e(old('codigo_barras')) ?>" <?= aria_error('codigo_barras') ?>>
        <?= mensaje_error('codigo_barras') ?>
    </div>

    <div class="question<?= $claseError('descripcion') ?>">
        <label class="question__label" for="descripcion">
            <span class="question__number">2</span>¿Qué envase es?
            <span class="question__optional">(opcional)</span>
        </label>
        <p class="question__hint">Sirve si el producto llega en distintos envases. Ejemplo: Bidón 5 L, Caja de 100.</p>
        <input class="input" id="descripcion" name="descripcion" type="text" maxlength="80"
            value="<?= e(old('descripcion')) ?>" <?= aria_error('descripcion') ?>>
        <?= mensaje_error('descripcion') ?>
    </div>

    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e(url('insumos/' . $insumo['id'])) ?>"><?= icon('close') ?>Cancelar</a>
        <button class="btn btn--lg btn--si" type="submit" data-loading-text="Guardando…"><?= icon('ok') ?>Guardar</button>
    </div>
</form>
