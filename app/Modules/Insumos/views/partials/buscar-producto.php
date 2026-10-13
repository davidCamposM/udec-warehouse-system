<?php

use App\Modules\Insumos\InsumoService;

defined('APP_ROOT') || exit;

/**
 * Etapa «el producto» de un formulario en ventana (Registrar producto, Actualizar cantidad): el campo para escanear
 * el código o escribir el nombre. Va dentro del formulario de la etapa, que envía lo escrito como «codigo».
 *
 * Mientras se escribe, bajo el campo aparecen los productos que coinciden (insumos.js) y tocar uno lo elige. Si se
 * envía un nombre que coincide con varios, la etapa vuelve con todos ellos a la vista (también sin JavaScript).
 * - $texto: lo enviado. $resultados: los productos que coinciden con $texto.
 * - $ruta: la de la tarea (guardar o ajustar); elegir un producto lleva a $ruta?producto=id.
 * - $ayuda: la línea bajo «Producto».
 * - $recientes y $tituloRecientes (opcionales): productos para elegir sin escribir nada.
 * - $agregar (opcional): URL de «Agregar nuevo producto», que se ofrece cuando no se encuentra (lleva lo escrito).
 */
$elegir = static fn (array $insumo): string => url($ruta, ['producto' => $insumo['id']]);
$recientes ??= [];
$cuantos = count($resultados);
?>
<div class="campo">
    <label class="campo__etiqueta" for="codigo">Producto<span class="campo__obligatorio" aria-hidden="true">*</span></label>
    <p class="campo__ayuda" id="codigo-ayuda"><?= e($ayuda) ?></p>
    <div class="field__control field__control--with-icon sugerencias">
        <span class="field__icon"><?= icon('search') ?></span>
        <input class="input input--relleno" id="codigo" name="codigo" type="search" placeholder="Nombre o código del producto"
            autocomplete="off" enterkeyhint="search" autofocus aria-required="true" aria-describedby="codigo-ayuda"
            data-sugerencias="<?= e(url('insumos/sugerencias', ['para' => $ruta])) ?>" value="<?= e($texto) ?>" <?= aria_error('codigo') ?>>
    </div>
    <?= mensaje_error('codigo') ?>

    <?php if ($texto === '' && $recientes !== []) : ?>
        <div class="opciones">
            <p class="opciones__titulo"><?= e($tituloRecientes) ?></p>
            <ul class="opciones__lista">
                <?php foreach ($recientes as $insumo) : ?>
                    <li><?= partial('Insumos::partials/opcion', ['insumo' => $insumo, 'url' => $elegir($insumo)]) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif ($cuantos > 0) : ?>
        <div class="opciones">
            <p class="opciones__titulo">
                <?= $cuantos === 1 ? 'Coincide 1 producto' : "Coinciden {$cuantos} productos" ?> con «<?= e($texto) ?>». Elige uno:
            </p>
            <ul class="opciones__lista">
                <?php foreach ($resultados as $insumo) : ?>
                    <li><?= partial('Insumos::partials/opcion', ['insumo' => $insumo, 'url' => $elegir($insumo)]) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif ($texto !== '') : ?>
        <div class="vacio vacio--compacto">
            <p class="vacio__titulo">No encontramos «<?= e($texto) ?>»</p>
            <p>Revisa lo escrito o escanea el código del producto.</p>
            <?php if (isset($agregar)) : ?>
                <a class="btn btn--tarea tono-turquesa"
                    href="<?= e($agregar . (str_contains($agregar, '?') ? '&' : '?') . http_build_query(InsumoService::paraAgregar($texto))) ?>">
                    <?= icon('plus') ?>Agregar nuevo producto
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
