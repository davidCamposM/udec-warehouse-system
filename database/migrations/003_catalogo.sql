-- =====================================================================
-- 003 · Catálogo configurable
-- Categorías y sectores de la bodega, que el encargado administra sin
-- tocar el código.
-- =====================================================================

-- Sectores de la bodega (un estante, una repisa, un rincón). Cada uno tiene un código que se
-- imprime como QR y se pega en el lugar: al guardar un producto se escanea el producto y
-- después el sector donde queda.
CREATE TABLE sectores (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(20) NOT NULL COMMENT 'Código del QR pegado en el lugar: SEC001',
    nombre VARCHAR(80) NOT NULL COMMENT 'Ej. Estante A, repisa 2',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sectores_codigo (codigo),
    UNIQUE KEY uq_sectores_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Sectores de la bodega';

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
