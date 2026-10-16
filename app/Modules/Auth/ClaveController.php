<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Core\Validator;
use App\Modules\Usuarios\CuentaService;
use App\Support\Correo;

defined('APP_ROOT') || exit;

/**
 * «¿Primera vez, o olvidaste tu contraseña?» (desde el login, sin sesión): la persona escribe su correo y, si
 * tiene cuenta, le llega un enlace (si el sistema envía correos; si no, se le pide que lo pida al encargado). Con el
 * enlace crea su contraseña y vuelve al login con el correo ya escrito. A quien pide el enlace se le responde siempre
 * lo mismo, tenga cuenta o no.
 */
final class ClaveController
{
    private CuentaService $cuentas;

    public function __construct()
    {
        $this->cuentas = new CuentaService();
    }

    public function pedir(Request $request): Response
    {
        if (Auth::check()) {
            return Response::redirect(Url::to());
        }

        return Response::view('Auth::clave-pedir', [
            'titulo' => 'Crear o recuperar contraseña',
            'lema' => 'Crea o recupera tu contraseña',
            'enviado' => Session::getFlash('enviado'),
            // Sin casilla de correo, el enlace lo da el encargado (en «Usuarios», «Dar enlace»).
            'conCorreo' => Correo::envia(),
        ], 'acceso');
    }

    public function enviar(Request $request): Response
    {
        if (!Correo::envia()) {
            return Response::redirect(Url::to('clave'));
        }

        $datos = Validator::validate($request->post(), ['email' => 'requerido|email|max:190']);
        $this->cuentas->pedirEnlace($datos['email']);
        Session::flash('enviado', $datos['email']);

        return Response::redirect(Url::to('clave'));
    }

    public function formulario(Request $request): Response
    {
        $cuenta = $this->cuentas->cuentaDelEnlace((string) $request->param('codigo'));

        return Response::view('Auth::clave-crear', [
            'titulo' => 'Crea tu contraseña',
            'lema' => $cuenta !== null && (bool) $cuenta['con_clave'] ? 'Crea una contraseña nueva' : 'Crea tu contraseña',
            'cuenta' => $cuenta,
            'codigo' => (string) $request->param('codigo'),
        ], 'acceso');
    }

    public function guardar(Request $request): Response
    {
        $email = $this->cuentas->crearClave(
            (string) $request->param('codigo'),
            $request->input('password'),
            $request->input('password_repetida'),
        );

        Session::flash('exito', 'Listo. Tu contraseña quedó guardada: ya puedes ingresar.');
        Session::flashInput(['email' => $email]);

        return Response::redirect(Url::to('login'));
    }
}
