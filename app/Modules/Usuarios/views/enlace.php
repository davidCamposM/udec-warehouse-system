<?php

defined('APP_ROOT') || exit;

// El enlace para que la persona cree su contraseña, con «Copiar enlace» (usuarios.js) para enviárselo por WhatsApp,
// Teams o correo. $enlace: lo de CuentaService::enlaceParaCompartir() y 'agregada' (se acaba de agregar a la persona).
?>
<?= partial('encabezado', [
    'volver' => [url('usuarios'), 'Volver a Usuarios'],
    'tarea' => 'usuarios',
    'icono' => 'lock',
    'titulo' => $enlace['agregada'] ? "Listo: se agregó a {$enlace['nombre']}" : "Enlace para {$enlace['nombre']}",
    'texto' => 'Envíale este enlace para que ' . ($enlace['primera_vez'] ? 'cree su contraseña.' : 'cree una contraseña nueva.'),
]) ?>

<section class="card card-body enlace">
    <label class="enlace__etiqueta" for="enlace">Enlace para <?= e($enlace['email']) ?></label>
    <input class="input enlace__campo" id="enlace" type="text" readonly value="<?= e($enlace['enlace']) ?>">
    <button class="btn btn--lg btn--si enlace__copiar" type="button" data-copiar="enlace" data-copiado="¡Enlace copiado!">
        <?= icon('clipboard') ?>Copiar enlace
    </button>

    <p class="enlace__nota">
        <?= icon('clock') ?>Sirve una sola vez y vence el <?= e($enlace['vence']) ?>.
    </p>
    <?php if ($enlace['enviado'] === true) : ?>
        <p class="enlace__nota"><?= icon('mail') ?>También se le envió por correo.</p>
    <?php endif; ?>
</section>

<div class="acciones">
    <a class="btn btn--lg btn--secondary" href="<?= e(url('usuarios')) ?>"><?= icon('arrow-left') ?>Volver a Usuarios</a>
    <a class="btn btn--lg btn--si" href="<?= e(url('usuarios/agregar')) ?>"><?= icon('plus') ?>Agregar otra persona</a>
</div>
