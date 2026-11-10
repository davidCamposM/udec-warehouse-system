<?php

defined('APP_ROOT') || exit;

// «Ingresar abastecimiento», una sola ventana («¿Qué llegó?») en un solo formulario: si se acaba de ingresar, arriba va
// la boleta (cada producto con lo que llegó y su stock). La fila para agregar, rotulada («Producto»: código SAP, código
// de barras o nombre · «Cantidad» · «Agregar»; Enter o la pistola también agregan); debajo, la lista de lo que llegó
// (con el total a la derecha), cada uno con su cantidad (− / +, se puede escribir) y la ✕ para quitarlo.
// «Ingresar N productos a la bodega» suma todo de una vez.
$unidades = array_sum(array_column($lista, 'cantidad'));
$cuantos = count($lista);
$filas = [];
if ($ingresado !== null) {
    foreach ($ingresado as $producto) {
        $filas[] = ['plus', '<strong>' . e($producto['nombre']) . '</strong> · llegaron ' . e(cantidad($producto['cantidad']))
            . ' · stock: ' . e(cantidad($producto['stock']))];
    }
}
?>
<div class="abastecimiento">
    <?php if ($ingresado !== null) : ?>
        <?= partial('boleta', ['titulo' => '¡Listo! Quedó ingresado', 'filas' => $filas]) ?>
    <?php endif; ?>

    <form class="formulario" method="post" action="<?= e(url('abastecimiento')) ?>" data-submit-once>
        <?= csrf_field() ?>

        <?php
        // Agregar a la lista: el producto (código SAP, código de barras o nombre) y la cantidad, rotulados y alineados en una fila;
        // debajo, dónde está el código. Enter (o la pistola) usa «Agregar», el primer botón del formulario.
        ?>
        <div class="abastecimiento__agregar-a">
            <div class="abastecimiento__nueva">
                <?= partial('Insumos::partials/buscar-producto', [
                    'texto' => $texto,
                    'resultados' => $resultados,
                    'ruta' => 'abastecimiento',
                    'etiqueta' => 'Producto',
                    'ayuda' => '',
                ]) ?>
                <div class="campo abastecimiento__cuantas<?= error_de('cantidad') !== null ? ' has-error' : '' ?>">
                    <label class="campo__etiqueta" for="cantidad">Cantidad</label>
                    <input class="input input--relleno" id="cantidad" name="cantidad" type="text" inputmode="numeric" maxlength="5"
                        autocomplete="off" placeholder="1" value="<?= e(old('cantidad')) ?>" <?= aria_error('cantidad') ?>>
                </div>
                <button class="btn btn--secondary abastecimiento__agregar" type="submit" name="accion" value="agregar" data-loading-text="Agregando…">
                    <?= icon('plus') ?>Agregar
                </button>
            </div>
            <?= mensaje_error('cantidad') ?>
            <p class="abastecimiento__ayuda"><?= icon('info') ?>Escanea el código de barras, o escribe el código SAP (ejemplo: 4105031) o el nombre.</p>
        </div>

        <?php if ($agregado !== null) : ?>
            <p class="cantidad-foco__sumada" role="status"><?= icon('check') ?>Se agregó «<?= e($agregado) ?>»</p>
        <?php endif; ?>

        <section class="abastecimiento__lista<?= error_de('lista') !== null ? ' has-error' : '' ?>" aria-labelledby="titulo-lista">
            <div class="abastecimiento__cabecera">
                <h2 class="abastecimiento__titulo" id="titulo-lista">Lo que llegó</h2>
                <?php if ($lista !== []) : ?>
                    <span class="abastecimiento__total"><?= e($cuantos === 1 ? '1 producto' : "{$cuantos} productos") ?> · <?= e(unidades($unidades)) ?></span>
                <?php endif; ?>
            </div>
            <?= mensaje_error('lista') ?>
            <?php if ($lista === []) : ?>
                <p class="abastecimiento__vacio"><?= icon('inbox') ?>La lista está vacía. Agrega el primer producto que llegó.</p>
            <?php else : ?>
                <ul class="abastecimiento__filas">
                    <?php foreach ($lista as ['insumo' => $insumo, 'cantidad' => $cantidad]) : ?>
                        <?php $campo = 'cantidad-' . $insumo['id']; ?>
                        <li class="abastecimiento__fila">
                            <span class="abastecimiento__producto">
                                <strong><?= e($insumo['nombre']) ?></strong>
                                <span><?= e(codigo_producto($insumo)) ?></span>
                            </span>
                            <span class="abastecimiento__stepper" data-stepper data-min="1" data-max="99999">
                                <button class="abastecimiento__paso" type="button" data-step="-1"><?= icon('minus') ?><span class="visually-hidden">Restar uno</span></button>
                                <label class="visually-hidden" for="<?= e($campo) ?>">Cantidad de <?= e($insumo['nombre']) ?></label>
                                <input class="abastecimiento__numero" id="<?= e($campo) ?>" name="cantidades[<?= e($insumo['id']) ?>]" type="text"
                                    inputmode="numeric" maxlength="5" autocomplete="off" value="<?= e($cantidad) ?>">
                                <button class="abastecimiento__paso" type="button" data-step="1"><?= icon('plus') ?><span class="visually-hidden">Sumar uno</span></button>
                            </span>
                            <button class="boton-fila abastecimiento__quitar" type="submit" name="quitar" value="<?= e($insumo['id']) ?>"
                                title="Quitar de la lista"><?= icon('close') ?><span class="visually-hidden">Quitar <?= e($insumo['nombre']) ?></span></button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <div class="formulario__acciones">
            <a class="btn btn--secondary" href="<?= e(url()) ?>"><?= icon($ingresado !== null ? 'home' : 'close') ?><?= $ingresado !== null ? 'Finalizar' : 'Cancelar' ?></a>
            <button class="btn btn--si" type="submit" name="accion" value="ingresar" data-loading-text="Ingresando…" <?= $lista === [] ? 'disabled' : '' ?>>
                <?= icon('ok') ?><?= $lista === [] ? 'Ingresar a la bodega' : 'Ingresar ' . e($cuantos === 1 ? '1 producto' : "{$cuantos} productos") . ' a la bodega' ?>
            </button>
        </div>
    </form>
</div>
