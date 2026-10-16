<?php

defined('APP_ROOT') || exit;

$errorEmail = error_de('email');
$errorPassword = error_de('password');

// Tras un intento fallido el correo ya viene escrito: el cursor parte en la contraseña.
$enfocarPassword = old('email') !== '' && $errorEmail === null;
?>
<?php if ($aviso !== null) : ?>
    <div class="alert alert--warning auth__alert" role="status">
        <?= icon('info') ?>
        <p><?= e($aviso) ?></p>
    </div>
<?php endif; ?>

<?php if ($exito !== null) : ?>
    <div class="alert alert--success auth__alert" role="status">
        <?= icon('check') ?>
        <p><?= e($exito) ?></p>
    </div>
<?php endif; ?>

<?php if ($error !== null) : ?>
    <div class="alert alert--danger auth__alert" role="alert">
        <?= icon('alert') ?>
        <p><?= e($error) ?></p>
    </div>
<?php endif; ?>

<form class="form" method="post" action="<?= e(url('login')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <div class="field">
        <label class="field__label" for="email">Correo electrónico</label>
        <div class="field__control field__control--with-icon">
            <span class="field__icon"><?= icon('mail') ?></span>
            <input class="input" id="email" name="email" type="email" inputmode="email"
                autocomplete="username" placeholder="nombre@udec.cl" value="<?= e(old('email')) ?>" required
                <?= $enfocarPassword ? '' : 'autofocus' ?>
                <?php if ($errorEmail !== null) : ?>aria-invalid="true" aria-describedby="email-error"<?php endif; ?>>
        </div>
        <?php if ($errorEmail !== null) : ?>
            <p class="field__error" id="email-error"><?= icon('alert') ?><?= e($errorEmail) ?></p>
        <?php endif; ?>
    </div>

    <div class="field">
        <label class="field__label" for="password">Contraseña</label>
        <div class="field__control field__control--with-icon field__control--with-action">
            <span class="field__icon"><?= icon('lock') ?></span>
            <input class="input" id="password" name="password" type="password"
                autocomplete="current-password" placeholder="Escribe tu contraseña" required
                <?= $enfocarPassword ? 'autofocus' : '' ?>
                <?php if ($errorPassword !== null) : ?>aria-invalid="true" aria-describedby="password-error"<?php endif; ?>>
            <button class="field__action" type="button" data-toggle-password="password"
                aria-label="Mostrar contraseña" aria-pressed="false">
                <?= icon('eye', 'icon icon--show') ?>
                <?= icon('eye-off', 'icon icon--hide') ?>
            </button>
        </div>
        <?php if ($errorPassword !== null) : ?>
            <p class="field__error" id="password-error"><?= icon('alert') ?><?= e($errorPassword) ?></p>
        <?php endif; ?>
    </div>

    <button class="btn btn--primary auth__submit" type="submit" data-loading-text="Ingresando…">Ingresar</button>
</form>

<p class="auth__forgot">
    <a href="<?= e(url('clave')) ?>">¿Primera vez, o olvidaste tu contraseña?</a>
</p>

<p class="auth__account">¿No tienes cuenta? <strong>Pídela al encargado de bodega.</strong></p>
