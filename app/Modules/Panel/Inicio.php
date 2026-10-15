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
use App\Modules\Insumos\InsumoService;
use App\Support\Tareas;

defined('APP_ROOT') || exit;

/**
 * Lo que muestra el inicio (la vista Panel::inicio). También va de fondo, difuminado, detrás de las ventanas que se
 * abren sobre él, como «Registrar producto».
 */
final class Inicio
{
    /**
     * @param ?int $categoria la categoría elegida en el inventario (null: todas)
     * @param int  $pagina    la página de la lista de inventario
     *
     * @return array<string, mixed>
     */
    public static function datos(bool $deFondo = false, ?int $categoria = null, int $pagina = 1): array
    {
        return [
            'buscar' => Tareas::puede('buscar-producto'),
            'tareas' => Tareas::botones(),
            'avisos' => self::avisos(),
            // Las categorías a la izquierda y la lista de inventario a la derecha, para quien puede ver los insumos.
            'inventario' => Permission::can('insumos.ver') ? (new InsumoService())->inventario($categoria, $pagina) : null,
            'intervalo' => max(10, (int) Config::get('panel.poll_seconds', 30)),
            // De fondo no se muestra (ni se gasta) el aviso de bienvenida, y la barra de búsqueda no toma el foco.
            'bienvenida' => $deFondo ? null : Session::getFlash('bienvenida'),
            'deFondo' => $deFondo,
            // Más ancho que las demás páginas, para que la lista de inventario quepa sin apretarse.
            'amplia' => true,
        ];
    }

    /**
     * El inicio de fondo, difuminado, con una ventana encima (parcial «ventana»): así se muestran las tareas que son un
     * formulario por etapas, como «Registrar producto». El título, el color y el ícono salen de la tarea.
     *
     * @param string       $tarea   clave de config/tareas.php
     * @param string       $texto   la línea bajo el título
     * @param string       $cuerpo  HTML de la etapa
     * @param list<string> $estilos hojas propias de la tarea
     * @param list<string> $scripts scripts propios de la tarea
     * @param bool         $ancha   ventana más ancha, para un formulario con varias columnas
     */
    public static function conVentana(
        string $tarea,
        string $texto,
        string $cuerpo,
        array $estilos = [],
        array $scripts = [],
        bool $ancha = false,
    ): Response
    {
        $titulo = Tareas::tarea($tarea)['texto'];

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
    public static function avisos(): string
    {
        $servicio = new PanelService();
        $conteos = Permission::can('panel.bodega')
            ? $servicio->avisosBodega()
            : $servicio->avisosDocente((int) Auth::id());

        return View::render('Panel::avisos', ['conteos' => $conteos], null);
    }
}
