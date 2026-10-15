<?php

declare(strict_types=1);

namespace App\Modules\Panel;

use App\Core\Database;
use App\Modules\Herramientas\SolicitudRepository;

defined('APP_ROOT') || exit;

/**
 * Conteos de solo lectura para los avisos del inicio que no son de insumos (los de insumos los da su
 * propio servicio, con las mismas reglas que la lista). Las reglas que modifican estos datos siguen en
 * el servicio de cada módulo.
 */
final class PanelRepository
{
    /** Retiros de insumos registrados hoy (cada «Retirar producto» confirmado cuenta como uno). */
    public function contarSalidasDeHoy(): int
    {
        return (int) Database::fetchValue("SELECT COUNT(*) FROM movimientos WHERE tipo = 'salida' AND fecha >= CURDATE()");
    }

    /** Herramientas prestadas cuya fecha de recepción ya pasó. */
    public function contarPrestamosAtrasados(): int
    {
        return (int) Database::fetchValue(
            "SELECT COUNT(*) FROM solicitudes WHERE estado = 'prestada' AND fecha_devolucion < CURDATE()"
        );
    }

    /** Herramientas que volvieron con daños o no volvieron, y aún no se resuelven (una por línea de solicitud). */
    public function contarHerramientasPorResolver(): int
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM solicitud_items si WHERE ' . SolicitudRepository::PROBLEMA
        );
    }

    public function contarSolicitudesPendientes(): int
    {
        return (int) Database::fetchValue("SELECT COUNT(*) FROM solicitudes WHERE estado = 'pendiente'");
    }

    /** Préstamos atrasados del docente: lo que pidió, le entregaron y no ha vuelto a tiempo. */
    public function contarPrestamosAtrasadosDe(int $usuarioId): int
    {
        return (int) Database::fetchValue(
            "SELECT COUNT(*) FROM solicitudes WHERE docente_id = ? AND estado = 'prestada' AND fecha_devolucion < CURDATE()",
            [$usuarioId]
        );
    }
}
