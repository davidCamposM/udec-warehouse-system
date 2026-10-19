<?php

/**
 * Equipo de la Práctica de Invierno (equipo de montaña, lista entregada el 16/10/2026). Se presta y se devuelve, como
 * las herramientas: una categoría principal propia (clave practica-invierno), agrupada por tipo, con 0 unidades porque
 * aún no se sabe cuántas hay (el encargado las anota en «Editar herramienta»). Se corrigieron
 * erratas evidentes (Marmmot → Marmot, Petzi → Petzl, Frontral → Frontal…), los plurales pasaron a singular y los
 * repetidos de la lista quedaron una sola vez. Lo que la lista agrupaba («Sky Rondoné: 1 Modelo 777…», «Bastones: 5 de
 * 110 cm…») queda como un producto por modelo o medida.
 */

declare(strict_types=1);

use App\Core\Database;
use App\Support\Consola;

defined('APP_ROOT') || exit;

$practica = 'Práctica de Invierno';

// subcategoría => productos
$productos = [
    'Esquí y fijaciones' => [
        'Sky Rondoné Modelo 777',
        'Sky Rondoné Modelo 888',
        'Sky Rondoné Modelo Sierra',
        'Sky Rondoné Modelo Saúl 153 dama',
        'Sky Elan Modelo Karakorum 1,70',
        'Sky Elan Modelo Karakorum 1,78',
        'Sky Makalu Rondoné',
        'Fijaciones Rondoné',
        'Botas Rondoné Alpine Boots SA Quest Access 80 Anthra BK 27,5',
        'Piel de foca',
        'Piel de foca Kohla',
        'Piel de foca 130 mm - 190 cm Multifit Kohla 70% Mohair',
        'Ascensión Nylon 125 mm',
    ],
    'Bastones' => [
        'Bastón 110 cm',
        'Bastón 115 cm',
        'Bastón 120 cm',
        'Bastón 125 cm',
        'Bastón 130 cm',
        'Bastón de montaña Sherpa Masters',
    ],
    'Cuerdas, arneses y seguridad' => [
        'Cordín Beal 8 mm',
        'Cuerda Mammut 8,0 Phoenix 60 m',
        'Cuerda simple Mammut Galaxy SDry 70 m x 10 mm',
        'Rollo de cuerda Mammut Performance Static 11 mm',
        'Cuerda 94 Rope 70 m (3-70M)',
        'Arnés de rescate CAMP Golden Top Seat Alu color único (S-L)',
        'Arnés de cintas Group II CAMP',
        'Descendedor Element 8 Mammut Orange One Size',
        'Mosquetón HMS Ultratech Mad Rock',
        'Nudo para trabajos verticales',
        'Wall Alpine Belay Orange sin talla',
    ],
    'Cascos y protección' => [
        'Casco Armour CAMP Naranjo One Size',
        'Casco Tripod Mammut Salsa Cayenne 2',
        'Ophir 3 Slide Smoke-Orange L',
        'Ophir 3 Slide Smoke-Orange M',
        'Guante Basic Work Glove Marmot Black L',
    ],
    'Crampones, piolets y raquetas' => [
        'Crampón Stalker CAMP NB c/antibott y estuche',
        'Piolet de marcha Neve CAMP 57 cm',
        'Piolet de marcha Neve CAMP 65 cm',
        'Raqueta Baldas Miage Naranjo One Size',
        'Raqueta Baldas Lys Castor Rojo One Size',
        'Pala extensible de policarbonato 3DX',
    ],
    'Carpas y sacos de dormir' => [
        'Carpa de montaña SualBoard 3 personas verde olivo',
        'Carpa 4 estaciones Lhotse 3 Ferrino',
        'Carpa 4 estaciones Lhotse 3 Ferrino Naranjo One Size',
        'Carpa 4 estaciones Pumori 2 Ferrino',
        'Saco Marmot Trestles 15 Long Cobalt Blue/Deep Blue izquierdo',
        'Saco Marmot Trestles 15 Regular Cobalt Blue/Deep Blue izquierdo',
    ],
    'Cocina e iluminación' => [
        'Cocinilla Primus Mimer',
        'Cocinilla Pocket Rocket MSR',
        'Set de cocina 4 personas con sartén antiadherente',
        'Lámpara Lighthouse Goal Zero',
        'Linterna frontal Petzl Tikka 2 azul',
        'Linterna frontal Petzl Tikka 2 gris',
    ],
    'Otros' => [
        'Caja porta herramientas',
        'Cadenas para ruedas (par)',
    ],
];

[$nuevas, $nuevos] = Database::transaction(static function () use ($productos, $practica): array {
    $nuevas = 0;
    $categoria = static function (string $nombre, ?int $padreId) use (&$nuevas): int {
        $id = Database::fetchValue(
            "SELECT id FROM categorias WHERE ambito = 'herramienta' AND nombre = ? AND padre_id <=> ?",
            [$nombre, $padreId]
        );
        if ($id !== null) {
            return (int) $id;
        }
        $nuevas++;

        return Database::insert("INSERT INTO categorias (nombre, padre_id, ambito) VALUES (?, ?, 'herramienta')", [$nombre, $padreId]);
    };

    // La principal se reconoce por su clave (aunque le cambien el nombre).
    $principal = Database::fetchValue("SELECT id FROM categorias WHERE clave = 'practica-invierno'");
    if ($principal === null) {
        $principal = $categoria($practica, null);
        Database::execute("UPDATE categorias SET clave = 'practica-invierno' WHERE id = ?", [$principal]);
    }
    $principal = (int) $principal;
    $nuevos = 0;

    foreach ($productos as $subcategoria => $nombres) {
        $categoriaId = $categoria($subcategoria, $principal);
        foreach ($nombres as $nombre) {
            // Ya cargado (aunque se haya eliminado después): no se repite.
            if (Database::fetchValue('SELECT id FROM herramientas WHERE nombre = ? AND categoria_id = ?', [$nombre, $categoriaId]) !== null) {
                continue;
            }
            Database::insert('INSERT INTO herramientas (nombre, categoria_id) VALUES (?, ?)', [$nombre, $categoriaId]);
            $nuevos++;
        }
    }

    return [$nuevas, $nuevos];
});

Consola::exito('Práctica de Invierno: ' . $nuevas . ' categorías nuevas, ' . $nuevos . ' herramientas nuevas de '
    . array_sum(array_map('count', $productos)) . '.');
