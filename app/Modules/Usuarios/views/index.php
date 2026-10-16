<?php

use App\Modules\Usuarios\CuentaService;
use App\Support\Fecha;

defined('APP_ROOT') || exit;

// «Usuarios»: las cuentas de docentes y administrativas, con los componentes básicos (basico.css). Arriba, «Agregar
// persona» y los filtros; debajo, una tabla: nombre y correo, perfil, si ya creó su contraseña, último ingreso y los
// botones «Dar enlace» (para crear su contraseña, o una nueva si la olvidó), «Editar» y «Eliminar».
?>
<?= partial('encabezado', [
    'volver' => [url(), 'Volver al inicio'],
    'tarea' => 'usuarios',
    'titulo' => 'Usuarios',
    'texto' => 'Las personas que pueden entrar al sistema. Cada una crea su contraseña con el enlace que le compartes.',
    'acciones' => '<a class="btn btn-success" href="' . e(url('usuarios/agregar')) . '">' . icon('plus') . 'Agregar persona</a>',
]) ?>

<nav class="nav-pills usuarios__filtros" aria-label="Mostrar">
    <?php foreach (CuentaService::FILTROS as $clave => $nombre) : ?>
        <a class="nav-link<?= $clave === 'sin-clave' ? ' nav-link--atencion' : '' ?><?= $clave === $filtro ? ' active' : '' ?>"
            href="<?= e(url('usuarios', $clave === 'todas' ? [] : ['ver' => $clave])) ?>" <?= $clave === $filtro ? 'aria-current="true"' : '' ?>>
            <?= e($nombre) ?> <span class="nav-count"><?= e($conteo[$clave]) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<?php if ($cuentas === []) : ?>
    <div class="card card-body vacio-simple">
        <span class="vacio-simple__icono"><?= icon('users') ?></span>
        <p class="vacio-simple__titulo"><?= $filtro === 'todas' ? 'Todavía no hay personas' : 'No hay personas aquí' ?></p>
        <?php if ($filtro === 'todas') : ?>
            <p class="text-muted">Toca «Agregar persona» y escribe su nombre, su correo (UdeC o Gmail) y si es docente o administrativa.</p>
        <?php endif; ?>
    </div>
<?php else : ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table usuarios__tabla">
                <thead>
                    <tr>
                        <th scope="col">Persona</th>
                        <th scope="col">Perfil</th>
                        <th scope="col">Contraseña</th>
                        <th scope="col">Último ingreso</th>
                        <th scope="col"><span class="visually-hidden">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cuentas as $cuenta) : ?>
                        <tr>
                            <td>
                                <strong class="usuarios__nombre"><?= e($cuenta['nombre']) ?></strong>
                                <span class="usuarios__email"><?= e($cuenta['email']) ?></span>
                            </td>
                            <td><?= e(CuentaService::PERFILES[$cuenta['perfil']] ?? $cuenta['perfil']) ?></td>
                            <td>
                                <?php if ((bool) $cuenta['con_clave']) : ?>
                                    <span class="status status--ok"><?= icon('check') ?>Creada</span>
                                <?php else : ?>
                                    <span class="status status--low"><?= icon('clock') ?>Falta crearla</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= e($cuenta['ultimo_acceso'] !== null ? Fecha::mostrar($cuenta['ultimo_acceso']) : 'Nunca') ?></td>
                            <td>
                                <div class="usuarios__acciones">
                                    <form method="post" action="<?= e(url('usuarios/' . $cuenta['id'] . '/enlace')) ?>" data-submit-once>
                                        <?= csrf_field() ?>
                                        <button class="btn btn-outline-secondary btn-sm" type="submit" data-loading-text="Un momento…"
                                            title="Un enlace para que <?= (bool) $cuenta['con_clave'] ? 'cree una contraseña nueva' : 'cree su contraseña' ?>">
                                            <?= icon('lock') ?>Dar enlace
                                        </button>
                                    </form>
                                    <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('usuarios/' . $cuenta['id'] . '/editar')) ?>"><?= icon('edit') ?>Editar</a>
                                    <a class="btn btn-outline-danger btn-sm" href="<?= e(url('usuarios/' . $cuenta['id'] . '/eliminar')) ?>">Eliminar</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
