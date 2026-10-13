<?php

use App\Modules\Insumos\GuardarController;

defined('APP_ROOT') || exit;

// Etapa 1, en la ventana: se escaneó un código que no tiene ningún producto. ¿Es nuevo, u otro envase de uno que ya
// existe?
?>
<?= partial('Insumos::partials/pasos', ['paso' => 1, 'pasos' => GuardarController::PASOS, 'color' => 'verde']) ?>

<div class="formulario">
    <div class="alert alert--warning" role="status">
        <?= icon('alert') ?>
        <p>El código <strong><?= e($codigo) ?></strong> no está registrado en ningún producto.</p>
    </div>

    <?php if ($puedeAdministrar) : ?>
        <div class="campo">
            <p class="campo__etiqueta">¿Qué es este producto?</p>
            <ul class="lista-acciones lista-acciones--una-columna">
                <li>
                    <a class="accion accion--turquesa" href="<?= e(url('insumos/nuevo', ['para' => 'guardar', 'codigo' => $codigo])) ?>">
                        <span class="accion__icono"><?= icon('plus') ?></span>
                        <span class="accion__texto">
                            <strong class="accion__titulo">Es un producto nuevo</strong>
                            <span class="accion__detalle">Todavía no está en el sistema: lo agregamos con este código</span>
                        </span>
                        <span class="accion__flecha" aria-hidden="true"><?= icon('arrow-right') ?></span>
                    </a>
                </li>
                <li>
                    <a class="accion accion--azul" href="<?= e(url('guardar/envase', ['codigo' => $codigo])) ?>">
                        <span class="accion__icono"><?= icon('barcode') ?></span>
                        <span class="accion__texto">
                            <strong class="accion__titulo">Es otro envase de uno que ya existe</strong>
                            <span class="accion__detalle">Por ejemplo, otra marca o tamaño del mismo producto</span>
                        </span>
                        <span class="accion__flecha" aria-hidden="true"><?= icon('arrow-right') ?></span>
                    </a>
                </li>
            </ul>
        </div>
    <?php else : ?>
        <p class="caja caja--nota">Pide al encargado de bodega que lo agregue con «Agregar nuevo producto». Después podrás registrarlo.</p>
    <?php endif; ?>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url('guardar')) ?>"><?= icon('arrow-left') ?>Atrás</a>
    </div>
</div>
