<?php

defined('APP_ROOT') || exit;

// Página sola, sin el menú, para imprimir: el nombre del producto y un código de barras (Code 128) con su código
// interno. etiqueta.js dibuja el código y abre la ventana de imprimir.
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Etiqueta · <?= e($insumo['nombre']) ?></title>
    <style>
        body { margin: 0; padding: 24px 16px; background: #fff; color: #1f2a1f; font-family: system-ui, sans-serif; }
        .etiqueta { width: 70mm; margin: 0 auto; padding: 4mm; border: 1px dashed #9aa79a; text-align: center; }
        .etiqueta__nombre { margin: 0 0 2mm; font-size: 12pt; font-weight: 700; line-height: 1.2; }
        .etiqueta__codigo { display: block; width: 100%; height: auto; }
        .acciones { display: flex; gap: 12px; justify-content: center; margin-top: 24px; }
        .acciones a, .acciones button { padding: 12px 20px; border-radius: 10px; border: 0; font: inherit; font-weight: 600;
            text-decoration: none; cursor: pointer; background: #e6e8ee; color: #1f2330; }
        .acciones button { background: #223c6a; color: #fff; }
        @media print {
            body { padding: 0; }
            .etiqueta { border: 0; }
            .acciones { display: none; }
        }
    </style>
</head>
<body>
    <div class="etiqueta">
        <p class="etiqueta__nombre"><?= e($insumo['nombre']) ?></p>
        <svg class="etiqueta__codigo" data-codigo="<?= e($insumo['codigo']) ?>" role="img"
            aria-label="Código de barras <?= e($insumo['codigo']) ?>"></svg>
    </div>

    <div class="acciones">
        <a href="<?= e(url('insumos/' . $insumo['id'])) ?>">Volver</a>
        <button type="button" data-imprimir>Imprimir</button>
    </div>

    <script src="<?= e(asset('js/vendor/JsBarcode.code128.min.js')) ?>"></script>
    <?php // La librería del código de barras es un script clásico (define window.JsBarcode); el nuestro, un módulo. ?>
    <?= script_modulo('js/modules/etiqueta.js') ?>
</body>
</html>
