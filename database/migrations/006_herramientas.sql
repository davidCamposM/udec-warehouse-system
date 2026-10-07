-- =====================================================================
-- 006 · Herramientas
-- Tipos de herramientas e instrumentos de terreno, y cada unidad física
-- con su propio código. Se prestan y se devuelven siempre.
-- =====================================================================

-- Tipo o modelo (ej. Hipsómetro Suunto). Los docentes solicitan por tipo y cantidad.
CREATE TABLE herramientas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    categoria_id INT UNSIGNED NULL COMMENT 'Null = sin categoría',
    descripcion VARCHAR(500) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_herramientas_nombre (nombre),
    KEY ix_herramientas_categoria (categoria_id),
    CONSTRAINT fk_herramientas_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tipos de herramientas e instrumentos';

-- Cada unidad física, etiquetada una a una.
-- estado:
--   disponible     en bodega, se puede prestar
--   prestada       en un préstamo abierto
--   no_disponible  dañada o en revisión, hasta que el encargado cambie su estado
--   baja           ya no forma parte del inventario
CREATE TABLE herramienta_unidades (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    herramienta_id INT UNSIGNED NOT NULL,
    codigo VARCHAR(20) NOT NULL COMMENT 'Código interno de la etiqueta: HER00001',
    numero_serie VARCHAR(80) NULL COMMENT 'Número de serie de fábrica, si tiene',
    estado ENUM('disponible', 'prestada', 'no_disponible', 'baja') NOT NULL DEFAULT 'disponible',
    observacion VARCHAR(255) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_herramienta_unidades_codigo (codigo),
    KEY ix_herramienta_unidades_estado (herramienta_id, estado),
    CONSTRAINT fk_herramienta_unidades_herramienta FOREIGN KEY (herramienta_id) REFERENCES herramientas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Unidades físicas de cada herramienta';
