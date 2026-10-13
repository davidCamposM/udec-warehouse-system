<?php

use App\Modules\Personas\PersonaService;
use App\Support\Rut;
use App\Support\Tareas;

defined('APP_ROOT') || exit;

// Personas autorizadas, con los componentes básicos (basico.css): una tarjeta por persona (sus iniciales, nombre, tipo,
// RUT y departamento, el código de su credencial y sus botones) y, aparte, la lista de las desactivadas.

$activas = array_values(array_filter($personas, static fn (array $p): bool => (int) $p['activo'] === 1));
$desactivadas = array_values(array_filter($personas, static fn (array $p): bool => (int) $p['activo'] !== 1));

// Las iniciales del nombre (dos como mucho), para el círculo de cada persona: «María José Soto» → «MJ»
$iniciales = static fn (string $nombre): string => mb_strtoupper(implode('', array_map(
    static fn (string $parte): string => mb_substr($parte, 0, 1),
    array_slice(preg_split('/\s+/', trim($nombre)) ?: [], 0, 2)
)));
?>
<?= partial('encabezado', [
    'volver' => [url(), 'Volver al inicio'],
    'tarea' => 'personas',
    'titulo' => 'Personas autorizadas',
    'texto' => 'Quienes pueden retirar en el punto de retiro. Cada una tiene su credencial con un código propio.',
    'acciones' => '<a class="btn btn-success" href="' . e(url('personas/nueva')) . '">' . icon('plus') . 'Agregar persona</a>',
]) ?>

<?php if ($activas === []) : ?>
    <div class="card card-body vacio-simple">
        <span class="vacio-simple__icono"><?= icon('id-card') ?></span>
        <p class="vacio-simple__titulo">Todavía no hay personas autorizadas</p>
        <p class="text-muted">Agrega a los auxiliares y a quienes pueden retirar con el botón «Agregar persona».</p>
    </div>
<?php else : ?>
    <ul class="registros tono-<?= e(Tareas::tarea('personas')['color']) ?>">
        <?php foreach ($activas as $persona) : ?>
            <li class="registro">
                <div class="registro__cabecera">
                    <span class="registro__icono persona__inicial persona__inicial--<?= e($persona['tipo']) ?>" aria-hidden="true"><?= e($iniciales($persona['nombre'])) ?></span>
                    <div>
                        <p class="registro__nombre"><?= e($persona['nombre']) ?></p>
                        <span class="persona__tipo persona__tipo--<?= e($persona['tipo']) ?>"><?= e(PersonaService::TIPOS[$persona['tipo']][0]) ?></span>
                    </div>
                </div>
                <?php if ($persona['rut'] !== null || $persona['departamento'] !== null) : ?>
                    <p class="registro__detalle">
                        <?php if ($persona['rut'] !== null) : ?>RUT <?= e(Rut::formatear($persona['rut'])) ?><?php endif; ?>
                        <?php if ($persona['rut'] !== null && $persona['departamento'] !== null) : ?> · <?php endif; ?>
                        <?php if ($persona['departamento'] !== null) : ?><?= e($persona['departamento']) ?><?php endif; ?>
                    </p>
                <?php endif; ?>
                <span class="registro__etiqueta" title="Código de su credencial">
                    <?= icon('id-card') ?><span class="visually-hidden">Código de su credencial:</span><span class="registro__codigo"><?= e($persona['codigo_credencial']) ?></span>
                </span>
                <div class="registro__acciones">
                    <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('personas/' . $persona['id'] . '/editar')) ?>"><?= icon('edit') ?>Editar</a>
                    <a class="btn btn-outline-danger btn-sm" href="<?= e(url('personas/' . $persona['id'] . '/desactivar')) ?>">Desactivar</a>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    <p class="nota-info">
        <?= icon('info') ?>
        <span>
            Pronto podrás imprimir la credencial de cada persona. Mientras tanto, en el punto de retiro pueden escribir su
            código o su RUT.
        </span>
    </p>
<?php endif; ?>

<?php if ($desactivadas !== []) : ?>
    <section class="card desactivados" aria-labelledby="titulo-desactivadas">
        <div class="card-header">
            <h2 class="card-title" id="titulo-desactivadas">Desactivadas</h2>
            <span class="text-muted desactivados__ayuda">Su credencial no sirve en el punto de retiro</span>
        </div>
        <ul class="list-group">
            <?php foreach ($desactivadas as $persona) : ?>
                <li class="list-group-item desactivado">
                    <span class="desactivado__codigo"><?= e($persona['codigo_credencial']) ?></span>
                    <span class="desactivado__nombre"><?= e($persona['nombre']) ?></span>
                    <form method="post" action="<?= e(url('personas/' . $persona['id'] . '/activar')) ?>" data-submit-once>
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-secondary btn-sm" type="submit" data-loading-text="Activando…">Volver a activar</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
