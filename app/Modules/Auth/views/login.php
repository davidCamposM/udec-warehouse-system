<?php

defined('APP_ROOT') || exit;

$errorEmail = error_de('email');
$errorPassword = error_de('password');
?>
<h1 class="auth__title">Iniciar sesión</h1>
<p class="auth__lead">¿No tienes cuenta? Pídela al encargado de bodega.</p>

<?php if ($aviso !== null) : ?>
    <div class="alert alert--warning auth__alert" role="status">
        <?= icon('info') ?>
        <p><?= e($aviso) ?></p>
    </div>
<?php endif; ?>

<?php if ($error !== null) : ?>
    <div class="alert alert--danger auth__alert" role="alert">
        <?= icon('alert') ?>
        <p><?= e($error) ?></p>
    </div>
<?php endif; ?>

<form class="form auth__form" method="post" action="<?= e(url('login')) ?>">
    <?= csrf_field() ?>

    <div class="field">
        <label class="field__label" for="email">Correo electrónico</label>
        <input class="input" id="email" name="email" type="email" inputmode="email"
            autocomplete="username" placeholder="nombre@udec.cl" value="<?= e(old('email')) ?>" required autofocus
            <?php if ($errorEmail !== null) : ?>aria-invalid="true" aria-describedby="email-error"<?php endif; ?>>
        <?php if ($errorEmail !== null) : ?>
            <p class="field__error" id="email-error"><?= e($errorEmail) ?></p>
        <?php endif; ?>
    </div>

    <div class="field">
        <label class="field__label" for="password">Contraseña</label>
        <div class="field__control field__control--with-action">
            <input class="input" id="password" name="password" type="password"
                autocomplete="current-password" placeholder="Ingresa tu contraseña" required
                <?php if ($errorPassword !== null) : ?>aria-invalid="true" aria-describedby="password-error"<?php endif; ?>>
            <button class="field__action" type="button" data-toggle-password="password"
                aria-label="Mostrar contraseña" aria-pressed="false">
                <?= icon('eye', 'icon icon--show') ?>
                <?= icon('eye-off', 'icon icon--hide') ?>
            </button>
        </div>
        <?php if ($errorPassword !== null) : ?>
            <p class="field__error" id="password-error"><?= e($errorPassword) ?></p>
        <?php endif; ?>
    </div>

    <details class="auth__forgot">
        <summary>¿Olvidaste tu contraseña?</summary>
        <p>Pide al encargado de bodega que la restablezca.</p>
    </details>

    <button class="btn btn--primary btn--block" type="submit">Ingresar</button>
</form>

<p class="auth__divider">o</p>

<aside class="auth__station">
    <span class="auth__station-icon"><?= icon('scan') ?></span>
    <div>
        <p class="auth__station-title">¿Retiras sin cuenta?</p>
        <p class="auth__station-text">
            Auxiliares y personas autorizadas se identifican con su credencial en la estación de retiro.
        </p>
    </div>
</aside>
