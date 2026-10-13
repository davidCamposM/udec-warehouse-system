<?php

declare(strict_types=1);

namespace App\Modules\Sectores;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;

defined('APP_ROOT') || exit;

final class SectorController
{
    private SectorService $sectores;

    public function __construct()
    {
        $this->sectores = new SectorService();
    }

    public function index(Request $request): Response
    {
        return Response::view('Sectores::index', [
            'titulo' => 'Sectores de bodega',
            'sectores' => $this->sectores->todos(),
        ]);
    }

    /**
     * Con ?para=guardar se llegó desde «Registrar producto», y con ?para=nuevo desde «Agregar nuevo producto» (no había
     * sectores): al terminar, se vuelve ahí.
     */
    public function create(Request $request): Response
    {
        return Response::view('Sectores::formulario', [
            'titulo' => 'Agregar sector',
            'sector' => null,
            'para' => self::para($request->query('para')),
        ]);
    }

    public function store(Request $request): Response
    {
        $sector = $this->sectores->crear($request->post());
        Session::flash('exito', "Listo. Se agregó «{$sector['nombre']}» con el código {$sector['codigo']}.");

        return Response::redirect(Url::to(match (self::para($request->input('para'))) {
            'guardar' => 'guardar/sector',
            'nuevo' => 'insumos/nuevo/sector',
            default => 'sectores',
        }));
    }

    public function edit(Request $request): Response
    {
        return Response::view('Sectores::formulario', [
            'titulo' => 'Cambiar nombre del sector',
            'sector' => $this->sector($request),
            'para' => null,
        ]);
    }

    public function update(Request $request): Response
    {
        $sector = $this->sector($request);
        $this->sectores->renombrar((int) $sector['id'], $request->post());
        Session::flash('exito', 'Listo. Se guardó el nombre.');

        return Response::redirect(Url::to('sectores'));
    }

    public function confirmDeactivate(Request $request): Response
    {
        $sector = $this->sector($request);

        if ((int) $sector['activo'] !== 1) {
            return Response::redirect(Url::to('sectores'));
        }

        return Response::view('Sectores::desactivar', ['titulo' => 'Desactivar sector', 'sector' => $sector]);
    }

    public function deactivate(Request $request): Response
    {
        $sector = $this->sector($request);
        $this->sectores->cambiarActivo((int) $sector['id'], false);
        Session::flash('exito', "Se desactivó «{$sector['nombre']}». Ya no aparece al registrar productos.");

        return Response::redirect(Url::to('sectores'));
    }

    public function activate(Request $request): Response
    {
        $sector = $this->sector($request);
        $this->sectores->cambiarActivo((int) $sector['id'], true);
        Session::flash('exito', "Listo. «{$sector['nombre']}» está activo otra vez.");

        return Response::redirect(Url::to('sectores'));
    }

    /** @return array<string, mixed> el sector del {id} de la ruta */
    private function sector(Request $request): array
    {
        $id = (string) $request->param('id');
        $sector = ctype_digit($id) ? $this->sectores->buscarPorId((int) $id) : null;

        return $sector ?? throw new HttpException(404, 'Ese sector no existe.');
    }

    /** A dónde volver después de crear: solo se acepta 'guardar'. */
    private static function para(mixed $valor): ?string
    {
        return in_array($valor, ['guardar', 'nuevo'], true) ? $valor : null;
    }
}
