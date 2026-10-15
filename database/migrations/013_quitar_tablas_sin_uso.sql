-- =====================================================================
-- 013 · Se quitan las tablas y columnas que ya no usa el sistema
-- =====================================================================

-- Decisiones del 14 y 15/10/2026: ya no hay punto de retiro ni personas autorizadas (todo lo
-- hace la cuenta del encargado), las herramientas se prestan por tipo y cantidad sobre la
-- misma solicitud (no por unidad etiquetada) y no hay conteo de inventario. Quedan solo los
-- datos que el sistema guarda de verdad. Se borran primero las tablas que apuntan a otras.

-- Préstamo por unidad (reemplazado por solicitudes y solicitud_items)
DROP TABLE IF EXISTS herramienta_danos;
DROP TABLE IF EXISTS unidad_estados;
DROP TABLE IF EXISTS prestamo_extensiones;
DROP TABLE IF EXISTS prestamo_items;
DROP TABLE IF EXISTS prestamos;
DROP TABLE IF EXISTS herramienta_unidades;

-- Conteo de inventario
ALTER TABLE movimientos DROP FOREIGN KEY fk_movimientos_conteo;
ALTER TABLE movimientos DROP COLUMN conteo_id;
DROP TABLE IF EXISTS conteo_items;
DROP TABLE IF EXISTS conteos;

-- Punto de retiro: cada movimiento lo registra una cuenta (usuario_id)
ALTER TABLE movimientos
    DROP FOREIGN KEY fk_movimientos_persona,
    DROP FOREIGN KEY fk_movimientos_estacion,
    DROP CHECK ck_movimientos_responsable;

ALTER TABLE movimientos
    DROP INDEX ix_movimientos_persona_fecha,
    DROP COLUMN persona_id,
    DROP COLUMN estacion_id;

DROP TABLE IF EXISTS estaciones;
DROP TABLE IF EXISTS personas;
