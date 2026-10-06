<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

defined('APP_ROOT') || exit;

/**
 * Datos de un formulario inválidos. App vuelve al formulario con los errores y lo ya escrito,
 * o responde 422 con los errores si la petición es JSON.
 */
final class ValidationException extends RuntimeException
{
    /** @param array<string, string> $errores campo => mensaje */
    public function __construct(private readonly array $errores)
    {
        parent::__construct('Revisa los datos ingresados.');
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errores;
    }
}
