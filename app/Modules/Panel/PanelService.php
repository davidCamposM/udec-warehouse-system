<?php

declare(strict_types=1);

namespace App\Modules\Panel;

use App\Modules\Insumos\InsumoService;

defined('APP_ROOT') || exit;

/**
 * Avisos del inicio: solo lo que necesita atención. El de toda la bodega (administrador y administrativas)
 * y el propio de cada docente.
 */
final class PanelService
{
    public function __construct(
        private readonly PanelRepository $panel = new PanelRepository(),
        private readonly InsumoService $insumos = new InsumoService(),
    ) {
    }

    /**
     * Cuántos hay de cada cosa por atender, de lo más grave a lo menos. Los avisos en 0 los omite la vista.
     *
     * @return array<string, int> aviso => cantidad
     */
    public function avisosBodega(): array
    {
        // Los mismos números que los filtros de la lista de insumos.
        $insumos = $this->insumos->contar('');

        return [
            'sin_unidades' => $insumos['no-queda'],
            'por_reponer' => $insumos['reponer'],
            'prestamos_atrasados' => $this->panel->contarPrestamosAtrasados(),
            'herramientas_por_resolver' => $this->panel->contarHerramientasPorResolver(),
            'solicitudes_pendientes' => $this->panel->contarSolicitudesPendientes(),
            // Informativo (no urgente): se muestra aunque todo esté en orden.
            'salidas_hoy' => $this->panel->contarSalidasDeHoy(),
        ];
    }

    /** @return array<string, int> aviso => cantidad */
    public function avisosDocente(int $usuarioId): array
    {
        return [
            'prestamos_atrasados' => $this->panel->contarPrestamosAtrasadosDe($usuarioId),
        ];
    }
}
