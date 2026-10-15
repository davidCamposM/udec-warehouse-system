<?php

declare(strict_types=1);

namespace App\Modules\Herramientas;

use App\Core\Auth;
use App\Core\Database;
use App\Core\ValidationException;
use App\Modules\Historial\AuditoriaService;
use App\Support\Fecha;
use DateTimeImmutable;

defined('APP_ROOT') || exit;

/**
 * Préstamos de herramientas, como el formulario en papel «Solicitud de instrumentos y herramientas»: el docente envía la
 * solicitud, el encargado entrega las herramientas (o la rechaza) y, cuando vuelven, anota cuántas volvieron y en qué
 * estado. Mientras las unidades no tengan etiqueta, se presta por tipo de herramienta y cantidad.
 */
final class SolicitudService
{
    /** Departamentos del formulario: clave => como se muestra (y se guarda). */
    public const DEPARTAMENTOS = [
        'silvicultura' => 'Silvicultura',
        'manejo' => 'Manejo Bosque y MA',
    ];

    /** Estado => [como se muestra, clase de color (status--)]. */
    public const ESTADOS = [
        'pendiente' => ['Por entregar', 'low'],
        'prestada' => ['Prestada', 'ok'],
        'devuelta' => ['Devuelta', 'off'],
        'rechazada' => ['Rechazada', 'out'],
        'cancelada' => ['Cancelada', 'off'],
    ];

    /** Filtros de la lista del encargado: clave => nombre. */
    public const FILTROS = [
        'pendientes' => 'Por entregar',
        'prestadas' => 'Prestadas',
        'atrasadas' => 'Atrasadas',
        'problemas' => 'Con daños o faltas',
        'devueltas' => 'Devueltas',
        'todas' => 'Todas',
    ];

    /** Cómo se resolvió una herramienta que volvió con daños o no volvió: clave => como se muestra. */
    public const RESOLUCIONES = [
        'reparada' => 'Se reparó',
        'repuesta' => 'Se repuso',
        'baja' => 'Se dio de baja',
    ];

    /** Máximo de unidades de una herramienta en una solicitud: evita errores de tipeo como 100. */
    public const MAXIMO_UNIDADES = 50;

    public function __construct(
        private readonly SolicitudRepository $solicitudes = new SolicitudRepository(),
        private readonly HerramientaRepository $herramientas = new HerramientaRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService(),
    ) {
    }

    public static function filtro(mixed $valor): string
    {
        return is_string($valor) && isset(self::FILTROS[$valor]) ? $valor : 'pendientes';
    }

    /** @return list<array<string, mixed>> */
    public function listar(string $filtro, ?int $docenteId = null): array
    {
        return $this->solicitudes->listar($filtro, $docenteId);
    }

    /** @return array<string, int> filtro => cuántas hay */
    public function contar(): array
    {
        return $this->solicitudes->contar();
    }

    /**
     * La solicitud con su detalle, y si está atrasada (prestada y ya pasó la fecha de recepción).
     *
     * @return array{solicitud: array<string, mixed>, items: list<array<string, mixed>>, atrasada: bool}|null
     */
    public function ficha(int $id): ?array
    {
        $solicitud = $this->solicitudes->buscar($id);
        if ($solicitud === null) {
            return null;
        }

        return [
            'solicitud' => $solicitud,
            'items' => $this->solicitudes->items($id),
            'atrasada' => self::atrasada($solicitud),
        ];
    }

    /** @param array<string, mixed> $solicitud */
    public static function atrasada(array $solicitud): bool
    {
        return self::diasDeAtraso($solicitud) > 0;
    }

    /**
     * Días desde la fecha de recepción, si está prestada y ya pasó (0 si no está atrasada).
     *
     * @param array<string, mixed> $solicitud
     */
    public static function diasDeAtraso(array $solicitud): int
    {
        if ($solicitud['estado'] !== 'prestada' || $solicitud['fecha_devolucion'] >= Fecha::hoy()) {
            return 0;
        }

        $recepcion = new DateTimeImmutable($solicitud['fecha_devolucion']);

        return (int) $recepcion->diff(new DateTimeImmutable(Fecha::hoy()))->days;
    }

    // ---------- El docente ----------

    /**
     * Crea la solicitud del docente con la sesión iniciada.
     *
     * @param array<string, mixed> $datos lo enviado por el formulario
     * @return int el id de la solicitud
     * @throws ValidationException
     */
    public function solicitar(array $datos): int
    {
        $herramientas = $this->herramientas->activas();
        [$limpios, $detalle] = $this->validar($datos, $herramientas);

        return Database::transaction(function () use ($limpios, $detalle): int {
            $id = $this->solicitudes->crear((int) Auth::id(), $limpios);
            foreach ($detalle as $herramientaId => $cantidad) {
                $this->solicitudes->agregarItem($id, $herramientaId, $cantidad);
            }
            $this->auditoria->registrar('crear', 'solicitud', $id, ['detalle' => $detalle]);

            return $id;
        });
    }

    /** El docente retira su solicitud antes de que se la entreguen. @throws ValidationException */
    public function cancelar(int $id): void
    {
        Database::transaction(function () use ($id): void {
            $solicitud = $this->solicitudes->buscar($id, bloquear: true);
            if ($solicitud === null || (int) $solicitud['docente_id'] !== (int) Auth::id()) {
                throw new ValidationException(['solicitud' => 'Esa solicitud no existe.']);
            }
            if ($solicitud['estado'] !== 'pendiente') {
                throw new ValidationException(['solicitud' => 'Esta solicitud ya no se puede cancelar: '
                    . mb_strtolower(self::ESTADOS[$solicitud['estado']][0]) . '.']);
            }

            $this->solicitudes->cancelar($id);
            $this->auditoria->registrar('cancelar', 'solicitud', $id);
        });
    }

    // ---------- El encargado ----------

    /** Entrega las herramientas: la solicitud pasa a «Prestada». @throws ValidationException */
    public function entregar(int $id): void
    {
        Database::transaction(function () use ($id): void {
            $this->pendiente($id);
            $this->solicitudes->marcarPrestada($id, (int) Auth::id());
            $this->auditoria->registrar('entregar', 'solicitud', $id);
        });
    }

    /** @throws ValidationException */
    public function rechazar(int $id, mixed $motivo): void
    {
        $motivo = is_string($motivo) ? trim($motivo) : '';
        if (mb_strlen($motivo) > 255) {
            throw new ValidationException(['motivo' => 'El motivo puede tener hasta 255 letras.']);
        }

        Database::transaction(function () use ($id, $motivo): void {
            $this->pendiente($id);
            $this->solicitudes->rechazar($id, (int) Auth::id(), $motivo === '' ? null : $motivo);
            $this->auditoria->registrar('rechazar', 'solicitud', $id, $motivo === '' ? [] : ['motivo' => $motivo]);
        });
    }

    /**
     * La devolución: cuántas volvieron de cada línea, en qué estado y, si volvieron con daños o faltan, qué pasó. La
     * solicitud pasa a «Devuelta»; las líneas con daños o faltas quedan como problemas por resolver.
     *
     * @param array<string, mixed> $datos devueltas_{item}, estado_{item} y detalle_{item} por línea
     * @throws ValidationException
     */
    public function recibir(int $id, array $datos): void
    {
        Database::transaction(function () use ($id, $datos): void {
            $solicitud = $this->solicitudes->buscar($id, bloquear: true);
            if ($solicitud === null || $solicitud['estado'] !== 'prestada') {
                throw new ValidationException(['recepcion' => 'Esta solicitud no está prestada: no hay nada que recibir.']);
            }

            $errores = [];
            $lineas = [];
            foreach ($this->solicitudes->items($id) as $item) {
                $campo = 'devueltas_' . $item['id'];
                $valor = trim((string) ($datos[$campo] ?? ''));
                $devueltas = ctype_digit($valor) ? (int) $valor : -1;

                if ($devueltas < 0 || $devueltas > (int) $item['cantidad']) {
                    $errores[$campo] = "Escribe cuántas volvieron, de 0 a {$item['cantidad']}.";
                    continue;
                }

                $estado = $datos['estado_' . $item['id']] ?? '';
                if ($devueltas > 0 && !in_array($estado, ['bien', 'danada'], true)) {
                    $errores['estado_' . $item['id']] = 'Elige en qué estado volvieron.';
                    continue;
                }

                // Con daños o con unidades que no volvieron, se anota qué pasó: así se sabe qué resolver.
                $campoDetalle = 'detalle_' . $item['id'];
                $detalle = trim((string) ($datos[$campoDetalle] ?? ''));
                $problema = $estado === 'danada' || $devueltas < (int) $item['cantidad'];
                if ($problema && $detalle === '') {
                    $errores[$campoDetalle] = $devueltas < (int) $item['cantidad']
                        ? 'Escribe qué pasó con las que no volvieron. Ejemplo: se perdió en terreno.'
                        : 'Escribe qué daño tiene. Ejemplo: lente rayado.';
                    continue;
                }
                if (mb_strlen($detalle) > 255) {
                    $errores[$campoDetalle] = 'Puede tener hasta 255 letras.';
                    continue;
                }

                // Si no volvió ninguna, no hay estado que anotar: la línea muestra «No volvió».
                $lineas[(int) $item['id']] = [$devueltas, $devueltas > 0 ? $estado : null, $problema ? $detalle : null];
            }

            if ($errores !== []) {
                throw new ValidationException($errores);
            }

            foreach ($lineas as $itemId => [$devueltas, $estado, $detalle]) {
                $this->solicitudes->recibirItem($itemId, $devueltas, $estado, $detalle);
            }
            $this->solicitudes->marcarDevuelta($id, (int) Auth::id());
            $this->auditoria->registrar('recibir', 'solicitud', $id, ['lineas' => $lineas]);
        });
    }

    /**
     * Extiende el plazo de un préstamo: nueva fecha de recepción, desde hoy en adelante.
     *
     * @throws ValidationException
     */
    public function extenderPlazo(int $id, mixed $fecha): void
    {
        $nueva = Fecha::normalizar(is_string($fecha) ? $fecha : '');
        if ($nueva === null) {
            throw new ValidationException(['fecha_devolucion' => 'Elige la nueva fecha de recepción.']);
        }
        if ($nueva < Fecha::hoy()) {
            throw new ValidationException(['fecha_devolucion' => 'Ese día ya pasó. Elige hoy o un día que venga.']);
        }

        Database::transaction(function () use ($id, $nueva): void {
            $solicitud = $this->solicitudes->buscar($id, bloquear: true);
            if ($solicitud === null || $solicitud['estado'] !== 'prestada') {
                throw new ValidationException(['fecha_devolucion' => 'Solo se extiende el plazo de un préstamo que está prestado.']);
            }
            if ($nueva === $solicitud['fecha_devolucion']) {
                throw new ValidationException(['fecha_devolucion' => 'Es la misma fecha que ya tiene.']);
            }

            $this->solicitudes->extenderPlazo($id, $nueva);
            $this->auditoria->registrar('extender_plazo', 'solicitud', $id, [
                'fecha_devolucion' => [$solicitud['fecha_devolucion'], $nueva],
            ]);
        });
    }

    /**
     * Resuelve una herramienta que volvió con daños o no volvió: se reparó, se repuso o se dio de baja.
     *
     * @throws ValidationException
     */
    public function resolver(int $id, int $itemId, mixed $resolucion): void
    {
        if (!is_string($resolucion) || !isset(self::RESOLUCIONES[$resolucion])) {
            throw new ValidationException(['resolucion_' . $itemId => 'Elige cómo se resolvió.']);
        }

        Database::transaction(function () use ($id, $itemId, $resolucion): void {
            $item = $this->solicitudes->bloquearItem($id, $itemId);
            if ($item === null || (int) $item['problema'] !== 1) {
                throw new ValidationException(['resolucion_' . $itemId => 'Esa herramienta no tiene nada por resolver.']);
            }

            $this->solicitudes->resolverItem($itemId, $resolucion, (int) Auth::id());
            $this->auditoria->registrar('resolver', 'solicitud', $id, ['item' => $itemId, 'resolucion' => $resolucion]);
        });
    }

    // ---------- Apoyo ----------

    /** @throws ValidationException si no existe o ya no está por entregar */
    private function pendiente(int $id): void
    {
        $solicitud = $this->solicitudes->buscar($id, bloquear: true);
        if ($solicitud === null) {
            throw new ValidationException(['solicitud' => 'Esa solicitud no existe.']);
        }
        if ($solicitud['estado'] !== 'pendiente') {
            throw new ValidationException(['solicitud' => 'Esta solicitud ya no está por entregar: '
                . mb_strtolower(self::ESTADOS[$solicitud['estado']][0]) . '.']);
        }
    }

    /**
     * @param array<string, mixed>                               $datos
     * @param list<array{id: int, nombre: string, descripcion: ?string}> $herramientas
     * @return array{0: array{departamento: string, tipo_actividad: string, autoriza_retiro: ?string, fecha_retiro: string,
     *     fecha_devolucion: string}, 1: array<int, int>} los datos limpios y herramienta => unidades
     * @throws ValidationException
     */
    private function validar(array $datos, array $herramientas): array
    {
        $errores = [];
        $texto = static fn (string $campo): string => trim((string) ($datos[$campo] ?? ''));

        $departamento = self::DEPARTAMENTOS[$texto('departamento')] ?? null;
        if ($departamento === null) {
            $errores['departamento'] = 'Marca el departamento.';
        }

        $actividad = $texto('tipo_actividad');
        if ($actividad === '') {
            $errores['tipo_actividad'] = 'Escribe para qué son. Ejemplo: Terreno de ecología en la cantera.';
        } elseif (mb_strlen($actividad) > 255) {
            $errores['tipo_actividad'] = 'Puede tener hasta 255 letras.';
        }

        $autoriza = $texto('autoriza_retiro');
        if (mb_strlen($autoriza) > 150) {
            $errores['autoriza_retiro'] = 'Puede tener hasta 150 letras.';
        }

        $retiro = Fecha::normalizar($texto('fecha_retiro'));
        $devolucion = Fecha::normalizar($texto('fecha_devolucion'));
        if ($retiro === null) {
            $errores['fecha_retiro'] = 'Elige el día en que las retiras.';
        } elseif ($retiro < Fecha::hoy()) {
            $errores['fecha_retiro'] = 'Ese día ya pasó. Elige hoy o un día que venga.';
        }
        if ($devolucion === null) {
            $errores['fecha_devolucion'] = 'Elige el día en que las devuelves.';
        } elseif ($retiro !== null && $devolucion < $retiro) {
            $errores['fecha_devolucion'] = 'Debe ser el mismo día del retiro o uno después.';
        }

        $detalle = [];
        foreach ($herramientas as $herramienta) {
            $valor = $texto('unidades_' . $herramienta['id']);
            if ($valor === '' || $valor === '0') {
                continue;
            }
            if (!ctype_digit($valor) || (int) $valor > self::MAXIMO_UNIDADES) {
                $errores['detalle'] = "Revisa las unidades de «{$herramienta['nombre']}»: un número entero de 0 a "
                    . self::MAXIMO_UNIDADES . '.';
                continue;
            }
            $detalle[(int) $herramienta['id']] = (int) $valor;
        }
        if ($detalle === [] && !isset($errores['detalle'])) {
            $errores['detalle'] = 'Indica cuántas unidades necesitas de al menos una herramienta.';
        }

        if ($errores !== []) {
            throw new ValidationException($errores);
        }

        return [[
            'departamento' => $departamento,
            'tipo_actividad' => $actividad,
            'autoriza_retiro' => $autoriza === '' ? null : $autoriza,
            'fecha_retiro' => $retiro,
            'fecha_devolucion' => $devolucion,
        ], $detalle];
    }
}
