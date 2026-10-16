<?php

declare(strict_types=1);

namespace App\Modules\Difusion;

use App\Core\Request;
use App\Core\Response;

defined('APP_ROOT') || exit;

/**
 * «Difusión» (las administrativas): los insumos que se piden fuera de abasto, a distintos proveedores. PENDIENTE: por
 * ahora solo se sabe eso (qué se pide, cuánto y cómo se registra está por definir), así que la pantalla avisa que está
 * en preparación.
 */
final class DifusionController
{
    public function index(Request $request): Response
    {
        return Response::view('Difusion::index', ['titulo' => 'Difusión']);
    }
}
