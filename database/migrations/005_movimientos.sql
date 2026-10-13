-- =====================================================================
-- 005 · Movimientos de insumos
-- Toda entrada, salida o ajuste de stock. Los movimientos no se editan
-- ni se borran: un error se corrige con un movimiento inverso (corrige_id).
-- =====================================================================

-- Encabezado: una operación completa (por ejemplo, un retiro con varios productos).
-- Siempre se sabe quién la hizo: una cuenta (usuario_id), una persona identificada en la
-- estación (persona_id), o ambas.
CREATE TABLE movimientos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo ENUM('entrada', 'salida', 'ajuste') NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT UNSIGNED NULL COMMENT 'Cuenta que registró la operación',
    persona_id INT UNSIGNED NULL COMMENT 'Persona identificada en la estación',
    estacion_id INT UNSIGNED NULL COMMENT 'Equipo donde se registró, si fue en la estación',
    documento VARCHAR(120) NULL COMMENT 'Opcional en entradas: guía, factura o proveedor',
    motivo VARCHAR(255) NULL COMMENT 'Obligatorio en ajustes y correcciones',
    corrige_id INT UNSIGNED NULL COMMENT 'Movimiento que esta operación corrige',
    conteo_id INT UNSIGNED NULL COMMENT 'Conteo de inventario que originó el ajuste',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_movimientos_fecha (fecha),
    KEY ix_movimientos_tipo_fecha (tipo, fecha),
    KEY ix_movimientos_persona_fecha (persona_id, fecha),
    KEY ix_movimientos_usuario_fecha (usuario_id, fecha),
    CONSTRAINT fk_movimientos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_movimientos_persona FOREIGN KEY (persona_id) REFERENCES personas (id),
    CONSTRAINT fk_movimientos_estacion FOREIGN KEY (estacion_id) REFERENCES estaciones (id),
    CONSTRAINT fk_movimientos_corrige FOREIGN KEY (corrige_id) REFERENCES movimientos (id),
    CONSTRAINT ck_movimientos_responsable CHECK (usuario_id IS NOT NULL OR persona_id IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Operaciones de stock de insumos (encabezado)';

-- Detalle: un ítem por insumo. La cantidad lleva signo: positiva suma y negativa resta.
-- stock_resultante deja el saldo después de cada ítem, como en una tarjeta de existencias.
CREATE TABLE movimiento_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    movimiento_id INT UNSIGNED NOT NULL,
    insumo_id INT UNSIGNED NOT NULL,
    cantidad INT NOT NULL,
    stock_resultante INT NOT NULL,
    sector_id INT UNSIGNED NULL COMMENT 'Sector donde se guardó (en las entradas)',
    PRIMARY KEY (id),
    UNIQUE KEY uq_movimiento_items (movimiento_id, insumo_id),
    KEY ix_movimiento_items_insumo (insumo_id, movimiento_id),
    CONSTRAINT fk_movimiento_items_movimiento FOREIGN KEY (movimiento_id) REFERENCES movimientos (id),
    CONSTRAINT fk_movimiento_items_insumo FOREIGN KEY (insumo_id) REFERENCES insumos (id),
    CONSTRAINT fk_movimiento_items_sector FOREIGN KEY (sector_id) REFERENCES sectores (id),
    CONSTRAINT ck_movimiento_items_cantidad CHECK (cantidad <> 0),
    CONSTRAINT ck_movimiento_items_stock CHECK (stock_resultante >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ítems de cada operación de stock';
