<?php

declare(strict_types=1);

namespace App\Modules\Retiro;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Core\ValidationException;
use App\Core\View;
use App\Modules\Insumos\InsumoService;
use App\Modules\Insumos\LectorDeProducto;
use App\Modules\Insumos\StockService;
use App\Modules\Personas\PersonaService;
use App\Modules\Sectores\SectorService;

defined('APP_ROOT') || exit;

/**
 * El punto de retiro: como la caja de autoservicio del supermercado. La persona se identifica con su credencial o
 * RUT, escanea lo que retira, corrige si hace falta y confirma. Un auxiliar elige antes si retira o registra una
 * entrada (lo que llegó): escanea, elige dónde quedó y confirma. Solo funciona en un equipo habilitado, y no permite
 * nada más (ni crear productos, ni ajustar, ni consultar otra información).
 *
 * Cada acción responde con una redirección (funciona sin JavaScript) o, si la pide retiro.js, con JSON: el mensaje y la
 * pantalla nueva. Así la página no se recarga con cada lectura y no se pierde ningún código escaneado rápido.
 */
final class RetiroController
{
    private EstacionService $estaciones;
    private InsumoService $insumos;
    private PersonaService $personas;
    private SectorService $sectores;

    public function __construct()
    {
        $this->estaciones = new EstacionService();
        $this->insumos = new InsumoService();
        $this->personas = new PersonaService();
        $this->sectores = new SectorService();
    }

    /** La pantalla del punto de retiro: identificarse o lo que lleva la persona. */
    public function index(Request $request): Response
    {
        $estacion = $this->estaciones->actual();
        if ($estacion === null) {
            return Response::view('Retiro::no-habilitado', ['titulo' => 'Punto de retiro'], 'retiro', 403);
        }

        // Aquí no puede quedar abierta la sesión de un usuario del sistema: cualquiera podría usarla.
        if (Auth::check()) {
            Auth::logout();
            Session::flash('aviso', 'Se cerró la sesión del sistema en este computador: aquí funciona el punto de retiro.');

            return Response::redirect(Url::to('retiro'));
        }

        $carrito = Carrito::actual($estacion['id']);
        if ($carrito === null) {
            return Response::view('Retiro::identificar', ['titulo' => 'Punto de retiro', 'vencido' => Carrito::vencido()], 'retiro');
        }

        // Un auxiliar elige primero qué va a hacer.
        if (self::modoDe($carrito) === null) {
            return Response::view('Retiro::modo', ['titulo' => 'Punto de retiro', 'persona' => $carrito['persona']], 'retiro');
        }

        $texto = trim((string) $request->query('q', ''));

        return Response::view('Retiro::carrito', $this->pantalla($carrito, $texto) + [
            'titulo' => 'Punto de retiro',
            'persona' => $carrito['persona'],
            'inactividad' => Carrito::inactividad(),
            'scripts' => ['js/modules/retiro.js'],
        ], 'retiro');
    }

    public function identificar(Request $request): Response
    {
        $estacion = $this->estacion();
        $texto = (string) $request->input('credencial', '');

        if (trim($texto) === '') {
            throw new ValidationException(['credencial' => 'Escanea tu credencial o escribe tu RUT.']);
        }

        $persona = $this->personas->identificar($texto);
        if ($persona === null) {
            throw new ValidationException(['credencial' => 'No encontramos esa credencial ni ese RUT. Revisa y prueba otra vez.']);
        }
        if ((int) $persona['activo'] !== 1) {
            throw new ValidationException(['credencial' => 'Tu credencial está desactivada. Habla con el encargado de bodega.']);
        }

        Carrito::identificar($persona, $estacion['id']);

        return Response::redirect(Url::to('retiro'));
    }

    /** «Retirar» o «Registrar entrada» (esta, solo para auxiliares). */
    public function modo(Request $request): Response
    {
        [, $carrito] = $this->enCurso();
        if ($carrito === null) {
            return $this->cerrado($request);
        }

        Carrito::elegirModo((string) $request->input('modo', 'retiro'));

        return Response::redirect(Url::to('retiro'));
    }

    /** Un código escaneado, un nombre escrito o un producto tocado en los resultados: suma uno. */
    public function leer(Request $request): Response
    {
        [$estacion, $carrito] = $this->enCurso();
        if ($carrito === null) {
            return $this->cerrado($request);
        }

        $elegido = $request->input('insumo_id');
        if (is_string($elegido) && ctype_digit($elegido)) {
            $id = (int) $elegido;
        } else {
            $codigo = (string) $request->input('codigo', '');
            if ($this->personas->identificar($codigo) !== null) {
                throw new ValidationException(['codigo' => 'Esa es una credencial. Si retira otra persona, primero toca «Terminar».']);
            }

            $lectura = (new LectorDeProducto($this->insumos))->leer($codigo);
            if ($lectura['tipo'] === 'desconocido') {
                throw new ValidationException(['codigo' => 'Ese código no está registrado. Pide ayuda al encargado de bodega.']);
            }
            if ($lectura['tipo'] === 'nombre') {
                Carrito::renovar();

                return $request->wantsJson()
                    ? $this->json($carrito, 'info', self::modoDe($carrito) === 'entrada' ? 'Toca el producto que llegó.' : 'Toca el producto que retiras.', $lectura['texto'])
                    : Response::redirect(Url::to('retiro', ['q' => $lectura['texto']]));
            }
            $id = $lectura['id'];
        }

        $insumo = $this->insumos->buscarPorId($id);
        if ($insumo === null || (int) $insumo['activo'] !== 1) {
            throw new ValidationException(['codigo' => 'Ese producto no está disponible. Pide ayuda al encargado de bodega.']);
        }

        $items = $carrito['items'];
        $cantidad = ($items[$id] ?? 0) + 1;
        $entrada = self::modoDe($carrito) === 'entrada';
        if (!$entrada) {
            self::revisarStock($insumo, $cantidad, 'codigo');
        }

        $items[$id] = $cantidad;
        Carrito::guardarItems($items);

        return $this->respuesta($request, 'ok', "Agregado: {$insumo['nombre']}. " . ($entrada ? 'Llegaron ' : 'Llevas ') . unidades($cantidad) . '.');
    }

    /** Corregir una línea: − / +, escribir la cantidad o quitarla. */
    public function cambiar(Request $request): Response
    {
        [$estacion, $carrito] = $this->enCurso();
        if ($carrito === null) {
            return $this->cerrado($request);
        }

        $id = (int) $request->input('insumo_id', 0);
        $items = $carrito['items'];
        if (!isset($items[$id])) {
            return $this->respuesta($request, 'info', 'Ese producto ya no estaba en la lista.');
        }

        $insumo = $this->insumos->buscarPorId($id);
        $nombre = $insumo['nombre'] ?? 'el producto';
        $paso = (string) $request->input('paso', '0');
        $escrito = trim((string) $request->input('cantidad', ''));

        $cantidad = match (true) {
            $request->input('quitar') !== null => 0,
            $paso === '1' => $items[$id] + 1,
            $paso === '-1' => $items[$id] - 1,
            default => ctype_digit($escrito) ? (int) $escrito : -1,
        };

        if ($cantidad < 0 || $cantidad > StockService::MAXIMO) {
            throw new ValidationException(['codigo' => 'Escribe la cantidad con un número entero. Ejemplo: 3.']);
        }

        if ($cantidad === 0) {
            unset($items[$id]);
            Carrito::guardarItems($items);

            return $this->respuesta($request, 'info', "Se quitó {$nombre}.");
        }

        $entrada = self::modoDe($carrito) === 'entrada';
        if ($insumo !== null && !$entrada) {
            self::revisarStock($insumo, $cantidad, 'codigo');
        }
        $items[$id] = $cantidad;
        Carrito::guardarItems($items);

        return $this->respuesta($request, 'ok', "{$nombre}: " . ($entrada ? 'llegaron ' : 'llevas ') . unidades($cantidad) . '.');
    }

    public function confirmar(Request $request): Response
    {
        [$estacion, $carrito] = $this->enCurso();
        if ($carrito === null) {
            return $this->cerrado($request);
        }

        if (self::modoDe($carrito) !== 'retiro') {
            return Response::redirect(Url::to('retiro'));
        }

        $this->revisarPersona($carrito);

        $retirado = (new StockService())->retirar($carrito['persona']['id'], $estacion['id'], $carrito['items']);
        $this->estaciones->registrarUso($estacion['id']);
        Carrito::terminar();
        Session::flash('retirado', ['persona' => $carrito['persona']['nombre'], 'lineas' => $retirado]);

        return Response::redirect(Url::to('retiro/listo'));
    }

    // ---------- Entrada (auxiliares): ¿dónde quedó? ----------

    /** Tras escanear lo que llegó: lo que se registrará y el sector donde quedó. */
    public function sector(Request $request): Response
    {
        [, $carrito] = $this->enCurso();
        if ($carrito === null || self::modoDe($carrito) !== 'entrada' || $carrito['items'] === []) {
            return Response::redirect(Url::to('retiro'));
        }

        return Response::view('Retiro::entrada-sector', $this->pantalla($carrito, '') + [
            'titulo' => 'Punto de retiro',
            'persona' => $carrito['persona'],
            'sectores' => $this->sectores->activos(),
        ], 'retiro');
    }

    /** «Confirmar entrada»: suma lo que llegó y lo deja en el sector elegido o escaneado. */
    public function confirmarEntrada(Request $request): Response
    {
        [$estacion, $carrito] = $this->enCurso();
        if ($carrito === null) {
            return $this->cerrado($request);
        }
        if (self::modoDe($carrito) !== 'entrada') {
            return Response::redirect(Url::to('retiro'));
        }

        $this->revisarPersona($carrito);

        $codigo = trim((string) $request->input('codigo', ''));
        $sectorId = (string) $request->input('sector_id', '');
        if ($codigo !== '') {
            $sector = $this->sectores->buscarPorCodigo($codigo)
                ?? throw new ValidationException(['codigo' => 'Ese código no es de un sector. Escanea la etiqueta del sector o elígelo en la lista.']);
        } elseif (ctype_digit($sectorId)) {
            $sector = $this->sectores->buscarPorId((int) $sectorId)
                ?? throw new ValidationException(['sector_id' => 'Elige el sector donde quedó.']);
        } else {
            throw new ValidationException(['sector_id' => 'Elige el sector donde quedó, o escanea su etiqueta.']);
        }

        $registrado = (new StockService())->registrarEntradaEnPunto(
            $carrito['persona']['id'],
            $estacion['id'],
            $carrito['items'],
            (int) $sector['id']
        );
        $this->estaciones->registrarUso($estacion['id']);
        // La persona sigue identificada: puede registrar otra entrada o terminar.
        Carrito::guardarItems([]);
        Session::flash('ingresado', ['persona' => $carrito['persona']['nombre'], 'sector' => $sector['nombre'], 'lineas' => $registrado]);

        return Response::redirect(Url::to('retiro/listo'));
    }

    public function listo(Request $request): Response
    {
        $this->estacion();
        $retirado = Session::getFlash('retirado');
        $ingresado = Session::getFlash('ingresado');
        if (!is_array($retirado) && !is_array($ingresado)) {
            return Response::redirect(Url::to('retiro'));
        }

        return Response::view('Retiro::listo', [
            'titulo' => 'Listo',
            'retirado' => is_array($retirado) ? $retirado : null,
            'ingresado' => is_array($ingresado) ? $ingresado : null,
            'volverEn' => is_array($retirado) ? 10 : 30,
        ], 'retiro');
    }

    /** «Terminar»: cierra la identificación sin retirar nada. */
    public function terminar(Request $request): Response
    {
        $this->estacion();
        Carrito::terminar();

        return Response::redirect(Url::to('retiro'));
    }

    /** La persona sigue frente a la pantalla (la tocó): retiro.js avisa para que no se cierre por inactividad. */
    public function sigo(Request $request): Response
    {
        [, $carrito] = $this->enCurso();
        if ($carrito !== null) {
            Carrito::renovar();
        }

        return Response::json(['abierto' => $carrito !== null]);
    }

    // ---------- Apoyo ----------

    /** 'retiro', 'entrada' o null (un auxiliar que todavía no elige). */
    private static function modoDe(array $carrito): ?string
    {
        return array_key_exists('modo', $carrito) ? $carrito['modo'] : 'retiro';
    }

    /** @throws ValidationException si la persona fue desactivada mientras estaba identificada */
    private function revisarPersona(array $carrito): void
    {
        $persona = $this->personas->buscarPorId($carrito['persona']['id']);
        if ($persona === null || (int) $persona['activo'] !== 1) {
            Carrito::terminar();
            throw new ValidationException(['credencial' => 'Tu credencial está desactivada. Habla con el encargado de bodega.']);
        }
    }

    /**
     * @return array{id: int, nombre: string}
     * @throws HttpException 403 si este equipo no está habilitado
     */
    private function estacion(): array
    {
        return $this->estaciones->actual()
            ?? throw new HttpException(403, 'Este computador no está habilitado como punto de retiro.');
    }

    /** @return array{0: array{id: int, nombre: string}, 1: array<string, mixed>|null} */
    private function enCurso(): array
    {
        $estacion = $this->estacion();

        return [$estacion, Carrito::actual($estacion['id'])];
    }

    /** La identificación ya se cerró (por inactividad o porque otra pestaña terminó). */
    private function cerrado(Request $request): Response
    {
        if ($request->wantsJson()) {
            return Response::json(['cerrado' => true, 'url' => Url::to('retiro')]);
        }

        return Response::redirect(Url::to('retiro'));
    }

    /**
     * @param 'ok'|'info' $tono
     */
    private function respuesta(Request $request, string $tono, string $mensaje): Response
    {
        if ($request->wantsJson()) {
            [$estacion, $carrito] = $this->enCurso();

            return $this->json($carrito, $tono, $mensaje);
        }

        Session::flash($tono === 'ok' ? 'exito' : 'aviso', $mensaje);

        return Response::redirect(Url::to('retiro'));
    }

    /** @param array<string, mixed>|null $carrito */
    private function json(?array $carrito, string $tono, string $mensaje, string $texto = ''): Response
    {
        if ($carrito === null) {
            return Response::json(['cerrado' => true, 'url' => Url::to('retiro')]);
        }

        return Response::json([
            'tono' => $tono,
            'mensaje' => $mensaje,
            'html' => View::render('Retiro::partials/pantalla', $this->pantalla($carrito, $texto), null),
        ]);
    }

    /**
     * Lo que muestra la parte cambiante de la pantalla: los productos que coinciden con lo escrito y lo que se lleva.
     *
     * @param array<string, mixed> $carrito
     * @return array<string, mixed>
     */
    private function pantalla(array $carrito, string $texto): array
    {
        $lineas = [];
        foreach ($carrito['items'] as $id => $cantidad) {
            $insumo = $this->insumos->buscarPorId((int) $id);
            if ($insumo !== null) {
                $lineas[] = ['insumo' => $insumo, 'cantidad' => $cantidad];
            }
        }

        // Al retirar solo sirven los que tienen unidades; al registrar una entrada, cualquiera.
        $entrada = self::modoDe($carrito) === 'entrada';
        $resultados = $texto === '' ? [] : array_values(array_filter(
            $this->insumos->buscar($texto, 'todos'),
            static fn (array $insumo): bool => $entrada || (int) $insumo['stock_actual'] > 0
        ));

        return [
            'modo' => self::modoDe($carrito) ?? 'retiro',
            'lineas' => $lineas,
            'total' => array_sum($carrito['items']),
            'texto' => $texto,
            'resultados' => $resultados,
        ];
    }

    /**
     * @param array<string, mixed> $insumo
     * @throws ValidationException si no alcanza
     */
    private static function revisarStock(array $insumo, int $cantidad, string $campo): void
    {
        $stock = (int) $insumo['stock_actual'];

        if ($cantidad > $stock) {
            throw new ValidationException([$campo => $stock === 0
                ? "No queda {$insumo['nombre']} en bodega. Pide ayuda al encargado."
                : "No alcanza: de {$insumo['nombre']} " . ($stock === 1 ? 'queda ' : 'quedan ') . unidades($stock) . '.']);
        }
    }
}
