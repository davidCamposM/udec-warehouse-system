<?php

declare(strict_types=1);

namespace App\Modules\Insumos;

use App\Core\HttpException;
use App\Core\Permission;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;

defined('APP_ROOT') || exit;

final class InsumoController
{
    /** Cuántos productos muestra la lista bajo una barra de búsqueda; si coinciden más, ofrece verlos todos. */
    private const SUGERENCIAS = 8;

    /** Qué hace tocar un producto de esa lista (?para=), según la pantalla donde está la barra. */
    private const DESTINOS = ['ficha', 'guardar', 'ajustar', 'retirar', 'envase'];

    private InsumoService $insumos;

    public function __construct()
    {
        $this->insumos = new InsumoService();
    }

    /** Lista con buscador y filtros. Un código leído con la pistola abre directo la ficha de su producto. */
    public function index(Request $request): Response
    {
        $texto = trim((string) $request->query('q', ''));
        $filtro = InsumoService::filtro($request->query('ver'));

        $id = $this->insumos->idPorCodigo($texto);
        if ($id !== null) {
            return Response::redirect(Url::to("insumos/{$id}"));
        }

        return Response::view('Insumos::index', [
            'titulo' => 'Buscar producto',
            'texto' => $texto,
            'filtro' => $filtro,
            'insumos' => $this->insumos->buscar($texto, $filtro),
            'conteo' => $this->insumos->contar($texto),
            'puedeAdministrar' => Permission::can('insumos.administrar'),
            'estilos' => ['css/pages/insumos.css'],
            'scripts' => ['js/modules/insumos.js'],
        ]);
    }

    /**
     * La lista que aparece bajo una barra de búsqueda mientras se escribe (la pide insumos.js): los productos que
     * coinciden con ?q=. Tocar uno hace lo que toca en esa pantalla (?para=): abrir su ficha, elegirlo en «Registrar
     * producto», «Actualizar cantidad» o «Retirar producto», o elegirlo como el producto de un envase nuevo.
     */
    public function sugerencias(Request $request): Response
    {
        $texto = trim((string) $request->query('q', ''));
        if ($texto === '') {
            return Response::html('');
        }

        $para = $request->query('para');
        $para = is_string($para) && in_array($para, self::DESTINOS, true) ? $para : 'ficha';

        // «Agregar nuevo producto», cuando no se encuentra: desde la búsqueda, o para registrarlo apenas se agregue.
        $agregar = match ($para) {
            'ficha' => Url::to('insumos/nuevo', InsumoService::paraAgregar($texto)),
            'guardar' => Url::to('insumos/nuevo', ['para' => 'guardar'] + InsumoService::paraAgregar($texto)),
            default => null,
        };

        return Response::view('Insumos::partials/sugerencias', $this->insumos->sugerencias($texto, self::SUGERENCIAS) + [
            'texto' => $texto,
            'para' => $para,
            'agregar' => Permission::can('insumos.administrar') ? $agregar : null,
        ], null);
    }

    public function show(Request $request): Response
    {
        $ficha = $this->insumos->ficha(self::id($request));

        if ($ficha === null) {
            throw new HttpException(404, 'Ese producto no existe.');
        }

        return Response::view('Insumos::show', $ficha + [
            'titulo' => $ficha['insumo']['nombre'],
            'puedeAdministrar' => Permission::can('insumos.administrar'),
            'puedeGuardar' => Permission::can('insumos.entrada'),
            'puedeAjustar' => Permission::can('insumos.ajuste'),
            'puedeRetirar' => Permission::can('insumos.salida'),
            'estilos' => ['css/pages/insumos.css'],
        ]);
    }

    // ---------- Editar (agregar uno nuevo es NuevoProductoController) ----------

    public function edit(Request $request): Response
    {
        return Response::view('Insumos::formulario', $this->insumos->opcionesFormulario() + [
            'titulo' => 'Editar producto',
            'insumo' => $this->insumo($request),
            'estilos' => ['css/pages/insumos.css'],
        ]);
    }

    public function update(Request $request): Response
    {
        $insumo = $this->insumo($request);
        $this->insumos->editar((int) $insumo['id'], $request->post());
        Session::flash('exito', 'Listo. Se guardaron los cambios.');

        return Response::redirect(Url::to("insumos/{$insumo['id']}"));
    }

    // ---------- Eliminar ----------

    /** Antes de eliminar se pregunta en una pantalla propia, explicando qué pasa. */
    public function confirmDelete(Request $request): Response
    {
        return Response::view('Insumos::eliminar', [
            'titulo' => 'Eliminar producto',
            'insumo' => $this->insumo($request),
            'estilos' => ['css/pages/insumos.css'],
        ]);
    }

    /** Para las personas el producto desaparece; por dentro solo se desactiva (InsumoService::eliminar). */
    public function delete(Request $request): Response
    {
        $insumo = $this->insumo($request);
        $this->insumos->eliminar((int) $insumo['id']);
        Session::flash('exito', "Se eliminó «{$insumo['nombre']}».");

        return Response::redirect(Url::to('insumos'));
    }

    // ---------- Códigos de barra ----------

    public function createCode(Request $request): Response
    {
        $insumo = $this->insumo($request);

        return Response::view('Insumos::codigo-nuevo', [
            'titulo' => 'Agregar código de barras',
            'insumo' => $insumo,
            'estilos' => ['css/pages/insumos.css'],
        ]);
    }

    public function storeCode(Request $request): Response
    {
        $insumo = $this->insumo($request);
        $codigo = $this->insumos->agregarCodigo((int) $insumo['id'], $request->post());
        Session::flash('exito', "Listo. Ahora el código {$codigo} lleva a este producto.");

        return Response::redirect(Url::to("insumos/{$insumo['id']}"));
    }

    public function confirmRemoveCode(Request $request): Response
    {
        [$insumo, $codigo] = $this->codigo($request);

        return Response::view('Insumos::codigo-quitar', [
            'titulo' => 'Quitar código de barras',
            'insumo' => $insumo,
            'codigo' => $codigo,
            'estilos' => ['css/pages/insumos.css'],
        ]);
    }

    public function removeCode(Request $request): Response
    {
        [$insumo, $codigo] = $this->codigo($request);
        $this->insumos->quitarCodigo((int) $insumo['id'], (int) $codigo['id']);
        Session::flash('exito', "Se quitó el código {$codigo['codigo']}.");

        return Response::redirect(Url::to("insumos/{$insumo['id']}"));
    }

    // ---------- Apoyo ----------

    /** @return array<string, mixed> el producto del {id} de la ruta */
    private function insumo(Request $request): array
    {
        return $this->insumos->buscarPorId(self::id($request))
            ?? throw new HttpException(404, 'Ese producto no existe.');
    }

    /** @return array{0: array<string, mixed>, 1: array<string, mixed>} el producto y su código del {codigo} de la ruta */
    private function codigo(Request $request): array
    {
        $insumo = $this->insumo($request);
        $codigoId = (string) $request->param('codigo');
        $codigo = ctype_digit($codigoId) ? $this->insumos->codigoDeInsumo((int) $insumo['id'], (int) $codigoId) : null;

        if ($codigo === null) {
            throw new HttpException(404, 'Ese código no existe o ya se quitó.');
        }

        return [$insumo, $codigo];
    }

    /** El {id} de la ruta, que debe ser un número. */
    private static function id(Request $request): int
    {
        $id = (string) $request->param('id');

        if (!ctype_digit($id)) {
            throw new HttpException(404, 'Ese producto no existe.');
        }

        return (int) $id;
    }
}
