<?php

/**
 * Funciones globales de uso frecuente en vistas y controladores.
 */

declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\Url;
use App\Core\View;
use App\Support\Icon;
use App\Support\Numero;

defined('APP_ROOT') || exit;

/** Escapa texto para HTML. Todo dato que se muestra en una vista pasa por aquí. */
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * URL de una ruta del sistema: url('insumos/entradas', ['id' => 5]).
 *
 * @param array<string, mixed> $parametros
 */
function url(string $ruta = '', array $parametros = []): string
{
    return Url::to($ruta, $parametros);
}

/** URL de un archivo de public/assets: asset('css/main.css'). */
function asset(string $ruta): string
{
    return Url::asset($ruta);
}

/**
 * Foto de un producto, por su código UdeC: public/assets/img/productos/{código}.jpg (sacadas de abasto.udec.cl).
 * Null si no tiene código UdeC o no hay foto.
 */
function foto_producto(?string $codigoUdec): ?string
{
    if ($codigoUdec === null || !ctype_digit($codigoUdec)) {
        return null;
    }

    $ruta = "img/productos/{$codigoUdec}.jpg";

    return is_file(APP_ROOT . '/public/assets/' . $ruta) ? asset($ruta) : null;
}

function config(string $clave, mixed $porDefecto = null): mixed
{
    return Config::get($clave, $porDefecto);
}

function csrf_token(): string
{
    return Csrf::token();
}

/** Campo oculto con el token CSRF. Va dentro de todo <form method="post">. */
function csrf_field(): string
{
    return '<input type="hidden" name="' . Csrf::CAMPO . '" value="' . e(Csrf::token()) . '">';
}

/**
 * Fragmento reutilizable: partial('flash') → app/views/partials/flash.php;
 * partial('Insumos::partials/estado') → la carpeta views de ese módulo.
 *
 * @param array<string, mixed> $datos
 */
function partial(string $nombre, array $datos = []): string
{
    return View::partial($nombre, $datos);
}

/** Ícono SVG en línea: icon('check'). Ver App\Support\Icon. */
function icon(string $nombre, string $clase = 'icon'): string
{
    return Icon::svg($nombre, $clase);
}

/** Lo que el usuario había escrito en un campo, cuando el formulario vuelve con errores. */
function old(string $campo, string $porDefecto = ''): string
{
    $valor = Session::getFlash('_old', [])[$campo] ?? $porDefecto;

    return is_scalar($valor) ? (string) $valor : $porDefecto;
}

/** @return array<string, string> errores de validación del formulario anterior (campo => mensaje) */
function errores(): array
{
    return Session::getFlash('_errores', []);
}

/** Mensaje de error de un campo del formulario anterior, o null. */
function error_de(string $campo): ?string
{
    return errores()[$campo] ?? null;
}

/** Número con formato chileno: cantidad(1500) → '1.500'. */
function cantidad(int|float|string|null $valor): string
{
    return Numero::cantidad($valor);
}

/** Cantidad de un producto en palabras: unidades(1) → '1 unidad'; unidades(12) → '12 unidades'. */
function unidades(int|float|string|null $valor): string
{
    return Numero::unidades($valor);
}

/**
 * Campo oculto con la ruta, para formularios GET. Sin reescritura de URLs (app.url_mode = query), el navegador
 * reemplaza la parte "?r=insumos" de la acción del formulario por los campos, así que la ruta debe ir como campo.
 */
function route_field(string $ruta): string
{
    if (Config::get('app.url_mode', 'rewrite') !== 'query') {
        return '';
    }

    return '<input type="hidden" name="r" value="' . e(trim($ruta, '/')) . '">';
}

/** Atributos de un campo con error: lo marcan como inválido y lo conectan con su mensaje (lectores de pantalla). */
function aria_error(string $campo): string
{
    return error_de($campo) !== null ? 'aria-invalid="true" aria-describedby="' . e($campo) . '-error"' : '';
}

/** Mensaje de error de un campo, para mostrar bajo él, o '' si no tiene. */
function mensaje_error(string $campo): string
{
    $mensaje = error_de($campo);

    return $mensaje === null
        ? ''
        : '<p class="field__error" id="' . e($campo) . '-error">' . icon('alert') . e($mensaje) . '</p>';
}
