<?php defined('APP_ROOT') || exit; ?>
<!DOCTYPE html>
<?php // El ingreso va en el modelo nuevo de la interfaz (azul marino, base/tema-azul.css). ?>
<html lang="es" class="tema-azul">
<head>
    <?= partial('head', ['titulo' => $titulo ?? null, 'estilos' => ['css/layout/auth.css'], 'temaColor' => '#223c6a']) ?>
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
