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
 * «Retirar producto»: un formulario por etapas, en una ventana sobre el inicio (con el fondo difuminado), igual que
 * «Registrar producto»: cada etapa es su propia ventana. Lo hace la cuenta del encargado; el retiro queda a su nombre.
 * 1) El producto: se escanea (o se escribe y se elige en la lista que aparece al escribir).
 * 2) Cuántas unidades se retiran: el número en grande. Escanear otra vez el mismo producto suma 1 (sin pasar de lo que
 *    hay). «Retirar» descuenta; si no alcanza, no se descuenta nada.
 * Al retirar se vuelve a la etapa 1 con el aviso de lo retirado, lista para el siguiente producto.
 */
final class RetirarController
{
    /** Cada etapa es su propia ventana: etapa => [número, la pregunta que va de título]. */
    private const ETAPAS = [
        'producto' => [1, '¿Qué producto se retira?'],
        'cantidad' => [2, '¿Cuántas unidades se retiran?'],
    ];

    private InsumoService $insumos;

    public function __construct()
    {
        $this->insumos = new InsumoService();
    }

    // ---------- Paso 1: el producto ----------

    /**
     * Empieza de nuevo. Con ?producto= (desde la ficha o desde la lista bajo el campo) parte con ese. Con ?q= (un
     * nombre enviado que coincide con varios productos, o con ninguno) muestra los que coinciden.
     */
    public function producto(Request $request): Response
    {
        $producto = $request->query('producto');
        if (is_string($producto) && ctype_digit($producto)) {
            return $this->empezarCon((int) $producto);
        }

        Retiro::terminar();
        $texto = trim((string) $request->query('q', ''));

        return $this->ventana('producto', [
            'texto' => $texto,
            'resultados' => $texto === '' ? [] : $this->insumos->buscar($texto, 'todos'),
            // Recién retirado: el aviso va arriba, y el campo queda listo para el siguiente producto.
            'retirado' => Session::getFlash('retirado'),
        ]);
    }

    /** Un código leído, un nombre escrito o un producto elegido de la lista. */
    public function leerProducto(Request $request): Response
    {
        $elegido = $request->input('insumo_id');
        if (is_string($elegido) && ctype_digit($elegido)) {
            return $this->empezarCon((int) $elegido);
        }

        $lectura = (new LectorDeProducto($this->insumos))->leer($request->input('codigo'));

        return match ($lectura['tipo']) {
            'producto' => $this->empezarCon($lectura['id']),
            'nombre' => $this->elegirPorNombre($lectura['texto']),
            'desconocido' => throw new ValidationException(['codigo' => 'Ese código no está registrado. Revisa que sea el '
                . 'código del producto, o búscalo por su nombre.']),
        };
    }

    /** Un nombre escrito: si un solo producto coincide, se sigue con ese; si no, se muestran los que coinciden. */
    private function elegirPorNombre(string $texto): Response
    {
        $coinciden = $this->insumos->buscar($texto, 'todos');

        return count($coinciden) === 1
            ? $this->empezarCon((int) $coinciden[0]['id'])
            : Response::redirect(Url::to('retirar', ['q' => $texto]));
    }

    // ---------- Paso 2: cuántos se retiran ----------

    public function cantidad(Request $request): Response
    {
        $insumo = $this->enCurso();
        if ($insumo === null) {
            return $this->sinRetiro();
        }

        return $this->ventana('cantidad', [
            'insumo' => $insumo,
            'cantidad' => Retiro::actual()['cantidad'] ?? 1,
            // Se acaba de sumar una unidad al escanear otra vez el producto.
            'sumada' => Session::getFlash('sumada') === true,
        ]);
    }

    /**
     * «Retirar» con la cantidad escrita: descuenta y vuelve a la etapa 1, que muestra lo retirado. Si lo que llegó es el
     * código del mismo producto (se escaneó otra vez), suma 1 y se queda en esta etapa.
     */
    public function retirar(Request $request): Response
    {
        $insumo = $this->enCurso();
        if ($insumo === null) {
            return $this->sinRetiro();
        }

        $valor = $request->input('cantidad');
        $valor = is_string($valor) ? trim($valor) : '';

        // Una cantidad es un número corto; un código leído es más largo o lleva letras.
        if ($valor !== '' && (!ctype_digit($valor) || strlen($valor) > 5)) {
            $id = $this->insumos->idPorCodigo($valor);
            if ($id === (int) $insumo['id']) {
                $siguiente = StockService::cantidadAntes($request->input('cantidad_antes'), Retiro::actual()['cantidad'] ?? 1) + 1;
                if ($siguiente > (int) $insumo['stock_actual']) {
                    throw new ValidationException(['cantidad' => 'No se puede sumar otra: en bodega '
                        . ((int) $insumo['stock_actual'] === 1 ? 'queda ' : 'quedan ') . unidades($insumo['stock_actual']) . '.']);
                }
                Retiro::cambiarCantidad($siguiente);
                Session::flash('sumada', true);

                return Response::redirect(Url::to('retirar/cantidad'));
            }
            throw new ValidationException(['cantidad' => $id !== null
                ? 'Ese código es de otro producto. Retira primero este y después escanea el otro.'
                : 'Escribe cuántas unidades se retiran, con un número entero. Ejemplo: 2.']);
        }

        $cantidad = ctype_digit($valor) ? (int) $valor : 0;

        $resultado = (new StockService())->retirar((int) $insumo['id'], $cantidad);
        Retiro::terminar();
        Session::flash('retirado', $resultado);

        return Response::redirect(Url::to('retirar'));
    }

    // ---------- Apoyo ----------

    /**
     * Cada etapa es su propia ventana sobre el inicio (que queda de fondo, difuminado): su pregunta de título y, debajo,
     * en qué etapa va («Retirar producto · 2 de 2»). Al avanzar, ventana.js cambia una ventana por la otra.
     *
     * @param array<string, mixed> $datos los de la vista de la etapa (Insumos::retirar/{$etapa})
     */
    private function ventana(string $etapa, array $datos): Response
    {
        [$numero, $pregunta] = self::ETAPAS[$etapa];

        return Inicio::conVentana(
            'retirar-producto',
            "Retirar producto · {$numero} de 2",
            View::render("Insumos::retirar/{$etapa}", $datos, null),
            scripts: ['js/modules/insumos.js'],
            titulo: $pregunta,
        );
    }

    /** @throws ValidationException si el producto no existe o no le quedan unidades */
    private function empezarCon(int $insumoId): Response
    {
        $insumo = (new LectorDeProducto($this->insumos))->productoActivo($insumoId);
        if ((int) $insumo['stock_actual'] < 1) {
            throw new ValidationException(['codigo' => "No quedan unidades de «{$insumo['nombre']}» en bodega: no se puede retirar."]);
        }

        Retiro::empezar($insumoId);

        return Response::redirect(Url::to('retirar/cantidad'));
    }

    /** @return array<string, mixed>|null el producto que se está retirando, si sigue activo */
    private function enCurso(): ?array
    {
        $retiro = Retiro::actual();
        $insumo = $retiro !== null ? $this->insumos->buscarPorId($retiro['insumo_id']) : null;

        if ($insumo === null || (int) $insumo['activo'] !== 1) {
            Retiro::terminar();

            return null;
        }

        return $insumo;
    }

    /** Se llegó a la cantidad sin haber elegido un producto (por ejemplo, después de retirar o al recargar). */
    private function sinRetiro(): Response
    {
        Session::flash('aviso', 'Empieza escaneando el producto que se retira.');

        return Response::redirect(Url::to('retirar'));
    }
}
