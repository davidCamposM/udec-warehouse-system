<?php

defined('APP_ROOT') || exit;

// Anchos de barras y espacios, alternados. Es solo una ilustración, no un código real.
$barras = [3, 1, 1, 2, 1, 1, 2, 3, 1, 2, 1, 1, 3, 1, 2, 2, 1, 1, 1, 3, 2, 1, 1, 2, 3, 1, 1, 1, 2, 2,
    1, 3, 1, 1, 2, 1, 3, 2, 1, 1, 2, 1, 1, 3, 2, 1, 1, 2, 1, 1, 3];
$x = 0;
?>
<aside class="showcase">
    <p class="showcase__eyebrow"><?= icon('tree') ?> Facultad de Ciencias Forestales · UdeC</p>

    <div class="showcase__visual" aria-hidden="true">
        <div class="showcase-card">
            <p class="showcase-card__title">Retira en segundos</p>
            <p class="showcase-card__text">Escanea el código, indica la cantidad y confirma.</p>
            <p class="showcase-card__steps">Escanear <?= icon('arrow-right') ?> Confirmar</p>

            <div class="showcase-label">
                <p class="showcase-label__head"><span>Bodega FCF</span><span>Insumo</span></p>
                <div class="showcase-label__bars">
                    <svg class="showcase-label__barcode" viewBox="0 0 <?= array_sum($barras) ?> 40" preserveAspectRatio="none">
                        <?php foreach ($barras as $i => $ancho) : ?>
                            <?php if ($i % 2 === 0) : ?>
                                <rect x="<?= $x ?>" width="<?= $ancho ?>" height="40"/>
                            <?php endif; ?>
                            <?php $x += $ancho; ?>
                        <?php endforeach; ?>
                    </svg>
                    <span class="showcase-label__scan"></span>
                </div>
                <p class="showcase-label__code">INS00042</p>
            </div>
        </div>

        <div class="showcase-chip">
            <span class="showcase-chip__icon"><?= icon('check') ?></span>
            <span class="showcase-chip__text">
                <span class="showcase-chip__label">Al confirmar</span>
                <span class="showcase-chip__value">Stock al día</span>
            </span>
        </div>
    </div>

    <div class="showcase__copy">
        <h2 class="showcase__title">Bodega FCF</h2>
        <p class="showcase__text">
            Insumos, herramientas y préstamos de la facultad, siempre al día y en un solo lugar.
        </p>
    </div>
</aside>
