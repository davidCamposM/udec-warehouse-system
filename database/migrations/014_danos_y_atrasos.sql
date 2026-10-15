-- =====================================================================
-- 014 · Daños y atrasos de las herramientas prestadas
-- =====================================================================

-- Atrasos: el encargado puede extender el plazo de un préstamo. plazo_original guarda la
-- fecha de recepción que se pidió, para mostrar que se extendió (null si nunca se extendió).
ALTER TABLE solicitudes
    ADD COLUMN plazo_original DATE NULL COMMENT 'Fecha de recepción antes de extender el plazo' AFTER fecha_devolucion;

-- Daños: lo que pasó con cada línea va en la línea misma (qué le pasó y cómo se resolvió),
-- así la nota general de la recepción sobra. Una línea tiene un problema si volvió con daños
-- o faltaron unidades, y queda pendiente hasta que el encargado lo resuelve:
--   reparada  se arregló y vuelve a prestarse
--   repuesta  quien la tenía la repuso
--   baja      ya no se puede usar (se descarta)
ALTER TABLE solicitudes DROP COLUMN observacion_recepcion;

ALTER TABLE solicitud_items
    ADD COLUMN detalle_recepcion VARCHAR(255) NULL COMMENT 'Qué le pasó, si volvió con daños o faltaron unidades',
    ADD COLUMN resolucion ENUM('reparada', 'repuesta', 'baja') NULL COMMENT 'Null mientras el problema esté pendiente',
    ADD COLUMN resuelta_por INT UNSIGNED NULL COMMENT 'Usuario que resolvió el problema',
    ADD COLUMN resuelta_en DATETIME NULL,
    ADD CONSTRAINT fk_solicitud_items_resuelta FOREIGN KEY (resuelta_por) REFERENCES usuarios (id);
