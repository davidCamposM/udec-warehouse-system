<?php

declare(strict_types=1);

namespace App\Modules\Panel;

use App\Core\Database;

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

    /** Préstamos abiertos cuya fecha de devolución ya pasó. */
    public function contarPrestamosAtrasados(): int
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM prestamos WHERE cerrado_en IS NULL AND fecha_pactada < CURDATE()'
        );
    }

    public function contarSolicitudesPendientes(): int
    {
        return (int) Database::fetchValue("SELECT COUNT(*) FROM solicitudes WHERE estado = 'pendiente'");
    }

    /**
     * Préstamos atrasados del docente: aquellos de los que es responsable y los que retiró él mismo (con la
     * persona ligada a su cuenta).
     */
    public function contarPrestamosAtrasadosDe(int $usuarioId): int
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*)
               FROM prestamos p
              WHERE p.cerrado_en IS NULL AND p.fecha_pactada < CURDATE()
                AND (p.responsable_id = ? OR p.persona_id IN (SELECT id FROM personas WHERE usuario_id = ?))',
            [$usuarioId, $usuarioId]
        );
    }
}
