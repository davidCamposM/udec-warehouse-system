<?php

defined('APP_ROOT') || exit;

// La parte que cambia con cada lectura: los productos que coinciden con lo escrito y lo que se lleva (o, al registrar
// una entrada, lo que llegó: al final se sigue a «¿dónde quedó?» en vez de confirmar).
$unidadesTexto = static fn (int $n): string => $n === 1 ? '1 unidad' : "{$n} unidades";
$entrada = ($modo ?? 'retiro') === 'entrada';
?>
<?php if ($texto !== '') : ?>
    <section class="retiro__resultados" aria-labelledby="titulo-resultados">
        <h2 class="block__title" id="titulo-resultados">Productos con «<?= e($texto) ?>»</h2>
        <?php if ($resultados === []) : ?>
            <p class="caja caja--nota"><?= $entrada
                ? 'No encontramos productos con ese nombre. Si es nuevo, pide al encargado que lo agregue.'
                : 'No encontramos productos con ese nombre que tengan unidades en bodega.' ?></p>
        <?php else : ?>
            <ul class="item-list">
                <?php foreach ($resultados as $insumo) : ?>
                    <li>
                        <form method="post" action="<?= e(url('retiro/leer')) ?>" data-retiro-form>
                            <?= csrf_field() ?>
                            <input type="hidden" name="insumo_id" value="<?= e($insumo['id']) ?>">
                            <button class="item-row item-row--elegir" type="submit">
                                <span class="item-row__name"><?= e($insumo['nombre']) ?></span>
                                <span class="item-row__meta">Hay <?= e(unidades($insumo['stock_actual'])) ?></span>
                                <span class="item-row__elegir"><?= icon('plus') ?>Agregar</span>
                            </button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="llevas" aria-labelledby="titulo-llevas">
    <h2 class="llevas__titulo" id="titulo-llevas">
        <?= $entrada ? 'Lo que llegó' : 'Lo que llevas' ?>
        <span class="llevas__total"><?= e($unidadesTexto($total)) ?></span>
    </h2>

    <?php if ($lineas === []) : ?>
        <p class="llevas__vacio"><?= icon('barcode') ?>Todavía nada. Escanea el primer producto.</p>
    <?php else : ?>
        <ul class="llevas__lista">
            <?php foreach ($lineas as ['insumo' => $insumo, 'cantidad' => $cantidad]) : ?>
                <li class="linea">
                    <div>
                        <p class="linea__nombre"><?= e($insumo['nombre']) ?></p>
                        <p class="linea__meta"><?= $entrada ? 'Hay ' : 'Quedan ' ?><?= e(unidades($insumo['stock_actual'])) ?> en bodega</p>
                    </div>
                    <form class="linea__cantidad" method="post" action="<?= e(url('retiro/linea')) ?>" data-retiro-form>
                        <?= csrf_field() ?>
                        <input type="hidden" name="insumo_id" value="<?= e($insumo['id']) ?>">
                        <?php /* Primer botón del formulario: el Enter en la cantidad la cambia a lo escrito. */ ?>
                        <button class="visually-hidden" type="submit" name="paso" value="0" tabindex="-1">Cambiar cantidad</button>
                        <div class="stepper">
                            <button class="stepper__button" type="submit" name="paso" value="-1"><?= icon('minus') ?><span class="visually-hidden">Uno menos</span></button>
                            <input class="stepper__input" name="cantidad" type="text" inputmode="numeric" value="<?= e($cantidad) ?>"
                                aria-label="Cantidad de <?= e($insumo['nombre']) ?>">
                            <button class="stepper__button" type="submit" name="paso" value="1"><?= icon('plus') ?><span class="visually-hidden">Uno más</span></button>
                        </div>
                        <button class="btn btn--secondary linea__quitar" type="submit" name="quitar" value="1"><?= icon('close') ?>Quitar</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if (error_de('carrito') !== null) : ?>
        <div class="alert alert--danger flash" role="alert"><?= icon('alert') ?><p><?= e(error_de('carrito')) ?></p></div>
    <?php endif; ?>

    <div class="acciones acciones--retiro">
        <form method="post" action="<?= e(url('retiro/terminar')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn--lg btn--no" type="submit"><?= icon('close') ?>Cancelar</button>
        </form>
        <?php if ($entrada) : ?>
            <?php if ($lineas === []) : ?>
                <span class="btn btn--lg btn--si is-disabled" aria-disabled="true">Siguiente: ¿dónde quedó?<?= icon('arrow-right') ?></span>
            <?php else : ?>
                <a class="btn btn--lg btn--si" href="<?= e(url('retiro/sector')) ?>">Siguiente: ¿dónde quedó?<?= icon('arrow-right') ?></a>
            <?php endif; ?>
        <?php else : ?>
            <form method="post" action="<?= e(url('retiro/confirmar')) ?>" data-submit-once data-retiro-confirmar>
                <?= csrf_field() ?>
                <button class="btn btn--lg btn--si" type="submit" data-loading-text="Guardando…" <?= $lineas === [] ? 'disabled' : '' ?>>
                    <?= icon('ok') ?>Confirmar retiro
                </button>
            </form>
        <?php endif; ?>
    </div>
</section>
