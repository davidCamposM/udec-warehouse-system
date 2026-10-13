<?php

use App\Support\Fecha;

defined('APP_ROOT') || exit;
?>
<?= partial('encabezado', [
    'volver' => [url(), 'Volver al inicio'],
    'color' => 'grafito',
    'icono' => 'scan',
    'titulo' => 'Punto de retiro',
    'texto' => 'El computador de la bodega donde cada persona retira con su credencial, sin cuenta en el sistema.',
]) ?>

<section class="block">
    <h2 class="block__title">Este computador</h2>
    <?php if ($este !== null) : ?>
        <div class="punto-estado">
            <span class="punto-estado__icono"><?= icon('check') ?></span>
            <div>
                <p class="punto-estado__titulo">Está habilitado como «<?= e($este['nombre']) ?>»</p>
                <p>Al abrir el punto de retiro se cierra tu sesión en este computador.</p>
            </div>
            <a class="btn btn--lg btn--tarea tono-grafito" href="<?= e(url('retiro')) ?>"><?= icon('scan') ?>Abrir el punto de retiro</a>
        </div>
    <?php else : ?>
        <form class="form-simple" method="post" action="<?= e(url('punto-de-retiro/habilitar')) ?>" data-submit-once>
            <?= csrf_field() ?>
            <div class="question<?= error_de('nombre') !== null ? ' has-error' : '' ?>">
                <label class="question__label question__label--sin-numero" for="nombre">Habilitar este computador</label>
                <p class="question__hint">
                    Hazlo en el computador de la bodega que tiene la pistola. Quedará abierto el punto de retiro y, por
                    seguridad, se cerrará tu sesión. Ponle un nombre para reconocerlo:
                </p>
                <input class="input" id="nombre" name="nombre" type="text" maxlength="80"
                    value="<?= e(old('nombre', 'PC de la bodega')) ?>" <?= aria_error('nombre') ?>>
                <?= mensaje_error('nombre') ?>
            </div>
            <div class="acciones">
                <a class="btn btn--lg btn--no" href="<?= e(url()) ?>"><?= icon('close') ?>Cancelar</a>
                <button class="btn btn--lg btn--si" type="submit" data-loading-text="Habilitando…"><?= icon('ok') ?>Habilitar</button>
            </div>
        </form>
    <?php endif; ?>
</section>

<section class="block">
    <h2 class="block__title">Equipos habilitados</h2>
    <?php if ($equipos === []) : ?>
        <p class="caja caja--nota">Todavía no hay ninguno.</p>
    <?php else : ?>
        <ul class="equipos">
            <?php foreach ($equipos as $equipo) : ?>
                <li class="caja equipo">
                    <div>
                        <p class="equipo__nombre"><?= icon('scan') ?><?= e($equipo['nombre']) ?></p>
                        <p class="equipo__meta">
                            Lo habilitó <?= e($equipo['autorizada_por']) ?> el <?= e(Fecha::mostrar($equipo['creado_en'])) ?>
                            · <?= $equipo['ultimo_uso'] === null ? 'Aún sin retiros' : 'Último retiro: ' . e(Fecha::mostrar($equipo['ultimo_uso'])) ?>
                        </p>
                    </div>
                    <a class="btn btn--secondary" href="<?= e(url('punto-de-retiro/' . $equipo['id'] . '/deshabilitar')) ?>">Deshabilitar</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
