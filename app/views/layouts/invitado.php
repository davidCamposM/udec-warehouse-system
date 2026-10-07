<?php defined('APP_ROOT') || exit; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= partial('head', ['titulo' => $titulo ?? null, 'estilos' => ['css/layout/guest.css']]) ?>
</head>
<body class="guest">
    <?= $contenido ?>
</body>
</html>
