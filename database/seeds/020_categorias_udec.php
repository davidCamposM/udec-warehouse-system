<?php

/**
 * Categorías de insumos: cuatro, de un solo nivel y con nombres de uso diario, para que cualquiera sepa dónde buscar
 * (decidido con la bodega el 10/11/2026). Reemplazan a las del catálogo de pedidos UdeC (Materiales y Útiles de Aseo,
 * Materiales de Oficina, Materiales Especializados, Ropa e Implementos de Trabajo y sus subcategorías): la relación con
 * SAP la da el código UdeC de cada producto, no la categoría.
 * - Limpieza: aseo, baño (papel, jabón, pastillas del estanque, servilletas) y protección (guantes, mascarillas,
 *   guardapolvos, alcohol gel) y primeros auxilios (termómetros, venda, suero).
 * - Oficina: papel, lápices, plumones, corchetes, carpetas, borradores de pizarra.
 * - Cafetería: café, té, azúcar, endulzantes, vasos, servilletas de mesa, tenedores.
 * - Pilas.
 * El archivo conserva su nombre (020_categorias_udec.php) porque e2e/preparar.php y las instalaciones lo cargan así.
 */

declare(strict_types=1);

use App\Core\Database;
use App\Support\Consola;

defined('APP_ROOT') || exit;

$categorias = ['Limpieza', 'Oficina', 'Cafetería', 'Pilas'];

$nuevas = 0;

foreach ($categorias as $nombre) {
    $existe = Database::fetchValue(
        'SELECT id FROM categorias WHERE ambito = ? AND nombre = ? AND padre_id IS NULL',
        ['insumo', $nombre]
    );

    if ($existe === null) {
        Database::insert('INSERT INTO categorias (nombre, padre_id, ambito) VALUES (?, NULL, ?)', [$nombre, 'insumo']);
        $nuevas++;
    }
}

Consola::exito("Categorías: {$nuevas} nuevas.");
