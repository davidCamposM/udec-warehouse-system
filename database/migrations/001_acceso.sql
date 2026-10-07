-- =====================================================================
-- 001 · Acceso
-- Usuarios con cuenta, registro de intentos de inicio de sesión y
-- equipos habilitados como estación de retiro.
-- =====================================================================

-- Usuarios con cuenta: inician sesión con correo y contraseña.
-- El perfil corresponde a uno de los definidos en config/permisos.php.
CREATE TABLE usuarios (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL COMMENT 'Único, sin distinguir mayúsculas (por la collation)',
    password_hash VARCHAR(255) NOT NULL COMMENT 'Resultado de password_hash() de PHP, nunca la contraseña',
    perfil VARCHAR(30) NOT NULL COMMENT 'administrador, administrativa o docente',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_acceso DATETIME NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Usuarios con cuenta en el sistema';

-- Cada intento de inicio de sesión, exitoso o no. Sirve para bloquear temporalmente
-- tras varios fallos y como registro de accesos. Se purga pasado el plazo de retención.
CREATE TABLE intentos_login (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(190) NOT NULL COMMENT 'Correo escrito, exista o no la cuenta',
    ip VARCHAR(45) NOT NULL COMMENT 'IPv4 o IPv6',
    exitoso TINYINT(1) NOT NULL,
    usuario_id INT UNSIGNED NULL COMMENT 'Solo si el correo corresponde a una cuenta',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_intentos_login_email (email, creado_en),
    KEY ix_intentos_login_ip (ip, creado_en),
    KEY ix_intentos_login_fecha (creado_en),
    CONSTRAINT fk_intentos_login_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Intentos de inicio de sesión';

-- Equipos habilitados como estación de retiro. El encargado habilita el navegador del PC
-- de la bodega y este guarda un token. Sin ese token la estación no funciona, así nadie
-- puede retirar a nombre de otra persona desde otro computador.
CREATE TABLE estaciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80) NOT NULL COMMENT 'Ej. PC bodega principal',
    token_hash CHAR(64) NOT NULL COMMENT 'SHA-256 del token guardado en el equipo',
    activa TINYINT(1) NOT NULL DEFAULT 1,
    autorizada_por INT UNSIGNED NOT NULL COMMENT 'Usuario que habilitó el equipo',
    ultimo_uso DATETIME NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_estaciones_token (token_hash),
    CONSTRAINT fk_estaciones_usuario FOREIGN KEY (autorizada_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Equipos habilitados como estación de retiro';
