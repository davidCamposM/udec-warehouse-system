<?php

declare(strict_types=1);

namespace App\Core;

defined('APP_ROOT') || exit;

/**
 * Protección CSRF: todo POST debe traer el token de la sesión. App lo verifica antes de
 * llegar al controlador.
 */
final class Csrf
{
    /** Nombre del campo oculto en los formularios. */
    public const CAMPO = '_csrf';

    /** Cabecera que usan las peticiones fetch. */
    public const CABECERA = 'X-CSRF-Token';

    public static function token(): string
    {
        $token = Session::get(self::CAMPO);

        if (!is_string($token)) {
            $token = bin2hex(random_bytes(32));
            Session::put(self::CAMPO, $token);
        }

        return $token;
    }

    public static function verify(Request $request): bool
    {
        $recibido = $request->input(self::CAMPO) ?? $request->header(self::CABECERA);
        $esperado = Session::get(self::CAMPO);

        return is_string($recibido) && is_string($esperado) && hash_equals($esperado, $recibido);
    }

    public static function regenerate(): void
    {
        Session::forget(self::CAMPO);
    }
}
