<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

defined('APP_ROOT') || exit;

/**
 * Íconos SVG en línea (24×24, de trazo). En las vistas se usan con el helper: icon('check').
 * Para agregar uno, se suma su trazo a ICONOS.
 */
final class Icon
{
    private const ICONOS = [
        'alert' => '<path d="M12 3l10 18H2z"/><path d="M12 10v5M12 18v.5"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off' => '<path d="M3 3l18 18"/><path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4.2"/>'
            . '<path d="M6.6 6.6C3.9 8.4 2 12 2 12s3.5 7 10 7a10 10 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.5v.5"/>',
        'scan' => '<path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3"/>'
            . '<path d="M8 8v8M11 8v8M14 8v8M17 8v8"/>',
        'tree' => '<path d="M12 3l4 6.5h-1.5l3 5h-2l3.5 5H5l3.5-5h-2l3-5H8z"/><path d="M12 19.5V22"/>',
    ];

    public static function svg(string $nombre, string $clase = 'icon'): string
    {
        $trazos = self::ICONOS[$nombre] ?? throw new InvalidArgumentException("Ícono desconocido: {$nombre}");

        return '<svg class="' . htmlspecialchars($clase, ENT_QUOTES, 'UTF-8') . '" viewBox="0 0 24 24" fill="none"'
            . ' stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $trazos . '</svg>';
    }
}
