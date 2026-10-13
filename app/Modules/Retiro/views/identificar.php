<?php defined('APP_ROOT') || exit; ?>
<section class="kiosco-tarjeta">
    <?php if ($vencido) : ?>
        <div class="alert alert--info flash" role="status">
            <?= icon('clock') ?><p>Se cerró por falta de actividad. Para retirar, vuelve a identificarte.</p>
        </div>
    <?php endif; ?>
    <?= partial('flash') ?>
    <span class="kiosco-tarjeta__icono"><?= icon('id-card') ?></span>
    <h1 class="kiosco-tarjeta__titulo">Hola. Para retirar, identifícate</h1>
    <p class="kiosco-tarjeta__texto">Escanea tu credencial con la pistola o escribe tu RUT.</p>

    <form class="scan-box tono-grafito kiosco-tarjeta__lectura" method="post" action="<?= e(url('retiro/identificar')) ?>" data-submit-once>
        <?= csrf_field() ?>
        <label class="scan-box__label" for="credencial">
            <span class="scan-box__icono"><?= icon('scan') ?></span>
            <span>Tu credencial <span class="scan-box__o">o tu RUT</span></span>
        </label>
        <div class="scan-box__fila">
            <input class="scan-box__input" data-lectura id="credencial" name="credencial" type="text" autocomplete="off" autofocus
                <?= aria_error('credencial') ?>>
            <button class="btn btn--lg scan-box__boton" type="submit" data-loading-text="Un momento…"><?= icon('ok') ?>Entrar</button>
        </div>
        <?= mensaje_error('credencial') ?>
    </form>
</section>
