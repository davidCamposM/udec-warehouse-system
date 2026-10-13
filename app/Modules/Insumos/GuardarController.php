<?php

declare(strict_types=1);

namespace App\Modules\Insumos;

use App\Core\Permission;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Core\ValidationException;
use App\Core\View;
use App\Modules\Panel\Inicio;
use App\Modules\Sectores\SectorService;

defined('APP_ROOT') || exit;

/**
 * «Registrar producto»: un formulario por etapas, en una ventana sobre el inicio (con el fondo difuminado), con los
 * menos clics posibles.
 * 1) El producto: se escanea, se escribe (los que coinciden aparecen al escribir) o se toca uno de los últimos
 *    registrados. 2) Cuántas unidades llegaron. 3) Dónde quedaron: se elige el sector (viene marcado el de antes) o se
 *    escanea su etiqueta, y «Confirmar producto» registra todo.
 * Al confirmar, la ventana muestra solo lo registrado, con «Finalizar» (al inicio) y «Registrar otro producto» (a la
 * etapa 1).
 */
final class GuardarController
{
    /** Nombres de los pasos, para la barra de avance. */
    public const PASOS = ['El producto', 'Cuántos llegaron', 'Dónde quedó'];

    /** Cuántos de los últimos productos registrados se ofrecen para elegir con un toque. */
    private const RECIENTES = 5;

    private InsumoService $insumos;
    private SectorService $sectores;

    public function __construct()
    {
        $this->insumos = new InsumoService();
        $this->sectores = new SectorService();
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

        Guardado::terminar();

        // Recién confirmado: lo registrado, y nada más.
        $registrado = Session::getFlash('registrado');
        if ($registrado !== null) {
            return $this->ventana('listo', ['registrado' => $registrado]);
        }

        $texto = trim((string) $request->query('q', ''));

        return $this->ventana('producto', [
            'texto' => $texto,
            'resultados' => $texto === '' ? [] : $this->insumos->buscar($texto, 'todos'),
            'recientes' => $this->insumos->ultimosRegistrados(self::RECIENTES),
            'puedeAdministrar' => Permission::can('insumos.administrar'),
        ]);
    }

    /** Un código leído, un nombre escrito o un producto elegido de la lista. */
    public function leerProducto(Request $request): Response
    {
        $elegido = $request->input('insumo_id');
        if (is_string($elegido) && ctype_digit($elegido)) {
            return $this->empezarCon((int) $elegido);
        }

        // Un código que nadie tiene: se ofrece registrarlo. Un nombre: se muestran los productos que coinciden.
        $lectura = (new LectorDeProducto($this->insumos, $this->sectores))->leer($request->input('codigo'));

        return match ($lectura['tipo']) {
            'producto' => $this->empezarCon($lectura['id']),
            'desconocido' => Response::redirect(Url::to('guardar/desconocido', ['codigo' => $lectura['codigo']])),
            'nombre' => $this->elegirPorNombre($lectura['texto']),
        };
    }

    /** Un nombre escrito: si un solo producto coincide, se sigue con ese; si no, se muestran los que coinciden. */
    private function elegirPorNombre(string $texto): Response
    {
        $coinciden = $this->insumos->buscar($texto, 'todos');

        return count($coinciden) === 1
            ? $this->empezarCon((int) $coinciden[0]['id'])
            : Response::redirect(Url::to('guardar', ['q' => $texto]));
    }

    /** El código leído no es de ningún producto: ¿es nuevo o es otro envase de uno que ya existe? */
    public function desconocido(Request $request): Response
    {
        $codigo = trim((string) $request->query('codigo', ''));
        if ($codigo === '') {
            return Response::redirect(Url::to('guardar'));
        }

        return $this->ventana('desconocido', [
            'codigo' => $codigo,
            'puedeAdministrar' => Permission::can('insumos.administrar'),
        ]);
    }

    /** Elegir el producto al que pertenece un envase con un código nuevo. */
    public function envase(Request $request): Response
    {
        $codigo = trim((string) $request->query('codigo', ''));
        if ($codigo === '') {
            return Response::redirect(Url::to('guardar'));
        }

        $texto = trim((string) $request->query('q', ''));

        return $this->ventana('envase', [
            'codigo' => $codigo,
            'texto' => $texto,
            'resultados' => $texto === '' ? [] : $this->insumos->buscar($texto, 'todos'),
        ]);
    }

    public function guardarEnvase(Request $request): Response
    {
        $insumoId = (string) $request->input('insumo_id', '');
        $insumo = ctype_digit($insumoId) ? $this->insumos->buscarPorId((int) $insumoId) : null;
        if ($insumo === null) {
            throw new ValidationException(['codigo_barras' => 'Elige el producto de la lista.']);
        }

        $codigo = $this->insumos->agregarCodigo((int) $insumo['id'], ['codigo_barras' => $request->input('codigo', '')]);
        Session::flash('exito', "Listo. Ahora el código {$codigo} lleva a «{$insumo['nombre']}».");

        return $this->empezarCon((int) $insumo['id']);
    }

    // ---------- Paso 2: cuántos llegaron ----------

    public function cantidad(Request $request): Response
    {
        [$guardado, $insumo] = $this->enCurso();
        if ($guardado === null) {
            return $this->sinGuardado();
        }

        return $this->ventana('cantidad', [
            'insumo' => $insumo,
            'cantidad' => $guardado['cantidad'],
        ]);
    }

    /** «Siguiente» con la cantidad escrita. */
    public function leerCantidad(Request $request): Response
    {
        [$guardado] = $this->enCurso();
        if ($guardado === null) {
            return $this->sinGuardado();
        }

        Guardado::cambiarCantidad(self::cantidadEscrita($request->input('cantidad')));

        return Response::redirect(Url::to('guardar/sector'));
    }

    // ---------- Paso 3: dónde se guardó ----------

    public function sector(Request $request): Response
    {
        [$guardado, $insumo] = $this->enCurso();
        if ($guardado === null) {
            return $this->sinGuardado();
        }

        // El sector donde estaba el producto va primero y viene marcado: lo más probable es que vuelva al mismo lugar.
        $sectores = $this->sectores->activos();
        usort($sectores, static fn (array $a, array $b): int
            => ((int) $b['id'] === (int) $insumo['sector_id']) <=> ((int) $a['id'] === (int) $insumo['sector_id']));

        return $this->ventana('sector', [
            'insumo' => $insumo,
            'cantidad' => $guardado['cantidad'],
            'sectores' => $sectores,
            'puedeCrearSectores' => Permission::can('sectores.administrar'),
        ]);
    }

    /** «Confirmar producto» con el sector elegido o escaneado. Registra todo y vuelve a la etapa 1 con el aviso. */
    public function guardarSector(Request $request): Response
    {
        [$guardado, $insumo] = $this->enCurso();
        if ($guardado === null) {
            return $this->sinGuardado();
        }

        $codigo = trim((string) $request->input('codigo', ''));
        $sectorId = (string) $request->input('sector_id', '');

        if ($codigo !== '') {
            $sector = $this->sectores->buscarPorCodigo($codigo);
            if ($sector === null) {
                throw new ValidationException(['codigo' => $this->insumos->idPorCodigo($codigo) === (int) $insumo['id']
                    ? 'Ese es el código del producto. Ahora escanea la etiqueta del sector.'
                    : 'Ese código no es de un sector. Escanea la etiqueta del sector o elígelo en la lista.']);
            }
        } elseif (ctype_digit($sectorId)) {
            $sector = $this->sectores->buscarPorId((int) $sectorId);
        } else {
            throw new ValidationException(['sector_id' => 'Elige el sector donde quedó, o escanea su etiqueta.']);
        }

        return $this->guardarEn($sector);
    }

    // ---------- Apoyo ----------

    /**
     * Cada etapa se muestra en una ventana sobre el inicio, que queda de fondo, difuminado.
     *
     * @param array<string, mixed> $datos los de la vista de la etapa (Insumos::guardar/{$etapa})
     */
    private function ventana(string $etapa, array $datos): Response
    {
        return Inicio::conVentana(
            'guardar-en-bodega',
            'Anota lo que llegó a la bodega.',
            View::render("Insumos::guardar/{$etapa}", $datos, null),
            scripts: ['js/modules/insumos.js'],
        );
    }

    private function empezarCon(int $insumoId): Response
    {
        (new LectorDeProducto($this->insumos, $this->sectores))->productoActivo($insumoId);
        Guardado::empezar($insumoId);

        return Response::redirect(Url::to('guardar/cantidad'));
    }

    /** @param array<string, mixed>|null $sector */
    private function guardarEn(?array $sector): Response
    {
        $guardado = Guardado::actual();

        if ($sector === null || (int) $sector['activo'] !== 1) {
            throw new ValidationException(['sector_id' => 'Ese sector está desactivado. Elige otro.']);
        }

        $resultado = (new StockService())->guardarEnBodega($guardado['insumo_id'], $guardado['cantidad'], (int) $sector['id']);
        Guardado::terminar();
        Session::flash('registrado', $resultado);

        return Response::redirect(Url::to('guardar'));
    }

    /** @return array{0: array{insumo_id: int, cantidad: int}|null, 1: array<string, mixed>|null} lo que se está guardando */
    private function enCurso(): array
    {
        $guardado = Guardado::actual();
        $insumo = $guardado !== null ? $this->insumos->buscarPorId($guardado['insumo_id']) : null;

        if ($insumo === null || (int) $insumo['activo'] !== 1) {
            Guardado::terminar();

            return [null, null];
        }

        return [$guardado, $insumo];
    }

    /** Se llegó a un paso sin haber escaneado un producto (por ejemplo, después de guardar o al recargar). */
    private function sinGuardado(): Response
    {
        Session::flash('aviso', 'Empieza escaneando el producto que llegó.');

        return Response::redirect(Url::to('guardar'));
    }

    /** @throws ValidationException */
    private static function cantidadEscrita(mixed $valor): int
    {
        $valor = is_string($valor) ? trim($valor) : '';
        $cantidad = ctype_digit($valor) ? (int) $valor : 0;
        StockService::validarCantidad($cantidad);

        return $cantidad;
    }
}
