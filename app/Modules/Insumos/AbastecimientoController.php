<?php

declare(strict_types=1);

namespace App\Modules\Insumos;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Core\ValidationException;
use App\Core\View;
use App\Modules\Panel\Inicio;

defined('APP_ROOT') || exit;

/**
 * «Ingresar abastecimiento» (las administrativas): lo que va llegando a la bodega, en una sola ventana sobre el inicio.
 * Se escribe o escanea cada producto (su código SAP, que es el código UdeC: 4105031; su código de barras; o su nombre),
 * con su cantidad al lado, y «Agregar» (o Enter, o la pistola) lo suma a la lista de abajo. En la lista se cambia la
 * cantidad de cada uno o se quita. «Ingresar a la bodega» suma todo de una vez y deja la boleta arriba, con la lista
 * vacía para lo siguiente que llegue.
 */
final class AbastecimientoController
{
    private InsumoService $insumos;

    public function __construct()
    {
        $this->insumos = new InsumoService();
    }

    /**
     * Con ?producto= (elegido en la lista que aparece al escribir) lo agrega a la lista, con 1. Con ?q= (un nombre que
     * coincide con varios productos) muestra los que coinciden.
     */
    public function index(Request $request): Response
    {
        $producto = $request->query('producto');
        if (is_string($producto) && ctype_digit($producto)) {
            return $this->agregar((int) $producto, 1);
        }

        $texto = trim((string) $request->query('q', ''));
        $lista = [];
        foreach (Abastecimiento::productos() as $id => $cantidad) {
            $insumo = $this->insumos->buscarPorId((int) $id);
            if ($insumo !== null) {
                $lista[] = ['insumo' => $insumo, 'cantidad' => $cantidad];
            }
        }

        return Inicio::conVentana(
            'ingresar-abastecimiento',
            'Ingresar productos',
            View::render('Insumos::abastecimiento/productos', [
                'lista' => $lista,
                'texto' => $texto,
                'resultados' => $texto === '' ? [] : $this->insumos->buscar($texto, 'todos'),
                'agregado' => Session::getFlash('agregado'),
                'ingresado' => Session::getFlash('ingresado'),
            ], null),
            scripts: ['js/modules/insumos.js'],
            titulo: '¿Qué llegó?',
        );
    }

    /**
     * El formulario, que lleva todo: primero se guardan las cantidades de la lista (como estén escritas) y después se
     * hace lo que se tocó: «Agregar» (o Enter) suma el producto escrito, la ✕ quita uno de la lista e «Ingresar a la
     * bodega» suma todo al stock.
     */
    public function leer(Request $request): Response
    {
        $this->guardarCantidades($request->input('cantidades'), Abastecimiento::productos());

        $quitar = $request->input('quitar');
        if (is_string($quitar) && ctype_digit($quitar)) {
            Abastecimiento::cantidad((int) $quitar, 0);

            return Response::redirect(Url::to('abastecimiento'));
        }

        if ($request->input('accion') === 'ingresar') {
            return $this->ingresar();
        }

        // «Agregar»: un producto elegido de la lista de coincidencias, o lo escrito (código o nombre). Enter en una
        // cantidad de la lista, sin nada escrito arriba, solo guarda las cantidades.
        $cantidad = self::cantidadEscrita($request->input('cantidad'));
        $elegido = $request->input('insumo_id');
        if (is_string($elegido) && ctype_digit($elegido)) {
            return $this->agregar((int) $elegido, $cantidad);
        }
        $codigo = $request->input('codigo');
        if ((!is_string($codigo) || trim($codigo) === '') && Abastecimiento::productos() !== []) {
            return Response::redirect(Url::to('abastecimiento'));
        }

        $lectura = (new LectorDeProducto($this->insumos))->leer($codigo);

        return match ($lectura['tipo']) {
            'producto' => $this->agregar($lectura['id'], $cantidad),
            'nombre' => $this->agregarPorNombre($lectura['texto'], $cantidad),
            'desconocido' => throw new ValidationException(['codigo' => "El código {$lectura['codigo']} no está en el sistema. "
                . 'Revisa que esté bien escrito, o pide al encargado de bodega que agregue el producto.']),
        };
    }

    // ---------- Apoyo ----------

    /** «Ingresar a la bodega»: suma todo lo de la lista y vuelve con la boleta y la lista vacía. */
    private function ingresar(): Response
    {
        $productos = Abastecimiento::productos();
        if ($productos === []) {
            throw new ValidationException(['codigo' => 'Agrega al menos un producto de los que llegaron.']);
        }

        $ingresados = (new StockService())->ingresarAbastecimiento($productos);
        Abastecimiento::terminar();
        Session::flash('ingresado', $ingresados);

        return Response::redirect(Url::to('abastecimiento'));
    }

    /**
     * Las cantidades escritas en la lista (cantidades[insumo_id]). Una que no sirve no se guarda y se avisa.
     *
     * @param array<int, int> $enLista
     * @throws ValidationException
     */
    private function guardarCantidades(mixed $cantidades, array $enLista): void
    {
        if (!is_array($cantidades)) {
            return;
        }

        $malas = 0;
        foreach ($cantidades as $id => $valor) {
            if (!isset($enLista[(int) $id])) {
                continue;
            }
            $valor = is_string($valor) ? trim($valor) : '';
            if (ctype_digit($valor) && strlen($valor) <= 5 && (int) $valor >= 1 && (int) $valor <= StockService::MAXIMO) {
                Abastecimiento::cantidad((int) $id, (int) $valor);
            } else {
                $malas++;
            }
        }

        if ($malas > 0) {
            throw new ValidationException(['lista' => 'Revisa las cantidades de la lista: cada una debe ser un número entero desde 1.']);
        }
    }

    /** @throws ValidationException si el producto no existe */
    private function agregar(int $insumoId, int $cantidad): Response
    {
        $insumo = (new LectorDeProducto($this->insumos))->productoActivo($insumoId);
        Abastecimiento::sumar($insumoId, $cantidad);
        Session::flash('agregado', $insumo['nombre']);

        return Response::redirect(Url::to('abastecimiento'));
    }

    private function agregarPorNombre(string $texto, int $cantidad): Response
    {
        $coinciden = $this->insumos->buscar($texto, 'todos');

        return count($coinciden) === 1
            ? $this->agregar((int) $coinciden[0]['id'], $cantidad)
            : Response::redirect(Url::to('abastecimiento', ['q' => $texto]));
    }

    /**
     * La cantidad escrita junto al producto (vacía: 1).
     *
     * @throws ValidationException si no es un número entero entre 1 y el máximo
     */
    private static function cantidadEscrita(mixed $valor): int
    {
        $valor = is_string($valor) ? trim($valor) : '';
        if ($valor === '') {
            return 1;
        }
        $cantidad = ctype_digit($valor) && strlen($valor) <= 5 ? (int) $valor : 0;
        if ($cantidad < 1 || $cantidad > StockService::MAXIMO) {
            throw new ValidationException(['cantidad' => 'Escribe cuántas llegaron, con un número entero. Ejemplo: 5.']);
        }

        return $cantidad;
    }
}
