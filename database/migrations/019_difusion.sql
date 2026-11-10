-- Catálogo independiente de Difusión. NULL significa que nadie ha verificado la existencia actual.
-- No se mezcla con insumos: su stock_actual numérico alimenta alertas y gráficos de bodega.
CREATE TABLE difusion_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(20) NOT NULL,
    nombre VARCHAR(160) NOT NULL,
    detalle VARCHAR(255) NULL,
    grupo VARCHAR(60) NOT NULL,
    tipo ENUM('articulo', 'servicio') NOT NULL DEFAULT 'articulo',
    cantidad_actual INT UNSIGNED NULL DEFAULT NULL,
    orden SMALLINT UNSIGNED NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_difusion_codigo (codigo),
    KEY ix_difusion_orden (orden),
    CONSTRAINT ck_difusion_cantidad CHECK (cantidad_actual IS NULL OR cantidad_actual >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Artículos y servicios de Difusión; existencia actual desconocida hasta el conteo';
