<?php

declare(strict_types=1);

namespace App\Modules\Insumos;

use App\Core\Session;

defined('APP_ROOT') || exit;

/**
 * El producto que se está actualizando, entre la etapa 1 y la 2 de «Actualizar cantidad». Vive en la sesión hasta que
 * se actualiza (lo contado y el motivo llegan con el formulario de la etapa 2).
 */
final class Ajuste
{
    private const CLAVE = 'ajustar_cantidad';

    public static function empezar(int $insumoId): void
    {
        Session::put(self::CLAVE, ['insumo_id' => $insumoId]);
    }

    /** @return array{insumo_id: int}|null */
    public static function actual(): ?array
    {
        $ajuste = Session::get(self::CLAVE);

        return is_array($ajuste) ? $ajuste : null;
    }

    public static function terminar(): void
    {
        Session::forget(self::CLAVE);
    }
}
