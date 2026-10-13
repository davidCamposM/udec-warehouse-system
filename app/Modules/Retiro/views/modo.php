<?php defined('APP_ROOT') || exit; ?>
<?php // Un auxiliar, al identificarse, elige qué va a hacer: retirar o registrar lo que llegó (RF 15). ?>
<div class="retiro-modo">
    <h1 class="retiro__saludo">Hola, <?= e(explode(' ', $persona['nombre'])[0]) ?>. ¿Qué vas a hacer?</h1>

    <div class="retiro-modo__opciones">
        <form method="post" action="<?= e(url('retiro/modo')) ?>" data-submit-once>
            <?= csrf_field() ?>
            <input type="hidden" name="modo" value="retiro">
            <button class="tarea tono-grafito retiro-modo__boton" type="submit" autofocus>
                <span class="tarea__icono"><?= icon('arrow-out') ?></span>
                <span class="tarea__texto">Retirar</span>
                <span class="tarea__descripcion">Lo que te llevas de la bodega</span>
            </button>
        </form>
        <form method="post" action="<?= e(url('retiro/modo')) ?>" data-submit-once>
            <?= csrf_field() ?>
            <input type="hidden" name="modo" value="entrada">
            <button class="tarea tono-verde retiro-modo__boton" type="submit">
                <span class="tarea__icono"><?= icon('arrow-in') ?></span>
                <span class="tarea__texto">Registrar entrada</span>
                <span class="tarea__descripcion">Lo que llegó a la bodega</span>
            </button>
        </form>
    </div>

    <form method="post" action="<?= e(url('retiro/terminar')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn--lg btn--no" type="submit"><?= icon('close') ?>Cancelar</button>
    </form>
</div>
