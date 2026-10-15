<?php

declare(strict_types=1);

namespace App\Modules\Herramientas;

use App\Core\HttpException;
use App\Core\Permission;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;

defined('APP_ROOT') || exit;

/**
 * Lo del encargado: «Préstamos de herramientas». Las solicitudes de los docentes por estado (por entregar, prestadas,
 * atrasadas, con daños o faltas, devueltas); en cada una, entregar las herramientas o rechazarla, extender el plazo y,
 * cuando vuelven, recibirlas anotando cuántas volvieron, en qué estado y qué pasó. Las que volvieron con daños o no
 * volvieron quedan por resolver (se reparó, se repuso o se dio de baja). Las administrativas solo miran.
 */
final class PrestamoController
{
    private SolicitudService $solicitudes;

    public function __construct()
    {
        $this->solicitudes = new SolicitudService();
    }

    /** ?ver= elige el filtro (por entregar, si no viene). */
    public function index(Request $request): Response
    {
        $filtro = SolicitudService::filtro($request->query('ver'));

        return Response::view('Herramientas::prestamos', [
            'titulo' => 'Préstamos de herramientas',
            'filtro' => $filtro,
            'solicitudes' => $this->solicitudes->listar($filtro),
            'conteo' => $this->solicitudes->contar(),
            'estilos' => ['css/pages/herramientas.css'],
        ]);
    }

    /** La solicitud como el formulario en papel, con lo que se puede hacer según su estado. */
    public function show(Request $request): Response
    {
        return Response::view('Herramientas::prestamo', $this->ficha($request) + [
            'titulo' => 'Préstamo de herramientas',
            'puedeAdministrar' => Permission::can('prestamos.administrar'),
            'estilos' => ['css/pages/herramientas.css'],
        ]);
    }

    public function entregar(Request $request): Response
    {
        $id = (int) $this->ficha($request)['solicitud']['id'];
        $this->solicitudes->entregar($id);
        Session::flash('exito', 'Listo. Quedó prestada: al volver las herramientas, tócala y elige «Recibir devolución».');

        return Response::redirect(Url::to("prestamos/{$id}"));
    }

    public function rechazar(Request $request): Response
    {
        $this->solicitudes->rechazar((int) $this->ficha($request)['solicitud']['id'], $request->input('motivo'));
        Session::flash('exito', 'Listo. La solicitud quedó rechazada; el docente lo verá en «Mis solicitudes».');

        return Response::redirect(Url::to('prestamos'));
    }

    /** La devolución: por cada línea, cuántas volvieron y en qué estado. */
    public function recepcion(Request $request): Response
    {
        $ficha = $this->ficha($request);
        if ($ficha['solicitud']['estado'] !== 'prestada') {
            return Response::redirect(Url::to('prestamos/' . $ficha['solicitud']['id']));
        }

        return Response::view('Herramientas::recibir', $ficha + [
            'titulo' => 'Recibir devolución',
            'estilos' => ['css/pages/herramientas.css'],
        ]);
    }

    public function recibir(Request $request): Response
    {
        $id = (int) $this->ficha($request)['solicitud']['id'];
        $this->solicitudes->recibir($id, $request->post());
        Session::flash('exito', 'Listo. La devolución quedó registrada.');

        return Response::redirect(Url::to("prestamos/{$id}"));
    }

    public function extender(Request $request): Response
    {
        $id = (int) $this->ficha($request)['solicitud']['id'];
        $this->solicitudes->extenderPlazo($id, $request->input('fecha_devolucion'));
        Session::flash('exito', 'Listo. Se extendió el plazo: la nueva fecha de recepción quedó en la solicitud.');

        return Response::redirect(Url::to("prestamos/{$id}"));
    }

    /** Una herramienta que volvió con daños o no volvió: cómo se resolvió. */
    public function resolver(Request $request): Response
    {
        $id = (int) $this->ficha($request)['solicitud']['id'];
        $item = (string) $request->param('item');
        $this->solicitudes->resolver($id, ctype_digit($item) ? (int) $item : 0, $request->input('resolucion'));
        Session::flash('exito', 'Listo. Quedó resuelto.');

        return Response::redirect(Url::to("prestamos/{$id}"));
    }

    /**
     * @return array{solicitud: array<string, mixed>, items: list<array<string, mixed>>, atrasada: bool}
     * @throws HttpException 404
     */
    private function ficha(Request $request): array
    {
        $id = (string) $request->param('id');
        $ficha = ctype_digit($id) ? $this->solicitudes->ficha((int) $id) : null;

        return $ficha ?? throw new HttpException(404, 'Esa solicitud no existe.');
    }
}
