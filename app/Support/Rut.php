<?php

declare(strict_types=1);

namespace App\Support;

defined('APP_ROOT') || exit;

/**
 * RUT chileno. En la base de datos se guarda normalizado ('12345678-K'); en pantalla se muestra
 * con puntos ('12.345.678-K').
 */
final class Rut
{
    /** '12.345.678-k' → '12345678-K'. Retorna null si no tiene forma de RUT (no revisa el dígito verificador). */
    public static function normalizar(string $rut): ?string
    {
        $limpio = strtoupper(str_replace(['.', '-', ' '], '', trim($rut)));

        if (!preg_match('/^(\d{6,8})([0-9K])$/', $limpio, $m)) {
            return null;
        }

        return $m[1] . '-' . $m[2];
    }

    public static function esValido(string $rut): bool
    {
        $normalizado = self::normalizar($rut);

        if ($normalizado === null) {
            return false;
        }

        [$cuerpo, $digito] = explode('-', $normalizado);

        return self::digitoVerificador($cuerpo) === $digito;
    }

    /** '12345678-K' → '12.345.678-K'. Si no es un RUT, lo retorna tal cual. */
    public static function formatear(string $rut): string
    {
        $normalizado = self::normalizar($rut);

        if ($normalizado === null) {
            return $rut;
        }

        [$cuerpo, $digito] = explode('-', $normalizado);

        return number_format((int) $cuerpo, 0, '', '.') . '-' . $digito;
    }

    /** Dígito verificador por módulo 11. */
    public static function digitoVerificador(string $cuerpo): string
    {
        $suma = 0;
        $factor = 2;

        for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
            $suma += (int) $cuerpo[$i] * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }

        return match ($resto = 11 - $suma % 11) {
            11 => '0',
            10 => 'K',
            default => (string) $resto,
        };
    }
}
