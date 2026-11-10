<?php

use App\Core\Auth;
use App\Modules\Panel\Inicio;
use App\Support\Fecha;

defined('APP_ROOT') || exit;

$usuario = Auth::user();
$perfil = Auth::perfil();
$hora = (int) date('G');
$saludo = match (true) {
    $hora < 12 => 'Buenos días',
    $hora < 20 => 'Buenas tardes',
    default => 'Buenas noches',
};
$nombre = explode(' ', trim((string) ($usuario['nombre'] ?? '')))[0];
$administracion = ['sectores', 'usuarios', 'etiquetas', 'configuracion'];
$grupos = ['Tareas' => [], 'Administración' => []];
foreach ($tareas as $tarea) {
    $grupos[in_array($tarea['clave'], $administracion, true) ? 'Administración' : 'Tareas'][] = $tarea;
}
$solicitar = null;
if ($perfil === 'docente') {
    foreach ($grupos['Tareas'] as $indice => $tarea) {
        if ($tarea['clave'] === 'solicitar-herramientas') {
            $solicitar = $tarea;
            unset($grupos['Tareas'][$indice]);
            break;
        }
    }
}
?>
<?php
// Las tareas del día a día (Operaciones) van bajo los gráficos, a todo el ancho, como tarjetas blancas compactas con el
// ícono y el nombre (.inicio-operaciones--tarjetas, panel.css). Las de administración (sectores, usuarios) van como
// botones junto a «Imprimir estado».
$navOperaciones = '';
if ($grupos['Tareas'] !== []) :
    ob_start();
    ?>
    <nav class="inicio-operaciones inicio-operaciones--tarjetas" aria-labelledby="titulo-tareas">
        <div class="inicio-operaciones__cabecera">
            <h2 id="titulo-tareas"><?= $perfil === 'docente' ? 'Tus herramientas' : 'Operaciones' ?></h2>
            <p><?= $perfil === 'docente' ? 'Consulta el catálogo y el estado de tus solicitudes.' : 'Accede directamente a la tarea que necesitas realizar.' ?></p>
        </div>
        <ul class="inicio-operaciones__lista">
            <?php foreach ($grupos['Tareas'] as $tarea) : ?>
                <li>
                    <a class="inicio-operacion accion--<?= e($tarea['color']) ?>" href="<?= e($tarea['url']) ?>">
                        <span class="inicio-operacion__icono"><?= icon($tarea['icono']) ?></span>
                        <span class="inicio-operacion__texto">
                            <strong><?= e($tarea['texto']) ?></strong>
                            <span class="inicio-operacion__detalle"><?= e($tarea['descripcion']) ?></span>
                        </span>
                        <span class="inicio-operacion__flecha" aria-hidden="true"><?= icon('arrow-right') ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <?php
    $navOperaciones = (string) ob_get_clean();
endif;
?>
<?php // Solo con avisos o con la campana se activa su actualización automática (panel.js). ?>
<div class="inicio inicio--<?= e($perfil) ?>"<?php if ($avisos !== null || $solicitudes !== null) : ?> data-panel data-panel-url="<?= e(url('panel/resumen')) ?>" data-panel-interval="<?= e($intervalo) ?>"<?php endif; ?>>
    <?php if ($perfil === 'docente') : ?>
        <?php
        // Arriba, la misma cabecera que los demás perfiles (el saludo y la fecha). A la derecha, la campana con sus
        // notificaciones (un panel que se abre al tocarla; el número, en un color que destaca, es cuántas son) y
        // «Solicitar herramientas». Los dos se mantienen al día solos (panel.js).
        $cuentaNotificaciones = preg_match('/data-cuenta="(\d+)"/', (string) $avisos, $coincide) === 1 ? (int) $coincide[1] : 0;
        ?>
        <header class="inicio-cabecera">
            <div class="inicio-cabecera__textos">
                <h1><?= e($saludo) ?><?= $nombre !== '' ? ', ' . e($nombre) : '' ?></h1>
                <p><?= e(mb_ucfirst(Fecha::larga())) ?></p>
            </div>
            <div class="inicio-cabecera__acciones">
                <?php if ($avisos !== null) : ?>
                    <details class="notificaciones" data-desplegable data-vistas-url="<?= e(url('panel/notificaciones/vistas')) ?>">
                        <summary class="notificaciones__boton" aria-label="Notificaciones de tus solicitudes">
                            <?= icon('bell') ?>
                            <span class="notificaciones__cuenta" data-panel-cuenta<?= $cuentaNotificaciones === 0 ? ' hidden' : '' ?>><?= e($cuentaNotificaciones > 99 ? '99+' : $cuentaNotificaciones) ?></span>
                        </summary>
                        <div class="notificaciones__panel">
                            <div class="notificaciones__cabecera">
                                <h2>Notificaciones</h2>
                                <p class="inicio__estado" data-panel-status>Actualizado a las <?= e(date('H:i')) ?></p>
                            </div>
                            <div class="notificaciones__contenido" data-panel-content><?= $avisos ?></div>
                            <a class="notificaciones__todas" href="<?= e(url('herramientas/mis-solicitudes')) ?>">Ver todas mis solicitudes<?= icon('arrow-right') ?></a>
                        </div>
                    </details>
                <?php endif; ?>
                <?php if ($solicitar !== null) : ?>
                    <a class="btn btn-primary" href="<?= e($solicitar['url']) ?>"><?= icon('tool') ?>Solicitar herramientas</a>
                <?php endif; ?>
            </div>
        </header>
        <?php
        // Debajo, la misma estructura que los demás perfiles: el stock de herramientas en tiempo real (la torta simple,
        // Panel::torta; se actualiza sola, panel.js) con su título y, bajo ella, sus accesos como tarjetas compactas
        // (el catálogo y sus solicitudes).
        ?>
        <?php if ($stockHerramientas !== null) : ?>
            <section class="graficos inicio-docente-stock" aria-labelledby="titulo-graficos">
                <div class="graficos__cabecera">
                    <h2 id="titulo-graficos">¿Qué herramientas hay?</h2>
                </div>
                <div data-panel-content data-panel-url="<?= e(url('panel/herramientas')) ?>"><?= $stockHerramientas ?></div>
                <?= $navOperaciones ?>
            </section>
        <?php endif; ?>
    <?php else : ?>
        <header class="inicio-cabecera">
            <?php // La misma cabecera para el encargado, las administrativas y el operador: el saludo y la fecha. ?>
            <div class="inicio-cabecera__textos">
                <h1><?= e($saludo) ?><?= $nombre !== '' ? ', ' . e($nombre) : '' ?></h1>
                <p><?= e(mb_ucfirst(Fecha::larga())) ?></p>
            </div>
            <?php if ($buscar || $bienvenida !== null) : ?>
                <div class="inicio-cabecera__lado">
                    <?php // El aviso de ingreso, en una línea discreta sobre la búsqueda (sin recuadro: deja la pantalla al trabajo). ?>
                    <?php if ($bienvenida !== null) : ?>
                        <p class="inicio-cabecera__ingreso" role="status">
                            <?= icon('info') ?>
                            <span><?= $bienvenida['acceso_anterior'] === null
                                ? 'Este es tu primer ingreso al sistema.'
                                : 'Ingreso anterior: ' . e(Fecha::mostrar($bienvenida['acceso_anterior'])) . '. ¿No fuiste tú? Avisa al encargado.' ?></span>
                        </p>
                    <?php endif; ?>
                    <?php if ($buscar) : ?>
                        <form class="input-group sugerencias inicio-cabecera__buscar" method="get" action="<?= e(url('insumos')) ?>" role="search">
                            <a class="btn btn-primary btn-icon" href="<?= e(url('insumos')) ?>" aria-label="Ir a Buscar producto"><?= icon('search') ?></a>
                            <label class="visually-hidden" for="buscar">Buscar producto</label>
                            <input class="form-control" id="buscar" name="q" type="search" placeholder="Buscar producto o código"
                                autocomplete="off" enterkeyhint="search" data-sugerencias="<?= e(url('insumos/sugerencias')) ?>" <?= $deFondo ? '' : 'autofocus' ?>>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </header>
        <?php if ($bodega !== null) : ?>
            <?php // Cómo está la bodega: una torta para los insumos y otra para las herramientas (Panel::torta). ?>
            <section class="graficos" aria-labelledby="titulo-graficos">
                <div class="graficos__cabecera">
                    <h2 id="titulo-graficos">¿Cómo está la bodega?</h2>
                    <div class="graficos__acciones">
                        <?php
                        // Primero el inventario (la lista de productos, en su propia página); después la gestión del
                        // sistema (sectores, usuarios) y el documento Word con el estado completo de la bodega.
                        ?>
                        <?php if ($inventario) : ?>
                            <a class="graficos__boton" href="<?= e(url('inventario')) ?>"><?= icon('layers') ?>Inventario</a>
                        <?php endif; ?>
                        <?php foreach ($grupos['Administración'] as $tarea) : ?>
                            <a class="graficos__boton" href="<?= e($tarea['url']) ?>"><?= icon($tarea['icono']) ?><?= e($tarea['texto']) ?></a>
                        <?php endforeach; ?>
                        <?php if ($imprimir) : ?>
                            <a class="graficos__boton" href="<?= e(url('panel/estado-bodega')) ?>" download>
                                <?= icon('printer') ?>Imprimir estado
                            </a>
                        <?php endif; ?>
                        <?php // La campana, al final y separada por una línea fina: lleva a las solicitudes de los docentes por entregar o rechazar; con alguna, su número. ?>
                        <?php
                        // La misma campana que la del docente: un círculo con la campana y, si llegaron solicitudes
                        // nuevas, su número en rojo. Al tocarla quedan vistas (el número desaparece) y lleva a los
                        // préstamos. El número se mantiene al día solo (panel.js, con la marca escondida de abajo).
                        ?>
                        <?php if ($solicitudes !== null) : ?>
                            <a class="notificaciones__boton graficos__campana" href="<?= e(url('panel/campana/ver')) ?>"
                                title="Solicitudes de herramientas de los docentes" aria-label="Solicitudes de herramientas de los docentes">
                                <?= icon('bell') ?>
                                <span class="notificaciones__cuenta" data-panel-cuenta<?= $solicitudes === 0 ? ' hidden' : '' ?>><?= e($solicitudes > 99 ? '99+' : $solicitudes) ?></span>
                            </a>
                            <span hidden data-panel-content data-panel-url="<?= e(url('panel/campana')) ?>"><?= Inicio::campana() ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="graficos__lista">
                    <?php // Izquierda: los insumos. Derecha: las herramientas. Debajo, Operaciones. ?>
                    <?php foreach ($bodega['graficos'] as $i => $grafico) : ?>
                        <div class="graficos__columna">
                            <?= partial('Panel::torta', $grafico + ['id' => 'grafico-' . $i]) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?= $navOperaciones ?>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($tareas === [] && !$buscar) : ?>
        <p class="card card-body text-muted">Pronto podrás hacer tus trámites aquí.</p>
    <?php endif; ?>
</div>