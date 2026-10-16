<?php

use App\Modules\Usuarios\CuentaService;

defined('APP_ROOT') || exit;

// Editar a una persona: su nombre y su perfil. El correo no cambia (es con el que entra); para otro correo, se elimina
// y se agrega de nuevo.
$perfil = old('perfil', $cuenta['perfil']);
?>
<?= partial('encabezado', [
    'volver' => [url('usuarios'), 'Volver a Usuarios'],
    'color' => 'pizarra',
    'icono' => 'edit',
    'titulo' => 'Editar persona',
    'texto' => 'Entra con ' . $cuenta['email'] . '. El correo no cambia.',
]) ?>

<form class="form-simple" method="post" action="<?= e(url('usuarios/' . $cuenta['id'])) ?>" data-submit-once>
    <?= csrf_field() ?>

    <div class="question<?= error_de('nombre') !== null ? ' has-error' : '' ?>">
        <label class="question__label" for="nombre"><span class="question__number">1</span>Nombre</label>
        <input class="input" id="nombre" name="nombre" type="text" maxlength="120" required autofocus
            value="<?= e(old('nombre', $cuenta['nombre'])) ?>" <?= aria_error('nombre') ?>>
        <?= mensaje_error('nombre') ?>
    </div>

    <fieldset class="question<?= error_de('perfil') !== null ? ' has-error' : '' ?>">
        <legend class="question__label"><span class="question__number">2</span>Perfil</legend>
        <div class="options">
            <?php foreach (CuentaService::PERFILES as $clave => $nombre) : ?>
                <label class="option">
                    <input class="option__input" type="radio" name="perfil" value="<?= e($clave) ?>" <?= $clave === $perfil ? 'checked' : '' ?>>
                    <span class="option__box"><?= e($nombre) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?= mensaje_error('perfil') ?>
    </fieldset>

    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e(url('usuarios')) ?>"><?= icon('close') ?>Cancelar</a>
        <button class="btn btn--lg btn--si" type="submit" data-loading-text="Guardando…"><?= icon('ok') ?>Guardar</button>
    </div>
</form>
