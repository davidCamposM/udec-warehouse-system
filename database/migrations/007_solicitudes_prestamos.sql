-- =====================================================================
-- 007 · Solicitudes y préstamos de herramientas
-- Solicitud web del docente, préstamo con fecha pactada, devolución
-- total o parcial, extensiones de plazo, daños e historial por unidad.
-- =====================================================================

-- Mismos datos que el formulario en papel "Solicitud de instrumentos y herramientas".
-- estado:
--   pendiente  enviada por el docente, espera revisión
--   aprobada   el encargado la aprobó (la disponibilidad se verifica al aprobar)
--   rechazada  con motivo_rechazo
--   cancelada  el docente la retiró antes de usarla
--   retirada   ya se registró el préstamo
CREATE TABLE solicitudes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    docente_id INT UNSIGNED NOT NULL COMMENT 'Usuario docente que solicita',
    departamento VARCHAR(120) NOT NULL,
    tipo_actividad VARCHAR(60) NOT NULL COMMENT 'Docencia, investigación, extensión u otra',
    actividad VARCHAR(255) NULL COMMENT 'Asignatura o proyecto',
    lugar VARCHAR(150) NULL COMMENT 'Lugar de la salida a terreno',
    persona_retiro_id INT UNSIGNED NULL COMMENT 'Persona autorizada para retirar, si no es el docente',
    fecha_retiro DATE NOT NULL,
    fecha_devolucion DATE NOT NULL,
    estado ENUM('pendiente', 'aprobada', 'rechazada', 'cancelada', 'retirada') NOT NULL DEFAULT 'pendiente',
    motivo_rechazo VARCHAR(255) NULL,
    revisada_por INT UNSIGNED NULL COMMENT 'Usuario que aprobó o rechazó',
    revisada_en DATETIME NULL,
    observacion VARCHAR(500) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_solicitudes_estado_fecha (estado, fecha_retiro),
    KEY ix_solicitudes_docente (docente_id),
    CONSTRAINT fk_solicitudes_docente FOREIGN KEY (docente_id) REFERENCES usuarios (id),
    CONSTRAINT fk_solicitudes_persona FOREIGN KEY (persona_retiro_id) REFERENCES personas (id),
    CONSTRAINT fk_solicitudes_revisor FOREIGN KEY (revisada_por) REFERENCES usuarios (id),
    CONSTRAINT ck_solicitudes_fechas CHECK (fecha_devolucion >= fecha_retiro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Solicitudes web de préstamo de herramientas';

-- Se solicita por tipo de herramienta y cantidad. Las unidades concretas se asignan al retirar.
CREATE TABLE solicitud_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    solicitud_id INT UNSIGNED NOT NULL,
    herramienta_id INT UNSIGNED NOT NULL,
    cantidad SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_solicitud_items (solicitud_id, herramienta_id),
    KEY ix_solicitud_items_herramienta (herramienta_id),
    CONSTRAINT fk_solicitud_items_solicitud FOREIGN KEY (solicitud_id) REFERENCES solicitudes (id),
    CONSTRAINT fk_solicitud_items_herramienta FOREIGN KEY (herramienta_id) REFERENCES herramientas (id),
    CONSTRAINT ck_solicitud_items_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Herramientas y cantidades de cada solicitud';

-- Préstamo: nace en la estación (retiro presencial) o al retirar una solicitud aprobada.
-- Abierto mientras cerrado_en sea null. Atrasado si está abierto y fecha_pactada ya pasó
-- (se calcula al consultar, no se guarda).
CREATE TABLE prestamos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    solicitud_id INT UNSIGNED NULL COMMENT 'Null si fue un retiro presencial sin solicitud',
    persona_id INT UNSIGNED NOT NULL COMMENT 'Quien retiró físicamente',
    responsable_id INT UNSIGNED NULL COMMENT 'Docente responsable (usuario), si corresponde',
    registrado_por INT UNSIGNED NULL COMMENT 'Usuario que lo registró. Null si fue en la estación',
    estacion_id INT UNSIGNED NULL,
    fecha_retiro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_pactada DATE NOT NULL COMMENT 'Fecha de devolución acordada',
    cerrado_en DATETIME NULL COMMENT 'Cuando se devolvió la última unidad',
    observacion VARCHAR(500) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_prestamos_abiertos (cerrado_en, fecha_pactada),
    KEY ix_prestamos_persona (persona_id),
    KEY ix_prestamos_responsable (responsable_id),
    KEY ix_prestamos_solicitud (solicitud_id),
    CONSTRAINT fk_prestamos_solicitud FOREIGN KEY (solicitud_id) REFERENCES solicitudes (id),
    CONSTRAINT fk_prestamos_persona FOREIGN KEY (persona_id) REFERENCES personas (id),
    CONSTRAINT fk_prestamos_responsable FOREIGN KEY (responsable_id) REFERENCES usuarios (id),
    CONSTRAINT fk_prestamos_registrado FOREIGN KEY (registrado_por) REFERENCES usuarios (id),
    CONSTRAINT fk_prestamos_estacion FOREIGN KEY (estacion_id) REFERENCES estaciones (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Préstamos de herramientas';

-- Cada unidad prestada. La devolución se registra unidad por unidad, lo que permite
-- devoluciones parciales: el préstamo sigue abierto con lo pendiente.
CREATE TABLE prestamo_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    prestamo_id INT UNSIGNED NOT NULL,
    unidad_id INT UNSIGNED NOT NULL,
    devuelto_en DATETIME NULL,
    recibido_por_usuario_id INT UNSIGNED NULL COMMENT 'Encargado que recibió',
    recibido_por_persona_id INT UNSIGNED NULL COMMENT 'Auxiliar que recibió',
    estado_devolucion ENUM('bueno', 'danado') NULL,
    observacion VARCHAR(255) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_prestamo_items (prestamo_id, unidad_id),
    KEY ix_prestamo_items_unidad (unidad_id, devuelto_en),
    CONSTRAINT fk_prestamo_items_prestamo FOREIGN KEY (prestamo_id) REFERENCES prestamos (id),
    CONSTRAINT fk_prestamo_items_unidad FOREIGN KEY (unidad_id) REFERENCES herramienta_unidades (id),
    CONSTRAINT fk_prestamo_items_usuario FOREIGN KEY (recibido_por_usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_prestamo_items_persona FOREIGN KEY (recibido_por_persona_id) REFERENCES personas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Unidades de cada préstamo y su devolución';

-- Salidas a terreno que se extienden: cada cambio de fecha pactada queda registrado.
CREATE TABLE prestamo_extensiones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    prestamo_id INT UNSIGNED NOT NULL,
    fecha_anterior DATE NOT NULL,
    fecha_nueva DATE NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    usuario_id INT UNSIGNED NOT NULL COMMENT 'Usuario que autorizó la extensión',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_prestamo_extensiones_prestamo (prestamo_id),
    CONSTRAINT fk_prestamo_extensiones_prestamo FOREIGN KEY (prestamo_id) REFERENCES prestamos (id),
    CONSTRAINT fk_prestamo_extensiones_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT ck_prestamo_extensiones_fechas CHECK (fecha_nueva > fecha_anterior)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Extensiones de la fecha pactada de un préstamo';

-- Historial de estados de cada unidad (préstamo, devolución, daño, reparación, baja).
CREATE TABLE unidad_estados (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    unidad_id INT UNSIGNED NOT NULL,
    estado_anterior ENUM('disponible', 'prestada', 'no_disponible', 'baja') NULL COMMENT 'Null al dar de alta',
    estado_nuevo ENUM('disponible', 'prestada', 'no_disponible', 'baja') NOT NULL,
    motivo VARCHAR(255) NULL,
    usuario_id INT UNSIGNED NULL,
    persona_id INT UNSIGNED NULL,
    prestamo_id INT UNSIGNED NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_unidad_estados_unidad (unidad_id, creado_en),
    CONSTRAINT fk_unidad_estados_unidad FOREIGN KEY (unidad_id) REFERENCES herramienta_unidades (id),
    CONSTRAINT fk_unidad_estados_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_unidad_estados_persona FOREIGN KEY (persona_id) REFERENCES personas (id),
    CONSTRAINT fk_unidad_estados_prestamo FOREIGN KEY (prestamo_id) REFERENCES prestamos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historial de estados de cada unidad de herramienta';

-- Daños informados, al devolver o después (daños detectados tras la recepción).
CREATE TABLE herramienta_danos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    unidad_id INT UNSIGNED NOT NULL,
    prestamo_id INT UNSIGNED NULL COMMENT 'Préstamo en que ocurrió, si se sabe',
    descripcion VARCHAR(500) NOT NULL,
    informado_por_usuario_id INT UNSIGNED NULL COMMENT 'Encargado o docente con cuenta',
    informado_por_persona_id INT UNSIGNED NULL COMMENT 'Auxiliar o persona en la estación',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_herramienta_danos_unidad (unidad_id, creado_en),
    KEY ix_herramienta_danos_prestamo (prestamo_id),
    CONSTRAINT fk_herramienta_danos_unidad FOREIGN KEY (unidad_id) REFERENCES herramienta_unidades (id),
    CONSTRAINT fk_herramienta_danos_prestamo FOREIGN KEY (prestamo_id) REFERENCES prestamos (id),
    CONSTRAINT fk_herramienta_danos_usuario FOREIGN KEY (informado_por_usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_herramienta_danos_persona FOREIGN KEY (informado_por_persona_id) REFERENCES personas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Daños registrados por unidad de herramienta';
