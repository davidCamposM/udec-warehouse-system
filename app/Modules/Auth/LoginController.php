<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

defined('APP_ROOT') || exit;

final class LoginController
{
    public function show(Request $request): Response
    {
        return Response::view('Auth::login', [
            'titulo' => 'Iniciar sesión',
            'aviso' => Session::getFlash('aviso'),
            'error' => Session::getFlash('error'),
        ], 'acceso');
    }
}
