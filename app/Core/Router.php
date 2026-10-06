<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use LogicException;

defined('APP_ROOT') || exit;

/**
 * Tabla de rutas. Cada ruta declara obligatoriamente el permiso que exige, para que ninguna
 * pantalla quede abierta por olvido.
 *
 *   $router->get('/insumos/{id}', [InsumoController::class, 'show'], 'insumos.ver');
 */
final class Router
{
    /** @var list<array{metodo: string, patron: string, accion: array{0: class-string, 1: string}|Closure, permiso: string}> */
    private array $rutas = [];

    /**
     * @param array{0: class-string, 1: string}|Closure $accion
     * @param string $permiso Permission::PUBLICO, Permission::AUTENTICADO o un permiso ('insumos.ver')
     */
    public function get(string $ruta, array|Closure $accion, string $permiso): void
    {
        $this->agregar('GET', $ruta, $accion, $permiso);
    }

    /**
     * @param array{0: class-string, 1: string}|Closure $accion
     */
    public function post(string $ruta, array|Closure $accion, string $permiso): void
    {
        $this->agregar('POST', $ruta, $accion, $permiso);
    }

    /**
     * Busca la ruta que corresponde a la petición.
     *
     * @return array{accion: array{0: class-string, 1: string}|Closure, permiso: string, parametros: array<string, string>}
     * @throws HttpException 404 si la ruta no existe; 405 si existe con otro método
     */
    public function match(string $metodo, string $ruta): array
    {
        $metodo = $metodo === 'HEAD' ? 'GET' : $metodo;
        $existeConOtroMetodo = false;

        foreach ($this->rutas as $definicion) {
            if (!preg_match($definicion['patron'], $ruta, $coincidencias)) {
                continue;
            }

            if ($definicion['metodo'] !== $metodo) {
                $existeConOtroMetodo = true;
                continue;
            }

            return [
                'accion' => $definicion['accion'],
                'permiso' => $definicion['permiso'],
                'parametros' => array_filter($coincidencias, 'is_string', ARRAY_FILTER_USE_KEY),
            ];
        }

        throw new HttpException($existeConOtroMetodo ? 405 : 404);
    }

    /**
     * @param array{0: class-string, 1: string}|Closure $accion
     */
    private function agregar(string $metodo, string $ruta, array|Closure $accion, string $permiso): void
    {
        if ($permiso === '') {
            throw new LogicException("La ruta {$metodo} {$ruta} debe declarar un permiso.");
        }

        // '/insumos/{id}' → '#^/insumos/(?P<id>[^/]+)$#'
        $patron = preg_replace_callback(
            '#\{([a-z_][a-z0-9_]*)\}|[^{}]+#i',
            static fn (array $m): string => isset($m[1]) && $m[1] !== ''
                ? '(?P<' . $m[1] . '>[^/]+)'
                : preg_quote($m[0], '#'),
            '/' . trim($ruta, '/')
        );

        $this->rutas[] = [
            'metodo' => $metodo,
            'patron' => '#^' . $patron . '$#',
            'accion' => $accion,
            'permiso' => $permiso,
        ];
    }
}
