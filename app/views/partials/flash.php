<?php

use App\Core\Session;

defined('APP_ROOT') || exit;

// Mensajes que deja la acción anterior (tras una redirección): clave => [variante, ícono, rol ARIA].
$tipos = [
    'exito' => ['success', 'check', 'status'],
    'aviso' => ['warning', 'info', 'status'],
    'error' => ['danger', 'alert', 'alert'],
];
?>
<?php foreach ($tipos as $clave => [$variante, $icono, $rol]) : ?>
    <?php $mensaje = Session::getFlash($clave); ?>
    <?php if (is_string($mensaje) && $mensaje !== '') : ?>
        <div class="alert alert--<?= $variante ?> flash" role="<?= $rol ?>">
            <?= icon($icono) ?>
            <p><?= e($mensaje) ?></p>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
