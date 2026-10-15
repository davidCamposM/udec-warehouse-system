<?php

declare(strict_types=1);

namespace App\Modules\Insumos;

use App\Core\Database;
use App\Core\ValidationException;
use App\Core\Validator;
use App\Modules\Historial\AuditoriaService;

defined('APP_ROOT') || exit;

/**
 * Catálogo de insumos: búsqueda, ficha y estado de cada producto. Otros módulos (panel, historial,
 * inventario) usan este servicio, nunca el repositorio directamente.
 */
final class InsumoService
{
    /** Movimientos que muestra la ficha. */
    public const MOVIMIENTOS_FICHA = 10;

    /** Estado de un producto => [texto, ícono]. Siempre se muestra escrito, no solo con color. */
    public const ESTADOS = [
        'ok' => ['Hay', 'check'],
        'low' => ['Queda poco', 'alert'],
        'out' => ['No queda', 'x-circle'],
        'off' => ['Desactivado', 'close'],
    ];

    public function __construct(
        private readonly InsumoRepository $insumos = new InsumoRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService(),
    ) {
    }

    /**
     * Desactivado → off; sin stock → out; en o bajo su mínimo (si tiene uno) → low; si no → ok.
     *
     * @param array<string, mixed> $insumo
     */
    public static function estado(array $insumo): string
    {
        return match (true) {
            (int) $insumo['activo'] !== 1 => 'off',
            (float) $insumo['stock_actual'] <= 0 => 'out',
            (float) $insumo['stock_minimo'] > 0 && (float) $insumo['stock_actual'] <= (float) $insumo['stock_minimo'] => 'low',
            default => 'ok',
        };
    }

    /**
     * Lo buscado sin encontrarlo, para llevarlo a «Agregar nuevo producto»: si parece un código (sin espacios y con
     * números) va como código de barras; si no, como nombre.
     *
     * @return array<string, string>
     */
    public static function paraAgregar(string $texto): array
    {
        $texto = trim($texto);
        if ($texto === '') {
            return [];
        }

        return [!preg_match('/\s/', $texto) && preg_match('/\d/', $texto) ? 'codigo' : 'nombre' => $texto];
    }

    /** Filtro válido de la lista; cualquier otro valor se trata como "todos". */
    public static function filtro(mixed $valor, bool $puedeVerDesactivados): string
    {
        $filtro = is_string($valor) && isset(InsumoRepository::FILTROS[$valor]) ? $valor : 'todos';

        return $filtro === 'desactivados' && !$puedeVerDesactivados ? 'todos' : $filtro;
    }

    /** @return list<array<string, mixed>> */
    public function buscar(string $texto, string $filtro): array
    {
        return $this->insumos->buscar($texto, $filtro);
    }

    /** @return array<string, int> */
    public function contar(string $texto): array
    {
        return $this->insumos->contar($texto);
    }

    /**
     * La lista bajo una barra de búsqueda: hasta $limite productos activos que coinciden con el texto (primero los que
     * empiezan con lo escrito) y cuántos coinciden en total.
     *
     * @return array{productos: list<array<string, mixed>>, total: int}
     */
    public function sugerencias(string $texto, int $limite): array
    {
        $texto = trim($texto);
        if ($texto === '') {
            return ['productos' => [], 'total' => 0];
        }

        return [
            'productos' => $this->insumos->sugerencias($texto, $limite),
            'total' => $this->insumos->contar($texto)['todos'],
        ];
    }

    /**
     * El inventario del inicio: las categorías principales de insumos y los productos activos de la elegida (incluidos
     * los de sus subcategorías), de a $porPagina. Sin categoría elegida, o con una que no es principal, van todos. Una
     * página fuera de rango se trata como la más cercana.
     *
     * @return array{categorias: list<array<string, mixed>>, categoria: ?int, productos: list<array<string, mixed>>,
     *     pagina: int, paginas: int, total: int, desde: int}
     */
    public function inventario(?int $categoriaId, int $pagina = 1, int $porPagina = 10): array
    {
        $categorias = $this->insumos->categorias();
        $principales = $this->categoriasPrincipales();
        if (!in_array($categoriaId, array_map('intval', array_column($principales, 'id')), true)) {
            $categoriaId = null;
        }

        $productos = $this->insumos->buscar('', 'todos');
        if ($categoriaId !== null) {
            $incluidas = [$categoriaId];
            foreach ($categorias as $categoria) {
                if ((int) $categoria['padre_id'] === $categoriaId) {
                    $incluidas[] = (int) $categoria['id'];
                }
            }
            $productos = array_values(array_filter(
                $productos,
                static fn (array $insumo): bool => in_array((int) $insumo['categoria_id'], $incluidas, true)
            ));
        }

        $total = count($productos);
        $paginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min(max(1, $pagina), $paginas);
        $desde = ($pagina - 1) * $porPagina;

        return [
            'categorias' => $principales,
            'categoria' => $categoriaId,
            'productos' => array_slice($productos, $desde, $porPagina),
            'pagina' => $pagina,
            'paginas' => $paginas,
            'total' => $total,
            'desde' => $desde,
        ];
    }

    /**
     * Las categorías principales de insumos (Materiales de Oficina, Materiales Especializados…), por nombre.
     *
     * @return list<array{id: int, nombre: string, padre_id: null}>
     */
    public function categoriasPrincipales(): array
    {
        return array_values(array_filter(
            $this->insumos->categorias(),
            static fn (array $categoria): bool => $categoria['padre_id'] === null
        ));
    }

    /** Id del producto si el texto es exactamente uno de sus códigos (lo típico al leer con la pistola). */
    public function idPorCodigo(string $texto): ?int
    {
        $texto = trim($texto);

        return $texto === '' ? null : $this->insumos->idPorCodigo($texto);
    }

    /** @return array<string, mixed>|null */
    public function buscarPorId(int $id): ?array
    {
        return $this->insumos->findById($id);
    }

    /** Si el producto nunca tuvo movimientos (su primer ajuste será, casi seguro, la carga inicial). */
    public function sinMovimientos(int $id): bool
    {
        return !$this->insumos->tieneMovimientos($id);
    }

    /**
     * Los últimos productos que entraron a bodega: en «Registrar producto» se eligen con un toque.
     *
     * @return list<array<string, mixed>>
     */
    public function ultimosRegistrados(int $limite): array
    {
        return $this->insumos->ultimosConMovimiento('entrada', $limite);
    }

    /**
     * Los últimos productos que se retiraron: en «Retirar producto» se eligen con un toque.
     *
     * @return list<array<string, mixed>>
     */
    public function ultimosRetirados(int $limite): array
    {
        return $this->insumos->ultimosConMovimiento('salida', $limite);
    }

    // ---------- Crear, editar, activar y desactivar ----------

    /**
     * Revisa los datos de una etapa de «Agregar nuevo producto», sin crear nada: así cada etapa avisa sus errores.
     * Al final, crear() vuelve a revisar todo.
     *
     * @param array<string, mixed> $datos
     * @param list<string>         $campos nombre, categoria_id, stock_minimo, codigo_udec y/o codigo_barras
     * @return array<string, mixed> los valores limpios de esos campos (codigo_barras: '' si no vino)
     * @throws ValidationException
     */
    public function revisarNuevo(array $datos, array $campos): array
    {
        $limpios = [];
        $errores = [];
        $delProducto = array_values(array_diff($campos, ['codigo_barras']));

        if ($delProducto !== []) {
            try {
                $limpios = $this->validar($datos, null, $delProducto);
            } catch (ValidationException $e) {
                $errores = $e->errors();
            }
        }

        if (in_array('codigo_barras', $campos, true)) {
            try {
                $limpios['codigo_barras'] = $this->validarCodigoNuevo($datos, obligatorio: false)['codigo'] ?? '';
            } catch (ValidationException $e) {
                $errores += $e->errors();
            }
        }

        if ($errores !== []) {
            throw new ValidationException($errores);
        }

        return $limpios;
    }

    /** «Materiales de Oficina › Papeles y Cartones», o «Sin categoría». */
    public function nombreCategoria(?int $id): string
    {
        $categorias = array_column($this->insumos->categorias(), null, 'id');
        $categoria = $id !== null ? ($categorias[$id] ?? null) : null;

        if ($categoria === null) {
            return 'Sin categoría';
        }

        $padre = $categoria['padre_id'] !== null ? ($categorias[(int) $categoria['padre_id']] ?? null) : null;

        return $padre !== null ? "{$padre['nombre']} › {$categoria['nombre']}" : $categoria['nombre'];
    }

    /**
     * Categorías para el formulario, agrupadas bajo su principal. Una principal sin subcategorías es ella misma
     * la opción.
     *
     * @return array{grupos: list<array{titulo: string, opciones: list<array{id: int, nombre: string}>, principal: array{id: int, nombre: string}}>}
     */
    public function opcionesFormulario(): array
    {
        $principales = [];
        $hijas = [];

        foreach ($this->insumos->categorias() as $categoria) {
            if ($categoria['padre_id'] === null) {
                $principales[(int) $categoria['id']] = $categoria['nombre'];
            } else {
                $hijas[(int) $categoria['padre_id']][] = ['id' => (int) $categoria['id'], 'nombre' => $categoria['nombre']];
            }
        }

        $grupos = [];
        foreach ($principales as $id => $nombre) {
            $grupos[] = [
                'titulo' => $nombre,
                'opciones' => $hijas[$id] ?? [['id' => $id, 'nombre' => $nombre]],
                'principal' => ['id' => $id, 'nombre' => 'Toda la categoría'],
            ];
        }

        return ['grupos' => $grupos];
    }

    /**
     * Crea el producto con el siguiente código interno y, si viene, su código de barras.
     *
     * @param array<string, mixed> $datos lo enviado por el formulario
     * @return array{id: int, codigo: string, nombre: string}
     * @throws ValidationException
     */
    public function crear(array $datos): array
    {
        // Se revisan el producto y su código de barras juntos, para mostrar todos los errores de una vez.
        $errores = [];
        $insumo = [];
        $barras = null;

        try {
            $insumo = $this->validar($datos, null);
        } catch (ValidationException $e) {
            $errores = $e->errors();
        }

        try {
            $barras = $this->validarCodigoNuevo($datos, obligatorio: false);
        } catch (ValidationException $e) {
            $errores += $e->errors();
        }

        if ($errores !== []) {
            throw new ValidationException($errores);
        }

        return Database::transaction(function () use ($insumo, $barras): array {
            $insumo['codigo'] = $this->insumos->siguienteCodigo();
            $id = $this->insumos->create($insumo);

            if ($barras !== null) {
                $this->insumos->guardarCodigo($id, $barras['codigo'], $barras['descripcion']);
            }

            $this->auditoria->registrar('crear', 'insumo', $id, [
                'codigo' => $insumo['codigo'],
                'nombre' => $insumo['nombre'],
                'codigo_barras' => $barras['codigo'] ?? null,
            ]);

            return ['id' => $id, 'codigo' => $insumo['codigo'], 'nombre' => $insumo['nombre']];
        });
    }

    /**
     * @param array<string, mixed> $datos
     * @throws ValidationException
     */
    public function editar(int $id, array $datos): void
    {
        $actual = $this->insumos->findById($id) ?? throw new ValidationException(['nombre' => 'Ese producto no existe.']);
        $nuevo = $this->validar($datos, $id);

        $cambios = AuditoriaService::diferencias($actual, $nuevo);
        if ($cambios === []) {
            return;
        }

        Database::transaction(function () use ($id, $nuevo, $cambios): void {
            $this->insumos->update($id, $nuevo);
            $this->auditoria->registrar('editar', 'insumo', $id, ['cambios' => $cambios]);
        });
    }

    public function cambiarActivo(int $id, bool $activo): void
    {
        $insumo = $this->insumos->findById($id);

        if ($insumo === null || (int) $insumo['activo'] === (int) $activo) {
            return;
        }

        Database::transaction(function () use ($id, $activo, $insumo): void {
            $this->insumos->setActivo($id, $activo);
            $this->auditoria->registrar($activo ? 'activar' : 'desactivar', 'insumo', $id, [
                'codigo' => $insumo['codigo'],
                'nombre' => $insumo['nombre'],
            ]);
        });
    }

    // ---------- Códigos de barra ----------

    /**
     * @param array<string, mixed> $datos codigo_barras y descripcion
     * @throws ValidationException
     */
    public function agregarCodigo(int $insumoId, array $datos): string
    {
        if ($this->insumos->findById($insumoId) === null) {
            throw new ValidationException(['codigo_barras' => 'Ese producto no existe.']);
        }
        $barras = $this->validarCodigoNuevo($datos, obligatorio: true);

        Database::transaction(function () use ($insumoId, $barras): void {
            $this->insumos->guardarCodigo($insumoId, $barras['codigo'], $barras['descripcion']);
            $this->auditoria->registrar('agregar_codigo', 'insumo', $insumoId, ['codigo_barras' => $barras['codigo']]);
        });

        return $barras['codigo'];
    }

    /** @return array<string, mixed>|null */
    public function codigoDeInsumo(int $insumoId, int $codigoId): ?array
    {
        return $this->insumos->findCodigoDeInsumo($insumoId, $codigoId);
    }

    public function quitarCodigo(int $insumoId, int $codigoId): void
    {
        $codigo = $this->insumos->findCodigoDeInsumo($insumoId, $codigoId);

        if ($codigo === null) {
            return;
        }

        Database::transaction(function () use ($insumoId, $codigo): void {
            $this->insumos->quitarCodigo((int) $codigo['id']);
            $this->auditoria->registrar('quitar_codigo', 'insumo', $insumoId, ['codigo_barras' => $codigo['codigo']]);
        });
    }

    // ---------- Validación ----------

    /**
     * Datos del producto ya limpios, en el formato de la base. Mensajes en palabras simples. Con $campos revisa solo
     * esos (los de una etapa de «Agregar nuevo producto»).
     *
     * @param array<string, mixed> $datos
     * @param list<string>|null    $campos nombre, categoria_id, stock_minimo y/o codigo_udec; null: todos
     * @return array{codigo_udec?: ?string, nombre?: string, categoria_id?: ?int, stock_minimo?: int}
     * @throws ValidationException
     */
    private function validar(array $datos, ?int $id, ?array $campos = null): array
    {
        // Espacios repetidos fuera, para que "CLORO  1 L" y "CLORO 1 L" sean el mismo nombre.
        foreach (['nombre', 'codigo_udec'] as $campo) {
            if (is_string($datos[$campo] ?? null)) {
                $datos[$campo] = preg_replace('/\s+/u', ' ', trim($datos[$campo]));
            }
        }

        $reglas = [
            'nombre' => 'requerido|max:150',
            'categoria_id' => 'entero',
            'stock_minimo' => 'entero|min:0|max:999999',
            'codigo_udec' => 'max:20',
        ];
        $campos ??= array_keys($reglas);
        [$limpios, $errores] = self::validarCampos($datos, array_intersect_key($reglas, array_flip($campos)));

        if (isset($errores['stock_minimo'])) {
            $errores['stock_minimo'] = 'Escribe un número entero, sin coma ni puntos. Ejemplo: 5.';
        }

        if (!empty($limpios['categoria_id']) && !$this->insumos->categoriaValida($limpios['categoria_id'])) {
            $errores['categoria_id'] = 'Elige una categoría de la lista.';
        }

        if (!isset($errores['nombre']) && isset($limpios['nombre'])) {
            $otro = $this->insumos->idPorNombre($limpios['nombre'], $id);
            if ($otro !== null) {
                $errores['nombre'] = 'Ya existe un producto con ese nombre. Búscalo en la lista antes de crear otro.';
            }
        }

        $udec = $limpios['codigo_udec'] ?? null;
        if ($udec !== null && !isset($errores['codigo_udec'])) {
            $duenio = $this->insumos->codigoEnUso($udec, $id);
            if ($duenio !== null) {
                $errores['codigo_udec'] = "Ese código ya lo usa {$duenio}.";
            }
        }

        if ($errores !== []) {
            throw new ValidationException($errores);
        }

        return array_intersect_key([
            'codigo_udec' => $udec,
            'nombre' => $limpios['nombre'] ?? '',
            'categoria_id' => empty($limpios['categoria_id']) ? null : $limpios['categoria_id'],
            'stock_minimo' => (int) ($limpios['stock_minimo'] ?? 0),
        ], array_flip($campos));
    }

    /**
     * Código de barras que se va a agregar: formato y que no lo use otra cosa del sistema.
     *
     * @param array<string, mixed> $datos codigo_barras y descripcion (opcional)
     * @return array{codigo: string, descripcion: ?string}|null null si es opcional y no vino
     * @throws ValidationException
     */
    private function validarCodigoNuevo(array $datos, bool $obligatorio): ?array
    {
        $codigo = is_string($datos['codigo_barras'] ?? null) ? trim($datos['codigo_barras']) : '';

        if ($codigo === '') {
            if ($obligatorio) {
                throw new ValidationException(['codigo_barras' => 'Escanea el código o escríbelo.']);
            }

            return null;
        }

        [$limpios, $errores] = self::validarCampos($datos, ['descripcion' => 'max:80']);

        // Lo que escribe un lector: letras, números y signos, sin espacios.
        if (!preg_match('/^[\x21-\x7E]{3,64}$/', $codigo)) {
            $errores['codigo_barras'] = 'El código no se leyó bien. Escanéalo de nuevo o escríbelo sin espacios.';
        } else {
            $duenio = $this->insumos->codigoEnUso($codigo);
            if ($duenio !== null) {
                $errores['codigo_barras'] = "Ese código ya lo usa {$duenio}.";
            }
        }

        if ($errores !== []) {
            throw new ValidationException($errores);
        }

        return ['codigo' => $codigo, 'descripcion' => $limpios['descripcion'] ?? null];
    }

    /**
     * Valida cada campo por separado: así los campos correctos quedan disponibles para las demás revisiones
     * (por ejemplo, la categoría elegida) aunque otro campo tenga un error.
     *
     * @param array<string, mixed>  $datos
     * @param array<string, string> $reglas
     * @return array{0: array<string, mixed>, 1: array<string, string>} [limpios, errores]
     */
    private static function validarCampos(array $datos, array $reglas): array
    {
        $limpios = [];
        $errores = [];

        foreach ($reglas as $campo => $regla) {
            try {
                $limpios += Validator::validate($datos, [$campo => $regla]);
            } catch (ValidationException $e) {
                $errores += $e->errors();
            }
        }

        return [$limpios, $errores];
    }

    /**
     * Todo lo que muestra la ficha de un producto.
     *
     * @return array{insumo: array<string, mixed>, codigos: list<array<string, mixed>>, movimientos: list<array<string, mixed>>}|null
     */
    public function ficha(int $id): ?array
    {
        $insumo = $this->insumos->findById($id);

        if ($insumo === null) {
            return null;
        }

        return [
            'insumo' => $insumo,
            'codigos' => $this->insumos->codigos($id),
            'movimientos' => $this->insumos->ultimosMovimientos($id, self::MOVIMIENTOS_FICHA),
        ];
    }
}
