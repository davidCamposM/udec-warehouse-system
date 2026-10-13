<?php

declare(strict_types=1);

namespace App\Support;

defined('APP_ROOT') || exit;

/**
 * Números en pantalla con el formato chileno: punto de miles y coma decimal.
 */
final class Numero
{
    /** Número con punto de miles: 12 → '12'; 1500 → '1.500'. Si trae decimales, con coma: '1.50' → '1,5'. */
    public static function cantidad(int|float|string|null $valor): string
    {
        $numero = (float) $valor;
        $texto = number_format($numero, 2, ',', '.');

        return rtrim(rtrim($texto, '0'), ',');
    }

    /** Cantidad de un producto: 1 → '1 unidad'; 12 → '12 unidades'; 1500 → '1.500 unidades'. Todo se cuenta por unidad. */
    public static function unidades(int|float|string|null $valor): string
    {
        return self::cantidad($valor) . ((float) $valor === 1.0 ? ' unidad' : ' unidades');
    }
}
