<?php

defined('APP_ROOT') || exit;

// Etapa 1, en la ventana («Este código no está en el sistema»): se escaneó un código que no tiene ningún producto. Lo
// principal es agregarlo como producto nuevo (con el código ya puesto); más discreto, si es otro envase de uno que ya
// existe (solo el encargado). Quien no puede crear productos ve que debe avisar al encargado.
?>

<div class="formulario">
    <p class="desconocido__codigo"><?= icon('barcode') ?><span><?= e($codigo) ?></span></p>

    <?php if ($puedeCrear) : ?>
        <div class="desconocido__opciones">
            <a class="btn btn--si" href="<?= e(url('insumos/nuevo', ['para' => 'guardar', 'codigo' => $codigo])) ?>">
                <?= icon('plus') ?>Agregarlo como producto nuevo
            </a>
            <?php // Sumar el código a un producto que ya existe es gestionar sus códigos: solo el encargado. ?>
            <?php if ($puedeAdministrar) : ?>
                <a class="boton-fila" href="<?= e(url('guardar/envase', ['codigo' => $codigo])) ?>">
                    <?= icon('barcode') ?>Es otro envase de un producto que ya existe
                </a>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <p class="desconocido__aviso"><?= icon('info') ?>Avisa al encargado para que lo agregue.</p>
    <?php endif; ?>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url('guardar')) ?>"><?= icon('arrow-left') ?>Atrás</a>
    </div>
</div>
