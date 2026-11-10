<?php

declare(strict_types=1);

namespace App\Modules\Usuarios;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Core\ValidationException;

defined('APP_ROOT') || exit;

/**
 * «Usuarios» (el encargado): las cuentas de docentes y administrativas. Se agrega a cada persona con su nombre, correo
 * y perfil, y el sistema muestra el enlace para que cree su contraseña, que el encargado le comparte.
 */
final class UsuarioController
{
    private CuentaService $cuentas;

    public function __construct()
    {
        $this->cuentas = new CuentaService();
    }

    public function index(Request $request): Response
    {
        $filtro = $request->query('ver');
        $filtro = is_string($filtro) && isset(CuentaService::FILTROS[$filtro]) ? $filtro : 'todas';

        return Response::view('Usuarios::index', [
            'titulo' => 'Usuarios',
            'filtro' => $filtro,
            'cuentas' => $this->cuentas->listar($filtro),
            'conteo' => $this->cuentas->contar(),
            'estilos' => ['css/pages/usuarios.css'],
        ]);
    }

    // ---------- Agregar a una persona ----------

    public function agregar(Request $request): Response
    {
        return Response::view('Usuarios::agregar', ['titulo' => 'Agregar persona']);
    }

    /** Agrega a la persona y muestra el enlace para que cree su contraseña. */
    public function guardar(Request $request): Response
    {
        Session::flash('enlace', $this->cuentas->agregar($request->post()) + ['agregada' => true]);

        return Response::redirect(Url::to('usuarios/enlace'));
    }

    /**
     * El enlace para crear la contraseña, recién creado (al agregar a la persona o con «Dar enlace»), con «Copiar» para
     * compartirlo. Se muestra una sola vez: al recargar, se vuelve a la lista.
     */
    public function mostrarEnlace(Request $request): Response
    {
        $enlace = Session::getFlash('enlace');
        if (!is_array($enlace)) {
            return Response::redirect(Url::to('usuarios'));
        }

        return Response::view('Usuarios::enlace', [
            'titulo' => 'Enlace para la contraseña',
            'enlace' => $enlace,
            'estilos' => ['css/pages/usuarios.css'],
            'scripts' => ['js/modules/usuarios.js'],
        ]);
    }

    // ---------- Una cuenta ----------

    public function edit(Request $request): Response
    {
        return Response::view('Usuarios::editar', ['titulo' => 'Editar persona', 'cuenta' => $this->cuenta($request)]);
    }

    public function update(Request $request): Response
    {
        $cuenta = $this->cuenta($request);
        $this->cuentas->editar((int) $cuenta['id'], $request->post());
        Session::flash('exito', 'Listo. Se guardaron los cambios.');

        return Response::redirect(Url::to('usuarios'));
    }

    /** «Dar enlace»: uno nuevo para crear la contraseña (la primera vez, o si la olvidó). */
    public function enlace(Request $request): Response
    {
        $cuenta = $this->cuenta($request);
        Session::flash('enlace', $this->cuentas->enlaceParaCompartir((int) $cuenta['id']) + ['agregada' => false]);

        return Response::redirect(Url::to('usuarios/enlace'));
    }

    public function confirmDelete(Request $request): Response
    {
        return Response::view('Usuarios::eliminar', ['titulo' => 'Eliminar persona', 'cuenta' => $this->cuenta($request)]);
    }

    public function delete(Request $request): Response
    {
        $cuenta = $this->cuenta($request);
        $this->cuentas->eliminar((int) $cuenta['id']);
        Session::flash('exito', "Se eliminó a {$cuenta['nombre']}: ya no puede entrar al sistema.");

        return Response::redirect(Url::to('usuarios'));
    }

    /** @return array<string, mixed> la cuenta del {id} de la ruta (docente, administrativa u operador de bodega) */
    private function cuenta(Request $request): array
    {
        $id = (string) $request->param('id');
        $cuenta = ctype_digit($id) ? $this->cuentas->buscar((int) $id) : null;

        return $cuenta ?? throw new HttpException(404, 'Esa persona no existe.');
    }
}
