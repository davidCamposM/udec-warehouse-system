-- =====================================================================
-- 008 · Pedidos de materiales y conteos de inventario
-- =====================================================================

-- Mismos datos que el formulario "Pedido de materiales": insumos, cantidad disponible
-- al generar el pedido y necesidad de reposición.
CREATE TABLE pedidos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    numero VARCHAR(20) NOT NULL COMMENT 'Año y correlativo, como los formularios: 2026-1',
    estado ENUM('borrador', 'enviado', 'recibido') NOT NULL DEFAULT 'borrador',
    creado_por INT UNSIGNED NOT NULL,
    enviado_en DATETIME NULL,
    observacion VARCHAR(500) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pedidos_numero (numero),
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (creado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pedidos de materiales';

CREATE TABLE pedido_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id INT UNSIGNED NOT NULL,
    insumo_id INT UNSIGNED NOT NULL,
    stock_al_generar DECIMAL(10,2) NOT NULL COMMENT 'Cantidad disponible al armar el pedido',
    cantidad_solicitada DECIMAL(10,2) NOT NULL COMMENT 'Necesidad de reposición',
    PRIMARY KEY (id),
    UNIQUE KEY uq_pedido_items (pedido_id, insumo_id),
    KEY ix_pedido_items_insumo (insumo_id),
    CONSTRAINT fk_pedido_items_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos (id),
    CONSTRAINT fk_pedido_items_insumo FOREIGN KEY (insumo_id) REFERENCES insumos (id),
    CONSTRAINT ck_pedido_items_cantidad CHECK (cantidad_solicitada > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Insumos de cada pedido de materiales';

-- Modo inventario: se escanea un estante y se compara lo contado con el sistema.
-- Al aplicar el conteo se genera un movimiento de ajuste por las diferencias.
CREATE TABLE conteos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ubicacion VARCHAR(60) NULL COMMENT 'Estante o sector contado. Null = bodega completa',
    estado ENUM('abierto', 'aplicado', 'descartado') NOT NULL DEFAULT 'abierto',
    usuario_id INT UNSIGNED NULL,
    persona_id INT UNSIGNED NULL COMMENT 'Auxiliar que contó',
    observacion VARCHAR(500) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cerrado_en DATETIME NULL,
    PRIMARY KEY (id),
    KEY ix_conteos_estado (estado, creado_en),
    CONSTRAINT fk_conteos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_conteos_persona FOREIGN KEY (persona_id) REFERENCES personas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Conteos de inventario';

CREATE TABLE conteo_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    conteo_id INT UNSIGNED NOT NULL,
    insumo_id INT UNSIGNED NOT NULL,
    cantidad_sistema DECIMAL(10,2) NOT NULL COMMENT 'Stock del sistema al contar',
    cantidad_contada DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_conteo_items (conteo_id, insumo_id),
    KEY ix_conteo_items_insumo (insumo_id),
    CONSTRAINT fk_conteo_items_conteo FOREIGN KEY (conteo_id) REFERENCES conteos (id),
    CONSTRAINT fk_conteo_items_insumo FOREIGN KEY (insumo_id) REFERENCES insumos (id),
    CONSTRAINT ck_conteo_items_contada CHECK (cantidad_contada >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Insumos contados en cada conteo';

-- El ajuste que genera un conteo queda ligado a él (la tabla conteos se crea recién aquí).
ALTER TABLE movimientos
    ADD CONSTRAINT fk_movimientos_conteo FOREIGN KEY (conteo_id) REFERENCES conteos (id);
