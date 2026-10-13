<?php

declare(strict_types=1);

namespace App\Modules\Personas;

use App\Core\Database;

defined('APP_ROOT') || exit;

final class PersonaRepository
{
    private const COLUMNAS = 'id, nombre, rut, tipo, codigo_credencial, departamento, activo';

    /** @return list<array<string, mixed>> activas primero, por nombre */
    public function todas(): array
    {
        return Database::fetchAll('SELECT ' . self::COLUMNAS . ' FROM personas ORDER BY activo DESC, nombre');
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        return Database::fetch('SELECT ' . self::COLUMNAS . ' FROM personas WHERE id = ?', [$id]);
    }

    /** @return array<string, mixed>|null la persona de esa credencial (sin distinguir mayúsculas) o de ese RUT normalizado */
    public function findPorCredencialORut(string $credencial, ?string $rut): ?array
    {
        return Database::fetch(
            'SELECT ' . self::COLUMNAS . ' FROM personas WHERE codigo_credencial = ? OR (? IS NOT NULL AND rut = ?) LIMIT 1',
            [$credencial, $rut, $rut]
        );
    }

    /**
     * Siguiente código de credencial libre (PER00001…). Se llama dentro de una transacción: el FOR UPDATE hace que dos
     * registros al mismo tiempo no reciban el mismo código.
     */
    public function siguienteCodigo(): string
    {
        $ultimo = (int) Database::fetchValue(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(codigo_credencial, 4) AS UNSIGNED)), 0) FROM personas
              WHERE codigo_credencial LIKE 'PER%' FOR UPDATE"
        );

        return sprintf('PER%05d', $ultimo + 1);
    }

    /** @param array{nombre: string, rut: ?string, tipo: string, departamento: ?string} $datos */
    public function create(string $codigo, array $datos): int
    {
        return Database::insert(
            'INSERT INTO personas (codigo_credencial, nombre, rut, tipo, departamento) VALUES (?, ?, ?, ?, ?)',
            [$codigo, $datos['nombre'], $datos['rut'], $datos['tipo'], $datos['departamento']]
        );
    }

    /** @param array{nombre: string, rut: ?string, tipo: string, departamento: ?string} $datos */
    public function update(int $id, array $datos): void
    {
        Database::execute(
            'UPDATE personas SET nombre = ?, rut = ?, tipo = ?, departamento = ? WHERE id = ?',
            [$datos['nombre'], $datos['rut'], $datos['tipo'], $datos['departamento'], $id]
        );
    }

    public function setActivo(int $id, bool $activo): void
    {
        Database::execute('UPDATE personas SET activo = ? WHERE id = ?', [(int) $activo, $id]);
    }

    /** Nombre de otra persona con ese RUT, o null. */
    public function nombrePorRut(string $rut, ?int $excepto): ?string
    {
        $nombre = Database::fetchValue('SELECT nombre FROM personas WHERE rut = ? AND id <> ? LIMIT 1', [$rut, $excepto ?? 0]);

        return $nombre === null ? null : (string) $nombre;
    }
}
