-- =====================================================================
-- 004 · Insumos
-- Productos de consumo (no se devuelven) y sus códigos de fábrica.
-- =====================================================================

-- Todo se cuenta por unidad entera: una caja, un bidón o una resma cuentan como 1.
-- stock_actual es una caché: se actualiza en la misma transacción que cada movimiento
-- y siempre debe ser igual a la suma de las cantidades de movimiento_items del insumo.
CREATE TABLE insumos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(20) NOT NULL COMMENT 'Código interno de la etiqueta: INS00001',
    codigo_udec VARCHAR(20) NULL COMMENT 'Código del catálogo de pedidos UdeC (ej. 3001027)',
    nombre VARCHAR(150) NOT NULL,
    categoria_id INT UNSIGNED NULL COMMENT 'Null = sin categoría',
    sector_id INT UNSIGNED NULL COMMENT 'Sector donde se guarda: el último donde se dejó. Null = sin asignar',
    stock_actual INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 0 COMMENT 'Con esta cantidad o menos se avisa y entra al pedido de materiales',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_insumos_codigo (codigo),
    UNIQUE KEY uq_insumos_codigo_udec (codigo_udec),
    KEY ix_insumos_nombre (nombre),
    KEY ix_insumos_categoria (categoria_id),
    KEY ix_insumos_sector (sector_id),
    CONSTRAINT fk_insumos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id),
    CONSTRAINT fk_insumos_sector FOREIGN KEY (sector_id) REFERENCES sectores (id),
    CONSTRAINT ck_insumos_stock CHECK (stock_actual >= 0),
    CONSTRAINT ck_insumos_stock_minimo CHECK (stock_minimo >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Insumos de consumo';

-- Códigos de fábrica (EAN, UPC, etc.). Un insumo puede tener varios: cambia la marca o
-- la presentación y sigue siendo el mismo insumo. Cada lectura suma una unidad.
CREATE TABLE insumo_codigos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    insumo_id INT UNSIGNED NOT NULL,
    codigo VARCHAR(64) NOT NULL COMMENT 'Tal como lo lee el lector',
    descripcion VARCHAR(80) NULL COMMENT 'Presentación: Bidón 5 L, Caja 100 u.',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_insumo_codigos_codigo (codigo),
    KEY ix_insumo_codigos_insumo (insumo_id),
    CONSTRAINT fk_insumo_codigos_insumo FOREIGN KEY (insumo_id) REFERENCES insumos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Códigos de barra de fábrica de los insumos';
