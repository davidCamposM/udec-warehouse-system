<?php

declare(strict_types=1);

namespace App\Modules\Panel;

use App\Core\Request;
use App\Core\Response;

defined('APP_ROOT') || exit;

final class PanelController
{
    /** ?categoria= elige la categoría del inventario y ?pagina=, la página de su lista. */
    public function index(Request $request): Response
    {
        $categoria = $request->query('categoria');
        $categoria = is_string($categoria) && ctype_digit($categoria) ? (int) $categoria : null;
        $pagina = $request->query('pagina');
        $pagina = is_string($pagina) && ctype_digit($pagina) && strlen($pagina) < 6 ? (int) $pagina : 1;

        return Response::view('Panel::inicio', Inicio::datos(categoria: $categoria, pagina: $pagina) + [
            'titulo' => 'Inicio',
            'estilos' => ['css/pages/panel.css'],
            'scripts' => ['js/modules/panel.js', 'js/modules/insumos.js'],
        ]);
    }

    /** Solo los avisos, sin la página: los pide panel.js cada cierto tiempo para mantenerlos al día. */
    public function actualizar(Request $request): Response
    {
        return Response::html(Inicio::avisos());
    }
}
