<?php

use App\Modules\Usuarios\CuentaService;

defined('APP_ROOT') || exit;

// «Agregar persona»: su nombre, su correo (UdeC o Gmail) y si es docente o administrativa. Al guardar se muestra el
// enlace para que cree su contraseña (Usuarios::enlace).
$perfil = old('perfil');
?>
<?= partial('encabezado', [
    'volver' => [url('usuarios'), 'Volver a Usuarios'],
    'tarea' => 'usuarios',
    'icono' => 'plus',
    'titulo' => 'Agregar persona',
    'texto' => 'Al agregarla, el sistema te da un enlace para que ella cree su contraseña.',
]) ?>

<form class="form-simple" method="post" action="<?= e(url('usuarios/agregar')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <div class="question<?= error_de('nombre') !== null ? ' has-error' : '' ?>">
        <label class="question__label" for="nombre"><span class="question__number">1</span>Nombre y apellido</label>
        <input class="input" id="nombre" name="nombre" type="text" maxlength="120" autocomplete="off" required autofocus
            placeholder="Ejemplo: Juan Pérez" value="<?= e(old('nombre')) ?>" <?= aria_error('nombre') ?>>
        <?= mensaje_error('nombre') ?>
    </div>

    <div class="question<?= error_de('email') !== null ? ' has-error' : '' ?>">
        <label class="question__label" for="email"><span class="question__number">2</span>Correo (UdeC o Gmail)</label>
        <input class="input" id="email" name="email" type="email" inputmode="email" maxlength="190" autocomplete="off" required
            placeholder="nombre@udec.cl o nombre@gmail.com" value="<?= e(old('email')) ?>" <?= aria_error('email') ?>>
        <?= mensaje_error('email') ?>
    </div>

    <fieldset class="question<?= error_de('perfil') !== null ? ' has-error' : '' ?>">
        <legend class="question__label"><span class="question__number">3</span>¿Qué es?</legend>
        <div class="options">
            <?php foreach (CuentaService::PERFILES as $clave => $nombre) : ?>
                <label class="option">
                    <input class="option__input" type="radio" name="perfil" value="<?= e($clave) ?>" required <?= $clave === $perfil ? 'checked' : '' ?>>
                    <span class="option__box"><?= e($nombre) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?= mensaje_error('perfil') ?>
    </fieldset>

    <div class="acciones">
        <a class="btn btn--lg btn--no" href="<?= e(url('usuarios')) ?>"><?= icon('close') ?>Cancelar</a>
        <button class="btn btn--lg btn--si" type="submit" data-loading-text="Agregando…"><?= icon('ok') ?>Agregar</button>
    </div>
</form>
