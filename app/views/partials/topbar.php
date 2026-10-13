<?php

use App\Core\Auth;
use App\Modules\Usuarios\UsuarioService;

defined('APP_ROOT') || exit;

$usuario = Auth::user();
// Detrás de una ventana se ve el inicio, así que la barra es la del inicio (sin el botón «Inicio»).
$enInicio = ($rutaActual ?? '/') === '/' || !empty($deFondo);
?>
<header class="topbar">
    <div class="topbar__inner">
        <?= partial('brand', ['variante' => 'inverse']) ?>

        <div class="topbar__actions">
            <?php if (!$enInicio) : ?>
                <a class="topbar__btn" href="<?= e(url()) ?>"><?= icon('home') ?><span>Inicio</span></a>
            <?php endif; ?>

            <p class="topbar__who">
                <span class="topbar__name"><?= e($usuario['nombre']) ?></span>
                <span class="topbar__role"><?= e(UsuarioService::nombrePerfil($usuario['perfil'])) ?></span>
            </p>

            <form method="post" action="<?= e(url('logout')) ?>">
                <?= csrf_field() ?>
                <button class="topbar__btn" type="submit"><?= icon('logout') ?><span>Salir</span></button>
            </form>
        </div>
    </div>
</header>
