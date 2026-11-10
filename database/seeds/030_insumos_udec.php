<?php

/**
 * Catálogo inicial de insumos: los 60 productos pedidos por la facultad en 2026
 * (Productos_pedidos_FCF_2026.xlsx). Se cargan con stock 0 y mínimo 0: el stock real se
 * carga con el ajuste de cantidad o el modo inventario, y el encargado define los mínimos.
 * Todo se cuenta por unidad: una caja de guantes, un bidón o una resma cuentan como 1.
 */

declare(strict_types=1);

use App\Core\Database;
use App\Support\Consola;

defined('APP_ROOT') || exit;

// [código UdeC, nombre, categoría UdeC, subcategoría UdeC] (la categoría que queda la decide $categoriaId, más abajo)
$insumos = [
    ['2005017', 'GUANTES DE NITRILO T/L LIBRE DE TALCO/FENOL CJA 100 UNID.', 'Ropa e Implementos de Trabajo', 'Ropa de Trabajo'],
    ['2005018', 'GUANTES DE NITRILO T/M LIBRE DE TALCO/FENOL CJA 100 UNID.', 'Ropa e Implementos de Trabajo', 'Ropa de Trabajo'],
    ['2200001', 'PILAS ALCALINA GRANDE D', 'Materiales Especializados', 'Material para Proyección y Sonido'],
    ['2200081', 'PILAS ALCALINA CHICA AA', 'Materiales Especializados', 'Material para Proyección y Sonido'],
    ['2200082', 'PILAS ALCALINA CHICA AAA', 'Materiales Especializados', 'Material para Proyección y Sonido'],
    ['2201268', 'TELEFONO ANALOGO ALCATEL 4028', 'Materiales Especializados', 'Telecomunicaciones'],
    ['3000003', 'PALA PLASTICA PARA BASURA', 'Materiales y Útiles de Aseo', 'Bienes Semidurables'],
    ['3000111', 'ESCOBILLON PARA PISO PLASTICO', 'Materiales y Útiles de Aseo', 'Bienes Semidurables'],
    ['3001008', 'BOLSA PLASTICA PARA BASURA DE 50 X 70 CM', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza'],
    ['3001010', 'BOLSA PLASTICA PARA BASURA DE 80 X 110CM', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza'],
    ['3001025', 'CIF CREMA DE 750 GRS', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza'],
    ['3001027', 'CLORO, ENVASE 1 LT.', 'Materiales y Útiles de Aseo', 'Artículos Desinfectantes'],
    ['3001031', 'DESINFECTANTE SPRAY AMB/SUP360C', 'Materiales y Útiles de Aseo', 'Artículos Desinfectantes'],
    ['3001048', 'ESPONJA PARA LOZA VERDE AMARILLA', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza'],
    ['3001057', 'INSECTICIDA SPRAY CASA Y JARDIN 360 CC', 'Materiales y Útiles de Aseo', 'Artículos Desinfectantes'],
    ['3001058', 'INSECTICIDA SPRAY MATA ARAÑAS 360 CC', 'Materiales y Útiles de Aseo', 'Artículos Desinfectantes'],
    ['3001063', 'JABON LIQUIDO DISPENSADOR BIDON 5LT NEUT', 'Materiales y Útiles de Aseo', 'Artículos de Tocador'],
    ['3001078', 'LAVALOZAS QUIX DE 500 ML.', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza'],
    ['3001081', 'LIMPIADOR DESINFECTANTE P/BAÑO DE 500 CC', 'Materiales y Útiles de Aseo', 'Artículos Desinfectantes'],
    ['3001083', 'LIMPIADOR MULTIUSO DE 500 CC.', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza'],
    ['3001102', 'PAÑO DE ASEO DE COCINA 48 X 50 CMS.', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza'],
    ['3001103', 'PAÑO DE ASEO MOLETON BLANCO 45 X 50 CMS', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza'],
    ['3001104', 'PAÑO DE ASEO MULTIUSO ABSORBENT 38X40 CM', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza'],
    ['3001108', 'PAPEL HIGIENICO DISPENSADOR ROLLO 500 MT', 'Materiales y Útiles de Aseo', 'Artículos de Tocador'],
    ['3001111', 'PASTILLA DESINFEC BAÑO REDONDA 40GR ESTA', 'Materiales y Útiles de Aseo', 'Artículos Desinfectantes'],
    ['3001118', 'SERVILLETA DE PAPEL DE 24 X 24 CMS.', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza'],
    ['3001127', 'TRAPERO DE PISO DE 50 X 50 DOBLE C/OJAL', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza'],
    ['3001132', 'PAPEL TOALLA CLASICA NOVA 12 MTS.', 'Materiales y Útiles de Aseo', 'Artículos de Tocador'],
    ['3001133', 'PAPEL TOALLA DISPENS BLANCA ROLLO 250 MT', 'Materiales y Útiles de Aseo', 'Artículos de Tocador'],
    ['3005060', 'AROMATIZADOR LATA SANI AIR AROMA ORANGE', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza'],
    ['3005061', 'MULTILUSTRADOR CON SILICONA AROMA NARANJA', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza'],
    ['3005062', 'MULTILUSTRADOR CON SILICONA AROMA LAVANDA', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza'],
    ['3005066', 'DESODORANTE AMB. SPRAY AROMA CHIRIMOYA ALEGRE', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza'],
    ['3005104', 'ALCOHOL ETANOL 70%, BIDÓN 5 litros', 'Materiales y Útiles de Aseo', 'Artículos Desinfectantes'],
    ['3005305', 'Limpiapisos desinfectante envase de 5 ltr.', null, null],
    ['3212246', 'Toallita desinfectante con alcohol', null, null],
    ['4100005', 'CINTA ADHESIVA TRANSPARENTE 1/2PUL ECON.', 'Materiales de Oficina', 'Accesorios de Oficina'],
    ['4100022', 'CORCHETERA 26/6 MEDIANA', 'Materiales y Útiles de Aseo', 'Bienes Semidurables'],
    ['4100041', 'BORRADOR PARA PIZARRA PARA TIZA', 'Materiales y Útiles de Aseo', 'Bienes Semidurables'],
    ['4100056', 'FASTENER PARA CARPETA ACCO CLIPS', 'Materiales de Oficina', 'Accesorios de Oficina'],
    ['4100064', 'CLIP CHICO N° 1 30 MM', 'Materiales de Oficina', 'Accesorios de Oficina'],
    ['4100070', 'CORCHETES 26/6 1000 U', 'Materiales de Oficina', 'Accesorios de Oficina'],
    ['4100071', 'CORCHETES DE 5000 UNIDADES', 'Materiales de Oficina', 'Accesorios de Oficina'],
    ['4101049', 'CORRECTOR LIQUIDO BASE SOLVENTE LAPIZ', 'Materiales de Oficina', 'Suministros para Máquina de Oficina'],
    ['4101090', 'PAPEL FOTOCOPIA EQUALIT T/ CARTA 500 H.', 'Materiales de Oficina', 'Papeles y Cartones'],
    ['4101091', 'PAPEL FOTOCOPIA EQUALIT T/OFICIO 500 H.', 'Materiales de Oficina', 'Papeles y Cartones'],
    ['4101093', 'PAPEL FOTOCOPIA DIMACOFI CARTA DE 500 HOJAS', 'Materiales de Oficina', 'Papeles y Cartones'],
    ['4101094', 'PAPEL FOTOCOPIA DIMACOFI OFICIO DE 500 H.', 'Materiales de Oficina', 'Papeles y Cartones'],
    ['4101106', 'PEGAMENTO SOLIDO EN BARRA STIC FIX 20 GR', 'Materiales de Oficina', 'Accesorios de Oficina'],
    ['4105031', 'LAPIZ PASTA BIC COLOR AZUL', 'Materiales de Oficina', 'Artículos de Escritorio y Dibujo'],
    ['4105032', 'LAPIZ PASTA BIC COLOR NEGRO', 'Materiales de Oficina', 'Artículos de Escritorio y Dibujo'],
    ['4105033', 'LAPIZ PASTA BIC COLOR ROJO', 'Materiales de Oficina', 'Artículos de Escritorio y Dibujo'],
    ['4105034', 'LAPIZ PLUMÓN P/PIZARRA ACRÍLICA COLOR NEGRO', 'Materiales de Oficina', 'Artículos de Escritorio y Dibujo'],
    ['4105035', 'LAPIZ PLUMÓN P/PIZARRA ACRÍLICA COLOR AZUL', 'Materiales de Oficina', 'Artículos de Escritorio y Dibujo'],
    ['4105036', 'LAPIZ PLUMÓN P/PIZARRA ACRÍLICA COLOR ROJO', 'Materiales de Oficina', 'Artículos de Escritorio y Dibujo'],
    ['4105037', 'LAPIZ PLUMÓN P/PIZARRA ACRÍLICA COLOR VERDE', 'Materiales de Oficina', 'Artículos de Escritorio y Dibujo'],
    ['4105052', 'CARPETA CON ELASTICO T/OFICIO COLOR GRIS', 'Materiales de Oficina', 'Papeles y Cartones'],
    ['4305026', 'GUARDAPOLVO BLANCO P/DAMA T/46', 'Ropa e Implementos de Trabajo', 'Ropa de Trabajo'],
    ['4305032', 'GUARDAPOLVO BLANCO P/DAMA T/58', 'Ropa e Implementos de Trabajo', 'Ropa de Trabajo'],
    ['4305038', 'GUARDAPOLVO BLANCO P/HOMBRE T/XXL', 'Ropa e Implementos de Trabajo', 'Ropa de Trabajo'],
];

/**
 * Id de la categoría de un producto. La lista conserva la clasificación del catálogo UdeC (categoría y subcategoría,
 * como venía en el Excel); se traduce a las cuatro categorías simples (seed 020): todo lo de Aseo y de Ropa e
 * Implementos va en Limpieza, Oficina en Oficina y las pilas en Pilas. Excepciones, por su código UdeC: la corchetera y
 * el borrador para tiza (la UdeC los tenía en Aseo) y el teléfono, en Oficina.
 */
$categoriaId = static function (string $codigoUdec, ?string $categoria): int {
    $excepciones = ['4100022' => 'Oficina', '4100041' => 'Oficina', '2201268' => 'Oficina'];
    $nombre = $excepciones[$codigoUdec] ?? match ($categoria) {
        'Materiales de Oficina' => 'Oficina',
        'Materiales Especializados' => 'Pilas',
        default => 'Limpieza', // Materiales y Útiles de Aseo, Ropa e Implementos de Trabajo y los sin categoría
    };

    $id = Database::fetchValue(
        'SELECT id FROM categorias WHERE ambito = ? AND padre_id IS NULL AND nombre = ?',
        ['insumo', $nombre]
    );

    if ($id === null) {
        throw new RuntimeException("Falta la categoría {$nombre}: ejecuta antes 020_categorias_udec.php.");
    }

    return (int) $id;
};

$nuevos = Database::transaction(static function () use ($insumos, $categoriaId): int {
    // Siguiente número de código interno: INS00001, INS00002...
    $numero = (int) Database::fetchValue(
        "SELECT COALESCE(MAX(CAST(SUBSTRING(codigo, 4) AS UNSIGNED)), 0) FROM insumos WHERE codigo LIKE 'INS%'"
    );
    $nuevos = 0;

    foreach ($insumos as [$codigoUdec, $nombre, $categoria]) {
        if (Database::fetchValue('SELECT id FROM insumos WHERE codigo_udec = ?', [$codigoUdec]) !== null) {
            continue;
        }

        Database::insert(
            'INSERT INTO insumos (codigo, codigo_udec, nombre, categoria_id) VALUES (?, ?, ?, ?)',
            [sprintf('INS%05d', ++$numero), $codigoUdec, $nombre, $categoriaId($codigoUdec, $categoria)]
        );
        $nuevos++;
    }

    return $nuevos;
});

Consola::exito("Insumos: {$nuevos} nuevos de " . count($insumos) . '.');
