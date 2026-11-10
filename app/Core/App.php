<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use LogicException;
use Throwable;

defined('APP_ROOT') || exit;

/**
 * Atiende una petición de principio a fin:
 * sesión → ruta → permiso → CSRF → controlador → respuesta.
 * También convierte cualquier error en una respuesta adecuada (HTML o JSON).
 */
final class App
{
    /** El código estable de cada estado HTTP en las respuestas de error de la API (Response::error). */
    private const CODIGOS_HTTP = [
        400 => 'bad_request',
        403 => 'forbidden',
        404 => 'not_found',
        405 => 'method_not_allowed',
        419 => 'session_expired',
        429 => 'too_many_requests',
        500 => 'server_error',
        503 => 'service_unavailable',
    ];

    public function __construct(private readonly Router $router)
    {
    }

    public function handle(Request $request): Response
    {
        try {
            Session::start(!$request->isBackground());
            Auth::refresh();

            $ruta = $this->router->match($request->method(), $request->path());
            $request = $request->withParams($ruta['parametros']);
            View::share('rutaActual', $request->path());

            $this->autorizar($ruta['permiso']);

            if ($request->method() === 'POST' && !Csrf::verify($request)) {
                return $this->formularioVencido($request);
            }

            return $this->ejecutar($ruta['accion'], $request);
        } catch (ValidationException $e) {
            return $this->respuestaValidacion($e, $request);
        } catch (HttpException $e) {
            return $this->respuestaError($e, $request);
        } catch (Throwable $e) {
            Logger::exception($e);

            return $this->respuestaError(new HttpException(500), $request, $e);
        }
    }

    private function autorizar(string $permiso): void
    {
        if ($permiso === Permission::PUBLICO) {
            return;
        }
        if (!Auth::check()) {
            throw new HttpException(401);
        }
        if (!Permission::can($permiso)) {
            throw new HttpException(403);
        }
    }

    /** @param array{0: class-string, 1: string}|Closure $accion */
    private function ejecutar(array|Closure $accion, Request $request): Response
    {
        if (is_array($accion)) {
            [$clase, $metodo] = $accion;
            $accion = [new $clase(), $metodo];
        }

        $respuesta = $accion($request);

        if (!$respuesta instanceof Response) {
            throw new LogicException("La acción de la ruta {$request->path()} debe retornar un Response.");
        }

        return $respuesta;
    }

    /** Vuelve al formulario con los errores y lo que el usuario ya había escrito. */
    private function respuestaValidacion(ValidationException $e, Request $request): Response
    {
        if ($request->wantsJson()) {
            return Response::error('validation_failed', $e->getMessage(), 422, ['fields' => $e->errors()]);
        }

        Session::flash('_errores', $e->errors());
        Session::flashInput(self::sinCamposSensibles($request->post()));

        return Response::redirect($this->paginaAnterior($request));
    }

    /**
     * El token CSRF no coincide: la página estuvo abierta más que la sesión, se inició sesión en
     * otra pestaña o el envío viene de otro sitio. En vez de una página de error sin salida, se
     * vuelve al formulario con el aviso (sin rellenar nada con lo recibido).
     */
    private function formularioVencido(Request $request): Response
    {
        if ($request->wantsJson()) {
            return Response::error(
                'csrf_invalid',
                'El formulario venció o no es válido. Recarga la página e inténtalo de nuevo.',
                403
            );
        }

        Session::flash('error', 'El formulario venció. Inténtalo de nuevo.');

        return Response::redirect($this->paginaAnterior($request));
    }

    /** Página de error (o JSON) según el estado HTTP. Sin sesión: al login, con el aviso que corresponda. */
    private function respuestaError(HttpException $e, Request $request, ?Throwable $causa = null): Response
    {
        $estado = $e->status();

        // Sin sesión: al login, recordando a dónde quería ir.
        if ($estado === 401) {
            $aviso = match (true) {
                Session::expired() => 'Tu sesión se cerró por inactividad. Vuelve a ingresar.',
                Auth::revoked() => 'Tu cuenta fue desactivada. Habla con el encargado de bodega.',
                // La raíz es la entrada natural al sistema: ahí el login no necesita explicación.
                $request->wantsJson(), $request->path() === '/' => null,
                default => 'Inicia sesión para continuar.',
            };
            if ($aviso !== null) {
                Session::flash('aviso', $aviso);
            }

            // Una petición de JavaScript (como la actualización del panel) recibe la dirección del
            // login para llevar ahí al usuario, donde verá el aviso.
            if ($request->wantsJson()) {
                return Response::error('unauthenticated', $e->getMessage(), 401, ['login' => Url::to('login')]);
            }

            if ($request->method() === 'GET') {
                Session::put('_url_previa', $request->fullUrl());
            }

            return Response::redirect(Url::to('login'));
        }

        if ($request->wantsJson()) {
            return Response::error(self::CODIGOS_HTTP[$estado] ?? 'http_error', $e->getMessage(), $estado);
        }

        try {
            return Response::view('errores/http', [
                'titulo' => $e->title(),
                'codigo' => $estado,
                'mensaje' => $e->getMessage(),
                'detalle' => $causa !== null && Config::get('app.debug') ? (string) $causa : null,
            ], 'invitado', $estado);
        } catch (Throwable $falla) {
            // Si ni la página de error se puede mostrar, se responde en texto plano.
            Logger::exception($falla);

            return new Response(
                $e->title() . ': ' . $e->getMessage(),
                $estado,
                ['Content-Type' => 'text/plain; charset=UTF-8']
            );
        }
    }

    /** La página anterior si pertenece al sistema; si no, el inicio. */
    private function paginaAnterior(Request $request): string
    {
        $anterior = $request->referer();

        return $anterior !== null && Url::isInternal($anterior) ? $anterior : Url::to();
    }

    /**
     * Quita el token y las contraseñas de lo que se guarda para rellenar el formulario.
     *
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    private static function sinCamposSensibles(array $datos): array
    {
        return array_filter(
            $datos,
            static fn (int|string $campo): bool => !preg_match('/^_csrf$|clave|contrase|password/i', (string) $campo),
            ARRAY_FILTER_USE_KEY
        );
    }
}
