<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Database;

defined('APP_ROOT') || exit;

/**
 * Las migraciones de database/migrations/: los archivos en orden y cómo se aplica cada uno. Lo usan bin/migrar.php y
 * las pruebas (tests/bootstrap.php, que arma su base desde cero).
 */
final class Migraciones
{
    /** @return list<string> rutas de los archivos .sql, en orden */
    public static function archivos(): array
    {
        $archivos = glob(APP_ROOT . '/database/migrations/*.sql') ?: [];
        sort($archivos);

        return $archivos;
    }

    /**
     * Ejecuta las sentencias de un archivo. Se separan por ";" al final de una línea: las migraciones no usan
     * procedimientos ni triggers, así que no hay ";" dentro de una sentencia. Los trozos que solo tienen comentarios
     * se descartan. MySQL confirma cada CREATE/ALTER por separado: si una falla, lo anterior queda aplicado.
     */
    public static function aplicar(string $ruta): void
    {
        $sql = (string) file_get_contents($ruta);
        $sentencias = array_filter(
            array_map('trim', preg_split('/;\s*$/m', $sql) ?: []),
            static fn (string $s): bool => trim((string) preg_replace('/^\s*--.*$/m', '', $s)) !== ''
        );

        foreach ($sentencias as $sentencia) {
            Database::query($sentencia);
        }
    }
}
