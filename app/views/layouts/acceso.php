<?php defined('APP_ROOT') || exit; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= partial('head', ['titulo' => $titulo ?? null, 'estilos' => ['css/layout/auth.css']]) ?>
    <script src="<?= e(asset('js/core/ui.js')) ?>" defer></script>
</head>
<body>
    <div class="auth">
        <div class="auth__panel">
            <header>
                <?= partial('brand') ?>
            </header>

            <main class="auth__main">
                <div class="auth__content">
                    <?= $contenido ?>
                </div>
            </main>

            <footer class="auth__footer">
                © <?= e(date('Y')) ?> Facultad de Ciencias Forestales · Universidad de Concepción
            </footer>
        </div>

        <?= partial('auth-showcase') ?>
    </div>
</body>
</html>
