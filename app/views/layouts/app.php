<?php

defined('APP_ROOT') || exit;

// Hojas de los componentes de las páginas internas, más las que pida cada página ($estilos). basico.css va después de
// simple.css: sus componentes (al estilo de Bootstrap) son los de todo el sistema y ajustan algunas piezas de simple.css.
$hojas = [
    'css/layout/app.css',
    'css/components/simple.css',
    'css/components/basico.css',
    ...($estilos ?? []),
    'css/components/busqueda.css',
    // Los ajustes del modelo nuevo (azul marino): actúan solo donde <html> lleva «tema-azul».
    'css/components/tema-azul-ajustes.css',
];

// $ventana (opcional): HTML de una ventana (parcial «ventana») que se abre sobre la página. La página queda de fondo,
// difuminada e inactiva (inert): no se puede tocar ni recorrer con el teclado mientras la ventana está abierta.
$conVentana = isset($ventana);
// $amplia (opcional): la página usa más ancho (el inicio, por su lista de inventario).
// Al final va la ventana «¿Sigues ahí?» (parcial «sesion»), que aparece tras un rato sin uso.
$clasePagina = 'pagina' . (!empty($amplia) ? ' pagina--amplia' : '');
// El modelo nuevo de la interfaz (azul marino, base/tema-azul.css): en todos los perfiles.
$temaAzul = true;
?>
<!DOCTYPE html>
<html lang="es"<?= $temaAzul ? ' class="tema-azul"' : '' ?>>
<head>
    <?= partial('head', ['titulo' => $titulo ?? null, 'estilos' => $hojas] + ($temaAzul ? ['temaColor' => '#223c6a'] : [])) ?>
    <?php // Módulos ES: se ejecutan en orden, después de leer la página (como defer), sin variables globales. ?>
    <?= script_modulo('js/core/ui.js') ?>
    <?= script_modulo('js/core/sesion.js') ?>
    <?= script_modulo('js/core/ventana.js') ?>
    <?= script_modulo('js/core/posicion.js') ?>
    <?php foreach ($scripts ?? [] as $script) : ?>
    <?= script_modulo($script) ?>
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
