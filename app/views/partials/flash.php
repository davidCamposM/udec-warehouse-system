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

<?php
// Resumen de validación, solo cuando hay varios datos por corregir: el mensaje concreto va junto a cada campo. Con un
// solo error, el resumen repetiría lo que ya dice el campo y empujaría el formulario hacia abajo en el celular.
?>
<?php if (count(errores()) > 1) : ?>
    <div class="alert alert--danger flash flash--errors" role="alert">
        <?= icon('alert') ?>
        <p><strong>Revisa los campos marcados.</strong> Hay <?= e(count(errores())) ?> datos por corregir antes de continuar.</p>
    </div>
<?php endif; ?>
