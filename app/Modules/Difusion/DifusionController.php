<?php

declare(strict_types=1);

namespace App\Modules\Difusion;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

defined('APP_ROOT') || exit;

/** Catálogo de Difusión, separado del inventario cuyo stock numérico alimenta las alertas de bodega. */
final class DifusionController
{
    public function index(Request $request): Response
    {
        $grupos = [
            'Textiles y bolsas' => ['id' => 'textiles', 'icono' => 'layers', 'items' => []],
            'Objetos promocionales' => ['id' => 'objetos', 'icono' => 'box', 'items' => []],
            'Gráfica y exhibición' => ['id' => 'grafica', 'icono' => 'printer', 'items' => []],
            'Servicios asociados' => ['id' => 'servicios', 'icono' => 'clipboard', 'items' => []],
        ];
        $conteo = ['articulo' => 0, 'servicio' => 0];

        foreach (Database::fetchAll('SELECT nombre, detalle, grupo, tipo, cantidad_actual FROM difusion_items WHERE activo = 1 ORDER BY orden, id') as $item) {
            $grupos[$item['grupo']]['items'][] = $item;
            $conteo[$item['tipo']]++;
        }

        return Response::view('Difusion::index', [
            'titulo' => 'Difusión',
            'estilos' => ['css/pages/difusion.css'],
            'grupos' => array_filter($grupos, static fn (array $grupo): bool => $grupo['items'] !== []),
            'conteo' => $conteo,
        ]);
    }
}
