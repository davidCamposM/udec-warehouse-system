<?php

defined('APP_ROOT') || exit;

/**
 * Layout del punto de retiro: pantalla de autoservicio, sin la barra del sistema (no hay cuenta de usuario). Arriba,
 * quién está retirando y «Terminar». Opcionales: $persona (identificada), $volverEn (segundos para volver solo al
 * inicio del punto de retiro) e $inactividad (sin JavaScript, la pantalla se recarga y el servidor cierra la
 * identificación vencida).
 */
$hojas = ['css/components/simple.css', 'css/components/basico.css', 'css/layout/retiro.css', ...($estilos ?? [])];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= partial('head', ['titulo' => $titulo ?? null, 'estilos' => $hojas]) ?>
    <?php if (isset($volverEn)) : ?>
    <meta http-equiv="refresh" content="<?= e($volverEn) ?>;url=<?= e(url('retiro')) ?>">
    <?php elseif (isset($inactividad)) : ?>
    <noscript><meta http-equiv="refresh" content="<?= e($inactividad + 5) ?>;url=<?= e(url('retiro')) ?>"></noscript>
    <?php endif; ?>
    <script src="<?= e(asset('js/core/ui.js')) ?>" defer></script>
    <?php foreach ($scripts ?? [] as $script) : ?>
    <script src="<?= e(asset($script)) ?>" defer></script>
    <?php endforeach; ?>
</head>
<body class="kiosco">
    <header class="kiosco__barra">
        <div class="kiosco__barra-inner">
            <span class="brand brand--inverse">
                <span class="brand__mark"><?= icon('tree') ?></span>
                <span class="brand__text">
                    <span class="brand__name">Punto de retiro</span>
                    <span class="brand__sub">Bodega FCF · Ciencias Forestales</span>
                </span>
            </span>

            <?php if (isset($persona)) : ?>
                <div class="kiosco__quien">
                    <p class="kiosco__persona"><?= icon('id-card') ?><span><?= e($persona['nombre']) ?></span></p>
                    <form method="post" action="<?= e(url('retiro/terminar')) ?>">
                        <?= csrf_field() ?>
                        <button class="kiosco__terminar" type="submit"><?= icon('close') ?><span>Terminar</span></button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <main class="kiosco__contenido" id="contenido">
        <?= $contenido ?>
    </main>

    <footer class="kiosco__pie">
        <a href="<?= e(url('punto-de-retiro')) ?>">¿Eres el encargado? Administrar el punto de retiro</a>
    </footer>
</body>
</html>
