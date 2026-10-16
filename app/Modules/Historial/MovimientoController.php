<?php

declare(strict_types=1);

namespace App\Modules\Historial;

use App\Core\Auth;
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

    /**
     * «Descargar para Excel»: un libro con las hojas Resumen, Por producto y Detalle, de lo que muestran los filtros
     * elegidos (período y tipo).
     */
    public function excel(Request $request): Response
    {
        $filtros = $this->filtros($request);
        $nombre = 'Entradas y salidas - ' . MovimientoService::PERIODOS[$filtros['periodo']] . ' - '
            . MovimientoService::TIPOS[$filtros['tipo'] ?? ''] . ' - ' . date('Y-m-d');

        return Response::descarga(
            iconv('UTF-8', 'ASCII//TRANSLIT', $nombre) . '.xlsx',
            $this->movimientos->libroExcel($filtros, (string) (Auth::user()['nombre'] ?? '')),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
    }

    /** @return array{periodo: string, tipo: ?string, pagina: int} */
    private function filtros(Request $request): array
    {
        return MovimientoService::filtros($request->query('periodo'), $request->query('tipo'), $request->query('pagina'));
    }
}
