<?php

defined('APP_ROOT') || exit;

// Hojas de los componentes de las páginas internas, más las que pida cada página ($estilos). basico.css va después de
// simple.css: sus componentes (al estilo de Bootstrap) son los de todo el sistema y ajustan algunas piezas de simple.css.
$hojas = [
    'css/layout/app.css',
    'css/components/simple.css',
    'css/components/basico.css',
    ...($estilos ?? []),
];

// $ventana (opcional): HTML de una ventana (parcial «ventana») que se abre sobre la página. La página queda de fondo,
// difuminada e inactiva (inert): no se puede tocar ni recorrer con el teclado mientras la ventana está abierta.
$conVentana = isset($ventana);
// $amplia (opcional): la página usa más ancho (el inicio, por su lista de inventario).
// Al final va la ventana «¿Sigues ahí?» (parcial «sesion»), que aparece tras un rato sin uso.
$clasePagina = 'pagina' . (!empty($amplia) ? ' pagina--amplia' : '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= partial('head', ['titulo' => $titulo ?? null, 'estilos' => $hojas]) ?>
    <script src="<?= e(asset('js/core/ui.js')) ?>" defer></script>
    <script src="<?= e(asset('js/core/sesion.js')) ?>" defer></script>
    <?php foreach ($scripts ?? [] as $script) : ?>
    <script src="<?= e(asset($script)) ?>" defer></script>
    <?php endforeach; ?>
</head>
<body<?= $conVentana ? ' class="con-ventana"' : '' ?>>
    <?php if (!$conVentana) : ?>
        <a class="skip-link" href="#contenido">Saltar al contenido</a>
    <?php endif; ?>

    <div class="<?= $clasePagina ?>"<?= $conVentana ? ' inert' : '' ?>>
        <?= partial('topbar', ['deFondo' => $conVentana]) ?>

        <main class="app" id="contenido" tabindex="-1">
            <?php if (!$conVentana) : ?>
                <?= partial('flash') ?>
            <?php endif; ?>
            <?= $contenido ?>
        </main>
    </div>

    <?php if ($conVentana) : ?>
        <?= $ventana ?>
    <?php endif; ?>

    <?= partial('sesion') ?>
</body>
</html>
