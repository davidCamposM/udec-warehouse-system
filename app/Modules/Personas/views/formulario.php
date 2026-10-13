<?php

use App\Modules\Personas\PersonaService;
use App\Support\Rut;

defined('APP_ROOT') || exit;

$editando = $persona !== null;
$tipo = old('tipo', $persona['tipo'] ?? '');
$claseError = static fn (string $campo): string => error_de($campo) !== null ? ' has-error' : '';
?>
<?= partial('encabezado', [
    'volver' => [url('personas'), 'Volver a Personas'],
    'color' => $editando ? 'pizarra' : 'turquesa',
    'icono' => $editando ? 'edit' : 'id-card',
    'titulo' => $editando ? 'Editar persona' : 'Agregar persona',
    'texto' => $editando
        ? 'Su credencial (' . $persona['codigo_credencial'] . ') no cambia.'
        : 'El sistema le asigna un código de credencial solo (PER00001, PER00002…).',
]) ?>

<?php if (errores() !== []) : ?>
    <div class="alert alert--danger flash" role="alert">
        <?= icon('alert') ?>
        <p>Falta algo o hay que corregir un dato. Revisa lo marcado en rojo.</p>
    </div>
<?php endif; ?>

<form class="form-simple" method="post" action="<?= e($editando ? url('personas/' . $persona['id']) : url('personas')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <div class="question<?= $claseError('nombre') ?>">
        <label class="question__label" for="nombre"><span class="question__number">1</span>¿Cómo se llama?</label>
        <p class="question__hint">Nombre y apellidos, como en su carnet.</p>
        <input class="input" id="nombre" name="nombre" type="text" maxlength="120" required <?= $editando ? '' : 'autofocus' ?>
            value="<?= e(old('nombre', $persona['nombre'] ?? '')) ?>" <?= aria_error('nombre') ?>>
        <?= mensaje_error('nombre') ?>
    </div>

    <div class="question<?= $claseError('tipo') ?>">
        <fieldset class="question__set">
            <legend class="question__label"><span class="question__number">2</span>¿Qué es en la bodega?</legend>
            <div class="options options--wide">
                <?php foreach (PersonaService::TIPOS as $clave => [$nombre, $detalle]) : ?>
                    <label class="option">
                        <input class="option__input" type="radio" name="tipo" value="<?= e($clave) ?>" <?= $clave === $tipo ? 'checked' : '' ?>>
                        <span class="option__box option__box--detalle">
                            <span><?= e($nombre) ?><small><?= e($detalle) ?></small></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <?= mensaje_error('tipo') ?>
    </div>

    <div class="question<?= $claseError('rut') ?>">
        <label class="question__label" for="rut">
            <span class="question__number">3</span>RUT <span class="question__optional">(opcional)</span>
        </label>
        <p class="question__hint">Le sirve para identificarse en el punto de retiro si no trae su credencial. Ejemplo: 12.345.678-5.</p>
        <input class="input" id="rut" name="rut" type="text" maxlength="12" autocomplete="off"
            value="<?= e(old('rut', isset($persona['rut']) ? Rut::formatear($persona['rut']) : '')) ?>" <?= aria_error('rut') ?>>
        <?= mensaje_error('rut') ?>
    </div>

    <div class="question<?= $claseError('departamento') ?>">
        <label class="question__label" for="departamento">
            <span class="question__number">4</span>Departamento o unidad <span class="question__optional">(opcional)</span>
        </label>
        <p class="question__hint">Ejemplo: Silvicultura, Manejo de Bosques.</p>
        <input class="input" id="departamento" name="departamento" type="text" maxlength="120"
            value="<?= e(old('departamento', $persona['departamento'] ?? '')) ?>" <?= aria_error('departamento') ?>>
        <?= mensaje_error('departamento') ?>
    </div>

    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e(url('personas')) ?>"><?= icon('close') ?>Cancelar</a>
        <button class="btn btn--lg btn--si" type="submit" data-loading-text="Guardando…"><?= icon('ok') ?>Guardar</button>
    </div>
</form>
