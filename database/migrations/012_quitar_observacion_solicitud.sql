-- =====================================================================
-- 012 · Se quita «¿Necesitas algo más?» de la solicitud de herramientas
-- =====================================================================

-- El formulario ya no pregunta por lo que falta en la lista (decisión del 15/10/2026): la
-- columna queda sin uso y se elimina. La nota de la bodega al recibir (observacion_recepcion)
-- se mantiene.
ALTER TABLE solicitudes DROP COLUMN observacion;
