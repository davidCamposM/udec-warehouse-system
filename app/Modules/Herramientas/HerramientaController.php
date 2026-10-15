<?php

declare(strict_types=1);

namespace App\Modules\Herramientas;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Permission;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;

defined('APP_ROOT') || exit;

/**
 * «Catálogo de herramientas»: lo que tiene la bodega para prestar, por categoría, con cuántas están prestadas ahora.
 * Todos los perfiles lo ven (el docente, para saber qué pedir); el encargado además agrega, edita y elimina.
 */
final class HerramientaController
{
    private HerramientaService $herramientas;

    public function __construct()
    {
        $this->herramientas = new HerramientaService();
    }

    public function index(Request $request): Response
    {
        $administrar = Permission::can('herramientas.administrar');

        return Response::view('Herramientas::catalogo', [
            'titulo' => 'Catálogo de herramientas',
            'grupos' => $this->herramientas->porCategoria(),
            'puedeAdministrar' => $administrar,
            'puedeSolicitar' => Permission::can('herramientas.solicitar') && Auth::perfil() === 'docente',
            'estilos' => ['css/pages/herramientas.css'],
            'scripts' => ['js/modules/herramientas.js'],
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->formulario(null);
    }

    public function store(Request $request): Response
    {
        $nombre = $this->herramientas->crear($request->post());
        Session::flash('exito', "Listo. Se agregó «{$nombre}» al catálogo: ya se puede solicitar.");

        return Response::redirect(Url::to('herramientas'));
    }

    public function edit(Request $request): Response
    {
        return $this->formulario($this->herramienta($request));
    }

    public function update(Request $request): Response
    {
        $herramienta = $this->herramienta($request);
        $this->herramientas->editar((int) $herramienta['id'], $request->post());
        Session::flash('exito', 'Listo. Se guardaron los cambios.');

        return Response::redirect(Url::to('herramientas'));
    }

    public function confirmDelete(Request $request): Response
    {
        return Response::view('Herramientas::eliminar', [
            'titulo' => 'Eliminar herramienta',
            'herramienta' => $this->herramienta($request),
        ]);
    }

    public function delete(Request $request): Response
    {
        $herramienta = $this->herramienta($request);
        $this->herramientas->eliminar((int) $herramienta['id']);
        Session::flash('exito', "Se eliminó «{$herramienta['nombre']}».");

        return Response::redirect(Url::to('herramientas'));
    }

    /** @param array<string, mixed>|null $herramienta null: agregar una nueva */
    private function formulario(?array $herramienta): Response
    {
        return Response::view('Herramientas::formulario', [
            'titulo' => $herramienta === null ? 'Agregar herramienta' : 'Editar herramienta',
            'herramienta' => $herramienta,
            'categorias' => $this->herramientas->categorias(),
        ]);
    }

    /** @return array<string, mixed> la herramienta del {id} de la ruta */
    private function herramienta(Request $request): array
    {
        $id = (string) $request->param('id');
        $herramienta = ctype_digit($id) ? $this->herramientas->buscarPorId((int) $id) : null;

        return $herramienta ?? throw new HttpException(404, 'Esa herramienta no existe.');
    }
}
