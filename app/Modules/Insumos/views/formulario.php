<?php

defined('APP_ROOT') || exit;

// «Editar producto», desde su ficha. (Agregar uno nuevo es un formulario por etapas en una ventana: Insumos::nuevo/*.)

/** Lo que el usuario había escrito (si el formulario volvió con errores) o, si no, el valor guardado. */
$valor = static fn (string $campo, mixed $guardado = ''): string => old($campo, (string) ($guardado ?? ''));

$categoriaId = $valor('categoria_id', $insumo['categoria_id'] ?? '');
$volver = [url('insumos/' . $insumo['id']), 'Volver al producto'];
$numero = 0;

/** Marca en rojo la pregunta con error. */
$claseError = static fn (string $campo): string => error_de($campo) !== null ? ' has-error' : '';
?>
<?= partial('encabezado', [
    'volver' => $volver,
    'color' => 'pizarra',
    'icono' => 'edit',
    'titulo' => 'Editar producto',
    'texto' => $insumo['nombre'],
]) ?>

<?php if (errores() !== []) : ?>
    <div class="alert alert--danger flash" role="alert">
        <?= icon('alert') ?>
        <p>Falta algo o hay que corregir un dato. Revisa lo marcado en rojo.</p>
    </div>
<?php endif; ?>

<form class="form-simple" method="post" action="<?= e(url('insumos/' . $insumo['id'])) ?>" data-submit-once>
    <?= csrf_field() ?>

    <div class="question<?= $claseError('nombre') ?>">
        <label class="question__label" for="nombre">
            <span class="question__number"><?= ++$numero ?></span>¿Cómo se llama el producto?
        </label>
        <p class="question__hint">Como aparece en el envase o en el pedido. Ejemplo: Cloro envase 1 litro.</p>
        <input class="input" id="nombre" name="nombre" type="text" maxlength="150" required
            value="<?= e($valor('nombre', $insumo['nombre'])) ?>" <?= aria_error('nombre') ?>>
        <?= mensaje_error('nombre') ?>
    </div>

    <div class="question<?= $claseError('categoria_id') ?>">
        <fieldset class="question__set">
            <legend class="question__label">
                <span class="question__number"><?= ++$numero ?></span>¿De qué tipo es?
            </legend>
            <p class="question__hint">Su categoría. Ayuda a encontrarlo.</p>
            <?= partial('Insumos::partials/categorias', ['grupos' => $grupos, 'categoriaId' => $categoriaId]) ?>
        </fieldset>
        <?= mensaje_error('categoria_id') ?>
    </div>

    <div class="question<?= $claseError('stock_minimo') ?>">
        <label class="question__label" for="stock_minimo">
            <span class="question__number"><?= ++$numero ?></span>Stock mínimo: aviso para pedir más
        </label>
        <p class="question__hint">
            No es lo que hay en bodega: es un aviso. Por ejemplo, si pones 10, cuando queden 10 o menos el producto
            aparecerá en «Por reponer». Con 0 no hay aviso.
        </p>
        <div class="quantity">
            <div class="stepper" data-stepper data-min="0">
                <button class="stepper__button" type="button" data-step="-1"><?= icon('minus') ?><span class="visually-hidden">Restar uno</span></button>
                <input class="stepper__input" id="stock_minimo" name="stock_minimo" type="text" inputmode="numeric"
                    value="<?= e($valor('stock_minimo', $insumo['stock_minimo'])) ?>" <?= aria_error('stock_minimo') ?>>
                <button class="stepper__button" type="button" data-step="1"><?= icon('plus') ?><span class="visually-hidden">Sumar uno</span></button>
            </div>
            <span class="quantity__unit">unidades</span>
        </div>
        <?= mensaje_error('stock_minimo') ?>
    </div>

    <div class="question<?= $claseError('codigo_udec') ?>">
        <label class="question__label" for="codigo_udec">
            <span class="question__number"><?= ++$numero ?></span>Código del catálogo UdeC
            <span class="question__optional">(opcional)</span>
        </label>
        <p class="question__hint">El número que tiene en el pedido de materiales de la UdeC. Ejemplo: 3001027.</p>
        <input class="input" id="codigo_udec" name="codigo_udec" type="text" maxlength="20" inputmode="numeric"
            value="<?= e($valor('codigo_udec', $insumo['codigo_udec'] ?? '')) ?>" <?= aria_error('codigo_udec') ?>>
        <?= mensaje_error('codigo_udec') ?>
    </div>

    <div class="question<?= $claseError('sku') ?>">
        <label class="question__label" for="sku">
            <span class="question__number"><?= ++$numero ?></span>Código de barras
            <span class="question__optional">(opcional)</span>
        </label>
        <p class="question__hint">El código del envase: escanéalo o escríbelo. Ejemplo: 7806505012554. Con solo ceros, todavía no está anotado.</p>
        <input class="input" id="sku" name="sku" type="text" maxlength="20" inputmode="numeric" autocomplete="off"
            value="<?= e($valor('sku', $insumo['sku'] ?? '')) ?>" <?= aria_error('sku') ?>>
        <?= mensaje_error('sku') ?>
    </div>

    <p class="form-simple__note">
        <?= icon('info') ?>
        Otros códigos de barras del mismo producto (otra marca, la caja) se agregan o se quitan desde su ficha.
    </p>

    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e($volver[0]) ?>"><?= icon('close') ?>Cancelar</a>
        <button class="btn btn--lg btn--si" type="submit" data-loading-text="Guardando…"><?= icon('ok') ?>Guardar</button>
    </div>
</form>
