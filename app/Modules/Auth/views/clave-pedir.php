<?php

defined('APP_ROOT') || exit;

// Pedir el enlace para crear (la primera vez) o recuperar la contraseña. Después de enviarlo se muestra el aviso, sin
// decir si el correo tiene cuenta. Si el sistema no envía correos, se explica que el enlace lo da el encargado.
?>
<?php if (!$conCorreo) : ?>
    <div class="alert alert--info auth__alert" role="status">
        <?= icon('info') ?>
        <p>
            Para crear tu contraseña (o una nueva, si la olvidaste), pide al <strong>encargado de bodega</strong> tu
            enlace. Con él creas tu contraseña y entras con tu correo.
        </p>
    </div>
    <p class="auth__volver"><a href="<?= e(url('login')) ?>">Volver a ingresar</a></p>
<?php elseif ($enviado !== null) : ?>
    <div class="alert alert--success auth__alert" role="status">
        <?= icon('mail') ?>
        <p>
            Si <strong><?= e($enviado) ?></strong> tiene cuenta en el sistema, en unos minutos te llega un correo con un
            enlace para crear tu contraseña. Revisa también la carpeta de correo no deseado.
        </p>
    </div>
    <p class="auth__volver"><a href="<?= e(url('login')) ?>">Volver a ingresar</a></p>
<?php else : ?>
    <p class="auth__ayuda">Escribe tu correo y te enviaremos un enlace para crear tu contraseña.</p>

    <form class="form" method="post" action="<?= e(url('clave')) ?>" data-submit-once>
        <?= csrf_field() ?>
        <div class="field">
            <label class="field__label" for="email">Correo electrónico</label>
            <div class="field__control field__control--with-icon">
                <span class="field__icon"><?= icon('mail') ?></span>
                <input class="input" id="email" name="email" type="email" inputmode="email" autocomplete="username"
                    placeholder="nombre@udec.cl" value="<?= e(old('email')) ?>" required autofocus <?= aria_error('email') ?>>
            </div>
            <?= mensaje_error('email') ?>
        </div>
        <button class="btn btn--primary auth__submit" type="submit" data-loading-text="Enviando…">Enviarme el enlace</button>
    </form>

    <p class="auth__volver"><a href="<?= e(url('login')) ?>">Volver a ingresar</a></p>
<?php endif; ?>
