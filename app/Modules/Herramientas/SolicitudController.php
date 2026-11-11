<?php

declare(strict_types=1);

namespace App\Modules\Herramientas;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;

defined('APP_ROOT') || exit;

/**
 * Lo del docente: «Solicitar herramientas» (el formulario en papel, en una ventana sobre el inicio) y «Mis solicitudes»
 * (lo que pidió y en qué va cada solicitud).
 */
final class SolicitudController
{
    private SolicitudService $solicitudes;

    public function __construct()
    {
        $this->solicitudes = new SolicitudService();
    }

    /** El formulario o, recién enviado, la solicitud enviada. */
    public function formulario(Request $request): Response
    {
        $enviada = Session::getFlash('solicitud_enviada');
        if (is_int($enviada) && ($ficha = $this->solicitudes->ficha($enviada)) !== null) {
            return $this->pagina('listo', $ficha);
        }

        return $this->pagina('formulario', [
            // Solo las que tiene la bodega: de las sin unidades no hay nada que pedir.
            'grupos' => (new HerramientaService())->porCategoria(soloConUnidades: true),
            'nombre' => (string) (Auth::user()['nombre'] ?? ''),
        ]);
    }

    public function enviar(Request $request): Response
    {
        $id = $this->solicitudes->solicitar($request->post());
        Session::flash('solicitud_enviada', $id);

        return Response::redirect(Url::to('herramientas/solicitar'));
    }

    public function mias(Request $request): Response
    {
        return Response::view('Herramientas::mis-solicitudes', [
            'titulo' => 'Mis solicitudes',
            'solicitudes' => $this->solicitudes->listar('todas', (int) Auth::id()),
            'estilos' => ['css/pages/herramientas.css'],
        ]);
    }

    /** Una solicitud propia, como el formulario en papel. */
    public function ver(Request $request): Response
    {
        $ficha = $this->propia($request);

        return Response::view('Herramientas::solicitud', $ficha + [
            'titulo' => 'Solicitud de herramientas',
            'estilos' => ['css/pages/herramientas.css'],
        ]);
    }

    public function cancelar(Request $request): Response
    {
        $ficha = $this->propia($request);
        $this->solicitudes->cancelar((int) $ficha['solicitud']['id']);
        Session::flash('exito', 'Listo. Cancelaste la solicitud.');

        return Response::redirect(Url::to('herramientas/mis-solicitudes'));
    }

    // ---------- Apoyo ----------

    /**
     * «Solicitar herramientas» es una página propia, no una ventana sobre el inicio: el formulario es largo (decenas
     * de herramientas) y en una ventana quedaba con un desplazamiento dentro de otro. Así se desplaza solo la página.
     *
     * @param array<string, mixed> $datos los de la vista Herramientas::solicitar/{$vista}
     */
    private function pagina(string $vista, array $datos): Response
    {
        return Response::view("Herramientas::solicitar/{$vista}", $datos + [
            'titulo' => 'Solicitar herramientas',
            // Más ancha que las demás páginas: caben cuatro columnas de herramientas y la lista queda en una plana.
            'amplia' => true,
            'estilos' => ['css/pages/herramientas.css'],
            'scripts' => ['js/modules/herramientas.js'],
        ]);
    }

    /**
     * @return array{solicitud: array<string, mixed>, items: list<array<string, mixed>>, atrasada: bool}
     * @throws HttpException 404 si no existe o es de otro docente
     */
    private function propia(Request $request): array
    {
        $id = (string) $request->param('id');
        $ficha = ctype_digit($id) ? $this->solicitudes->ficha((int) $id) : null;

        if ($ficha === null || (int) $ficha['solicitud']['docente_id'] !== (int) Auth::id()) {
            throw new HttpException(404, 'Esa solicitud no existe.');
        }

        return $ficha;
    }
}
