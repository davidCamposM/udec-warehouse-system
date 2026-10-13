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
 * «Actualizar cantidad»: un formulario por etapas, en una ventana sobre el inicio (con el fondo difuminado).
 * 1) El producto: se escanea, o se escribe y se elige (los que coinciden aparecen al escribir). 2) Lo que hay: cuántas
 *    hay realmente y por qué cambia. 3) Confirmar: «antes → ahora» y «Confirmar cantidad».
 * Al terminar se vuelve a la etapa 1 con el aviso de lo actualizado, lista para el siguiente producto. Sirve también
 * para la carga inicial del stock.
 */
final class AjusteController
{
    /** Nombres de los pasos, para la barra de avance. */
    public const PASOS = ['El producto', 'Lo que hay', 'Confirmar'];

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
                . 'agrégalo con «Agregar nuevo producto» y después regístralo con «Registrar producto».']),
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

        // Si nunca tuvo movimientos, lo más probable es que sea la carga inicial.
        $motivo = $ajuste['motivo'] ?? ($this->insumos->sinMovimientos((int) $insumo['id']) ? 'carga' : '');

        return $this->ventana('contar', [
            'insumo' => $insumo,
            'contado' => (string) ($ajuste['contado'] ?? $insumo['stock_actual']),
            'motivo' => $motivo,
            'detalle' => $ajuste['detalle'] ?? '',
            'motivos' => StockService::MOTIVOS_AJUSTE,
        ]);
    }

    public function leerConteo(Request $request): Response
    {
        [$ajuste, $insumo] = $this->enCurso();
        if ($ajuste === null) {
            return $this->sinAjuste();
        }

        $errores = [];
        $contado = trim((string) $request->input('contado', ''));
        $motivo = (string) $request->input('motivo', '');
        $detalle = preg_replace('/\s+/u', ' ', trim((string) $request->input('detalle', '')));

        if (!ctype_digit($contado) || (int) $contado > StockService::MAXIMO) {
            $errores['contado'] = 'Escribe cuántas hay, con un número entero. Ejemplo: 12. Si no queda ninguna, escribe 0.';
        } elseif ((int) $contado === (int) $insumo['stock_actual']) {
            $errores['contado'] = 'Es la misma cantidad que dice el sistema: no hay nada que actualizar.';
        }

        if (!isset(StockService::MOTIVOS_AJUSTE[$motivo])) {
            $errores['motivo'] = 'Elige por qué cambia la cantidad.';
        }

        if ($motivo === 'otro' && $detalle === '') {
            $errores['detalle'] = 'Escribe cuál es el motivo. Ejemplo: se usaron en la feria de la facultad.';
        } elseif (mb_strlen($detalle) > 150) {
            $errores['detalle'] = 'El detalle es muy largo: usa 150 letras como máximo.';
        }

        if ($errores !== []) {
            throw new ValidationException($errores);
        }

        Ajuste::contar((int) $contado, (int) $insumo['stock_actual'], $motivo, $detalle);

        return Response::redirect(Url::to('ajustar/confirmar'));
    }

    // ---------- Paso 3: confirmar ----------

    public function confirmar(Request $request): Response
    {
        [$ajuste, $insumo] = $this->enCurso();
        if ($ajuste === null) {
            return $this->sinAjuste();
        }
        if (!isset($ajuste['contado'])) {
            return Response::redirect(Url::to('ajustar/contar'));
        }

        return $this->ventana('confirmar', [
            'insumo' => $insumo,
            'antes' => $ajuste['stock_visto'],
            'ahora' => $ajuste['contado'],
            'motivo' => self::motivo($ajuste),
        ]);
    }

    /** «Confirmar cantidad»: actualiza y vuelve a la etapa 1 con el aviso de lo actualizado. */
    public function guardar(Request $request): Response
    {
        [$ajuste, $insumo] = $this->enCurso();
        if ($ajuste === null || !isset($ajuste['contado'])) {
            return $this->sinAjuste();
        }

        try {
            $resultado = (new StockService())->ajustar(
                (int) $insumo['id'],
                $ajuste['contado'],
                $ajuste['stock_visto'],
                self::motivo($ajuste)
            );
        } catch (ValidationException $e) {
            // Si el stock cambió mientras se contaba, la confirmación se vuelve a mostrar con el stock nuevo.
            $actual = $this->insumos->buscarPorId((int) $insumo['id']);
            if ($actual !== null) {
                Ajuste::actualizarStockVisto((int) $actual['stock_actual']);
            }
            throw $e;
        }

        Ajuste::terminar();
        Session::flash('actualizado', $resultado);

        return Response::redirect(Url::to('ajustar'));
    }

    // ---------- Apoyo ----------

    /**
     * Cada etapa se muestra en una ventana sobre el inicio, que queda de fondo, difuminado.
     *
     * @param array<string, mixed> $datos los de la vista de la etapa (Insumos::ajustar/{$etapa})
     */
    private function ventana(string $etapa, array $datos): Response
    {
        return Inicio::conVentana(
            'ajustar-cantidad',
            'Deja en el sistema lo que realmente hay.',
            View::render("Insumos::ajustar/{$etapa}", $datos, null),
            ['css/pages/ajustar.css'],
            ['js/modules/insumos.js'],
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

    /** "Conteo físico" o "Conteo físico: faltaban en el estante B". @param array<string, mixed> $ajuste */
    private static function motivo(array $ajuste): string
    {
        $texto = StockService::MOTIVOS_AJUSTE[$ajuste['motivo']];

        return $ajuste['detalle'] !== '' ? "{$texto}: {$ajuste['detalle']}" : $texto;
    }
}
