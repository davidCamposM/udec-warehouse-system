<?php defined('APP_ROOT') || exit; ?>
<!DOCTYPE html>
<html lang="es" class="tema-azul">
<head>
    <?= partial('head', ['titulo' => $titulo ?? null, 'estilos' => ['css/layout/guest.css'], 'temaColor' => '#223c6a']) ?>
</head>
<body class="guest">
    <?= $contenido ?>
</body>
</html>
