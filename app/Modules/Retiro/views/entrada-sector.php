<?php

defined('APP_ROOT') || exit;

// Entrada de un auxiliar, último paso: lo que se registrará y dónde quedó todo (un solo sector, elegido o escaneado).
// «Confirmar entrada» lo registra.
$unidadesTexto = static fn (int $n): string => $n === 1 ? '1 unidad' : "{$n} unidades";
$marcado = old('sector_id', count($sectores) === 1 ? (string) $sectores[0]['id'] : '');
?>
<div class="retiro tono-verde">
    <h1 class="retiro__saludo">¿Dónde quedó lo que llegó?</h1>

    <h2 class="llevas__titulo">Lo que llegó <span class="llevas__total"><?= e($unidadesTexto($total)) ?></span></h2>
    <ul class="entrada-resumen">
        <?php foreach ($lineas as ['insumo' => $insumo, 'cantidad' => $cantidad]) : ?>
            <li><strong><?= e(unidades($cantidad)) ?></strong> · <?= e($insumo['nombre']) ?></li>
        <?php endforeach; ?>
    </ul>

    <form class="formulario" method="post" action="<?= e(url('retiro/entrada')) ?>" data-submit-once>
        <?= csrf_field() ?>

        <?php if (error_de('carrito') !== null) : ?>
            <div class="alert alert--danger" role="alert"><?= icon('alert') ?><p><?= e(error_de('carrito')) ?></p></div>
        <?php endif; ?>

        <?php if ($sectores === []) : ?>
            <div class="vacio vacio--compacto">
                <p class="vacio__titulo">Todavía no hay sectores</p>
                <p>Pide al encargado de bodega que agregue los sectores. Lo escaneado no se pierde mientras sigas aquí.</p>
            </div>
        <?php else : ?>
            <fieldset class="campo<?= error_de('sector_id') !== null ? ' has-error' : '' ?>">
                <legend class="campo__etiqueta">Elige el sector<span class="campo__obligatorio" aria-hidden="true">*</span></legend>
                <div class="options options--wide">
                    <?php foreach ($sectores as $sector) : ?>
                        <label class="option">
                            <input class="option__input" type="radio" name="sector_id" value="<?= e($sector['id']) ?>"
                                <?= (string) $sector['id'] === $marcado ? 'checked' : '' ?>>
                            <span class="option__box option__box--detalle">
                                <?= icon('map-pin') ?>
                                <span><?= e($sector['nombre']) ?><small><?= e($sector['codigo']) ?></small></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?= mensaje_error('sector_id') ?>
            </fieldset>

            <div class="campo">
                <label class="campo__etiqueta campo__etiqueta--suave" for="codigo"><?= icon('scan') ?>¿Tiene etiqueta? También puedes escanearla</label>
                <input class="input input--relleno input--code" id="codigo" name="codigo" type="text" autocomplete="off"
                    placeholder="Código del sector" data-foco data-lectura <?= aria_error('codigo') ?>>
                <?= mensaje_error('codigo') ?>
            </div>
        <?php endif; ?>

        <div class="formulario__acciones">
            <a class="btn btn--lg btn--secondary" href="<?= e(url('retiro')) ?>"><?= icon('arrow-left') ?>Atrás</a>
            <?php if ($sectores !== []) : ?>
                <button class="btn btn--lg btn--si" type="submit" data-loading-text="Registrando…"><?= icon('ok') ?>Confirmar entrada</button>
            <?php endif; ?>
        </div>
    </form>
</div>
