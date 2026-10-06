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

/** @param array<string, mixed> $datos */
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
