<?php

declare(strict_types=1);

namespace App\Modules\Herramientas;

use App\Core\Database;

defined('APP_ROOT') || exit;

/** Solicitudes de herramientas: la solicitud, su entrega (préstamo) y su devolución. Solo lo usa SolicitudService. */
final class SolicitudRepository
{
    /**
     * Una línea con un problema por resolver: volvió con daños o faltaron unidades, y nadie lo ha resuelto. (Sobre el
     * alias si de solicitud_items.)
     */
    public const PROBLEMA = "si.resolucion IS NULL
        AND (si.estado_recepcion = 'danada' OR si.cantidad_devuelta < si.cantidad)";

    /**
     * Cuántas unidades de una línea con problema están afectadas: todas, si volvieron con daños (las que no volvieron
     * tampoco están); si no, las que no volvieron. No están en bodega hasta que se resuelva. (Sobre el alias si.)
     */
    public const AFECTADAS = "IF(si.estado_recepcion = 'danada', si.cantidad, si.cantidad - si.cantidad_devuelta)";

    /** Filtro de la lista => condición SQL (fija, sin datos del usuario). */
    public const FILTROS = [
        'pendientes' => "s.estado = 'pendiente'",
        'prestadas' => "s.estado = 'prestada'",
        'atrasadas' => "s.estado = 'prestada' AND s.fecha_devolucion < CURDATE()",
        'problemas' => 'EXISTS (SELECT 1 FROM solicitud_items si WHERE si.solicitud_id = s.id AND ' . self::PROBLEMA . ')',
        'devueltas' => "s.estado = 'devuelta'",
        'todas' => '1 = 1',
    ];

    private const COLUMNAS = 's.*, u.nombre AS docente,
        (SELECT COALESCE(SUM(si.cantidad), 0) FROM solicitud_items si WHERE si.solicitud_id = s.id) AS unidades,
        (SELECT COUNT(*) FROM solicitud_items si WHERE si.solicitud_id = s.id AND ' . self::PROBLEMA . ') AS problemas';

    /**
     * @param array{departamento: string, tipo_actividad: string, autoriza_retiro: ?string, fecha_retiro: string,
     *     fecha_devolucion: string} $datos
     */
    public function crear(int $docenteId, array $datos): int
    {
        return Database::insert(
            'INSERT INTO solicitudes (docente_id, departamento, tipo_actividad, autoriza_retiro, fecha_retiro, fecha_devolucion)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $docenteId,
                $datos['departamento'],
                $datos['tipo_actividad'],
                $datos['autoriza_retiro'],
                $datos['fecha_retiro'],
                $datos['fecha_devolucion'],
            ]
        );
    }

    public function agregarItem(int $solicitudId, int $herramientaId, int $cantidad): void
    {
        Database::insert(
            'INSERT INTO solicitud_items (solicitud_id, herramienta_id, cantidad) VALUES (?, ?, ?)',
            [$solicitudId, $herramientaId, $cantidad]
        );
    }

    /** @return array<string, mixed>|null la solicitud con el nombre del docente */
    public function buscar(int $id, bool $bloquear = false): ?array
    {
        return Database::fetch(
            'SELECT ' . self::COLUMNAS . ' FROM solicitudes s JOIN usuarios u ON u.id = s.docente_id WHERE s.id = ?'
                . ($bloquear ? ' FOR UPDATE' : ''),
            [$id]
        );
    }

    /**
     * Las líneas del detalle, con el nombre de cada herramienta, si tienen un problema por resolver y quién lo
     * resolvió.
     *
     * @return list<array<string, mixed>>
     */
    public function items(int $solicitudId): array
    {
        return Database::fetchAll(
            'SELECT si.*, h.nombre, (' . self::PROBLEMA . ') AS problema, r.nombre AS resuelta_por_nombre
               FROM solicitud_items si
               JOIN herramientas h ON h.id = si.herramienta_id
               LEFT JOIN usuarios r ON r.id = si.resuelta_por
              WHERE si.solicitud_id = ?
              ORDER BY h.nombre',
            [$solicitudId]
        );
    }

    /** @return array<string, mixed>|null una línea de la solicitud, bloqueada hasta que termine la transacción */
    public function bloquearItem(int $solicitudId, int $itemId): ?array
    {
        return Database::fetch(
            'SELECT si.*, (' . self::PROBLEMA . ') AS problema, ' . self::AFECTADAS . ' AS afectadas
               FROM solicitud_items si WHERE si.id = ? AND si.solicitud_id = ? FOR UPDATE',
            [$itemId, $solicitudId]
        );
    }

    /**
     * Las solicitudes de un filtro (todas las de la bodega, o solo las de un docente), de la fecha de retiro más
     * próxima a la más lejana; las devueltas, de la más reciente a la más antigua.
     *
     * @return list<array<string, mixed>>
     */
    public function listar(string $filtro, ?int $docenteId = null): array
    {
        $orden = $filtro === 'devueltas' ? 's.recibida_en DESC' : 's.fecha_retiro, s.id';

        return Database::fetchAll(
            'SELECT ' . self::COLUMNAS . '
               FROM solicitudes s JOIN usuarios u ON u.id = s.docente_id
              WHERE ' . self::FILTROS[$filtro] . ($docenteId !== null ? ' AND s.docente_id = ?' : '') . "
              ORDER BY {$orden}",
            $docenteId !== null ? [$docenteId] : []
        );
    }

    /** @return array<string, int> filtro => cuántas hay */
    public function contar(): array
    {
        $conteo = [];
        foreach (self::FILTROS as $filtro => $condicion) {
            $conteo[$filtro] = (int) Database::fetchValue("SELECT COUNT(*) FROM solicitudes s WHERE {$condicion}");
        }

        return $conteo;
    }

    public function marcarPrestada(int $id, int $usuarioId): void
    {
        Database::execute(
            "UPDATE solicitudes SET estado = 'prestada', entregada_por = ?, entregada_en = NOW() WHERE id = ?",
            [$usuarioId, $id]
        );
    }

    public function rechazar(int $id, int $usuarioId, ?string $motivo): void
    {
        Database::execute(
            "UPDATE solicitudes SET estado = 'rechazada', revisada_por = ?, revisada_en = NOW(), motivo_rechazo = ? WHERE id = ?",
            [$usuarioId, $motivo, $id]
        );
    }

    public function cancelar(int $id): void
    {
        Database::execute("UPDATE solicitudes SET estado = 'cancelada' WHERE id = ?", [$id]);
    }

    /**
     * @param 'bien'|'danada'|null $estado  null si no volvió ninguna
     * @param ?string              $detalle qué le pasó, si volvió con daños o faltaron
     */
    public function recibirItem(int $itemId, int $devueltas, ?string $estado, ?string $detalle): void
    {
        Database::execute(
            'UPDATE solicitud_items SET cantidad_devuelta = ?, estado_recepcion = ?, detalle_recepcion = ? WHERE id = ?',
            [$devueltas, $estado, $detalle, $itemId]
        );
    }

    public function marcarDevuelta(int $id, int $usuarioId): void
    {
        Database::execute(
            "UPDATE solicitudes SET estado = 'devuelta', recibida_por = ?, recibida_en = NOW() WHERE id = ?",
            [$usuarioId, $id]
        );
    }

    /** Nueva fecha de recepción. La primera vez guarda la que se había pedido en plazo_original. */
    public function extenderPlazo(int $id, string $fecha): void
    {
        Database::execute(
            'UPDATE solicitudes SET plazo_original = COALESCE(plazo_original, fecha_devolucion), fecha_devolucion = ? WHERE id = ?',
            [$fecha, $id]
        );
    }

    /** @param 'reparada'|'repuesta'|'baja' $resolucion */
    public function resolverItem(int $itemId, string $resolucion, int $usuarioId): void
    {
        Database::execute(
            'UPDATE solicitud_items SET resolucion = ?, resuelta_por = ?, resuelta_en = NOW() WHERE id = ?',
            [$resolucion, $usuarioId, $itemId]
        );
    }
}
