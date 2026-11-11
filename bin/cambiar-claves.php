<?php

/**
 * Cambia de una vez las contraseñas de un grupo de cuentas (todas, las de un perfil o una sola) por contraseñas nuevas
 * al azar. Es para el encargado del sistema, por fuera de la aplicación (igual que restablecer-clave.php): dentro del
 * sistema no hay ninguna forma de cambiar la contraseña de otra persona.
 *
 * El servidor no tiene SSH, así que esto trabaja con la copia local de la base del servidor:
 *   1. Lee las cuentas activas de esa copia y pregunta a quiénes cambiar (un menú).
 *   2. Cambia sus contraseñas en la copia, para que siga igual al servidor.
 *   3. Deja dos archivos en --salida: claves-….txt (las contraseñas nuevas, para entregarlas) y claves-….sql (los
 *      UPDATE para pegar en phpMyAdmin del servidor; sin eso, en el servidor no cambia nada).
 * Al cambiar una contraseña, la sesión que esa persona tenga abierta se cierra sola (Auth::refresh).
 *
 *   php bin/cambiar-claves.php --base=bodega_fcf_v1 --salida=storage/claves [--largo=7]
 *
 * En Windows lo abre cambiar-claves.cmd (con Docker y el sistema local corriendo).
 */

declare(strict_types=1);

use App\Core\Config;
use App\Core\Database;
use App\Modules\Usuarios\UsuarioService;
use App\Support\Consola;

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';

Consola::soloConsola();

$opciones = Consola::opciones(array_slice($argv, 1));
$base = is_string($opciones['base'] ?? null) ? $opciones['base'] : (string) Config::get('db.name');
$salida = APP_ROOT . '/' . trim(is_string($opciones['salida'] ?? null) ? $opciones['salida'] : 'storage/claves', '/');
$largo = max(7, min(UsuarioService::PASSWORD_MAX, (int) ($opciones['largo'] ?? 7)));

if (preg_match('/^[A-Za-z0-9_]+$/', $base) !== 1) {
    Consola::error("Nombre de base no válido: {$base}");
    exit(1);
}
Config::set('db.name', $base);

/** Contraseña al azar: al menos una mayúscula, una minúscula, un número y un símbolo; sin letras que se confunden. */
function claveNueva(int $largo): string
{
    $clases = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnpqrstuvwxyz', '23456789', '#$%&*+-=?@'];
    $elegir = static fn (string $letras): string => $letras[random_int(0, strlen($letras) - 1)];
    $todas = implode('', $clases);

    $c = array_map($elegir, $clases);
    for ($i = count($c); $i < $largo; $i++) {
        // Sobre todo letras: es más fácil de escribir.
        $c[] = $elegir(random_int(0, 2) === 0 ? $todas : $clases[$i % 2]);
    }
    for ($i = count($c) - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        [$c[$i], $c[$j]] = [$c[$j], $c[$i]];
    }

    return implode('', $c);
}

$perfiles = [
    'administrador' => 'Administradores',
    'operador' => 'Operadores de bodega',
    'administrativa' => 'Administrativas',
    'docente' => 'Docentes',
];

Consola::linea();
Consola::linea('CAMBIAR CONTRASEÑAS · base local: ' . $base);
Consola::linea(str_repeat('─', 60));

try {
    $total = (int) Database::fetchValue('SELECT COUNT(*) FROM usuarios WHERE activo = 1');
} catch (Throwable $e) {
    Consola::error("No se pudo abrir la base «{$base}». ¿Está corriendo el sistema local (Docker)?");
    exit(1);
}

$cuenta = static fn (string $perfil): int => (int) Database::fetchValue(
    'SELECT COUNT(*) FROM usuarios WHERE activo = 1 AND perfil = ?',
    [$perfil]
);
$menu = ['todos' => "Todas las cuentas ({$total})"];
foreach ($perfiles as $perfil => $nombre) {
    $menu[$perfil] = "Solo {$nombre} ({$cuenta($perfil)})";
}
$menu['uno'] = 'Una persona (escribir su correo exacto)';
$menu['salir'] = 'Salir sin cambiar nada';

$opcion = Consola::elegir('¿A quiénes les cambias la contraseña?', $menu);

$sql = 'SELECT id, nombre, email, perfil FROM usuarios WHERE activo = 1';
$parametros = [];
switch ($opcion) {
    case 'todos':
        break;
    case 'uno':
        $email = Consola::preguntar('Correo exacto');
        $sql .= ' AND email = ?';
        $parametros[] = $email;
        break;
    case '':
    case 'salir':
        Consola::aviso('No se cambió nada.');
        exit(0);
    default:
        $sql .= ' AND perfil = ?';
        $parametros[] = $opcion;
}
$cuentas = Database::fetchAll($sql . ' ORDER BY FIELD(perfil, \'administrador\', \'operador\', \'administrativa\', \'docente\'), nombre', $parametros);

if ($cuentas === []) {
    Consola::error($opcion === 'uno'
        ? 'No hay ninguna cuenta activa con ese correo. Revisa que esté bien escrito. No se cambió nada.'
        : 'No hay cuentas activas en ese grupo. No se cambió nada.');
    exit(1);
}

Consola::linea();
Consola::linea('Se cambiará la contraseña de ' . count($cuentas) . ' cuenta(s):');
foreach ($cuentas as $c) {
    Consola::linea('  · ' . mb_str_pad($c['email'], 32) . ' ' . mb_str_pad($c['nombre'], 32) . ' ' . UsuarioService::nombrePerfil($c['perfil']));
}
Consola::linea();
if (mb_strtoupper(Consola::preguntar('Escribe SI para confirmar')) !== 'SI') {
    Consola::aviso('No se cambió nada.');
    exit(0);
}

// Contraseñas nuevas y su hash; se guardan todas juntas (o ninguna) en la copia local.
foreach ($cuentas as &$c) {
    $c['clave'] = claveNueva($largo);
    $c['hash'] = UsuarioService::hash($c['clave']);
}
unset($c);

Database::transaction(static function () use ($cuentas): void {
    foreach ($cuentas as $c) {
        Database::execute('UPDATE usuarios SET password_hash = ? WHERE id = ?', [$c['hash'], $c['id']]);
    }
});

if (!is_dir($salida) && !mkdir($salida, 0700, true) && !is_dir($salida)) {
    Consola::error("No se pudo crear la carpeta {$salida}.");
    exit(1);
}
$marca = date('Y-m-d-His');
$grupo = $opcion === 'uno' ? $cuentas[0]['email'] : ($opcion === 'todos' ? 'todas las cuentas' : $perfiles[$opcion]);

$texto = ["Bodega FCF · contraseñas nuevas · " . date('d/m/Y H:i') . " · {$grupo}", ''];
$sentencias = [
    "-- Bodega FCF · contraseñas nuevas · " . date('d/m/Y H:i') . " · {$grupo}",
    '-- phpMyAdmin del servidor: base forestal3 → pestaña SQL → pegar todo → Continuar.',
    '-- Debe indicar ' . count($cuentas) . ' fila(s) afectada(s) en total. Las contraseñas están en el .txt del mismo nombre.',
];
foreach ($cuentas as $c) {
    // mb_str_pad: los nombres con tilde quedan alineados (sprintf cuenta bytes, no letras).
    $texto[] = mb_str_pad($c['email'], 32) . ' ' . mb_str_pad($c['nombre'], 32) . ' '
        . mb_str_pad(UsuarioService::nombrePerfil($c['perfil']), 20) . ' ' . $c['clave'];
    $sentencias[] = sprintf('UPDATE usuarios SET password_hash = %s WHERE email = %s;', Consola::sql($c['hash']), Consola::sql($c['email']));
}
file_put_contents("{$salida}/claves-{$marca}.txt", implode(PHP_EOL, $texto) . PHP_EOL);
file_put_contents("{$salida}/claves-{$marca}.sql", implode(PHP_EOL, $sentencias) . PHP_EOL);

Consola::linea();
Consola::exito('Contraseñas nuevas (ya cambiadas en la base local ' . $base . '):');
Consola::linea();
foreach ($texto as $i => $fila) {
    if ($i > 1) {
        Consola::linea('  ' . $fila);
    }
}
Consola::linea();
Consola::aviso("En el servidor todavía no cambian: ejecuta claves-{$marca}.sql en phpMyAdmin.");
