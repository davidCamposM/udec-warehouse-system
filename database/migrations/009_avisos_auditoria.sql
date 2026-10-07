-- =====================================================================
-- 009 · Avisos internos y auditoría
-- =====================================================================

-- Avisos dentro del sistema (no por correo). Se crea uno por destinatario.
-- tipo: solicitud_nueva, solicitud_aprobada, solicitud_rechazada, stock_minimo,
--       prestamo_atrasado
-- clave evita repetir el mismo aviso al mismo usuario (ej. tipo + id del insumo + fecha).
CREATE TABLE avisos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NOT NULL COMMENT 'Destinatario',
    tipo VARCHAR(40) NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    mensaje VARCHAR(500) NULL,
    url VARCHAR(255) NULL COMMENT 'Ruta interna a la que lleva el aviso',
    clave VARCHAR(120) NULL,
    leido_en DATETIME NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_avisos_clave (usuario_id, clave),
    KEY ix_avisos_pendientes (usuario_id, leido_en),
    CONSTRAINT fk_avisos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Avisos internos para los usuarios';

-- Cambios que no son movimientos de stock: catálogo, usuarios, personas, estados.
-- detalle guarda los valores antes y después del cambio.
CREATE TABLE auditoria (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NULL COMMENT 'Quién hizo el cambio',
    accion VARCHAR(40) NOT NULL COMMENT 'crear, editar, activar, desactivar, cambiar_estado...',
    entidad VARCHAR(40) NOT NULL COMMENT 'insumo, usuario, persona, herramienta_unidad...',
    entidad_id INT UNSIGNED NULL,
    detalle JSON NULL,
    ip VARCHAR(45) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_auditoria_entidad (entidad, entidad_id),
    KEY ix_auditoria_usuario (usuario_id, creado_en),
    KEY ix_auditoria_fecha (creado_en),
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Registro de cambios administrativos';
