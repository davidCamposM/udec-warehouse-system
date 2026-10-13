<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;

defined('APP_ROOT') || exit;

/**
 * Fechas: en la base de datos 'aaaa-mm-dd' (y 'aaaa-mm-dd hh:mm:ss'); en pantalla 'dd/mm/aaaa'.
 * La zona horaria (America/Santiago) la fija el arranque del sistema.
 */
final class Fecha
{
    private const DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

    private const MESES = [
        1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    ];

    /** '2026-10-06' → '06/10/2026'; '2026-10-06 14:30:00' → '06/10/2026 14:30'; vacío → ''. */
    public static function mostrar(?string $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        $conHora = strlen($valor) > 10;
        $fecha = DateTimeImmutable::createFromFormat($conHora ? '!Y-m-d H:i:s' : '!Y-m-d', $valor);

        if ($fecha === false) {
            return $valor;
        }

        return $fecha->format($conHora ? 'd/m/Y H:i' : 'd/m/Y');
    }

    /** '2026-10-07' → 'miércoles 7 de octubre de 2026'. Sin argumento, la fecha de hoy. */
    public static function larga(?string $valor = null): string
    {
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', substr($valor ?? self::hoy(), 0, 10));

        if ($fecha === false) {
            return (string) $valor;
        }

        return sprintf(
            '%s %d de %s de %d',
            self::DIAS[(int) $fecha->format('w')],
            (int) $fecha->format('j'),
            self::MESES[(int) $fecha->format('n')],
            (int) $fecha->format('Y')
        );
    }

    /**
     * Fecha escrita por el usuario → 'aaaa-mm-dd'. Acepta '06/10/2026' y '2026-10-06' (lo que envía
     * <input type="date">). Retorna null si la fecha no existe.
     */
    public static function normalizar(string $valor): ?string
    {
        $valor = trim($valor);

        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $valor, $m)) {
            [$dia, $mes, $anio] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $valor, $m)) {
            [$anio, $mes, $dia] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } else {
            return null;
        }

        return checkdate($mes, $dia, $anio) ? sprintf('%04d-%02d-%02d', $anio, $mes, $dia) : null;
    }

    public static function hoy(): string
    {
        return date('Y-m-d');
    }
}
