<?php

defined('APP_ROOT') || exit;

// Etapa 3, su propia ventana («¿Dónde quedó?»). Lo principal es escanear la etiqueta de la repisa (al leerla, se
// registra). Si el producto ya tenía un lugar, Enter sin escanear lo deja ahí mismo. Sin etiqueta, la lista de sectores
// va plegada debajo: se toca uno y se confirma. Arriba, lo elegido antes, con «Cambiar».
$marcado = old('sector_id');
$anterior = $insumo['sector_id'] !== null ? $insumo['sector'] : null;
?>

<form class="formulario" method="post" action="<?= e(url('guardar/sector')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <div class="resumen-fijo">
        <?= partial('campo-fijo', [
            'etiqueta' => 'Producto',
            'icono' => 'box',
            'valor' => $insumo['nombre'],
            'detalle' => codigo_producto($insumo),
            'cambiar' => url('guardar'),
            'compacto' => true,
        ]) ?>
        <?= partial('campo-fijo', [
            'etiqueta' => 'Cantidad',
            'icono' => 'layers',
            'valor' => unidades($cantidad),
            'cambiar' => url('guardar/cantidad'),
            'compacto' => true,
        ]) ?>
    </div>

    <?php if ($sectores === []) : ?>
        <div class="campo">
            <p class="campo__etiqueta">¿Dónde quedó?</p>
            <div class="vacio vacio--compacto">
                <p class="vacio__titulo">Todavía no hay sectores</p>
                <?php if ($puedeCrearSectores) : ?>
                    <p>Agrega las partes de la bodega (Estante A, Repisa de líquidos…). Lo que llevas no se pierde.</p>
                    <a class="btn btn--tarea tono-turquesa" href="<?= e(url('sectores/nuevo', ['para' => 'guardar'])) ?>">
                        <?= icon('plus') ?>Agregar sector
                    </a>
                <?php else : ?>
                    <p>Pide al encargado de bodega que agregue los sectores.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php else : ?>
        <div class="escaneo-foco<?= error_de('codigo') !== null ? ' has-error' : '' ?>">
            <span class="escaneo-foco__icono" aria-hidden="true"><?= icon('scan') ?></span>
            <label class="escaneo-foco__pregunta" for="codigo">Escanea la etiqueta de la repisa</label>
            <input class="escaneo-foco__campo" id="codigo" name="codigo" type="text" autocomplete="off" autofocus
                placeholder="Ejemplo: A03" aria-describedby="codigo-ayuda" data-lectura <?= aria_error('codigo') ?>>
            <?= mensaje_error('codigo') ?>
            <p class="escaneo-foco__ayuda" id="codigo-ayuda">
                <?php if ($anterior !== null) : ?>
                    Antes estaba en <strong><?= e($anterior) ?></strong>: presiona <kbd>Enter</kbd> para dejarlo ahí mismo.
                <?php else : ?>
                    Al escanearla se registra solo.
                <?php endif; ?>
            </p>
        </div>

        <details class="lista-plegada"<?= error_de('sector_id') !== null || $marcado !== '' ? ' open' : '' ?>>
            <summary>No tiene etiqueta: elegir de la lista</summary>
            <fieldset class="campo<?= error_de('sector_id') !== null ? ' has-error' : '' ?>">
                <legend class="visually-hidden">Sectores</legend>
                <div class="options options--wide">
                    <?php foreach ($sectores as $sector) : ?>
                        <?php $estaba = (int) $sector['id'] === (int) $insumo['sector_id']; ?>
                        <label class="option">
                            <input class="option__input" type="radio" name="sector_id" value="<?= e($sector['id']) ?>"
                                <?= (string) $sector['id'] === $marcado ? 'checked' : '' ?>>
                            <span class="option__box option__box--detalle">
                                <?= icon('map-pin') ?>
                                <span>
                                    <?= e($sector['nombre']) ?>
                                    <small><?= e($sector['codigo']) ?><?= $estaba ? ' · Aquí estaba antes' : '' ?></small>
                                </span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?= mensaje_error('sector_id') ?>
            </fieldset>
        </details>
    <?php endif; ?>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url('guardar/cantidad')) ?>"><?= icon('arrow-left') ?>Atrás</a>
        <?php if ($sectores !== []) : ?>
            <button class="btn btn--si" type="submit" data-loading-text="Registrando…"><?= icon('ok') ?>Registrar</button>
        <?php endif; ?>
    </div>
</form>
