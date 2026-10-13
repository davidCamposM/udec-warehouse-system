<?php

declare(strict_types=1);

namespace App\Modules\Historial;

use App\Core\Request;
use App\Core\Response;

defined('APP_ROOT') || exit;

/** «Entradas y salidas»: lo que entró, salió o se ajustó, con filtros por período y tipo, y su descarga para Excel. */
final class MovimientoController
{
    private MovimientoService $movimientos;

    public function __construct()
    {
        $this->movimientos = new MovimientoService();
    }

    public function index(Request $request): Response
    {
        $filtros = $this->filtros($request);

        return Response::view('Historial::movimientos', $this->movimientos->listar($filtros) + [
            'titulo' => 'Entradas y salidas',
            'filtros' => $filtros,
            'estilos' => ['css/pages/movimientos.css'],
        ]);
    }

    public function csv(Request $request): Response
    {
        $filtros = $this->filtros($request);

        return Response::csv(
            'entradas-y-salidas-' . $filtros['periodo'] . '-' . date('Y-m-d') . '.csv',
            ['Fecha', 'Tipo', 'Código', 'Producto', 'Cantidad', 'Quedaron', 'Quién', 'Sector', 'Motivo'],
            $this->movimientos->filasCsv($filtros)
        );
    }

    /** @return array{periodo: string, tipo: ?string, pagina: int} */
    private function filtros(Request $request): array
    {
        return MovimientoService::filtros($request->query('periodo'), $request->query('tipo'), $request->query('pagina'));
    }
}
