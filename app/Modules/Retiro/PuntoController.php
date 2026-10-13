<?php

declare(strict_types=1);

namespace App\Modules\Retiro;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;

defined('APP_ROOT') || exit;

/** Lo que hace el encargado: habilitar el computador de la bodega como punto de retiro y ver los equipos habilitados. */
final class PuntoController
{
    private EstacionService $estaciones;

    public function __construct()
    {
        $this->estaciones = new EstacionService();
    }

    public function index(Request $request): Response
    {
        return Response::view('Retiro::punto', [
            'titulo' => 'Punto de retiro',
            'este' => $this->estaciones->actual(),
            'equipos' => $this->estaciones->activas(),
            'estilos' => ['css/pages/punto.css'],
        ]);
    }

    /** Habilita este computador y abre el punto de retiro. Por seguridad, se cierra la sesión del encargado. */
    public function habilitar(Request $request): Response
    {
        $this->estaciones->habilitar($request->input('nombre'), (int) Auth::id());
        Auth::logout();
        Session::flash('exito', 'Listo. Este computador quedó habilitado como punto de retiro. Por seguridad se cerró tu sesión.');

        return Response::redirect(Url::to('retiro'));
    }

    public function confirmDisable(Request $request): Response
    {
        $equipo = $this->equipo($request);
        if ($equipo === null) {
            return Response::redirect(Url::to('punto-de-retiro'));
        }

        return Response::view('Retiro::deshabilitar', ['titulo' => 'Deshabilitar equipo', 'equipo' => $equipo]);
    }

    public function disable(Request $request): Response
    {
        $equipo = $this->equipo($request);
        if ($equipo !== null) {
            $this->estaciones->deshabilitar((int) $equipo['id']);
            Session::flash('exito', "Se deshabilitó «{$equipo['nombre']}». Ya no funciona como punto de retiro.");
        }

        return Response::redirect(Url::to('punto-de-retiro'));
    }

    /** @return array<string, mixed>|null el equipo activo del {id} de la ruta */
    private function equipo(Request $request): ?array
    {
        $id = (int) $request->param('id');

        foreach ($this->estaciones->activas() as $equipo) {
            if ((int) $equipo['id'] === $id) {
                return $equipo;
            }
        }

        return null;
    }
}
