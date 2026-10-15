<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Core\Validator;

defined('APP_ROOT') || exit;

final class LoginController
{
    public function show(Request $request): Response
    {
        if (Auth::check()) {
            return Response::redirect(Url::to());
        }

        return Response::view('Auth::login', [
            'titulo' => 'Iniciar sesión',
            'aviso' => Session::getFlash('aviso'),
            'error' => Session::getFlash('error'),
            'exito' => Session::getFlash('exito'),
        ], 'acceso');
    }

    public function login(Request $request): Response
    {
        if (Auth::check()) {
            return Response::redirect(Url::to());
        }

        $datos = Validator::validate($request->post(), [
            'email' => 'requerido|email|max:190',
            'password' => 'requerido',
        ]);
        // Sin recortar: los espacios al inicio o al final son parte de la contraseña.
        $password = (string) $request->input('password');

        try {
            $resultado = (new LoginService())->autenticar($datos['email'], $password, $request->ip());
        } catch (LoginException $e) {
            Session::flash('error', $e->getMessage());
            Session::flashInput(['email' => $datos['email']]);

            return Response::redirect(Url::to('login'));
        }

        Auth::login($resultado['usuario']);
        Session::flash('bienvenida', ['acceso_anterior' => $resultado['acceso_anterior']]);

        // Vuelve a la página que se quería abrir antes del login, si es de este sistema.
        $destino = Session::pull('_url_previa');

        return Response::redirect(is_string($destino) && Url::isInternal($destino) ? $destino : Url::to());
    }

    /** «Salir», o la ventana «¿Sigues ahí?» (sesion.js) sin respuesta: motivo=inactividad cambia el aviso del login. */
    public function logout(Request $request): Response
    {
        Auth::logout();

        if ($request->input('motivo') === 'inactividad') {
            Session::flash('aviso', 'Tu sesión se cerró por inactividad. Vuelve a ingresar.');
        } else {
            Session::flash('exito', 'Cerraste sesión correctamente.');
        }

        return Response::redirect(Url::to('login'));
    }

    /**
     * La persona sigue usando el sistema (sesion.js lo avisa mientras hay actividad, y al responder «Sí» en «¿Sigues
     * ahí?»). Basta con llegar aquí: la petición renueva la actividad de la sesión.
     */
    public function seguir(Request $request): Response
    {
        return Response::json(['ok' => true]);
    }
}
