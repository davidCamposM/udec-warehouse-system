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
    public function __construct(private readonly Router $router)
    {
    }

    public function handle(Request $request): Response
    {
        try {
            Session::start(!$request->isBackground());

            $ruta = $this->router->match($request->method(), $request->path());
            $request = $request->withParams($ruta['parametros']);

            $this->autorizar($ruta['permiso']);

            if ($request->method() === 'POST' && !Csrf::verify($request)) {
                throw new HttpException(
                    403,
                    'El formulario venció o no es válido. Recarga la página e inténtalo de nuevo.'
                );
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
            return Response::json(['error' => $e->getMessage(), 'errores' => $e->errors()], 422);
        }

        Session::flash('_errores', $e->errors());
        Session::flash('_old', self::sinCamposSensibles($request->post()));

        return Response::redirect($this->paginaAnterior($request));
    }

    private function respuestaError(HttpException $e, Request $request, ?Throwable $causa = null): Response
    {
        $estado = $e->status();

        // Sin sesión: al login, recordando a dónde quería ir.
        if ($estado === 401 && !$request->wantsJson()) {
            if ($request->method() === 'GET') {
                Session::put('_url_previa', $request->fullUrl());
            }
            Session::flash('aviso', Session::expired()
                ? 'Tu sesión se cerró por inactividad. Vuelve a ingresar.'
                : 'Inicia sesión para continuar.');

            return Response::redirect(Url::to('login'));
        }

        if ($request->wantsJson()) {
            return Response::json(['error' => $e->getMessage()], $estado);
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

        return $anterior !== null && str_starts_with($anterior, Url::base() . '/') ? $anterior : Url::to();
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
