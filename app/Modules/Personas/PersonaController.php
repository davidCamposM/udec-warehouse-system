<?php

declare(strict_types=1);

namespace App\Modules\Personas;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;

defined('APP_ROOT') || exit;

final class PersonaController
{
    private PersonaService $personas;

    public function __construct()
    {
        $this->personas = new PersonaService();
    }

    public function index(Request $request): Response
    {
        return Response::view('Personas::index', [
            'titulo' => 'Personas autorizadas',
            'personas' => $this->personas->todas(),
            'estilos' => ['css/pages/personas.css'],
        ]);
    }

    public function create(Request $request): Response
    {
        return Response::view('Personas::formulario', ['titulo' => 'Agregar persona', 'persona' => null]);
    }

    public function store(Request $request): Response
    {
        $persona = $this->personas->crear($request->post());
        Session::flash('exito', "Listo. Se agregó a {$persona['nombre']} con la credencial {$persona['codigo']}.");

        return Response::redirect(Url::to('personas'));
    }

    public function edit(Request $request): Response
    {
        return Response::view('Personas::formulario', ['titulo' => 'Editar persona', 'persona' => $this->persona($request)]);
    }

    public function update(Request $request): Response
    {
        $persona = $this->persona($request);
        $this->personas->editar((int) $persona['id'], $request->post());
        Session::flash('exito', 'Listo. Se guardaron los cambios.');

        return Response::redirect(Url::to('personas'));
    }

    public function confirmDeactivate(Request $request): Response
    {
        $persona = $this->persona($request);

        if ((int) $persona['activo'] !== 1) {
            return Response::redirect(Url::to('personas'));
        }

        return Response::view('Personas::desactivar', ['titulo' => 'Desactivar persona', 'persona' => $persona]);
    }

    public function deactivate(Request $request): Response
    {
        $persona = $this->persona($request);
        $this->personas->cambiarActivo((int) $persona['id'], false);
        Session::flash('exito', "Se desactivó a {$persona['nombre']}. Su credencial ya no sirve en el punto de retiro.");

        return Response::redirect(Url::to('personas'));
    }

    public function activate(Request $request): Response
    {
        $persona = $this->persona($request);
        $this->personas->cambiarActivo((int) $persona['id'], true);
        Session::flash('exito', "Listo. {$persona['nombre']} puede retirar otra vez.");

        return Response::redirect(Url::to('personas'));
    }

    /** @return array<string, mixed> la persona del {id} de la ruta */
    private function persona(Request $request): array
    {
        $id = (string) $request->param('id');
        $persona = ctype_digit($id) ? $this->personas->buscarPorId((int) $id) : null;

        return $persona ?? throw new HttpException(404, 'Esa persona no existe.');
    }
}
