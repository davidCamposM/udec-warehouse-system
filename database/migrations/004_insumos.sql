-- =====================================================================
-- 004 · Insumos
-- Productos de consumo (no se devuelven) y sus códigos de fábrica.
-- =====================================================================

-- stock_actual es una caché: se actualiza en la misma transacción que cada movimiento
-- y siempre debe ser igual a la suma de las cantidades de movimiento_items del insumo.
CREATE TABLE insumos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(20) NOT NULL COMMENT 'Código interno de la etiqueta: INS00001',
    codigo_udec VARCHAR(20) NULL COMMENT 'Código del catálogo de pedidos UdeC (ej. 3001027)',
    nombre VARCHAR(150) NOT NULL,
    categoria_id INT UNSIGNED NULL COMMENT 'Null = sin categoría',
    unidad_medida_id INT UNSIGNED NOT NULL,
    stock_actual DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock_minimo DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Bajo este valor se avisa y entra al pedido de materiales',
    ubicacion VARCHAR(60) NULL COMMENT 'Estante o sector de la bodega',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_insumos_codigo (codigo),
    UNIQUE KEY uq_insumos_codigo_udec (codigo_udec),
    KEY ix_insumos_nombre (nombre),
    KEY ix_insumos_categoria (categoria_id),
    CONSTRAINT fk_insumos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id),
    CONSTRAINT fk_insumos_unidad FOREIGN KEY (unidad_medida_id) REFERENCES unidades_medida (id),
    CONSTRAINT ck_insumos_stock CHECK (stock_actual >= 0),
    CONSTRAINT ck_insumos_stock_minimo CHECK (stock_minimo >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Insumos de consumo';

-- Códigos de fábrica (EAN, UPC, etc.). Un insumo puede tener varios: cambia la marca o
-- la presentación y sigue siendo el mismo insumo. cantidad_por_lectura permite que escanear
-- un bidón de 5 L sume 5 litros de una vez, para registrar entradas lo más rápido posible.
CREATE TABLE insumo_codigos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    insumo_id INT UNSIGNED NOT NULL,
    codigo VARCHAR(64) NOT NULL COMMENT 'Tal como lo lee el lector',
    cantidad_por_lectura DECIMAL(10,2) NOT NULL DEFAULT 1 COMMENT 'Cuánto suma cada lectura',
    descripcion VARCHAR(80) NULL COMMENT 'Presentación: Bidón 5 L, Caja 100 u.',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_insumo_codigos_codigo (codigo),
    KEY ix_insumo_codigos_insumo (insumo_id),
    CONSTRAINT fk_insumo_codigos_insumo FOREIGN KEY (insumo_id) REFERENCES insumos (id),
    CONSTRAINT ck_insumo_codigos_cantidad CHECK (cantidad_por_lectura > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Códigos de barra de fábrica de los insumos';
