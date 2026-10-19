<?php

declare(strict_types=1);

namespace App\Modules\Herramientas;

use App\Core\Request;
use App\Core\Response;

defined('APP_ROOT') || exit;

/**
 * «Insumos Práctica de Invierno» (las administrativas): el equipo de montaña de la práctica, que se presta y se devuelve
 * como las herramientas. De un vistazo: cuántos hay en bodega de cada uno, agrupados por tipo, con los mismos filtros
 * del catálogo de herramientas (en bodega, prestados, atrasados, sin unidades).
 */
final class PracticaInviernoController
{
    public function index(Request $request): Response
    {
        $estado = HerramientaService::filtro($request->query('ver'));

        return Response::view('Herramientas::practica-invierno', (new HerramientaService())->practicaInvierno($estado) + [
            'titulo' => 'Insumos Práctica de Invierno',
            'estado' => $estado,
            'estilos' => ['css/pages/herramientas.css'],
        ]);
    }
}
