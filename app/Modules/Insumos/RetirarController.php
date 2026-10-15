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
 * «Retirar producto»: un formulario por etapas, en una ventana sobre el inicio (con el fondo difuminado), como
 * «Registrar producto». Lo hace la cuenta del encargado; el retiro queda a su nombre.
 * 1) El producto: se escanea, se escribe (los que coinciden aparecen al escribir) o se toca uno de los últimos
 *    retirados. 2) Cuántas unidades se retiran, y «Retirar» descuenta. Si no alcanza, no se descuenta nada.
 * Al confirmar, la ventana muestra solo lo retirado, con «Finalizar» (al inicio) y «Retirar otro producto» (a la
 * etapa 1).
 */
final class RetirarController
{
    /** Nombres de los pasos, para la barra de avance. */
    public const PASOS = ['El producto', 'Cuántos se retiran'];

    /** Cuántos de los últimos productos retirados se ofrecen para elegir con un toque. */
    private const RECIENTES = 5;

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

        // Recién confirmado: lo retirado, y nada más.
        $retirado = Session::getFlash('retirado');
        if ($retirado !== null) {
            return $this->ventana('listo', ['retirado' => $retirado]);
        }

        $texto = trim((string) $request->query('q', ''));

        return $this->ventana('producto', [
            'texto' => $texto,
            'resultados' => $texto === '' ? [] : $this->insumos->buscar($texto, 'todos'),
            'recientes' => $this->insumos->ultimosRetirados(self::RECIENTES),
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

        return $this->ventana('cantidad', ['insumo' => $insumo]);
    }

    /** «Retirar» con la cantidad escrita: descuenta y vuelve a la etapa 1, que muestra lo retirado. */
    public function retirar(Request $request): Response
    {
        $insumo = $this->enCurso();
        if ($insumo === null) {
            return $this->sinRetiro();
        }

        $valor = $request->input('cantidad');
        $valor = is_string($valor) ? trim($valor) : '';
        $cantidad = ctype_digit($valor) && strlen($valor) < 7 ? (int) $valor : 0;

        $resultado = (new StockService())->retirar((int) $insumo['id'], $cantidad);
        Retiro::terminar();
        Session::flash('retirado', $resultado);

        return Response::redirect(Url::to('retirar'));
    }

    // ---------- Apoyo ----------

    /**
     * Cada etapa se muestra en una ventana sobre el inicio, que queda de fondo, difuminado.
     *
     * @param array<string, mixed> $datos los de la vista de la etapa (Insumos::retirar/{$etapa})
     */
    private function ventana(string $etapa, array $datos): Response
    {
        return Inicio::conVentana(
            'retirar-producto',
            'Descuenta lo que sale de la bodega.',
            View::render("Insumos::retirar/{$etapa}", $datos, null),
            scripts: ['js/modules/insumos.js'],
        );
    }

    /** @throws ValidationException si el producto no existe, está desactivado o no le quedan unidades */
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
