<?php

declare(strict_types=1);

namespace App\Modules\Insumos;

use App\Core\Request;
use App\Core\Response;

defined('APP_ROOT') || exit;

/**
 * «Insumos Práctica de Invierno» (las administrativas): el equipo de montaña de la práctica, de un vistazo: cuántos
 * hay de cada uno, agrupados por tipo, con filtros «Con stock» y «Sin stock». Tocar uno abre su ficha.
 */
final class PracticaInviernoController
{
    /** Filtro => nombre. */
    public const FILTROS = ['todos' => 'Todos', 'con-stock' => 'Con stock', 'sin-stock' => 'Sin stock'];

    public function index(Request $request): Response
    {
        $filtro = $request->query('ver');
        $filtro = is_string($filtro) && isset(self::FILTROS[$filtro]) ? $filtro : 'todos';

        return Response::view('Insumos::practica-invierno', (new InsumoService())->practicaInvierno($filtro) + [
            'titulo' => 'Insumos Práctica de Invierno',
            'filtro' => $filtro,
            'estilos' => ['css/pages/insumos.css'],
        ]);
    }
}
