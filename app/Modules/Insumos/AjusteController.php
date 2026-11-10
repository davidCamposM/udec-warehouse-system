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
 * «Corregir stock»: dos ventanas sobre el inicio (con el fondo difuminado), cada etapa la suya.
 * 1) El producto: se escanea, o se escribe y se elige (los que coinciden aparecen al escribir). 2) Lo que hay: cuántas
 *    hay realmente y por qué cambia; «Actualizar» deja la cantidad nueva al tiro, sin otra confirmación.
 * Al terminar se vuelve a la etapa 1 con la boleta de lo actualizado (antes, ahora, motivo y stock), lista para el
 * siguiente producto. Sirve también para la carga inicial del stock.
 */
final class AjusteController
{
    /** Cada etapa es su propia ventana: etapa => [número, la pregunta que va de título]. */
    private const ETAPAS = [
        'producto' => [1, '¿Qué producto vas a corregir?'],
        'contar' => [2, '¿Cuántas hay realmente?'],
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

        Ajuste::terminar();
        $texto = trim((string) $request->query('q', ''));

        return $this->ventana('producto', [
            'texto' => $texto,
            'resultados' => $texto === '' ? [] : $this->insumos->buscar($texto, 'todos'),
            'actualizado' => Session::getFlash('actualizado'),
        ]);
    }

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
            'desconocido' => throw new ValidationException(['codigo' => 'Ese código no está registrado. Si es un producto nuevo, '
                . 'agrégalo con «Agregar nuevo producto» y después regístralo con «Ingresar productos».']),
        };
    }

    /** Un nombre escrito: si un solo producto coincide, se sigue con ese; si no, se muestran los que coinciden. */
    private function elegirPorNombre(string $texto): Response
    {
        $coinciden = $this->insumos->buscar($texto, 'todos');

        return count($coinciden) === 1
            ? $this->empezarCon((int) $coinciden[0]['id'])
            : Response::redirect(Url::to('ajustar', ['q' => $texto]));
    }

    // ---------- Paso 2: cuántos hay realmente y por qué ----------

    public function contar(Request $request): Response
    {
        [$ajuste, $insumo] = $this->enCurso();
        if ($ajuste === null) {
            return $this->sinAjuste();
        }

        return $this->ventana('contar', [
            'insumo' => $insumo,
            'contado' => (string) $insumo['stock_actual'],
            // Si nunca tuvo movimientos, lo más probable es que sea la carga inicial.
            'motivo' => $this->insumos->sinMovimientos((int) $insumo['id']) ? 'carga' : '',
            'motivos' => StockService::MOTIVOS_AJUSTE,
        ]);
    }

    /**
     * «Actualizar»: con la cantidad contada y el motivo, deja la cantidad nueva y vuelve a la etapa 1 con la boleta.
     * «visto» es la cantidad que el sistema mostraba al contar: si cambió mientras tanto (alguien registró o retiró), no
     * se actualiza y se avisa, con el número nuevo a la vista.
     */
    public function leerConteo(Request $request): Response
    {
        [$ajuste, $insumo] = $this->enCurso();
        if ($ajuste === null) {
            return $this->sinAjuste();
        }

        $errores = [];
        $contado = trim((string) $request->input('contado', ''));
        $motivo = (string) $request->input('motivo', '');
        $visto = (string) $request->input('visto', '');

        if (!ctype_digit($contado) || (int) $contado > StockService::MAXIMO) {
            $errores['contado'] = 'Escribe cuántas hay, con un número entero. Ejemplo: 12. Si no queda ninguna, escribe 0.';
        }
        if (!isset(StockService::MOTIVOS_AJUSTE[$motivo])) {
            $errores['motivo'] = 'Elige por qué cambia la cantidad.';
        }
        if ($errores !== []) {
            throw new ValidationException($errores);
        }

        $resultado = (new StockService())->ajustar(
            (int) $insumo['id'],
            (int) $contado,
            ctype_digit($visto) ? (int) $visto : (int) $insumo['stock_actual'],
            StockService::MOTIVOS_AJUSTE[$motivo]
        );

        Ajuste::terminar();
        Session::flash('actualizado', $resultado);

        return Response::redirect(Url::to('ajustar'));
    }

    // ---------- Apoyo ----------

    /**
     * Cada etapa es su propia ventana sobre el inicio (que queda de fondo, difuminado): su pregunta de título y, debajo,
     * en qué etapa va («Corregir stock · 2 de 2»). Al avanzar, ventana.js cambia una ventana por la otra.
     *
     * @param array<string, mixed> $datos los de la vista de la etapa (Insumos::ajustar/{$etapa})
     */
    private function ventana(string $etapa, array $datos): Response
    {
        [$numero, $pregunta] = self::ETAPAS[$etapa];

        return Inicio::conVentana(
            'ajustar-cantidad',
            "Corregir stock · {$numero} de 2",
            View::render("Insumos::ajustar/{$etapa}", $datos, null),
            scripts: ['js/modules/insumos.js'],
            titulo: $pregunta,
        );
    }

    private function empezarCon(int $insumoId): Response
    {
        (new LectorDeProducto($this->insumos))->productoActivo($insumoId);
        Ajuste::empezar($insumoId);

        return Response::redirect(Url::to('ajustar/contar'));
    }

    /** @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null} el ajuste en curso y su producto */
    private function enCurso(): array
    {
        $ajuste = Ajuste::actual();
        $insumo = $ajuste !== null ? $this->insumos->buscarPorId($ajuste['insumo_id']) : null;

        if ($insumo === null || (int) $insumo['activo'] !== 1) {
            Ajuste::terminar();

            return [null, null];
        }

        return [$ajuste, $insumo];
    }

    private function sinAjuste(): Response
    {
        Session::flash('aviso', 'Empieza eligiendo el producto que vas a actualizar.');

        return Response::redirect(Url::to('ajustar'));
    }
}
