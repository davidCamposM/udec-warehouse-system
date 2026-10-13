<?php

/**
 * Restablece la contraseña de una cuenta: es el respaldo de "¿Olvidaste tu contraseña? Pide al
 * encargado de bodega". La contraseña nueva se escribe sin que se vea.
 *
 *   php bin/restablecer-clave.php --email=jperez@udec.cl
 *   php bin/restablecer-clave.php --email=jperez@udec.cl --sql   no toca la base: imprime el UPDATE
 *
 * En Docker: docker compose exec -u www-data app php bin/restablecer-clave.php --email=...
 */

declare(strict_types=1);

use App\Core\ValidationException;
use App\Core\Validator;
use App\Modules\Usuarios\UsuarioService;
use App\Support\Consola;

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';

Consola::soloConsola();

$opciones = Consola::opciones(array_slice($argv, 1));
$soloSql = isset($opciones['sql']);
$campos = ['email' => 'Correo', 'password' => 'Contraseña'];

try {
    $email = Validator::validate(
        ['email' => is_string($opciones['email'] ?? null) ? $opciones['email'] : Consola::preguntar('Correo de la cuenta')],
        ['email' => 'requerido|email|max:190']
    )['email'];

    $password = Consola::preguntarOculto(
        'Contraseña nueva (' . UsuarioService::PASSWORD_MIN . ' a ' . UsuarioService::PASSWORD_MAX . ' caracteres)'
    );

    if (Consola::preguntarOculto('Repite la contraseña') !== $password) {
        Consola::error('Las contraseñas no coinciden. No se cambió nada.');
        exit(1);
    }

    if ($soloSql) {
        $error = UsuarioService::validarPassword($password);
        if ($error !== null) {
            throw new ValidationException(['password' => $error]);
        }

        Consola::linea();
        Consola::linea(sprintf(
            'UPDATE usuarios SET password_hash = %s WHERE email = %s;',
            Consola::sql(UsuarioService::hash($password)),
            Consola::sql($email)
        ));
        Consola::linea();
        Consola::aviso('Ejecuta esta sentencia en la base del servidor. Debe indicar "1 fila afectada".');
        exit(0);
    }

    $usuario = (new UsuarioService())->restablecerPassword($email, $password);
} catch (ValidationException $e) {
    foreach ($e->errors() as $campo => $mensaje) {
        Consola::error(($campos[$campo] ?? $campo) . ': ' . $mensaje);
    }
    exit(1);
}

Consola::exito("Contraseña restablecida para {$usuario['nombre']} ({$usuario['email']}).");

if ((int) $usuario['activo'] !== 1) {
    Consola::aviso('La cuenta está desactivada: no podrá ingresar hasta que la activen.');
}
