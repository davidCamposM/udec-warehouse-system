<?php

use App\Modules\Usuarios\UsuarioService;

defined('APP_ROOT') || exit;

// Crear la contraseña con el enlace que llegó al correo: dos veces, para no equivocarse. Si el enlace ya se usó o
// venció, se explica y se ofrece pedir otro.
?>
<?php if ($cuenta === null) : ?>
    <div class="alert alert--warning auth__alert" role="status">
        <?= icon('clock') ?>
        <p>Este enlace ya no sirve: se usó o venció. Pide uno nuevo y usa el último que te llegue.</p>
    </div>
    <a class="btn btn--primary auth__submit" href="<?= e(url('clave')) ?>">Pedir un enlace nuevo</a>
    <p class="auth__volver"><a href="<?= e(url('login')) ?>">Volver a ingresar</a></p>
<?php else : ?>
    <p class="auth__ayuda">
        Hola, <?= e(explode(' ', trim((string) $cuenta['nombre']))[0]) ?>. Con esta contraseña vas a entrar con
        <strong><?= e($cuenta['email']) ?></strong>.
    </p>

    <form class="form" method="post" action="<?= e(url('clave/' . $codigo)) ?>" data-submit-once>
        <?= csrf_field() ?>
        <input type="hidden" name="email" value="<?= e($cuenta['email']) ?>" autocomplete="username">

        <div class="field">
            <label class="field__label" for="password">Contraseña nueva</label>
            <div class="field__control field__control--with-icon field__control--with-action">
                <span class="field__icon"><?= icon('lock') ?></span>
                <input class="input" id="password" name="password" type="password" autocomplete="new-password" required autofocus
                    minlength="<?= e(UsuarioService::PASSWORD_MIN) ?>" maxlength="<?= e(UsuarioService::PASSWORD_MAX) ?>"
                    placeholder="Al menos <?= e(UsuarioService::PASSWORD_MIN) ?> letras o números" <?= aria_error('password') ?>>
                <button class="field__action" type="button" data-toggle-password="password" aria-label="Mostrar contraseña" aria-pressed="false">
                    <?= icon('eye', 'icon icon--show') ?>
                    <?= icon('eye-off', 'icon icon--hide') ?>
                </button>
            </div>
            <?= mensaje_error('password') ?>
        </div>

        <div class="field">
            <label class="field__label" for="password_repetida">Escríbela otra vez</label>
            <div class="field__control field__control--with-icon">
                <span class="field__icon"><?= icon('lock') ?></span>
                <input class="input" id="password_repetida" name="password_repetida" type="password" autocomplete="new-password" required
                    maxlength="<?= e(UsuarioService::PASSWORD_MAX) ?>" <?= aria_error('password_repetida') ?>>
            </div>
            <?= mensaje_error('password_repetida') ?>
        </div>

        <button class="btn btn--primary auth__submit" type="submit" data-loading-text="Guardando…">Guardar mi contraseña</button>
    </form>
<?php endif; ?>
