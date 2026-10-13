<?php

declare(strict_types=1);

namespace App\Modules\Insumos;

use App\Core\Database;

defined('APP_ROOT') || exit;

/**
 * Acceso a `insumos` y sus códigos de barra. Los cambios de stock NO pasan por aquí: los hace StockService.
 */
final class InsumoRepository
{
    /** Condición SQL de cada filtro de la lista. */
    public const FILTROS = [
        'todos' => 'i.activo = 1',
        'reponer' => 'i.activo = 1 AND i.stock_minimo > 0 AND i.stock_actual <= i.stock_minimo',
        'no-queda' => 'i.activo = 1 AND i.stock_actual <= 0',
        'desactivados' => 'i.activo = 0',
    ];

    private const COLUMNAS = 'i.id, i.codigo, i.codigo_udec, i.nombre, i.stock_actual, i.stock_minimo, i.activo,
        i.categoria_id, c.nombre AS categoria, cp.nombre AS categoria_padre,
        i.sector_id, s.nombre AS sector, s.codigo AS sector_codigo';

    private const DESDE = 'insumos i
        LEFT JOIN categorias c ON c.id = i.categoria_id
        LEFT JOIN categorias cp ON cp.id = c.padre_id
        LEFT JOIN sectores s ON s.id = i.sector_id';

    /**
     * Productos del filtro que coinciden con el texto, ordenados por nombre.
     *
     * @return list<array<string, mixed>>
     */
    public function buscar(string $texto, string $filtro): array
    {
        [$condicion, $parametros] = self::condicionTexto($texto);

        return Database::fetchAll(
            'SELECT ' . self::COLUMNAS . ' FROM ' . self::DESDE
            . ' WHERE ' . self::FILTROS[$filtro] . " AND {$condicion} ORDER BY i.nombre",
            $parametros
        );
    }

    /**
     * Los primeros productos activos que coinciden con el texto, para la lista bajo una barra de búsqueda: primero los
     * que empiezan con la primera palabra escrita y después el resto, por nombre.
     *
     * @return list<array<string, mixed>>
     */
    public function sugerencias(string $texto, int $limite): array
    {
        [$condicion, $parametros] = self::condicionTexto($texto);
        $primera = preg_split('/\s+/', trim($texto), 2)[0] ?? '';
        $parametros[] = addcslashes($primera, '%_\\') . '%';

        return Database::fetchAll(
            'SELECT ' . self::COLUMNAS . ' FROM ' . self::DESDE
            . ' WHERE ' . self::FILTROS['todos'] . " AND {$condicion}"
            . ' ORDER BY i.nombre LIKE ? DESC, i.nombre LIMIT ' . max(1, $limite),
            $parametros
        );
    }

    /**
     * Cuántos productos que coinciden con el texto hay en cada filtro.
     *
     * @return array<string, int> filtro => cantidad
     */
    public function contar(string $texto): array
    {
        [$condicion, $parametros] = self::condicionTexto($texto);
        $columnas = [];

        foreach (self::FILTROS as $filtro => $sql) {
            $columnas[] = "COALESCE(SUM({$sql}), 0) AS `{$filtro}`";
        }

        $fila = Database::fetch(
            'SELECT ' . implode(', ', $columnas) . ' FROM ' . self::DESDE . " WHERE {$condicion}",
            $parametros
        );

        return array_map('intval', $fila);
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        return Database::fetch('SELECT ' . self::COLUMNAS . ' FROM ' . self::DESDE . ' WHERE i.id = ?', [$id]);
    }

    /**
     * Producto al que corresponde un código leído o escrito: el código interno (INS00001), el código UdeC o un
     * código de barras de fábrica activo.
     */
    public function idPorCodigo(string $codigo): ?int
    {
        $id = Database::fetchValue(
            'SELECT insumo_id FROM insumo_codigos WHERE codigo = ? AND activo = 1
             UNION ALL
             SELECT id FROM insumos WHERE codigo = ? OR codigo_udec = ?
             LIMIT 1',
            [$codigo, $codigo, $codigo]
        );

        return $id === null ? null : (int) $id;
    }

    /** Si el producto tuvo alguna vez una entrada, salida o ajuste. */
    public function tieneMovimientos(int $insumoId): bool
    {
        return Database::fetchValue('SELECT 1 FROM movimiento_items WHERE insumo_id = ? LIMIT 1', [$insumoId]) !== null;
    }

    /**
     * Los últimos productos activos que entraron a bodega, sin repetir, del más reciente al más antiguo.
     *
     * @return list<array<string, mixed>>
     */
    public function ultimosRegistrados(int $limite): array
    {
        return Database::fetchAll(
            'SELECT ' . self::COLUMNAS . ' FROM ' . self::DESDE . "
               JOIN (SELECT mi.insumo_id, MAX(mi.movimiento_id) AS ultimo
                       FROM movimiento_items mi
                       JOIN movimientos m ON m.id = mi.movimiento_id
                      WHERE m.tipo = 'entrada'
                      GROUP BY mi.insumo_id) r ON r.insumo_id = i.id
              WHERE i.activo = 1
              ORDER BY r.ultimo DESC
              LIMIT " . max(1, $limite)
        );
    }

    /** @return list<array<string, mixed>> códigos de barra activos del producto */
    public function codigos(int $insumoId): array
    {
        return Database::fetchAll(
            'SELECT id, codigo, descripcion FROM insumo_codigos WHERE insumo_id = ? AND activo = 1 ORDER BY id',
            [$insumoId]
        );
    }

    /**
     * Últimas entradas, salidas y ajustes del producto, con quién los hizo.
     *
     * @return list<array<string, mixed>>
     */
    public function ultimosMovimientos(int $insumoId, int $limite): array
    {
        return Database::fetchAll(
            'SELECT m.id, m.tipo, m.fecha, m.documento, m.motivo, mi.cantidad, mi.stock_resultante,
                    COALESCE(pe.nombre, us.nombre) AS quien, se.nombre AS sector
               FROM movimiento_items mi
               JOIN movimientos m ON m.id = mi.movimiento_id
               LEFT JOIN personas pe ON pe.id = m.persona_id
               LEFT JOIN usuarios us ON us.id = m.usuario_id
               LEFT JOIN sectores se ON se.id = mi.sector_id
              WHERE mi.insumo_id = ?
              ORDER BY m.fecha DESC, m.id DESC
              LIMIT ' . max(1, $limite),
            [$insumoId]
        );
    }

    // ---------- Crear y editar ----------

    /**
     * Siguiente código interno libre (INS00061…). Se llama dentro de una transacción: el FOR UPDATE hace que dos
     * personas creando productos al mismo tiempo no reciban el mismo código.
     */
    public function siguienteCodigo(): string
    {
        $ultimo = (int) Database::fetchValue(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(codigo, 4) AS UNSIGNED)), 0) FROM insumos WHERE codigo LIKE 'INS%' FOR UPDATE"
        );

        return sprintf('INS%05d', $ultimo + 1);
    }

    /** @param array{codigo: string, codigo_udec: ?string, nombre: string, categoria_id: ?int, stock_minimo: int} $datos */
    public function create(array $datos): int
    {
        return Database::insert(
            'INSERT INTO insumos (codigo, codigo_udec, nombre, categoria_id, stock_minimo) VALUES (?, ?, ?, ?, ?)',
            [$datos['codigo'], $datos['codigo_udec'], $datos['nombre'], $datos['categoria_id'], $datos['stock_minimo']]
        );
    }

    /** @param array{codigo_udec: ?string, nombre: string, categoria_id: ?int, stock_minimo: int} $datos */
    public function update(int $id, array $datos): void
    {
        Database::execute(
            'UPDATE insumos SET codigo_udec = ?, nombre = ?, categoria_id = ?, stock_minimo = ? WHERE id = ?',
            [$datos['codigo_udec'], $datos['nombre'], $datos['categoria_id'], $datos['stock_minimo'], $id]
        );
    }

    public function setActivo(int $id, bool $activo): void
    {
        Database::execute('UPDATE insumos SET activo = ? WHERE id = ?', [(int) $activo, $id]);
    }

    /** Id de otro producto con exactamente ese nombre (sin distinguir mayúsculas ni tildes), o null. */
    public function idPorNombre(string $nombre, ?int $excepto): ?int
    {
        $id = Database::fetchValue(
            'SELECT id FROM insumos WHERE nombre = ? AND id <> ? LIMIT 1',
            [$nombre, $excepto ?? 0]
        );

        return $id === null ? null : (int) $id;
    }

    /** @return list<array<string, mixed>> categorías de insumos activas: primero cada principal y luego sus subcategorías */
    public function categorias(): array
    {
        return Database::fetchAll(
            "SELECT c.id, c.nombre, c.padre_id
               FROM categorias c
               LEFT JOIN categorias p ON p.id = c.padre_id
              WHERE c.ambito = 'insumo' AND c.activo = 1
              ORDER BY COALESCE(p.nombre, c.nombre), c.padre_id IS NOT NULL, c.nombre"
        );
    }

    public function categoriaValida(int $id): bool
    {
        return Database::fetchValue(
            "SELECT 1 FROM categorias WHERE id = ? AND ambito = 'insumo' AND activo = 1",
            [$id]
        ) !== null;
    }

    // ---------- Códigos ----------

    /**
     * Dónde se usa un código en todo el sistema, para que un código lleve siempre a una sola cosa (RF17).
     * Retorna la descripción de su dueño ("el producto CLORO…", "la herramienta GPS…", "el sector Estante A"…) o null.
     * $exceptoInsumo deja fuera el código interno y el código UdeC de ese producto (al editarlo).
     */
    public function codigoEnUso(string $codigo, ?int $exceptoInsumo = null): ?string
    {
        $fila = Database::fetch(
            "SELECT CONCAT('el producto ', i.nombre) AS duenio
               FROM insumo_codigos ic JOIN insumos i ON i.id = ic.insumo_id
              WHERE ic.codigo = ? AND ic.activo = 1
             UNION ALL
             SELECT CONCAT('el producto ', nombre) FROM insumos WHERE (codigo = ? OR codigo_udec = ?) AND id <> ?
             UNION ALL
             SELECT CONCAT('la herramienta ', h.nombre)
               FROM herramienta_unidades hu JOIN herramientas h ON h.id = hu.herramienta_id
              WHERE hu.codigo = ?
             UNION ALL
             SELECT 'la credencial de una persona' FROM personas WHERE codigo_credencial = ?
             UNION ALL
             SELECT CONCAT('el sector ', nombre) FROM sectores WHERE codigo = ?
             LIMIT 1",
            [$codigo, $codigo, $codigo, $exceptoInsumo ?? 0, $codigo, $codigo, $codigo]
        );

        return $fila['duenio'] ?? null;
    }

    /** @return array<string, mixed>|null el código de barras, esté activo o no */
    public function findCodigoDeBarras(string $codigo): ?array
    {
        return Database::fetch('SELECT * FROM insumo_codigos WHERE codigo = ?', [$codigo]);
    }

    /** @return array<string, mixed>|null código activo de ese producto */
    public function findCodigoDeInsumo(int $insumoId, int $codigoId): ?array
    {
        return Database::fetch(
            'SELECT * FROM insumo_codigos WHERE id = ? AND insumo_id = ? AND activo = 1',
            [$codigoId, $insumoId]
        );
    }

    /**
     * Agrega un código de barras al producto. Si el código existió antes y se había quitado, se reutiliza esa fila
     * (la columna es única): así un envase que cambió de producto se puede volver a asignar.
     */
    public function guardarCodigo(int $insumoId, string $codigo, ?string $descripcion): int
    {
        $anterior = $this->findCodigoDeBarras($codigo);

        if ($anterior !== null) {
            Database::execute(
                'UPDATE insumo_codigos SET insumo_id = ?, descripcion = ?, activo = 1 WHERE id = ?',
                [$insumoId, $descripcion, $anterior['id']]
            );

            return (int) $anterior['id'];
        }

        return Database::insert(
            'INSERT INTO insumo_codigos (insumo_id, codigo, descripcion) VALUES (?, ?, ?)',
            [$insumoId, $codigo, $descripcion]
        );
    }

    public function quitarCodigo(int $codigoId): void
    {
        Database::execute('UPDATE insumo_codigos SET activo = 0 WHERE id = ?', [$codigoId]);
    }

    /**
     * Cada palabra debe aparecer en el nombre, la categoría, el sector o un código ("pilas aa" encuentra "PILAS ALCALINA
     * CHICA AA"). Además, el texto completo puede ser un código exacto, incluido un código de barras.
     * La collation de la base no distingue mayúsculas ni tildes: "jabon" encuentra "JABÓN".
     *
     * @return array{0: string, 1: list<string>}
     */
    private static function condicionTexto(string $texto): array
    {
        $palabras = preg_split('/\s+/', trim($texto), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($palabras === []) {
            return ['1 = 1', []];
        }

        $partes = [];
        $parametros = [];

        foreach ($palabras as $palabra) {
            $like = '%' . addcslashes($palabra, '%_\\') . '%';
            $partes[] = '(i.nombre LIKE ? OR c.nombre LIKE ? OR cp.nombre LIKE ? OR s.nombre LIKE ?
                OR i.codigo LIKE ? OR i.codigo_udec LIKE ?)';
            array_push($parametros, $like, $like, $like, $like, $like, $like);
        }

        $exacto = trim($texto);
        $parametros[] = $exacto;

        return [
            '((' . implode(' AND ', $partes) . ')
              OR EXISTS (SELECT 1 FROM insumo_codigos ic WHERE ic.insumo_id = i.id AND ic.activo = 1 AND ic.codigo = ?))',
            $parametros,
        ];
    }
}
