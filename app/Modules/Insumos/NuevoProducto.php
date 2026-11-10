<?php

declare(strict_types=1);

namespace App\Modules\Insumos;

use App\Core\Session;

defined('APP_ROOT') || exit;

/**
 * El producto que se está agregando, etapa por etapa («Agregar nuevo producto»). Vive en la sesión: así se puede ir y
 * volver entre las etapas sin perder lo escrito. Guarda los datos tal como se mostrarán en el formulario.
 */
final class NuevoProducto
{
    private const CLAVE = 'agregar_producto';

    /**
     * Parte de nuevo, con lo que venga ya escrito: lo que se buscó sin encontrarlo o el código leído. Con $paraGuardar
     * se llegó desde «Ingresar productos»: «Atrás» vuelve ahí y, al terminar, se muestra lo registrado como allí.
     * El stock mínimo no se pregunta: queda en 0 (sin aviso de «Por reponer»).
     */
    public static function empezar(string $nombre = '', string $codigoBarras = '', bool $paraGuardar = false): void
    {
        Session::put(self::CLAVE, [
            'nombre' => $nombre,
            'codigo_barras' => $codigoBarras,
            'categoria_id' => '',
            'stock_minimo' => '0',
            'codigo_udec' => '',
            'cantidad' => '1',
            'sector_id' => '',
            'para_guardar' => $paraGuardar,
            'etapa' => 1,
        ]);
    }

    /**
     * @return array{nombre: string, codigo_barras: string, categoria_id: string, stock_minimo: string, codigo_udec: string,
     *     cantidad: string, sector_id: string, para_guardar: bool, etapa: int}|null etapa: la más avanzada a la que se puede ir
     */
    public static function actual(): ?array
    {
        $nuevo = Session::get(self::CLAVE);

        return is_array($nuevo) ? $nuevo : null;
    }

    /**
     * Guarda lo de una etapa y deja pasar hasta $etapa.
     *
     * @param array<string, string> $datos
     */
    public static function completar(array $datos, int $etapa): void
    {
        $nuevo = self::actual();

        if ($nuevo !== null) {
            Session::put(self::CLAVE, ['etapa' => max($nuevo['etapa'], $etapa)] + $datos + $nuevo);
        }
    }

    public static function terminar(): void
    {
        Session::forget(self::CLAVE);
    }
}
