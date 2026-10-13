<?php

/**
 * Categorías y subcategorías de insumos, tomadas del catálogo de pedidos UdeC
 * (Productos_pedidos_FCF_2026.xlsx). Se corrigen los tildes de los nombres originales.
 * "Insumos Protocolo COVID-19" no se usa: sus productos van en Ropa de Trabajo y en
 * Artículos Desinfectantes.
 */

declare(strict_types=1);

use App\Core\Database;
use App\Support\Consola;

defined('APP_ROOT') || exit;

// categoría => subcategorías
$categorias = [
    'Materiales Especializados' => ['Material para Proyección y Sonido', 'Telecomunicaciones'],
    'Materiales y Útiles de Aseo' => [
        'Artículos de Limpieza',
        'Artículos Desinfectantes',
        'Artículos de Tocador',
        'Bienes Semidurables',
    ],
    'Materiales de Oficina' => [
        'Accesorios de Oficina',
        'Artículos de Escritorio y Dibujo',
        'Papeles y Cartones',
        'Suministros para Máquina de Oficina',
    ],
    'Ropa e Implementos de Trabajo' => ['Ropa de Trabajo'],
];

/** Id de la categoría; la crea si no existe. Null en padre = categoría principal. */
$asegurar = static function (string $nombre, ?int $padreId) use (&$nuevas): int {
    $id = Database::fetchValue(
        'SELECT id FROM categorias WHERE ambito = ? AND nombre = ? AND padre_id <=> ?',
        ['insumo', $nombre, $padreId]
    );

    if ($id !== null) {
        return (int) $id;
    }

    $nuevas++;

    return Database::insert(
        'INSERT INTO categorias (nombre, padre_id, ambito) VALUES (?, ?, ?)',
        [$nombre, $padreId, 'insumo']
    );
};

$nuevas = 0;

foreach ($categorias as $categoria => $subcategorias) {
    $padreId = $asegurar($categoria, null);

    foreach ($subcategorias as $subcategoria) {
        $asegurar($subcategoria, $padreId);
    }
}

Consola::exito("Categorías: {$nuevas} nuevas.");
