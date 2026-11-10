<?php

declare(strict_types=1);

namespace App\Modules\Herramientas;

use App\Core\Request;
use App\Core\Response;

defined('APP_ROOT') || exit;

/**
 * «Insumos Práctica de Invierno»: por ahora, una pantalla que avisa que la sección está en preparación. La lista del
 * equipo (2013-2019) no está confirmada; cuando llegue la actualizada, se vuelve a mostrar el equipo (la vista
 * Herramientas::practica-invierno, con HerramientaService::practicaInvierno()), que queda lista para eso.
 */
final class PracticaInviernoController
{
    public function index(Request $request): Response
    {
        return Response::view('Herramientas::proximamente', [
            'titulo' => 'Insumos Práctica de Invierno',
            'estilos' => ['css/pages/herramientas.css'],
        ]);
    }
}
