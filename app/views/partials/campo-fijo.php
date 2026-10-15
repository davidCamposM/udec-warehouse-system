<?php

defined('APP_ROOT') || exit;

/**
 * Lo ya elegido en una etapa anterior de un formulario: se ve como un campo lleno, con «Cambiar» para volver a esa
 * etapa. Recibe $etiqueta, $valor, $icono y, opcionales, $detalle, $cambiar (URL) y $compacto (en el resumen de
 * arriba, .resumen-fijo: la etiqueta va chica, dentro de la tarjeta, para ocupar menos alto).
 */
$compacto ??= false;
?>
<div class="campo<?= $compacto ? ' campo--compacto' : '' ?>">
    <?php if (!$compacto) : ?>
        <p class="campo__etiqueta"><?= e($etiqueta) ?></p>
    <?php endif; ?>
    <div class="campo__fijo">
        <span class="campo__fijo-icono"><?= icon($icono) ?></span>
        <div class="campo__fijo-datos">
            <?php if ($compacto) : ?>
                <p class="campo__mini"><?= e($etiqueta) ?></p>
            <?php endif; ?>
            <p class="campo__valor"><?= e($valor) ?></p>
            <?php if (($detalle ?? '') !== '') : ?>
                <p class="campo__detalle"><?= e($detalle) ?></p>
            <?php endif; ?>
        </div>
        <?php if (isset($cambiar)) : ?>
            <a class="campo__cambiar" href="<?= e($cambiar) ?>"><?= icon('edit') ?>Cambiar</a>
        <?php endif; ?>
    </div>
</div>
