<?php

declare(strict_types=1);

namespace App\Modules\Herramientas;

use App\Core\Database;
use App\Core\ValidationException;
use App\Modules\Historial\AuditoriaService;

defined('APP_ROOT') || exit;

/**
 * Catálogo de herramientas: lo que se puede pedir prestado, agrupado por categoría, con cuántas unidades tiene la
 * bodega. El encargado agrega, renombra, cambia de categoría, anota las unidades y elimina. Para las personas, eliminar
 * la saca del sistema; por dentro solo se desactiva (los préstamos antiguos la siguen mostrando). No se elimina la que
 * todavía tiene unidades, está prestada, pedida o por resolver.
 */
final class HerramientaService
{
    /** Nombre del grupo para las herramientas que no tienen categoría. */
    public const SIN_CATEGORIA = 'Otras';

    /** Máximo de unidades de una herramienta: evita errores de tipeo como 10000. */
    public const MAXIMO_UNIDADES = 999;

    /**
     * Los filtros del catálogo por dónde está cada herramienta (los mismos grupos del gráfico del inicio; cada una en
     * uno solo): filtro => nombre.
     */
    public const ESTADOS = [
        'todas' => 'Todas',
        'en-bodega' => 'En bodega',
        'prestadas' => 'Prestadas',
        'atrasadas' => 'Atrasadas',
        'sin-unidades' => 'Sin unidades',
    ];

    /** El color de cada estado (nav-link-- y el gráfico del inicio). */
    public const COLORES = ['en-bodega' => 'bien', 'prestadas' => 'fuera', 'atrasadas' => 'grave', 'sin-unidades' => 'atencion'];

    public function __construct(
        private readonly HerramientaRepository $herramientas = new HerramientaRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService(),
    ) {
    }

    /**
     * Las activas agrupadas por categoría principal: categoría => herramientas (las sin categoría al final, en
     * «Otras»). Con un estado (en-bodega, prestadas, atrasadas, sin-unidades), solo las que están así. Con
     * $soloConUnidades, solo las que la bodega tiene (para solicitar).
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function porCategoria(string $estado = 'todas', bool $soloConUnidades = false): array
    {
        $grupos = [];
        foreach ($this->activas() as $herramienta) {
            if ($soloConUnidades && (int) $herramienta['unidades'] === 0) {
                continue;
            }
            if ($estado === 'todas' || $herramienta['estado'] === $estado) {
                $grupos[$herramienta['categoria'] ?? self::SIN_CATEGORIA][] = $herramienta;
            }
        }

        return $grupos;
    }

    /** @return array<string, int> estado => cuántas herramientas activas hay así (y «todas») */
    public function contarPorEstado(): array
    {
        return self::contar($this->activas());
    }

    /**
     * Para el inicio y el documento del estado de la bodega: cuántas herramientas hay en cada estado y cuántas unidades
     * tiene la bodega, cuántas están en ella, prestadas y por resolver.
     *
     * @return array{en_bodega: int, prestadas: int, atrasadas: int, sin_unidades: int, unidades: int,
     *     unidades_en_bodega: int, unidades_prestadas: int, unidades_por_resolver: int}
     */
    public function resumen(): array
    {
        $activas = $this->activas();
        $conteo = self::contar($activas);
        $suma = static fn (string $campo): int => array_sum(array_map(static fn (array $h): int => (int) $h[$campo], $activas));

        return [
            'en_bodega' => $conteo['en-bodega'],
            'prestadas' => $conteo['prestadas'],
            'atrasadas' => $conteo['atrasadas'],
            'sin_unidades' => $conteo['sin-unidades'],
            'unidades' => $suma('unidades'),
            'unidades_en_bodega' => $suma('en_bodega'),
            'unidades_prestadas' => $suma('prestadas'),
            'unidades_por_resolver' => $suma('por_resolver'),
        ];
    }

    /**
     * «Insumos Práctica de Invierno»: el equipo de esa categoría, agrupado por su subcategoría (Esquí y fijaciones,
     * Bastones…; las que no tienen, en «Otros», al final), con cuántas hay en cada estado. Con $estado, solo esas.
     *
     * @return array{grupos: array<string, list<array<string, mixed>>>, conteo: array<string, int>}
     */
    public function practicaInvierno(string $estado): array
    {
        $equipo = array_values(array_filter(
            $this->activas(),
            static fn (array $herramienta): bool => $herramienta['clave'] === HerramientaRepository::PRACTICA_INVIERNO
        ));

        $grupos = [];
        foreach ($equipo as $herramienta) {
            if ($estado === 'todas' || $herramienta['estado'] === $estado) {
                $grupos[$herramienta['subcategoria'] ?? 'Otros'][] = $herramienta;
            }
        }
        uksort($grupos, static fn (string $a, string $b): int => [$a === 'Otros', $a] <=> [$b === 'Otros', $b]);

        return ['grupos' => $grupos, 'conteo' => self::contar($equipo)];
    }

    /** El filtro pedido, si es uno de ESTADOS; si no, «todas». */
    public static function filtro(mixed $valor): string
    {
        return is_string($valor) && isset(self::ESTADOS[$valor]) ? $valor : 'todas';
    }

    /**
     * Cuántas unidades están en la bodega para prestar: las que tiene, menos las prestadas y las por resolver.
     *
     * @param array<string, mixed> $herramienta
     */
    public static function enBodega(array $herramienta): int
    {
        return max(0, (int) $herramienta['unidades'] - (int) $herramienta['prestadas'] - (int) $herramienta['por_resolver']);
    }

    /**
     * Dónde está: «atrasadas» si alguna unidad debió volver, «prestadas» si alguna está fuera a tiempo, «en-bodega» si
     * hay alguna para prestar y «sin-unidades» si no queda ninguna.
     *
     * @param array<string, mixed> $herramienta
     */
    public static function estado(array $herramienta): string
    {
        return match (true) {
            (int) $herramienta['atrasadas'] > 0 => 'atrasadas',
            (int) $herramienta['prestadas'] > 0 => 'prestadas',
            self::enBodega($herramienta) > 0 => 'en-bodega',
            default => 'sin-unidades',
        };
    }

    /**
     * Por qué todavía no se puede eliminar (null: se puede). Una herramienta con unidades, prestada, pedida o con algo
     * por resolver sigue siendo parte de la bodega.
     *
     * @param array<string, mixed> $herramienta
     */
    public static function porQueNoSeElimina(array $herramienta): ?string
    {
        $prestadas = (int) $herramienta['prestadas'];
        $pedidas = (int) $herramienta['pedidas'];
        $porResolver = (int) $herramienta['por_resolver'];
        $unidades = (int) $herramienta['unidades'];

        return match (true) {
            $prestadas > 0 => ($prestadas === 1 ? 'Hay 1 prestada.' : "Hay {$prestadas} prestadas.")
                . ' Se puede eliminar cuando vuelvan.',
            $pedidas > 0 => ($pedidas === 1 ? 'Está en 1 solicitud por entregar.' : "Está en {$pedidas} solicitudes por entregar.")
                . ' Entrégalas o recházalas primero, en «Préstamos de herramientas».',
            $porResolver > 0 => ($porResolver === 1 ? 'Hay 1 por resolver' : "Hay {$porResolver} por resolver")
                . ' (volvieron con daños o no volvieron). Resuélvelo primero, en «Préstamos de herramientas».',
            $unidades > 0 => ($unidades === 1 ? 'Todavía tiene 1 unidad anotada.' : "Todavía tiene {$unidades} unidades anotadas.")
                . ' Si ya no están en la bodega, cambia «¿Cuántas hay?» a 0 y guarda.',
            default => null,
        };
    }

    /** @return list<array{id: int, nombre: string, padre: ?string}> */
    public function categorias(): array
    {
        return $this->herramientas->categorias();
    }

    /** @return array<string, mixed>|null con su estado y cuántas hay en bodega */
    public function buscarPorId(int $id): ?array
    {
        $herramienta = $this->herramientas->findById($id);

        return $herramienta === null ? null : self::completar($herramienta);
    }

    /**
     * @param array<string, mixed> $datos nombre, categoria_id y unidades
     * @return string el nombre con que quedó
     * @throws ValidationException
     */
    public function crear(array $datos): string
    {
        [$nombre, $categoriaId, $unidades] = $this->validar($datos, null);
        $id = $this->herramientas->create($nombre, $categoriaId, $unidades);
        $this->auditoria->registrar('crear', 'herramienta', $id, [
            'nombre' => $nombre,
            'categoria_id' => $categoriaId,
            'unidades' => $unidades,
        ]);

        return $nombre;
    }

    /**
     * @param array<string, mixed> $datos nombre, categoria_id y unidades
     * @throws ValidationException
     */
    public function editar(int $id, array $datos): void
    {
        Database::transaction(function () use ($id, $datos): void {
            $this->herramientas->bloquear([$id]);
            $actual = $this->buscarPorId($id) ?? throw new ValidationException(['nombre' => 'Esa herramienta no existe.']);
            [$nombre, $categoriaId, $unidades] = $this->validar($datos, $actual);
            $this->herramientas->update($id, $nombre, $categoriaId, $unidades);
            $this->auditoria->registrar('editar', 'herramienta', $id, AuditoriaService::diferencias(
                ['nombre' => $actual['nombre'], 'categoria_id' => $actual['categoria_id'], 'unidades' => (int) $actual['unidades']],
                ['nombre' => $nombre, 'categoria_id' => $categoriaId, 'unidades' => $unidades],
            ));
        });
    }

    /** @throws ValidationException si todavía tiene unidades, está prestada, pedida o por resolver */
    public function eliminar(int $id): void
    {
        Database::transaction(function () use ($id): void {
            $this->herramientas->bloquear([$id]);
            $herramienta = $this->buscarPorId($id);
            if ($herramienta === null) {
                return;
            }
            $motivo = self::porQueNoSeElimina($herramienta);
            if ($motivo !== null) {
                throw new ValidationException(['eliminar' => 'Todavía no se puede eliminar. ' . $motivo]);
            }

            $this->herramientas->eliminar($id);
            $this->auditoria->registrar('eliminar', 'herramienta', $id, ['nombre' => $herramienta['nombre']]);
        });
    }

    // ---------- Apoyo ----------

    /** @return list<array<string, mixed>> las activas, cada una con su estado y cuántas hay en bodega */
    private function activas(): array
    {
        return array_map(self::completar(...), $this->herramientas->activas());
    }

    /**
     * @param array<string, mixed> $herramienta
     * @return array<string, mixed> con en_bodega (unidades para prestar) y estado
     */
    private static function completar(array $herramienta): array
    {
        return $herramienta + ['en_bodega' => self::enBodega($herramienta), 'estado' => self::estado($herramienta)];
    }

    /**
     * @param list<array<string, mixed>> $herramientas
     * @return array<string, int> estado => cuántas (y «todas»)
     */
    private static function contar(array $herramientas): array
    {
        $conteo = array_fill_keys(array_keys(self::ESTADOS), 0);
        foreach ($herramientas as $herramienta) {
            $conteo['todas']++;
            $conteo[$herramienta['estado']]++;
        }

        return $conteo;
    }

    /**
     * @param array<string, mixed>      $datos
     * @param array<string, mixed>|null $actual la herramienta que se edita (null: una nueva)
     * @return array{0: string, 1: ?int, 2: int} el nombre, la categoría y las unidades
     * @throws ValidationException
     */
    private function validar(array $datos, ?array $actual): array
    {
        $errores = [];
        $nombre = preg_replace('/\s+/', ' ', trim((string) ($datos['nombre'] ?? ''))) ?? '';

        if ($nombre === '') {
            $errores['nombre'] = 'Escribe el nombre de la herramienta. Ejemplo: Hipsómetro.';
        } elseif (mb_strlen($nombre) > 150) {
            $errores['nombre'] = 'El nombre puede tener hasta 150 letras.';
        } elseif ($this->herramientas->nombreEnUso($nombre, $actual === null ? null : (int) $actual['id'])) {
            $errores['nombre'] = 'Ya hay una herramienta con ese nombre.';
        }

        $categoria = (string) ($datos['categoria_id'] ?? '');
        $categoriaId = null;
        if ($categoria !== '') {
            $validas = array_map('intval', array_column($this->herramientas->categorias(), 'id'));
            $categoriaId = ctype_digit($categoria) && in_array((int) $categoria, $validas, true) ? (int) $categoria : null;
            if ($categoriaId === null) {
                $errores['categoria_id'] = 'Elige una de las categorías.';
            }
        }

        // Las unidades: no menos de las que están fuera de la bodega (prestadas o por resolver).
        $texto = trim((string) ($datos['unidades'] ?? ''));
        $unidades = ctype_digit($texto) && strlen($texto) <= 4 ? (int) $texto : -1;
        $fuera = $actual === null ? 0 : (int) $actual['prestadas'] + (int) $actual['por_resolver'];
        if ($unidades < 0 || $unidades > self::MAXIMO_UNIDADES) {
            $errores['unidades'] = 'Escribe cuántas hay, de 0 a ' . self::MAXIMO_UNIDADES . '.';
        } elseif ($unidades < $fuera) {
            $errores['unidades'] = "Hay {$fuera} fuera de la bodega (prestadas o por resolver): no pueden ser menos de {$fuera}.";
        }

        if ($errores !== []) {
            throw new ValidationException($errores);
        }

        return [$nombre, $categoriaId, $unidades];
    }
}
