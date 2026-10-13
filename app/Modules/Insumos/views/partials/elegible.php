<?php

defined('APP_ROOT') || exit;

/**
 * Un producto que se puede elegir de una lista, con su propio formulario. Recibe $insumo, $accion (URL del POST) y
 * $ocultos (campos extra: nombre => valor).
 */
?>
<form method="post" action="<?= e($accion) ?>" data-submit-once>
    <?= csrf_field() ?>
    <?php foreach ($ocultos ?? [] as $nombre => $valor) : ?>
        <input type="hidden" name="<?= e($nombre) ?>" value="<?= e($valor) ?>">
    <?php endforeach; ?>
    <?= partial('Insumos::partials/opcion', ['insumo' => $insumo]) ?>
</form>
