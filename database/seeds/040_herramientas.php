<?php

/**
 * Catálogo oficial de herramientas e instrumentos de terreno, entregado por la bodega el 15/10/2026. Se corrigen los
 * tildes y algunos errores de tipeo de la lista original (Hiposómetro → Hipsómetro, Cinómetro → Clinómetro,
 * Relacopio → Relascopio, Huincha Carpinteri → Huincha Carpintero).
 *
 * Las categorías no venían en la lista: se agruparon por uso, para encontrarlas rápido al solicitar.
 * Solo agrega lo que falta, con 1 unidad cada una. Las herramientas activas que no están en esta lista (el catálogo
 * provisorio anterior) se desactivan, salvo las prestadas, pedidas o por resolver: las solicitudes que ya las usaron las
 * siguen mostrando.
 */

declare(strict_types=1);

use App\Core\Database;
use App\Support\Consola;

defined('APP_ROOT') || exit;

// categoría => herramientas
$catalogo = [
    'Medición forestal' => [
        'Barra Telescópica',
        'Brújulas',
        'Calibrador Corteza',
        'Clinómetro',
        'Forcípulas',
        'Forcípulas 100 cm / 80 cm',
        'Forcípulas 40-60 cm',
        'GPS',
        'Hipsómetro',
        'Huincha Carpintero (3 y 5 m)',
        'Huincha Diamétrica',
        'Huincha Distancia (10 m)',
        'Huincha Distancia (30 m)',
        'Huincha Distancia (50 m)',
        'Huincha Distancia (60 m)',
        'Huincha tela metric diameter',
        'Medidor espesor de corteza Haglof Bark Gauge',
        'Niveles de Adney',
        'Pie de Metro Análogo',
        'Pilodon Medido Corteza',
        'Placa Conteo (Relascopía)',
        'Prisma (Relascopía)',
        'Relascopio',
        'Relascopio Análogo',
        'Taladro Incremento (Normal 14")',
        'Taladro Incremento (8")',
    ],
    'Corte y poda' => [
        'Arco Sierra Trozar 21"',
        'Arco Sierra Trozar 30"',
        'Esmeril',
        'Hacha',
        'Hachas Casoc',
        'Machetes',
        'Marco Sierra Telescópica',
        'Motosierra',
        'Podadoras',
        'Serrucho cola de zorro',
        'Sierra Madera Grande',
        'Sierra Madera Pequeña',
        'Tijerones Poda',
    ],
    'Suelo y plantación' => [
        'Azacha',
        'Azadones',
        'Carretilla Concretera 90 litros',
        'Chuzos',
        'Laya',
        'Mcleod',
        'Palas',
        'Palas Plantadoras',
        'Picotas',
        'Rastrillos',
        'Rodillo Metálico',
        'Rozón',
    ],
    'Equipamiento de terreno' => [
        'Botas Wader',
        'Cajas Metálicas',
        'Chaleco Reflectante para terreno',
        'Estanques Plásticos',
        'Mata Chispas',
    ],
];

$nuevas = 0;
$movidas = 0;
$nombres = [];

foreach ($catalogo as $categoria => $herramientas) {
    $categoriaId = Database::fetchValue(
        "SELECT id FROM categorias WHERE ambito = 'herramienta' AND nombre = ? AND padre_id IS NULL",
        [$categoria]
    ) ?? Database::insert("INSERT INTO categorias (nombre, ambito) VALUES (?, 'herramienta')", [$categoria]);

    foreach ($herramientas as $nombre) {
        $nombres[] = $nombre;
        $existente = Database::fetch('SELECT id, categoria_id, activo FROM herramientas WHERE nombre = ?', [$nombre]);

        if ($existente === null) {
            // Al menos una: el encargado anota cuántas hay en «Editar herramienta».
            Database::insert('INSERT INTO herramientas (nombre, categoria_id, unidades) VALUES (?, ?, 1)', [$nombre, $categoriaId]);
            $nuevas++;
        } elseif ($existente['categoria_id'] === null) {
            // Ya estaba (del catálogo provisorio, como GPS): toma su categoría y queda activa.
            Database::execute('UPDATE herramientas SET categoria_id = ?, activo = 1 WHERE id = ?', [$categoriaId, $existente['id']]);
            $movidas++;
        }
    }
}

$marcas = implode(', ', array_fill(0, count($nombres), '?'));
// Las de la Práctica de Invierno (seed 050) y las que están prestadas, pedidas o por resolver no se tocan.
$desactivadas = Database::execute(
    "UPDATE herramientas h
        LEFT JOIN categorias c ON c.id = h.categoria_id
        LEFT JOIN categorias cp ON cp.id = c.padre_id
        SET h.activo = 0
      WHERE h.activo = 1 AND h.nombre NOT IN ({$marcas})
        AND COALESCE(cp.clave, c.clave, '') <> 'practica-invierno'
        AND NOT EXISTS (SELECT 1 FROM solicitud_items si JOIN solicitudes s ON s.id = si.solicitud_id
                         WHERE si.herramienta_id = h.id
                           AND (s.estado IN ('pendiente', 'prestada')
                                OR (si.resolucion IS NULL AND (si.estado_recepcion = 'danada' OR si.cantidad_devuelta < si.cantidad))))",
    $nombres
);

Consola::exito("Herramientas: {$nuevas} nuevas, {$movidas} ordenadas en su categoría, {$desactivadas} del catálogo provisorio desactivadas.");
