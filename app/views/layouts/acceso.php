<?php defined('APP_ROOT') || exit; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= partial('head', ['titulo' => $titulo ?? null, 'estilos' => ['css/layout/auth.css']]) ?>
    <script src="<?= e(asset('js/core/ui.js')) ?>" defer></script>
</head>
<body class="auth">
    <main class="auth__main">
        <div class="auth__card">
            <header class="auth__brand">
                <span class="auth__logo"><?= icon('tree') ?></span>
                <h1 class="auth__name">Bodega FCF</h1>
            </header>
            <p class="auth__tagline"><?= e($lema ?? 'Ingresa a tu cuenta') ?></p>

            <?= $contenido ?>
        </div>
    </main>

    <footer class="auth__footer">
        © <?= e(date('Y')) ?> Facultad de Ciencias Forestales · Universidad de Concepción
    </footer>
</body>
</html>
