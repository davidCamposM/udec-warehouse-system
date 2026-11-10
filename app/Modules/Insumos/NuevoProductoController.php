<?php

declare(strict_types=1);

namespace App\Modules\Insumos;

use App\Core\Permission;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Core\ValidationException;
use App\Core\Database;
use App\Core\View;
use App\Modules\Panel\Inicio;
use App\Modules\Sectores\SectorService;

defined('APP_ROOT') || exit;

/**
 * «Agregar nuevo producto»: un formulario por etapas, en una ventana sobre el inicio (con el fondo difuminado), pensado
 * para la pistola como «Ingresar productos»:
 * 1) El producto: se escanea su código de barras (Enter pasa al nombre) y se escribe el nombre; el código UdeC va
 *    plegado, porque casi nunca se tiene a mano. 2) El tipo: tocar uno avanza solo. 3) Cuántas llegaron: el número en
 *    grande; escanear otra vez el producto suma 1. 4) Dónde quedaron: se escanea la etiqueta de la repisa y se agrega
 *    (crea el producto y registra esas unidades, todo junto).
 * Cada etapa revisa lo suyo antes de seguir. Al agregar se vuelve a la etapa 1 con el aviso de lo agregado, lista para
 * el siguiente; si se llegó desde «Ingresar productos», se vuelve allí con lo registrado.
 */
final class NuevoProductoController
{
    private const ETAPAS = [1 => 'producto', 2 => 'tipo', 3 => 'cantidad', 4 => 'sector'];

    /** Cada etapa es su propia ventana, con su pregunta de título. */
    private const PREGUNTAS = [
        'producto' => '¿Qué producto es?',
        'tipo' => '¿De qué tipo es?',
        'cantidad' => '¿Cuántas unidades llegaron?',
        'sector' => '¿Dónde quedó?',
    ];

    private InsumoService $insumos;
    private SectorService $sectores;

    public function __construct()
    {
        $this->insumos = new InsumoService();
        $this->sectores = new SectorService();
    }

    /** Empieza de nuevo. Con ?nombre= o ?codigo= parte con eso escrito; con ?para=guardar, desde «Ingresar productos». */
    public function empezar(Request $request): Response
    {
        $nombre = $request->query('nombre');
        $codigo = $request->query('codigo');
        NuevoProducto::empezar(
            is_string($nombre) ? trim($nombre) : '',
            is_string($codigo) ? trim($codigo) : '',
            $request->query('para') === 'guardar'
        );

        return Response::redirect(Url::to('insumos/nuevo/producto'));
    }

    // ---------- Etapa 1: el producto ----------

    public function producto(Request $request): Response
    {
        if (NuevoProducto::actual() === null) {
            NuevoProducto::empezar();
        }

        return $this->ventana('producto', [
            'nuevo' => NuevoProducto::actual(),
            // Recién agregado: el aviso va arriba, y el campo queda listo para el siguiente producto.
            'agregado' => Session::getFlash('agregado'),
        ]);
    }

    public function leerProducto(Request $request): Response
    {
        if (NuevoProducto::actual() === null) {
            return $this->volverA(null);
        }

        $limpios = $this->insumos->revisarNuevo($request->post(), ['nombre', 'codigo_barras', 'codigo_udec']);
        NuevoProducto::completar([
            'nombre' => $limpios['nombre'],
            'codigo_barras' => $limpios['codigo_barras'],
            'codigo_udec' => (string) $limpios['codigo_udec'],
        ], 2);

        return Response::redirect(Url::to('insumos/nuevo/tipo'));
    }

    // ---------- Etapa 2: el tipo ----------

    public function tipo(Request $request): Response
    {
        $nuevo = NuevoProducto::actual();
        if ($nuevo === null || $nuevo['etapa'] < 2) {
            return $this->volverA($nuevo);
        }

        return $this->ventana('tipo', ['nuevo' => $nuevo, 'categorias' => $this->insumos->categoriasPrincipales()]);
    }

    public function leerTipo(Request $request): Response
    {
        $nuevo = NuevoProducto::actual();
        if ($nuevo === null || $nuevo['etapa'] < 2) {
            return $this->volverA($nuevo);
        }

        $elegida = (string) $request->input('categoria_id', '');
        if (!in_array($elegida, array_map('strval', array_column($this->insumos->categoriasPrincipales(), 'id')), true)) {
            throw new ValidationException(['categoria_id' => 'Elige de qué tipo es.']);
        }
        NuevoProducto::completar(['categoria_id' => $elegida], 3);

        return Response::redirect(Url::to('insumos/nuevo/cantidad'));
    }

    // ---------- Etapa 3: cuántos llegaron ----------

    public function cantidad(Request $request): Response
    {
        $nuevo = NuevoProducto::actual();
        if ($nuevo === null || $nuevo['etapa'] < 3) {
            return $this->volverA($nuevo);
        }

        return $this->ventana('cantidad', [
            'nuevo' => $nuevo,
            'categoria' => $this->categoria($nuevo),
            // Se acaba de sumar una unidad al escanear otra vez el producto.
            'sumada' => Session::getFlash('sumada') === true,
        ]);
    }

    public function leerCantidad(Request $request): Response
    {
        $nuevo = NuevoProducto::actual();
        if ($nuevo === null || $nuevo['etapa'] < 3) {
            return $this->volverA($nuevo);
        }

        $valor = trim((string) $request->input('cantidad', ''));

        // Se escaneó otra vez el código del producto: suma 1 al número que se veía y se queda en esta etapa (contar
        // pasando por la pistola).
        if ($nuevo['codigo_barras'] !== '' && strcasecmp($valor, $nuevo['codigo_barras']) === 0) {
            $antes = StockService::cantidadAntes($request->input('cantidad_antes'), (int) $nuevo['cantidad']);
            NuevoProducto::completar(['cantidad' => (string) min($antes + 1, StockService::MAXIMO)], 3);
            Session::flash('sumada', true);

            return Response::redirect(Url::to('insumos/nuevo/cantidad'));
        }

        // Otro código leído (no un número corto): se explica en vez de pedir «un número entero».
        if ($valor !== '' && (!ctype_digit($valor) || strlen($valor) > 5)) {
            throw new ValidationException(['cantidad' => $this->insumos->idPorCodigo($valor) !== null
                ? 'Ese código es de otro producto. Agrega primero este y después escanea el otro.'
                : 'Ese código no es de este producto. Escribe cuántas unidades llegaron. Ejemplo: 12.']);
        }

        StockService::validarCantidad(ctype_digit($valor) ? (int) $valor : 0);
        NuevoProducto::completar(['cantidad' => (string) (int) $valor], 4);

        return Response::redirect(Url::to('insumos/nuevo/sector'));
    }

    // ---------- Etapa 4: dónde quedó ----------

    public function sector(Request $request): Response
    {
        $nuevo = NuevoProducto::actual();
        if ($nuevo === null || $nuevo['etapa'] < 4) {
            return $this->volverA($nuevo);
        }

        return $this->ventana('sector', [
            'nuevo' => $nuevo,
            'categoria' => $this->categoria($nuevo),
            'sectores' => $this->sectores->activos(),
            'puedeCrearSectores' => Permission::can('sectores.administrar'),
        ]);
    }

    /**
     * «Agregar producto»: con el sector elegido o escaneado, crea el producto y registra las unidades que llegaron. Con
     * «Sin ubicación por ahora» (las repisas aún sin etiqueta), lo agrega sin sector.
     */
    public function agregar(Request $request): Response
    {
        $nuevo = NuevoProducto::actual();
        if ($nuevo === null || $nuevo['etapa'] < 4) {
            return $this->volverA($nuevo);
        }

        $sector = $request->input('sin_sector') === '1' ? null : $this->sectorElegido($request);
        NuevoProducto::completar(['sector_id' => (string) ($sector['id'] ?? '')], 4);

        try {
            [$creado, $registrado] = Database::transaction(function () use ($nuevo, $sector): array {
                $creado = $this->insumos->crear($nuevo);
                $registrado = (new StockService())->guardarEnBodega($creado['id'], (int) $nuevo['cantidad'], $sector !== null ? (int) $sector['id'] : null);

                return [$creado, $registrado];
            });
        } catch (ValidationException $e) {
            // Un dato de una etapa anterior dejó de servir (por ejemplo, alguien agregó mientras tanto un producto con
            // el mismo nombre): el error se muestra en esa etapa.
            $errores = $e->errors();
            $etapa = match (true) {
                isset($errores['nombre']) || isset($errores['codigo_barras']) || isset($errores['codigo_udec']) => 'producto',
                isset($errores['categoria_id']) => 'tipo',
                isset($errores['cantidad']) => 'cantidad',
                default => 'sector',
            };
            Session::flash('_errores', $etapa === 'sector' ? ['sector_id' => 'Ese sector ya no está disponible. Elige otro.'] : $errores);

            return Response::redirect(Url::to("insumos/nuevo/{$etapa}"));
        }

        if ($nuevo['para_guardar']) {
            NuevoProducto::terminar();
            Session::flash('registrado', $registrado);

            return Response::redirect(Url::to('guardar'));
        }

        NuevoProducto::empezar();
        Session::flash('agregado', $creado + [
            'cantidad' => $registrado['cantidad'],
            'stock' => $registrado['stock'],
            'sector' => $registrado['sector'],
            'sector_codigo' => $registrado['sector_codigo'],
        ]);

        return Response::redirect(Url::to('insumos/nuevo/producto'));
    }

    /**
     * El sector escaneado o elegido en la lista.
     *
     * @return array<string, mixed>
     */
    private function sectorElegido(Request $request): array
    {
        $codigo = trim((string) $request->input('codigo', ''));
        $sectorId = (string) $request->input('sector_id', '');

        if ($codigo !== '') {
            $sector = $this->sectores->buscarPorCodigo($codigo);
            if ($sector === null) {
                throw new ValidationException(['codigo' => 'Ese código no es de un sector. Escanea la etiqueta del sector o elígelo en la lista.']);
            }
        } elseif (ctype_digit($sectorId)) {
            $sector = $this->sectores->buscarPorId((int) $sectorId);
        } else {
            throw new ValidationException(['codigo' => 'Escanea la etiqueta de la repisa donde quedó, o elígela en la lista.']);
        }

        if ($sector === null || (int) $sector['activo'] !== 1) {
            throw new ValidationException(['sector_id' => 'Ese sector ya no existe. Elige otro.']);
        }

        return $sector;
    }

    /** @param array<string, mixed> $nuevo */
    private function categoria(array $nuevo): string
    {
        return $this->insumos->nombreCategoria(ctype_digit($nuevo['categoria_id']) ? (int) $nuevo['categoria_id'] : null);
    }

    // ---------- Apoyo ----------

    /**
     * Cada etapa es su propia ventana sobre el inicio (que queda de fondo, difuminado): su pregunta de título y, debajo,
     * en qué etapa va («Agregar nuevo producto · 2 de 4»). Al avanzar, ventana.js cambia una ventana por la otra.
     *
     * @param array<string, mixed> $datos los de la vista de la etapa (Insumos::nuevo/{$etapa})
     */
    private function ventana(string $etapa, array $datos): Response
    {
        $numero = (int) array_search($etapa, self::ETAPAS, true);

        return Inicio::conVentana(
            'agregar-producto',
            "Agregar nuevo producto · {$numero} de 4",
            View::render("Insumos::nuevo/{$etapa}", $datos, null),
            titulo: self::PREGUNTAS[$etapa],
        );
    }

    /**
     * Se llegó a una etapa sin pasar por las anteriores (o la sesión venció): se va a la más avanzada permitida.
     *
     * @param array<string, mixed>|null $nuevo
     */
    private function volverA(?array $nuevo): Response
    {
        if ($nuevo === null) {
            NuevoProducto::empezar();
            Session::flash('aviso', 'Empieza escribiendo el nombre del producto.');
        }

        $etapa = self::ETAPAS[min((int) ($nuevo['etapa'] ?? 1), 4)];

        return Response::redirect(Url::to("insumos/nuevo/{$etapa}"));
    }
}
