<?php

declare(strict_types=1);

namespace App\Modules\Historial;

use App\Core\Auth;
use App\Core\Database;

defined('APP_ROOT') || exit;

/**
 * Registro de cambios administrativos (catálogo, usuarios, estados) en la tabla `auditoria`. Los movimientos de
 * stock no pasan por aquí: ellos mismos son su historial.
 */
final class AuditoriaService
{
    /**
     * @param string               $accion  crear, editar, activar, desactivar, agregar_codigo, quitar_codigo…
     * @param string               $entidad insumo, usuario, persona, herramienta_unidad…
     * @param array<string, mixed> $detalle lo que cambió. Nunca contraseñas ni datos que no hagan falta.
     */
    public function registrar(string $accion, string $entidad, ?int $entidadId, array $detalle = []): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        Database::insert(
            'INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, detalle, ip) VALUES (?, ?, ?, ?, ?, ?)',
            [
                Auth::id(),
                $accion,
                $entidad,
                $entidadId,
                $detalle === [] ? null : json_encode($detalle, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                is_string($ip) ? $ip : null,
            ]
        );
    }

    /**
     * Campos que cambiaron entre dos versiones de un registro: campo => [antes, después].
     *
     * @param array<string, mixed> $antes
     * @param array<string, mixed> $despues
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public static function diferencias(array $antes, array $despues): array
    {
        $cambios = [];

        foreach ($despues as $campo => $valor) {
            if ((string) ($antes[$campo] ?? '') !== (string) ($valor ?? '')) {
                $cambios[$campo] = [$antes[$campo] ?? null, $valor];
            }
        }

        return $cambios;
    }
}
