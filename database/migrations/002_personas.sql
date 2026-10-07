-- =====================================================================
-- 002 · Personas
-- Quienes retiran (o registran movimientos) en la estación de retiro
-- identificándose con su credencial o su RUT, sin cuenta en el sistema.
-- =====================================================================

-- tipo:
--   auxiliar   equipo del encargado. Registra entradas y salidas, y retira.
--   autorizada persona autorizada por el encargado. Solo retira.
--   docente    docente que retira presencialmente. Si tiene cuenta, se liga en usuario_id.
CREATE TABLE personas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    rut VARCHAR(12) NULL COMMENT 'Normalizado: 12345678-K. Null si no tiene RUT',
    tipo ENUM('auxiliar', 'autorizada', 'docente') NOT NULL,
    codigo_credencial VARCHAR(20) NOT NULL COMMENT 'Código impreso en la credencial: PER00001',
    usuario_id INT UNSIGNED NULL COMMENT 'Cuenta del docente, si tiene',
    departamento VARCHAR(120) NULL,
    email VARCHAR(190) NULL,
    telefono VARCHAR(30) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Inactiva: no puede identificarse en la estación',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_personas_rut (rut),
    UNIQUE KEY uq_personas_credencial (codigo_credencial),
    UNIQUE KEY uq_personas_usuario (usuario_id),
    KEY ix_personas_nombre (nombre),
    CONSTRAINT fk_personas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Personas que se identifican con credencial en la estación de retiro';
