<?php

declare(strict_types=1);

namespace App\Modules\Insumos;

use App\Core\Session;

defined('APP_ROOT') || exit;

/**
 * El producto que se está retirando, entre una etapa y otra de «Retirar producto». Vive en la sesión, así no se pierde
 * si la persona recarga la página.
 */
final class Retiro
{
    private const CLAVE = 'retirar_producto';

    public static function empezar(int $insumoId): void
    {
        Session::put(self::CLAVE, ['insumo_id' => $insumoId]);
    }

    /** @return array{insumo_id: int}|null */
    public static function actual(): ?array
    {
        $retiro = Session::get(self::CLAVE);

        return is_array($retiro) ? $retiro : null;
    }

    public static function terminar(): void
    {
        Session::forget(self::CLAVE);
    }
}
