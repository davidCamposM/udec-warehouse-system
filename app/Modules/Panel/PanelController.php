<?php

declare(strict_types=1);

namespace App\Modules\Panel;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Url;
use App\Modules\Insumos\InsumoService;

defined('APP_ROOT') || exit;

final class PanelController
{
    public function index(Request $request): Response
    {
        return Response::view('Panel::inicio', Inicio::datos() + [
            'titulo' => 'Inicio',
            'estilos' => ['css/pages/panel.css'],
            'scripts' => ['js/modules/panel.js', 'js/modules/insumos.js', 'js/modules/insumos-regreso.js'],
        ]);
    }

    /**
     * «Inventario» (el botón del inicio): la lista de productos por páginas, con las categorías como pestañas.
     * ?categoria= elige la categoría y ?pagina=, la página de la lista.
     */
    public function inventario(Request $request): Response
    {
        $categoria = $request->query('categoria');
        $categoria = is_string($categoria) && ctype_digit($categoria) ? (int) $categoria : null;
        $pagina = $request->query('pagina');
        $pagina = is_string($pagina) && ctype_digit($pagina) && strlen($pagina) < 6 ? (int) $pagina : 1;

        return Response::view('Panel::inventario-pagina', [
            'inventario' => (new InsumoService())->inventario($categoria, $pagina),
            'titulo' => 'Inventario',
            'amplia' => true,
            'estilos' => ['css/pages/panel.css'],
            'scripts' => ['js/modules/insumos-regreso.js'],
        ]);
    }

    /** «Imprimir estado de bodega»: el documento Word con el estado completo de la bodega en este momento. */
    public function estadoBodega(Request $request): Response
    {
        return Response::descarga(
            'Estado_de_bodega_' . date('Y-m-d_Hi') . '.docx',
            (new EstadoBodega())->documento(),
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        );
    }

    /** Solo los avisos, sin la página: los pide panel.js cada cierto tiempo para mantenerlos al día. */
    public function actualizar(Request $request): Response
    {
        return Response::html(Inicio::avisos());
    }

    /** Solo el número de la campana del encargado y del operador: lo pide panel.js cada cierto tiempo. */
    public function campana(Request $request): Response
    {
        return Response::html(Inicio::campana());
    }

    /**
     * La campana del encargado y del operador: marca sus notificaciones como vistas (el número desaparece) y lleva a
     * las solicitudes por entregar o rechazar.
     */
    public function verSolicitudes(Request $request): Response
    {
        if (Inicio::conCampana()) {
            (new PanelRepository())->marcarNotificacionesVistas((int) Auth::id());
        }

        return Response::redirect(Url::to('prestamos'));
    }

    /** El docente abrió su campana (panel.js): sus notificaciones quedan vistas y el número desaparece. */
    public function notificacionesVistas(Request $request): Response
    {
        (new PanelRepository())->marcarNotificacionesVistas((int) Auth::id());

        return Response::html('');
    }

    /** Solo el estado de las herramientas (el inicio del docente): lo pide panel.js cada cierto tiempo. */
    public function herramientas(Request $request): Response
    {
        return Response::html(Inicio::stockHerramientas());
    }
}
