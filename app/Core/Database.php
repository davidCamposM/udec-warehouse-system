<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use PDO;
use PDOStatement;
use Throwable;

defined('APP_ROOT') || exit;

/**
 * Acceso a MySQL con PDO. Siempre con sentencias preparadas: los datos van en $parametros,
 * nunca concatenados en el SQL.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $db = (array) Config::get('db', []);
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'] ?? '127.0.0.1',
            $db['port'] ?? 3306,
            $db['name'] ?? '',
            $db['charset'] ?? 'utf8mb4'
        );

        $pdo = new PDO($dsn, (string) ($db['user'] ?? ''), (string) ($db['pass'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        // No se conoce la configuración del MySQL del servidor, así que se fija aquí:
        // NOW() con la misma hora que PHP, y modo estricto para rechazar datos inválidos en vez de truncarlos.
        $pdo->exec(sprintf(
            "SET time_zone = '%s', sql_mode = '%s'",
            (new DateTimeImmutable())->format('P'),
            'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,'
                . 'ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'
        ));

        return self::$pdo = $pdo;
    }

    /** @param array<int|string, mixed> $parametros */
    public static function query(string $sql, array $parametros = []): PDOStatement
    {
        $sentencia = self::connection()->prepare($sql);
        $sentencia->execute($parametros);

        return $sentencia;
    }

    /**
     * @param array<int|string, mixed> $parametros
     * @return array<string, mixed>|null
     */
    public static function fetch(string $sql, array $parametros = []): ?array
    {
        $fila = self::query($sql, $parametros)->fetch();

        return $fila === false ? null : $fila;
    }

    /**
     * @param array<int|string, mixed> $parametros
     * @return list<array<string, mixed>>
     */
    public static function fetchAll(string $sql, array $parametros = []): array
    {
        return self::query($sql, $parametros)->fetchAll();
    }

    /** @param array<int|string, mixed> $parametros */
    public static function fetchValue(string $sql, array $parametros = []): mixed
    {
        $valor = self::query($sql, $parametros)->fetchColumn();

        return $valor === false ? null : $valor;
    }

    /**
     * UPDATE o DELETE. Retorna las filas afectadas.
     *
     * @param array<int|string, mixed> $parametros
     */
    public static function execute(string $sql, array $parametros = []): int
    {
        return self::query($sql, $parametros)->rowCount();
    }

    /**
     * INSERT. Retorna el id generado.
     *
     * @param array<int|string, mixed> $parametros
     */
    public static function insert(string $sql, array $parametros = []): int
    {
        self::query($sql, $parametros);

        return (int) self::connection()->lastInsertId();
    }

    /**
     * Ejecuta $operacion en una transacción: si algo falla, no se aplica nada.
     * Si ya hay una transacción abierta, se suma a ella, así los servicios se pueden combinar.
     *
     * @template T
     * @param callable(): T $operacion
     * @return T
     */
    public static function transaction(callable $operacion): mixed
    {
        $pdo = self::connection();

        if ($pdo->inTransaction()) {
            return $operacion();
        }

        $pdo->beginTransaction();

        try {
            $resultado = $operacion();
            $pdo->commit();

            return $resultado;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
