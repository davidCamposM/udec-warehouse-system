<?php

/**
 * Catálogo oficial de herramientas e instrumentos de terreno, entregado por la bodega el 15/10/2026, con las unidades
 * del inventario del 06/11/2026 (la columna DISPONIBLE: cantidad + nuevas − dadas de baja). Se corrigen los tildes y
 * algunos errores de tipeo de la lista original (Hiposómetro → Hipsómetro, Cinómetro → Clinómetro, Relacopio →
 * Relascopio, Huincha Carpinteri → Huincha Carpintero, Barreros → Barrenos, Taba Munsell → Tabla Munsell).
 *
 * Las categorías no venían en la lista: se agruparon por para qué sirven, en cuatro (Para medir, Para cortar y podar,
 * Para el suelo y plantar, Seguridad y terreno), para encontrarlas rápido al solicitar. El Rozón (corta maleza) va con
 * las de cortar; el McLeod y el Mata chispas (combate de incendios), con las de seguridad. Solo agrega lo que falta,
 * con sus unidades (las que ya existen no se tocan). Las herramientas activas que no están en esta lista (el catálogo
 * provisorio anterior) se desactivan, salvo las prestadas, pedidas o por resolver: las solicitudes que ya las usaron las
 * siguen mostrando.
 */

declare(strict_types=1);

use App\Core\Database;
use App\Support\Consola;

defined('APP_ROOT') || exit;

// categoría => [herramienta => unidades] (la columna DISPONIBLE del inventario de la bodega, 06/11/2026)
$catalogo = [
    'Para medir' => [
        'Barra Telescópica' => 3,
        'Brújulas' => 20,
        'Calibrador Corteza' => 14,
        'Clinómetro' => 12,
        'Forcípulas' => 23,
        'Forcípulas 100 cm / 80 cm' => 7,
        'Forcípulas 40-60 cm' => 13,
        'GPS' => 12,
        'Hipsómetro' => 11,
        'Huincha Carpintero (3 y 5 m)' => 8,
        'Huincha Diamétrica' => 9,
        'Huincha Distancia (10 m)' => 6,
        'Huincha Distancia (30 m)' => 24,
        'Huincha Distancia (50 m)' => 3,
        'Huincha Distancia (60 m)' => 3,
        'Huincha tela metric diameter' => 15,
        'Medidor espesor de corteza Haglof Bark Gauge' => 14,
        'Niveles de Adney' => 1,
        'Pie de Metro Análogo' => 10,
        'Pilodon Medido Corteza' => 12,
        'Placa Conteo (Relascopía)' => 7,
        'Prisma (Relascopía)' => 12,
        'Relascopio' => 1,
        'Relascopio Análogo' => 2,
        'Taladro Incremento (Normal 14")' => 4,
        'Taladro Incremento (8")' => 4,
    ],
    'Para cortar y podar' => [
        'Arco Sierra Trozar 21"' => 19,
        'Arco Sierra Trozar 30"' => 13,
        'Esmeril' => 1,
        'Hacha' => 12,
        'Hachas Casoc' => 19,
        'Machetes' => 13,
        'Marco Sierra Telescópica' => 2,
        'Motosierra' => 1,
        'Podadoras' => 1,
        'Rozón' => 21,
        'Serrucho cola de zorro' => 28,
        'Sierra Madera Grande' => 12,
        'Sierra Madera Pequeña' => 16,
        'Tijerones Poda' => 9,
    ],
    'Para el suelo y plantar' => [
        'Azacha' => 2,
        'Azadones' => 6,
        'Barrenos' => 4,
        'Chuzos' => 1,
        'Laya' => 4,
        'Palas' => 10,
        'Palas Plantadoras' => 94,
        'Picotas' => 8,
        'Rastrillos' => 3,
        'Rodillo Metálico' => 2,
        'Tabla Munsell' => 5,
    ],
    'Seguridad y terreno' => [
        'Botas Wader' => 15,
        'Cajas Metálicas' => 2,
        'Carretilla Concretera 90 litros' => 2,
        'Chaleco Reflectante para terreno' => 23,
        'Estanques Plásticos' => 2,
        'Mata Chispas' => 17,
        'Mcleod' => 6,
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

    foreach ($herramientas as $nombre => $unidades) {
        $nombres[] = $nombre;
        $existente = Database::fetch('SELECT id, categoria_id, activo FROM herramientas WHERE nombre = ?', [$nombre]);

        if ($existente === null) {
            Database::insert('INSERT INTO herramientas (nombre, categoria_id, unidades) VALUES (?, ?, ?)', [$nombre, $categoriaId, $unidades]);
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
