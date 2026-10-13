<?php

use App\Modules\Insumos\GuardarController;

defined('APP_ROOT') || exit;

// Etapa 1, en la ventana: el código leído es de otro envase. Se busca el producto (mientras se escribe, debajo
// aparecen los que coinciden) y, al elegirlo, el código queda como uno de sus códigos y se sigue con la etapa 2.
// Los productos de esa lista son botones del formulario #envase-elegir.
?>
<?= partial('Insumos::partials/pasos', ['paso' => 1, 'pasos' => GuardarController::PASOS, 'color' => 'verde']) ?>

<div class="formulario">
    <?php if (error_de('codigo_barras') !== null) : ?>
        <div class="alert alert--danger" role="alert">
            <?= icon('alert') ?>
            <p><?= e(error_de('codigo_barras')) ?></p>
        </div>
    <?php endif; ?>

    <div class="campo">
        <label class="campo__etiqueta" for="q">¿De qué producto es este envase?<span class="campo__obligatorio" aria-hidden="true">*</span></label>
        <p class="campo__ayuda" id="q-ayuda">Busca el producto y elígelo. El código <?= e($codigo) ?> quedará como uno de sus códigos.</p>
        <form class="campo__busqueda" method="get" action="<?= e(url('guardar/envase')) ?>" role="search">
            <?= route_field('guardar/envase') ?>
            <input type="hidden" name="codigo" value="<?= e($codigo) ?>">
            <div class="field__control field__control--with-icon sugerencias">
                <span class="field__icon"><?= icon('search') ?></span>
                <input class="input input--relleno" id="q" name="q" type="search" autocomplete="off" autofocus
                    placeholder="Nombre del producto" aria-describedby="q-ayuda" value="<?= e($texto) ?>"
                    data-sugerencias="<?= e(url('insumos/sugerencias', ['para' => 'envase'])) ?>">
            </div>
            <button class="btn btn--secondary" type="submit"><?= icon('search') ?>Buscar</button>
        </form>
        <form id="envase-elegir" method="post" action="<?= e(url('guardar/envase')) ?>" data-submit-once hidden>
            <?= csrf_field() ?>
            <input type="hidden" name="codigo" value="<?= e($codigo) ?>">
        </form>

        <?php if ($texto !== '') : ?>
            <?php if ($resultados !== []) : ?>
                <ul class="opciones__lista">
                    <?php foreach ($resultados as $insumo) : ?>
                        <li><?= partial('Insumos::partials/elegible', [
                            'insumo' => $insumo,
                            'accion' => url('guardar/envase'),
                            'ocultos' => ['codigo' => $codigo],
                        ]) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <div class="vacio vacio--compacto">
                    <p class="vacio__titulo">No encontramos «<?= e($texto) ?>»</p>
                    <p>Prueba con otra palabra. Si no está, vuelve atrás y elige «Es un producto nuevo».</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url('guardar/desconocido', ['codigo' => $codigo])) ?>"><?= icon('arrow-left') ?>Atrás</a>
    </div>
</div>
