<?php

declare(strict_types=1);

use App\Core\Database;
use App\Support\Consola;

defined('APP_ROOT') || exit;

// Transcripción normalizada de las seis capturas entregadas. Las cifras de esos documentos no son un conteo
// vigente de bodega: cantidad_actual queda NULL. «Personalización IMP-X» figura dos veces y se consolida.
// [código estable, grupo, tipo, nombre, detalle]
$items = [
    ['DIF001', 'Textiles y bolsas', 'articulo', 'Bolsa de algodón BC012C', '132 g/m² · 41 × 37 cm'],
    ['DIF002', 'Textiles y bolsas', 'articulo', 'Mochila de algodón BC022C-S', '6 oz · 170 g/m² · 42 × 38 cm'],
    ['DIF003', 'Textiles y bolsas', 'articulo', 'Jockey de gabardina', 'Con logo estampado'],
    ['DIF004', 'Textiles y bolsas', 'articulo', 'Polera 100 % algodón', 'Con logo estampado'],
    ['DIF005', 'Textiles y bolsas', 'articulo', 'Bolsa de algodón crudo', '38 × 41 cm · estampada'],
    ['DIF006', 'Textiles y bolsas', 'articulo', 'Polerón de franela de algodón', 'Estampado personalizado en frente y espalda'],
    ['DIF007', 'Textiles y bolsas', 'articulo', 'Polera de algodón de manga corta', 'Difusión Día de Ingeniería Forestal · monitores CECO 10303AC001'],
    ['DIF008', 'Objetos promocionales', 'articulo', 'Botella de aluminio', 'Con gráfica corporativa estampada'],
    ['DIF009', 'Objetos promocionales', 'articulo', 'Llavero tipo huincha', 'Logo impreso por ambas caras'],
    ['DIF010', 'Gráfica y exhibición', 'articulo', 'Pendón roller', null],
    ['DIF011', 'Servicios asociados', 'servicio', 'Personalización IMP-X', 'Servicio de personalización de las bolsas de algodón'],
    ['DIF012', 'Servicios asociados', 'servicio', 'Envío', null],
    ['DIF013', 'Servicios asociados', 'servicio', 'Diseño y coordinación de maqueta e impresión', null],
];

$nuevos = Database::transaction(static function () use ($items): int {
    $nuevos = 0;
    foreach ($items as $orden => [$codigo, $grupo, $tipo, $nombre, $detalle]) {
        if (Database::fetchValue('SELECT id FROM difusion_items WHERE codigo = ?', [$codigo]) !== null) {
            continue;
        }
        Database::insert(
            'INSERT INTO difusion_items (codigo, grupo, tipo, nombre, detalle, orden) VALUES (?, ?, ?, ?, ?, ?)',
            [$codigo, $grupo, $tipo, $nombre, $detalle, $orden + 1]
        );
        $nuevos++;
    }
    return $nuevos;
});

Consola::exito("Difusión: {$nuevos} nuevos de " . count($items) . '.');
