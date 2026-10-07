-- =====================================================================
-- 003 · Catálogo configurable
-- Unidades de medida y categorías que el encargado administra sin
-- tocar el código.
-- =====================================================================

-- permite_decimales: Litro o Kilogramo admiten 0,5. Unidad, Caja o Rollo, solo enteros.
CREATE TABLE unidades_medida (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(40) NOT NULL,
    abreviatura VARCHAR(10) NOT NULL,
    permite_decimales TINYINT(1) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_unidades_medida_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Unidades de medida de los insumos';

-- Dos niveles, como el catálogo de pedidos UdeC: categoría y subcategoría (padre_id).
-- ambito separa las categorías de insumos de las de herramientas.
CREATE TABLE categorias (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80) NOT NULL,
    padre_id INT UNSIGNED NULL COMMENT 'Categoría superior. Null = categoría principal',
    ambito ENUM('insumo', 'herramienta') NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categorias_nombre (ambito, padre_id, nombre),
    CONSTRAINT fk_categorias_padre FOREIGN KEY (padre_id) REFERENCES categorias (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Categorías y subcategorías de insumos y herramientas';
