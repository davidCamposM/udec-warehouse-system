<?php

/**
 * Crea una cuenta de usuario. Pide los datos que no se pasen como opción. La contraseña se escribe
 * sin que se vea y nunca como opción, porque quedaría en el historial de la terminal.
 *
 *   php bin/crear-usuario.php
 *   php bin/crear-usuario.php --nombre="Juan Pérez" --email=jperez@udec.cl --perfil=administrador
 *   php bin/crear-usuario.php --sql   no toca la base: imprime el INSERT para ejecutarlo en phpMyAdmin
 *                                     (sirve para crear el primer administrador en un servidor sin consola)
 *
 * En Docker: docker compose exec -u www-data app php bin/crear-usuario.php
 */

declare(strict_types=1);

use App\Core\Permission;
use App\Core\ValidationException;
use App\Modules\Usuarios\UsuarioService;
use App\Support\Consola;

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';

Consola::soloConsola();

$opciones = Consola::opciones(array_slice($argv, 1));
$soloSql = isset($opciones['sql']);
$opcion = static fn (string $nombre): ?string => is_string($opciones[$nombre] ?? null) ? $opciones[$nombre] : null;

Consola::linea($soloSql ? 'Nueva cuenta (solo SQL: no se guarda en la base)' : 'Nueva cuenta de usuario');

$perfiles = [];
foreach (Permission::profiles() as $perfil) {
    $perfiles[$perfil] = UsuarioService::nombrePerfil($perfil);
}

$datos = [
    'nombre' => $opcion('nombre') ?? Consola::preguntar('Nombre completo'),
    'email' => $opcion('email') ?? Consola::preguntar('Correo'),
    'perfil' => $opcion('perfil') ?? Consola::elegir('Perfil', $perfiles),
    'password' => Consola::preguntarOculto(
        'Contraseña (' . UsuarioService::PASSWORD_MIN . ' a ' . UsuarioService::PASSWORD_MAX . ' caracteres)'
    ),
];

if (Consola::preguntarOculto('Repite la contraseña') !== $datos['password']) {
    Consola::error('Las contraseñas no coinciden. No se creó la cuenta.');
    exit(1);
}

$servicio = new UsuarioService();
$campos = ['nombre' => 'Nombre', 'email' => 'Correo', 'perfil' => 'Perfil', 'password' => 'Contraseña'];

try {
    if ($soloSql) {
        $usuario = $servicio->preparar($datos, verificarCorreo: false);

        Consola::linea();
        Consola::linea(sprintf(
            'INSERT INTO usuarios (nombre, email, password_hash, perfil) VALUES (%s, %s, %s, %s);',
            Consola::sql($usuario['nombre']),
            Consola::sql($usuario['email']),
            Consola::sql($usuario['password_hash']),
            Consola::sql($usuario['perfil'])
        ));
        Consola::linea();
        Consola::aviso('Ejecuta esta sentencia en la base del servidor (pestaña SQL de phpMyAdmin).');
        exit(0);
    }

    $id = $servicio->crear($datos);
} catch (ValidationException $e) {
    foreach ($e->errors() as $campo => $mensaje) {
        Consola::error(($campos[$campo] ?? $campo) . ': ' . $mensaje);
    }
    exit(1);
}

Consola::exito(sprintf(
    'Cuenta creada (id %d): %s, perfil %s.',
    $id,
    mb_strtolower(trim($datos['email'])),
    UsuarioService::nombrePerfil(trim($datos['perfil']))
));
