<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

defined('APP_ROOT') || exit;

/**
 * Corta la petición con un código HTTP. App la convierte en página de error o JSON.
 *
 *   throw new HttpException(404);
 *   throw new HttpException(403, 'Solo el encargado puede aprobar solicitudes.');
 */
final class HttpException extends RuntimeException
{
    private const TITULOS = [
        401 => 'Sesión requerida',
        403 => 'Acceso denegado',
        404 => 'Página no encontrada',
        405 => 'Acción no permitida',
        500 => 'Error interno',
    ];

    private const MENSAJES = [
        401 => 'Inicia sesión para continuar.',
        403 => 'No tienes permiso para realizar esta acción.',
        404 => 'La página que buscas no existe o fue movida.',
        405 => 'La acción solicitada no está permitida en esta dirección.',
        500 => 'Ocurrió un error inesperado. Si se repite, avisa al encargado del sistema.',
    ];

    public function __construct(private readonly int $estado, string $mensaje = '')
    {
        parent::__construct($mensaje !== '' ? $mensaje : (self::MENSAJES[$estado] ?? 'Error.'));
    }

    public function status(): int
    {
        return $this->estado;
    }

    public function title(): string
    {
        return self::TITULOS[$this->estado] ?? 'Error';
    }
}
