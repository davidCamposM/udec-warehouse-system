<?php

declare(strict_types=1);

namespace App\Core;

use App\Support\Fecha;
use App\Support\Rut;
use LogicException;

defined('APP_ROOT') || exit;

/**
 * Validación y normalización de datos de formularios.
 *
 *   $datos = Validator::validate($request->post(), [
 *       'nombre'   => 'requerido|max:120',
 *       'cantidad' => 'requerido|entero|min:1',
 *       'rut'      => 'requerido|rut',
 *   ]);
 *
 * Reglas: requerido, entero, numero, min:n, max:n, email, fecha, rut, codigo, en:a,b,c.
 * min/max limitan el valor si el campo es entero o numero; si no, el largo del texto.
 */
final class Validator
{
    /**
     * Retorna solo los campos con reglas, ya normalizados (entero → int, fecha → 'aaaa-mm-dd',
     * rut → '12345678-K', codigo → mayúsculas). Los campos opcionales vacíos quedan en null.
     *
     * @param array<string, mixed>  $datos
     * @param array<string, string> $reglas
     * @return array<string, mixed>
     * @throws ValidationException con un mensaje por campo
     */
    public static function validate(array $datos, array $reglas): array
    {
        $limpios = [];
        $errores = [];

        foreach ($reglas as $campo => $definicion) {
            $lista = explode('|', $definicion);
            $valor = $datos[$campo] ?? null;

            if (is_int($valor) || is_float($valor)) {
                $valor = (string) $valor;
            }

            if ($valor === null || (is_string($valor) && trim($valor) === '')) {
                if (in_array('requerido', $lista, true)) {
                    $errores[$campo] = 'Este campo es obligatorio.';
                } else {
                    $limpios[$campo] = null;
                }
                continue;
            }

            if (!is_string($valor)) {
                $errores[$campo] = 'Valor inválido.';
                continue;
            }

            $valor = trim($valor);
            $esNumero = in_array('entero', $lista, true) || in_array('numero', $lista, true);

            foreach ($lista as $regla) {
                [$nombre, $argumento] = array_pad(explode(':', $regla, 2), 2, '');
                $error = self::aplicar($nombre, $argumento, $valor, $esNumero);

                if ($error !== null) {
                    $errores[$campo] = $error;
                    continue 2;
                }
            }

            $limpios[$campo] = $valor;
        }

        if ($errores !== []) {
            throw new ValidationException($errores);
        }

        return $limpios;
    }

    /** Aplica una regla; puede normalizar $valor. Retorna el mensaje de error o null. */
    private static function aplicar(string $regla, string $argumento, mixed &$valor, bool $esNumero): ?string
    {
        switch ($regla) {
            case 'requerido':
                return null;

            case 'entero':
                if (!preg_match('/^-?\d+$/', (string) $valor)) {
                    return 'Debe ser un número entero.';
                }
                $valor = (int) $valor;
                return null;

            case 'numero':
                // Se acepta coma decimal (1,5). Se mantiene como texto para columnas DECIMAL.
                $normalizado = str_replace(',', '.', (string) $valor);
                if (!preg_match('/^-?\d+(\.\d+)?$/', $normalizado)) {
                    return 'Debe ser un número.';
                }
                $valor = $normalizado;
                return null;

            case 'min':
                if ($esNumero) {
                    return (float) $valor < (float) $argumento ? "Debe ser al menos {$argumento}." : null;
                }
                return mb_strlen((string) $valor) < (int) $argumento
                    ? "Debe tener al menos {$argumento} caracteres."
                    : null;

            case 'max':
                if ($esNumero) {
                    return (float) $valor > (float) $argumento ? "No puede ser mayor que {$argumento}." : null;
                }
                return mb_strlen((string) $valor) > (int) $argumento
                    ? "No puede superar los {$argumento} caracteres."
                    : null;

            case 'email':
                if (filter_var($valor, FILTER_VALIDATE_EMAIL) === false) {
                    return 'Correo electrónico inválido.';
                }
                $valor = mb_strtolower((string) $valor);
                return null;

            case 'fecha':
                $fecha = Fecha::normalizar((string) $valor);
                if ($fecha === null) {
                    return 'Fecha inválida. Usa el formato dd/mm/aaaa.';
                }
                $valor = $fecha;
                return null;

            case 'rut':
                if (!Rut::esValido((string) $valor)) {
                    return 'RUT inválido.';
                }
                $valor = Rut::normalizar((string) $valor);
                return null;

            case 'codigo':
                $valor = strtoupper((string) $valor);
                return preg_match('/^[A-Z0-9]+$/', $valor)
                    ? null
                    : 'El código solo puede tener letras mayúsculas y números.';

            case 'en':
                return in_array((string) $valor, explode(',', $argumento), true) ? null : 'Opción inválida.';

            default:
                throw new LogicException("Regla de validación desconocida: {$regla}");
        }
    }
}
