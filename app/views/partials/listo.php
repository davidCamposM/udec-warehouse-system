<?php

defined('APP_ROOT') || exit;

/**
 * «Listo»: lo que se acaba de hacer en un formulario en ventana. Va arriba de la primera etapa, que queda lista para
 * seguir con otro, o sola con sus botones (como al registrar un producto). Recibe $titulo, $detalle (HTML ya escapado) y, opcional, $enlaces: lista de [texto, URL] (como
 * «Ver el producto»).
 */
?>
<div class="listo" role="status">
    <span class="listo__icono"><?= icon('ok') ?></span>
    <div class="listo__datos">
        <p class="listo__titulo"><?= e($titulo) ?></p>
        <p><?= $detalle ?></p>
        <?php if (($enlaces ?? []) !== []) : ?>
            <p class="listo__enlaces">
                <?php foreach ($enlaces as [$texto, $url]) : ?>
                    <a href="<?= e($url) ?>"><?= e($texto) ?></a>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>
    </div>
</div>
