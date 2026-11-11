-- Un administrador que además hace clases: ve «Solicitar herramientas» y «Mis solicitudes» como un docente. Se marca
-- solo desde la base (phpMyAdmin): el sistema no tiene cómo cambiarlo, igual que las cuentas de administrador.
ALTER TABLE usuarios
    ADD COLUMN tambien_docente TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Administrador que también solicita herramientas como docente'
        AFTER perfil;
