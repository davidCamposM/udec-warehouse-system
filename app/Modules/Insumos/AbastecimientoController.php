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
 * «Ingresar abastecimiento» (las administrativas): lo que llega a la bodega con una reserva de SAP. Un formulario por
 * etapas en una ventana sobre el inicio, como «Registrar producto»:
 * 1) El número de reserva («Se creó la Reserva 0000018857»; sirve sin los ceros).
 * 2) Lo que llegó: se escribe el código del material de la reserva (es el código UdeC del producto: 4105031), o su
 *    nombre, con su cantidad al lado, y «Agregar» (o Enter, o la pistola) lo suma a la lista de abajo. En la lista se
 *    cambia la cantidad de cada uno o se quita. «Ingresar a la bodega» suma todo de una vez.
 * Al ingresar se vuelve a la etapa 1 con la boleta, lista para la siguiente reserva.
 */
final class AbastecimientoController
{
    /** Cada etapa es su propia ventana: etapa => [número, la pregunta que va de título]. */
    private const ETAPAS = [
        'reserva' => [1, '¿Cuál es el número de reserva?'],
        'productos' => [2, '¿Qué llegó?'],
    ];

    private InsumoService $insumos;

    public function __construct()
    {
        $this->insumos = new InsumoService();
    }

    // ---------- Etapa 1: la reserva ----------

    /** Con ?producto= (elegido en la lista que aparece al escribir, en la etapa 2) lo agrega a la lista, con 1. */
    public function reserva(Request $request): Response
    {
        $producto = $request->query('producto');
        if (is_string($producto) && ctype_digit($producto) && Abastecimiento::actual() !== null) {
            return $this->agregar((int) $producto, 1);
        }

        $actual = Abastecimiento::actual();

        return $this->ventana('reserva', [
            'reserva' => $actual['reserva'] ?? '',
            // Recién ingresada: la boleta arriba, y el campo listo para la siguiente reserva.
            'ingresada' => Session::getFlash('ingresada'),
        ]);
    }

    public function leerReserva(Request $request): Response
    {
        Abastecimiento::empezar(StockService::numeroReserva($request->input('reserva')));

        return Response::redirect(Url::to('abastecimiento/productos'));
    }

    // ---------- Etapa 2: lo que llegó ----------

    /** Con ?q= (un nombre que coincide con varios productos, o con ninguno) muestra los que coinciden. */
    public function productos(Request $request): Response
    {
        $actual = Abastecimiento::actual();
        if ($actual === null) {
            return $this->sinReserva();
        }

        $texto = trim((string) $request->query('q', ''));
        $lista = [];
        foreach ($actual['productos'] as $id => $cantidad) {
            $insumo = $this->insumos->buscarPorId((int) $id);
            if ($insumo !== null) {
                $lista[] = ['insumo' => $insumo, 'cantidad' => $cantidad];
            }
        }

        return $this->ventana('productos', [
            'reserva' => $actual['reserva'],
            'anterior' => (new StockService())->reservaIngresada($actual['reserva']),
            'lista' => $lista,
            'texto' => $texto,
            'resultados' => $texto === '' ? [] : $this->insumos->buscar($texto, 'todos'),
            'agregado' => Session::getFlash('agregado'),
        ]);
    }

    /**
     * El formulario de la etapa 2, que lleva todo: primero se guardan las cantidades de la lista (como estén escritas) y
     * después se hace lo que se tocó: «Agregar» (o Enter) suma el producto escrito, la ✕ quita uno de la lista e
     * «Ingresar a la bodega» suma todo al stock y vuelve a la etapa 1 con la boleta.
     */
    public function leerProducto(Request $request): Response
    {
        $actual = Abastecimiento::actual();
        if ($actual === null) {
            return $this->sinReserva();
        }

        $this->guardarCantidades($request->input('cantidades'), $actual['productos']);

        $quitar = $request->input('quitar');
        if (is_string($quitar) && ctype_digit($quitar)) {
            Abastecimiento::cantidad((int) $quitar, 0);

            return Response::redirect(Url::to('abastecimiento/productos'));
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
        if ((!is_string($codigo) || trim($codigo) === '') && (Abastecimiento::actual()['productos'] ?? []) !== []) {
            return Response::redirect(Url::to('abastecimiento/productos'));
        }

        $lectura = (new LectorDeProducto($this->insumos))->leer($codigo);

        return match ($lectura['tipo']) {
            'producto' => $this->agregar($lectura['id'], $cantidad),
            'nombre' => $this->agregarPorNombre($lectura['texto'], $cantidad),
            'desconocido' => throw new ValidationException(['codigo' => "El código {$lectura['codigo']} no está en el sistema. "
                . 'Revisa que sea el del material, o pide al encargado de bodega que agregue el producto.']),
        };
    }

    // ---------- Apoyo ----------

    /** «Ingresar a la bodega»: suma todo lo de la lista con el número de reserva, y vuelve a la etapa 1 con la boleta. */
    private function ingresar(): Response
    {
        $actual = Abastecimiento::actual();
        if ($actual === null || $actual['productos'] === []) {
            throw new ValidationException(['codigo' => 'Agrega al menos un producto de los que llegaron.']);
        }

        $ingresados = (new StockService())->ingresarReserva($actual['reserva'], $actual['productos']);
        Abastecimiento::terminar();
        Session::flash('ingresada', ['reserva' => $actual['reserva'], 'productos' => $ingresados]);

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

        return Response::redirect(Url::to('abastecimiento/productos'));
    }

    private function agregarPorNombre(string $texto, int $cantidad): Response
    {
        $coinciden = $this->insumos->buscar($texto, 'todos');

        return count($coinciden) === 1
            ? $this->agregar((int) $coinciden[0]['id'], $cantidad)
            : Response::redirect(Url::to('abastecimiento/productos', ['q' => $texto]));
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

    /** @param array<string, mixed> $datos los de la vista de la etapa (Insumos::abastecimiento/{$etapa}) */
    private function ventana(string $etapa, array $datos): Response
    {
        [$numero, $pregunta] = self::ETAPAS[$etapa];

        return Inicio::conVentana(
            'ingresar-abastecimiento',
            "Ingresar abastecimiento · {$numero} de 2",
            View::render("Insumos::abastecimiento/{$etapa}", $datos, null),
            scripts: ['js/modules/insumos.js'],
            titulo: $pregunta,
        );
    }

    private function sinReserva(): Response
    {
        Session::flash('aviso', 'Empieza escribiendo el número de reserva.');

        return Response::redirect(Url::to('abastecimiento'));
    }
}
