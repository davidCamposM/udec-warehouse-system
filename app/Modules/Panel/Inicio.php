<?php

declare(strict_types=1);

namespace App\Modules\Panel;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Permission;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Core\View;
use App\Support\Tareas;

defined('APP_ROOT') || exit;

/**
 * Lo que muestra el inicio (la vista Panel::inicio). También va de fondo, difuminado, detrás de las ventanas que se
 * abren sobre él, como «Ingresar productos».
 */
final class Inicio
{
    /**
     * @return array<string, mixed>
     */
    public static function datos(bool $deFondo = false): array
    {
        return [
            'buscar' => Tareas::puede('buscar-producto'),
            'tareas' => Tareas::botones(),
            // Los avisos (al día solos, panel.js) quedan para los docentes: lo suyo. El administrador, las administrativas
            // y el operador leen el estado de la bodega en los gráficos.
            'avisos' => Auth::perfil() === 'docente' ? self::avisos() : null,
            // El estado de la bodega abre el inicio del administrador, las administrativas y el operador.
            'bodega' => in_array(Auth::perfil(), ['administrador', 'administrativa', 'operador'], true) ? ['graficos' => (new PanelService())->graficos()] : null,
            // El docente abre su inicio con el stock de herramientas (se actualiza solo, panel.js).
            'stockHerramientas' => Auth::perfil() === 'docente' ? self::stockHerramientas() : null,
            // «Imprimir estado de bodega», para el encargado, el operador y las administrativas.
            'imprimir' => Permission::can('panel.bodega'),
            // La campana, al final de los botones de «¿Cómo está la bodega?» (encargado y operador; las administrativas
            // no): las solicitudes de herramientas de los docentes que llegaron desde la última vez que vio la campana
            // (como un chat: al verlas, el número desaparece). null: no va.
            'solicitudes' => self::conCampana() ? (new PanelRepository())->contarSolicitudesNuevas((int) Auth::id()) : null,
            // El botón «Inventario» (la lista de productos, en su propia página), para quien puede ver los insumos.
            'inventario' => Permission::can('insumos.ver'),
            'intervalo' => max(10, (int) Config::get('panel.poll_seconds', 30)),
            // De fondo no se muestra (ni se gasta) el aviso de bienvenida, y la barra de búsqueda no toma el foco.
            'bienvenida' => $deFondo ? null : Session::getFlash('bienvenida'),
            'deFondo' => $deFondo,
            // Más ancho que las demás páginas, para que los gráficos y Operaciones quepan sin apretarse.
            'amplia' => true,
        ];
    }

    /**
     * El inicio de fondo, difuminado, con una ventana encima (parcial «ventana»): así se muestran las tareas que son un
     * formulario por etapas, como «Ingresar productos». El título, el color y el ícono salen de la tarea.
     *
     * @param string       $tarea   clave de config/tareas.php
     * @param string       $texto   la línea bajo el título
     * @param string       $cuerpo  HTML de la etapa
     * @param list<string> $estilos hojas propias de la tarea
     * @param list<string> $scripts scripts propios de la tarea
     * @param bool         $ancha   ventana más ancha, para un formulario con varias columnas
     * @param ?string      $titulo  el título de la ventana; si no se indica, el nombre de la tarea. Las tareas en las
     *                              que cada etapa es su propia ventana ponen aquí la pregunta de la etapa
     */
    public static function conVentana(
        string $tarea,
        string $texto,
        string $cuerpo,
        array $estilos = [],
        array $scripts = [],
        bool $ancha = false,
        ?string $titulo = null,
    ): Response
    {
        $titulo ??= Tareas::tarea($tarea)['texto'];

        return Response::view('Panel::inicio', self::datos(deFondo: true) + [
            'titulo' => $titulo,
            'ventana' => View::partial('ventana', [
                'tarea' => $tarea,
                'titulo' => $titulo,
                'texto' => $texto,
                'cerrar' => Url::to(),
                'cuerpo' => $cuerpo,
                'ancha' => $ancha,
            ]),
            'estilos' => ['css/components/ventana-basico.css', 'css/pages/panel.css', ...$estilos],
            'scripts' => $scripts,
        ]);
    }

    /** Los de toda la bodega para quien tiene el permiso panel.bodega; para el resto, solo lo propio. */
    /** Si esta persona tiene la campana de solicitudes de los docentes (el encargado y el operador). */
    public static function conCampana(): bool
    {
        return in_array(Auth::perfil(), ['administrador', 'operador'], true) && Permission::can('prestamos.administrar');
    }

    /**
     * El número de la campana del encargado y del operador, para que panel.js lo mantenga al día: una marca vacía con
     * data-cuenta (las solicitudes nuevas).
     */
    public static function campana(): string
    {
        $nuevas = self::conCampana() ? (new PanelRepository())->contarSolicitudesNuevas((int) Auth::id()) : 0;

        return '<span data-cuenta="' . $nuevas . '"></span>';
    }

    /** El estado de las herramientas del docente (la torta simple, Panel::torta; sin los datos internos de la bodega). */
    public static function stockHerramientas(): string
    {
        return View::render('Panel::torta', (new PanelService())->graficoHerramientas(false) + ['id' => 'grafico-herramientas'], null);
    }

    public static function avisos(): string
    {
        $servicio = new PanelService();
        // El docente: sus notificaciones, en la campana de su inicio.
        if (Auth::perfil() === 'docente') {
            return View::render('Panel::notificaciones', ['notificaciones' => $servicio->notificacionesDocente((int) Auth::id())], null);
        }
        $conteos = Permission::can('panel.bodega')
            ? $servicio->avisosBodega()
            : $servicio->avisosDocente((int) Auth::id());

        return View::render('Panel::avisos', ['conteos' => $conteos], null);
    }
}
