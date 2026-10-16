-- =====================================================================
-- 015 · Cuentas por correo
-- El encargado carga la lista de personas (nombre, correo UdeC, perfil) y
-- cada una crea su propia contraseña con un enlace que le llega al correo.
-- El mismo enlace sirve para recuperar una contraseña olvidada.
-- =====================================================================

-- Una cuenta recién cargada todavía no tiene contraseña: la crea la persona con el enlace.
ALTER TABLE usuarios
    MODIFY password_hash VARCHAR(255) NULL COMMENT 'Resultado de password_hash() de PHP; null: la persona aún no crea su contraseña';

-- Enlaces para crear la contraseña. Se guarda el hash del enlace, nunca el enlace:
-- quien lea la base no puede usarlos. Sirven una sola vez y vencen.
CREATE TABLE usuario_enlaces (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL COMMENT 'SHA-256 del código del enlace',
    expira_en DATETIME NOT NULL,
    usado_en DATETIME NULL COMMENT 'Cuándo se creó la contraseña con él; null: sin usar',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuario_enlaces_token (token_hash),
    KEY ix_usuario_enlaces_usuario (usuario_id, creado_en),
    CONSTRAINT fk_usuario_enlaces_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Enlaces para crear o recuperar la contraseña';
