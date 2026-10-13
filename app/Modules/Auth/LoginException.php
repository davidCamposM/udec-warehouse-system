<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use RuntimeException;

defined('APP_ROOT') || exit;

/**
 * El inicio de sesión no procede. El mensaje se muestra tal cual en el login.
 */
final class LoginException extends RuntimeException
{
}
