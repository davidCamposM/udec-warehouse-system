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

<?php // Resumen de validación: los mensajes concretos permanecen junto a cada campo. ?>
<?php if (errores() !== []) : ?>
    <div class="alert alert--danger flash flash--errors" role="alert">
        <?= icon('alert') ?>
        <p><strong>Revisa los campos marcados.</strong> <?= count(errores()) === 1
            ? 'Hay un dato por corregir antes de continuar.'
            : 'Hay ' . e(count(errores())) . ' datos por corregir antes de continuar.' ?></p>
    </div>
<?php endif; ?>
