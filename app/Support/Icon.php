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
        'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'arrow-in' => '<path d="M12 3v12M7 10l5 5 5-5"/><path d="M4 15v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4"/>',
        'arrow-out' => '<path d="M12 15V3M7 8l5-5 5 5"/><path d="M4 15v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'barcode' => '<path d="M4 6v12M7 6v12M10.5 6v12M14 6v12M17 6v12M20 6v12"/>',
        'box' => '<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>',
        'clipboard' => '<rect x="8" y="2" width="8" height="4" rx="1"/>'
            . '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 12h6M9 16h4"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'close' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'edit' => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M13.5 6.5l4 4"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off' => '<path d="M3 3l18 18"/><path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4.2"/>'
            . '<path d="M6.6 6.6C3.9 8.4 2 12 2 12s3.5 7 10 7a10 10 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
        'history' => '<path d="M3 12a9 9 0 1 0 2.6-6.4L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/>',
        'home' => '<path d="M3 10.5L12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/>',
        'id-card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/>'
            . '<path d="M6 16c.6-1.3 1.7-2 3-2s2.4.7 3 2M14 10h4M14 14h3"/>',
        'inbox' => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5h13L22 12v7H2v-7z"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.5v.5"/>',
        'layers' => '<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/>',
        'lock' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'logout' => '<path d="M9 20H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h4"/><path d="M16 16l4-4-4-4M20 12H9"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'map-pin' => '<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
        'minus' => '<path d="M5 12h14"/>',
        'ok' => '<path d="M4.5 12.5l5 5 10-11"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'refresh' => '<path d="M3 12a9 9 0 0 1 15.4-6.4L21 8"/><path d="M21 3v5h-5"/>'
            . '<path d="M21 12a9 9 0 0 1-15.4 6.4L3 16"/><path d="M8 16H3v5"/>',
        'scan' => '<path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3"/>'
            . '<path d="M8 8v8M11 8v8M14 8v8M17 8v8"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.5-4.5"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M2 12h3M19 12h3'
            . 'M4.9 19.1L7 17M17 7l2.1-2.1"/>',
        'swap' => '<path d="M7 7h13l-4-4"/><path d="M17 17H4l4 4"/>',
        'tag' => '<path d="M3 3h8l10 10-8 8L3 11z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'tool' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/>',
        'tree' => '<path d="M12 3l4 6.5h-1.5l3 5h-2l3.5 5H5l3.5-5h-2l3-5H8z"/><path d="M12 19.5V22"/>',
        'users' => '<circle cx="9" cy="8" r="4"/><path d="M1 21v-1a7 7 0 0 1 14 0v1"/>'
            . '<path d="M17 3.5a4 4 0 0 1 0 9M23 21v-1a7 7 0 0 0-5-6.7"/>',
        'x-circle' => '<circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/>',
    ];

    public static function svg(string $nombre, string $clase = 'icon'): string
    {
        $trazos = self::ICONOS[$nombre] ?? throw new InvalidArgumentException("Ícono desconocido: {$nombre}");

        return '<svg class="' . htmlspecialchars($clase, ENT_QUOTES, 'UTF-8') . '" viewBox="0 0 24 24" fill="none"'
            . ' stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $trazos . '</svg>';
    }
}
