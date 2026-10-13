<?php

declare(strict_types=1);

namespace App\Modules\Retiro;

use App\Core\Config;
use App\Core\Session;

defined('APP_ROOT') || exit;

/**
 * Quién está en el punto de retiro, qué está haciendo y lo que lleva. Vive en la sesión del navegador del equipo. La
 * identificación se cierra al confirmar un retiro, al terminar o tras un rato sin actividad.
 *
 * modo: 'retiro' (lo que se lleva) o 'entrada' (lo que llegó; solo auxiliares). Un auxiliar parte sin modo y lo elige
 * en la pantalla; el resto parte retirando.
 */
final class Carrito
{
    private const CLAVE = 'retiro';

    /** En esta petición se cerró una identificación por falta de actividad (para avisarlo en la misma pantalla). */
    private static bool $vencido = false;

    /** @param array{id: int, nombre: string, tipo: string} $persona */
    public static function identificar(array $persona, int $estacionId): void
    {
        Session::regenerate();
        Session::put(self::CLAVE, [
            'persona' => ['id' => (int) $persona['id'], 'nombre' => (string) $persona['nombre'], 'tipo' => (string) $persona['tipo']],
            'estacion_id' => $estacionId,
            'modo' => $persona['tipo'] === 'auxiliar' ? null : 'retiro',
            'items' => [],
            'actividad' => time(),
        ]);
    }

    /** Elige qué hace la persona y vacía la lista. Solo un auxiliar puede registrar entradas. */
    public static function elegirModo(string $modo): bool
    {
        $carrito = Session::get(self::CLAVE);
        $permitido = $modo === 'retiro' || ($modo === 'entrada' && ($carrito['persona']['tipo'] ?? '') === 'auxiliar');

        if (!is_array($carrito) || !$permitido) {
            return false;
        }

        Session::put(self::CLAVE, ['modo' => $modo, 'items' => [], 'actividad' => time()] + $carrito);

        return true;
    }

    /**
     * El carrito en curso, si la persona sigue identificada en este equipo y no pasó el tiempo sin actividad.
     *
     * @return array{persona: array{id: int, nombre: string, tipo: string}, estacion_id: int, modo: ?string, items: array<int, int>,
     *     actividad: int}|null
     */
    public static function actual(int $estacionId): ?array
    {
        $carrito = Session::get(self::CLAVE);

        if (!is_array($carrito) || $carrito['estacion_id'] !== $estacionId) {
            return null;
        }

        if (time() - $carrito['actividad'] > self::inactividad()) {
            self::terminar();
            self::$vencido = true;

            return null;
        }

        return $carrito;
    }

    /** @param array<int, int> $items insumo_id => cantidad */
    public static function guardarItems(array $items): void
    {
        $carrito = Session::get(self::CLAVE);

        if (is_array($carrito)) {
            Session::put(self::CLAVE, ['items' => $items, 'actividad' => time()] + $carrito);
        }
    }

    /** La persona sigue ahí (tocó la pantalla o escaneó): se renueva el tiempo. */
    public static function renovar(): void
    {
        self::guardarItems(Session::get(self::CLAVE)['items'] ?? []);
    }

    public static function vencido(): bool
    {
        return self::$vencido;
    }

    public static function terminar(): void
    {
        Session::forget(self::CLAVE);
    }

    /** Segundos sin actividad tras los que se cierra la identificación. */
    public static function inactividad(): int
    {
        return max(30, (int) Config::get('retiro.inactividad_segundos', 90));
    }
}
