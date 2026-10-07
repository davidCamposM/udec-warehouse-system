<?php

/**
 * Catálogo inicial de insumos: los 60 productos pedidos por la facultad en 2026
 * (Productos_pedidos_FCF_2026.xlsx). Se cargan con stock 0 y mínimo 0. El stock real se
 * carga con el modo inventario y el encargado ajusta mínimos y unidades.
 *
 * Unidad de medida asignada según el nombre: "CJA 100 UNID." → Caja, "500 H." → Resma,
 * rollos → Rollo, bidones de 5 L → Litro (se entregan por litro); el resto → Unidad.
 */

declare(strict_types=1);

use App\Core\Database;
use App\Support\Consola;

defined('APP_ROOT') || exit;

// [código UdeC, nombre, categoría, subcategoría, unidad de medida]
$insumos = [
    ['2005017', 'GUANTES DE NITRILO T/L LIBRE DE TALCO/FENOL CJA 100 UNID.', 'Insumos Protocolo COVID-19', null, 'Caja'],
    ['2005018', 'GUANTES DE NITRILO T/M LIBRE DE TALCO/FENOL CJA 100 UNID.', 'Insumos Protocolo COVID-19', null, 'Caja'],
    ['2200001', 'PILAS ALCALINA GRANDE D', 'Materiales Especializados', 'Material para Proyección y Sonido', 'Unidad'],
    ['2200081', 'PILAS ALCALINA CHICA AA', 'Materiales Especializados', 'Material para Proyección y Sonido', 'Unidad'],
    ['2200082', 'PILAS ALCALINA CHICA AAA', 'Materiales Especializados', 'Material para Proyección y Sonido', 'Unidad'],
    ['2201268', 'TELEFONO ANALOGO ALCATEL 4028', 'Materiales Especializados', 'Telecomunicaciones', 'Unidad'],
    ['3000003', 'PALA PLASTICA PARA BASURA', 'Materiales y Útiles de Aseo', 'Bienes Semidurables', 'Unidad'],
    ['3000111', 'ESCOBILLON PARA PISO PLASTICO', 'Materiales y Útiles de Aseo', 'Bienes Semidurables', 'Unidad'],
    ['3001008', 'BOLSA PLASTICA PARA BASURA DE 50 X 70 CM', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza', 'Unidad'],
    ['3001010', 'BOLSA PLASTICA PARA BASURA DE 80 X 110CM', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza', 'Unidad'],
    ['3001025', 'CIF CREMA DE 750 GRS', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza', 'Unidad'],
    ['3001027', 'CLORO, ENVASE 1 LT.', 'Materiales y Útiles de Aseo', 'Artículos Desinfectantes', 'Unidad'],
    ['3001031', 'DESINFECTANTE SPRAY AMB/SUP360C', 'Materiales y Útiles de Aseo', 'Artículos Desinfectantes', 'Unidad'],
    ['3001048', 'ESPONJA PARA LOZA VERDE AMARILLA', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza', 'Unidad'],
    ['3001057', 'INSECTICIDA SPRAY CASA Y JARDIN 360 CC', 'Materiales y Útiles de Aseo', 'Artículos Desinfectantes', 'Unidad'],
    ['3001058', 'INSECTICIDA SPRAY MATA ARAÑAS 360 CC', 'Materiales y Útiles de Aseo', 'Artículos Desinfectantes', 'Unidad'],
    ['3001063', 'JABON LIQUIDO DISPENSADOR BIDON 5LT NEUT', 'Materiales y Útiles de Aseo', 'Artículos de Tocador', 'Litro'],
    ['3001078', 'LAVALOZAS QUIX DE 500 ML.', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza', 'Unidad'],
    ['3001081', 'LIMPIADOR DESINFECTANTE P/BAÑO DE 500 CC', 'Materiales y Útiles de Aseo', 'Artículos Desinfectantes', 'Unidad'],
    ['3001083', 'LIMPIADOR MULTIUSO DE 500 CC.', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza', 'Unidad'],
    ['3001102', 'PAÑO DE ASEO DE COCINA 48 X 50 CMS.', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza', 'Unidad'],
    ['3001103', 'PAÑO DE ASEO MOLETON BLANCO 45 X 50 CMS', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza', 'Unidad'],
    ['3001104', 'PAÑO DE ASEO MULTIUSO ABSORBENT 38X40 CM', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza', 'Unidad'],
    ['3001108', 'PAPEL HIGIENICO DISPENSADOR ROLLO 500 MT', 'Materiales y Útiles de Aseo', 'Artículos de Tocador', 'Rollo'],
    ['3001111', 'PASTILLA DESINFEC BAÑO REDONDA 40GR ESTA', 'Materiales y Útiles de Aseo', 'Artículos Desinfectantes', 'Unidad'],
    ['3001118', 'SERVILLETA DE PAPEL DE 24 X 24 CMS.', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza', 'Unidad'],
    ['3001127', 'TRAPERO DE PISO DE 50 X 50 DOBLE C/OJAL', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza', 'Unidad'],
    ['3001132', 'PAPEL TOALLA CLASICA NOVA 12 MTS.', 'Materiales y Útiles de Aseo', 'Artículos de Tocador', 'Rollo'],
    ['3001133', 'PAPEL TOALLA DISPENS BLANCA ROLLO 250 MT', 'Materiales y Útiles de Aseo', 'Artículos de Tocador', 'Rollo'],
    ['3005060', 'AROMATIZADOR LATA SANI AIR AROMA ORANGE', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza', 'Unidad'],
    ['3005061', 'MULTILUSTRADOR CON SILICONA AROMA NARANJA', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza', 'Unidad'],
    ['3005062', 'MULTILUSTRADOR CON SILICONA AROMA LAVANDA', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza', 'Unidad'],
    ['3005066', 'DESODORANTE AMB. SPRAY AROMA CHIRIMOYA ALEGRE', 'Materiales y Útiles de Aseo', 'Artículos de Limpieza', 'Unidad'],
    ['3005104', 'ALCOHOL ETANOL 70%, BIDÓN 5 litros', 'Insumos Protocolo COVID-19', null, 'Litro'],
    ['3005305', 'Limpiapisos desinfectante envase de 5 ltr.', null, null, 'Litro'],
    ['3212246', 'Toallita desinfectante con alcohol', null, null, 'Unidad'],
    ['4100005', 'CINTA ADHESIVA TRANSPARENTE 1/2PUL ECON.', 'Materiales de Oficina', 'Accesorios de Oficina', 'Unidad'],
    ['4100022', 'CORCHETERA 26/6 MEDIANA', 'Materiales y Útiles de Aseo', 'Bienes Semidurables', 'Unidad'],
    ['4100041', 'BORRADOR PARA PIZARRA PARA TIZA', 'Materiales y Útiles de Aseo', 'Bienes Semidurables', 'Unidad'],
    ['4100056', 'FASTENER PARA CARPETA ACCO CLIPS', 'Materiales de Oficina', 'Accesorios de Oficina', 'Unidad'],
    ['4100064', 'CLIP CHICO N° 1 30 MM', 'Materiales de Oficina', 'Accesorios de Oficina', 'Unidad'],
    ['4100070', 'CORCHETES 26/6 1000 U', 'Materiales de Oficina', 'Accesorios de Oficina', 'Caja'],
    ['4100071', 'CORCHETES DE 5000 UNIDADES', 'Materiales de Oficina', 'Accesorios de Oficina', 'Caja'],
    ['4101049', 'CORRECTOR LIQUIDO BASE SOLVENTE LAPIZ', 'Materiales de Oficina', 'Suministros para Máquina de Oficina', 'Unidad'],
    ['4101090', 'PAPEL FOTOCOPIA EQUALIT T/ CARTA 500 H.', 'Materiales de Oficina', 'Papeles y Cartones', 'Resma'],
    ['4101091', 'PAPEL FOTOCOPIA EQUALIT T/OFICIO 500 H.', 'Materiales de Oficina', 'Papeles y Cartones', 'Resma'],
    ['4101093', 'PAPEL FOTOCOPIA DIMACOFI CARTA DE 500 HOJAS', 'Materiales de Oficina', 'Papeles y Cartones', 'Resma'],
    ['4101094', 'PAPEL FOTOCOPIA DIMACOFI OFICIO DE 500 H.', 'Materiales de Oficina', 'Papeles y Cartones', 'Resma'],
    ['4101106', 'PEGAMENTO SOLIDO EN BARRA STIC FIX 20 GR', 'Materiales de Oficina', 'Accesorios de Oficina', 'Unidad'],
    ['4105031', 'LAPIZ PASTA BIC COLOR AZUL', 'Materiales de Oficina', 'Artículos de Escritorio y Dibujo', 'Unidad'],
    ['4105032', 'LAPIZ PASTA BIC COLOR NEGRO', 'Materiales de Oficina', 'Artículos de Escritorio y Dibujo', 'Unidad'],
    ['4105033', 'LAPIZ PASTA BIC COLOR ROJO', 'Materiales de Oficina', 'Artículos de Escritorio y Dibujo', 'Unidad'],
    ['4105034', 'LAPIZ PLUMÓN P/PIZARRA ACRÍLICA COLOR NEGRO', 'Materiales de Oficina', 'Artículos de Escritorio y Dibujo', 'Unidad'],
    ['4105035', 'LAPIZ PLUMÓN P/PIZARRA ACRÍLICA COLOR AZUL', 'Materiales de Oficina', 'Artículos de Escritorio y Dibujo', 'Unidad'],
    ['4105036', 'LAPIZ PLUMÓN P/PIZARRA ACRÍLICA COLOR ROJO', 'Materiales de Oficina', 'Artículos de Escritorio y Dibujo', 'Unidad'],
    ['4105037', 'LAPIZ PLUMÓN P/PIZARRA ACRÍLICA COLOR VERDE', 'Materiales de Oficina', 'Artículos de Escritorio y Dibujo', 'Unidad'],
    ['4105052', 'CARPETA CON ELASTICO T/OFICIO COLOR GRIS', 'Materiales de Oficina', 'Papeles y Cartones', 'Unidad'],
    ['4305026', 'GUARDAPOLVO BLANCO P/DAMA T/46', 'Ropa e Implementos de Trabajo', 'Ropa de Trabajo', 'Unidad'],
    ['4305032', 'GUARDAPOLVO BLANCO P/DAMA T/58', 'Ropa e Implementos de Trabajo', 'Ropa de Trabajo', 'Unidad'],
    ['4305038', 'GUARDAPOLVO BLANCO P/HOMBRE T/XXL', 'Ropa e Implementos de Trabajo', 'Ropa de Trabajo', 'Unidad'],
];

$unidades = array_column(Database::fetchAll('SELECT id, nombre FROM unidades_medida'), 'id', 'nombre');

/** Id de la categoría (o subcategoría) por nombre; null si no tiene. */
$categoriaId = static function (?string $categoria, ?string $subcategoria): ?int {
    if ($categoria === null) {
        return null;
    }

    $padre = Database::fetchValue(
        'SELECT id FROM categorias WHERE ambito = ? AND padre_id IS NULL AND nombre = ?',
        ['insumo', $categoria]
    );

    if ($padre === null) {
        throw new RuntimeException("Falta la categoría {$categoria}: ejecuta antes 020_categorias_udec.php.");
    }

    if ($subcategoria === null) {
        return (int) $padre;
    }

    return (int) Database::fetchValue(
        'SELECT id FROM categorias WHERE ambito = ? AND padre_id = ? AND nombre = ?',
        ['insumo', $padre, $subcategoria]
    );
};

$nuevos = Database::transaction(static function () use ($insumos, $unidades, $categoriaId): int {
    // Siguiente número de código interno: INS00001, INS00002...
    $numero = (int) Database::fetchValue(
        "SELECT COALESCE(MAX(CAST(SUBSTRING(codigo, 4) AS UNSIGNED)), 0) FROM insumos WHERE codigo LIKE 'INS%'"
    );
    $nuevos = 0;

    foreach ($insumos as [$codigoUdec, $nombre, $categoria, $subcategoria, $unidad]) {
        if (Database::fetchValue('SELECT id FROM insumos WHERE codigo_udec = ?', [$codigoUdec]) !== null) {
            continue;
        }

        Database::insert(
            'INSERT INTO insumos (codigo, codigo_udec, nombre, categoria_id, unidad_medida_id) VALUES (?, ?, ?, ?, ?)',
            [
                sprintf('INS%05d', ++$numero),
                $codigoUdec,
                $nombre,
                $categoriaId($categoria, $subcategoria),
                $unidades[$unidad] ?? throw new RuntimeException("Falta la unidad {$unidad}."),
            ]
        );
        $nuevos++;
    }

    return $nuevos;
});

Consola::exito("Insumos: {$nuevos} nuevos de " . count($insumos) . '.');
