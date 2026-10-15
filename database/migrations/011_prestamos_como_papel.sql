-- =====================================================================
-- 011 · Préstamos de herramientas, igual al formulario en papel
-- =====================================================================

-- El formulario «Solicitud de instrumentos y herramientas» es a la vez la solicitud, el
-- préstamo y la devolución: departamento, nombre del funcionario (el docente con su cuenta),
-- tipo de actividad (texto libre), a quién autoriza el retiro (un nombre escrito), fecha de
-- retiro, fecha de recepción y el detalle (herramienta, unidades y estado en la recepción).
-- Mientras no estén etiquetadas las unidades de cada herramienta, se presta por tipo y
-- cantidad, sobre la misma solicitud (decisión del 14/10/2026). Ya no hay personas
-- registradas para elegir, así que quien retira se escribe. Las tablas están vacías.
--
-- Estados de una solicitud:
--   pendiente  enviada por el docente, espera que el encargado entregue
--   rechazada  el encargado no la entregará (con motivo_rechazo)
--   cancelada  el docente la retiró antes de la entrega
--   prestada   el encargado entregó las herramientas (entregada_en)
--   devuelta   volvieron a la bodega (recibida_en), con el estado de cada línea
ALTER TABLE solicitudes DROP FOREIGN KEY fk_solicitudes_persona;

ALTER TABLE solicitudes
    DROP COLUMN persona_retiro_id,
    DROP COLUMN actividad,
    DROP COLUMN lugar,
    MODIFY departamento VARCHAR(120) NOT NULL COMMENT 'Silvicultura o Manejo Bosque y MA',
    MODIFY tipo_actividad VARCHAR(255) NOT NULL COMMENT 'Lo que se hará, en palabras del docente',
    ADD COLUMN autoriza_retiro VARCHAR(150) NULL COMMENT 'Quién retira, si no es el docente' AFTER tipo_actividad,
    MODIFY fecha_devolucion DATE NOT NULL COMMENT 'Fecha de recepción: cuándo vuelven a la bodega',
    MODIFY estado ENUM('pendiente', 'rechazada', 'cancelada', 'prestada', 'devuelta') NOT NULL DEFAULT 'pendiente',
    MODIFY revisada_por INT UNSIGNED NULL COMMENT 'Usuario que la rechazó',
    MODIFY revisada_en DATETIME NULL COMMENT 'Cuándo se rechazó',
    ADD COLUMN entregada_por INT UNSIGNED NULL COMMENT 'Usuario que entregó las herramientas' AFTER revisada_en,
    ADD COLUMN entregada_en DATETIME NULL AFTER entregada_por,
    ADD COLUMN recibida_por INT UNSIGNED NULL COMMENT 'Usuario que recibió la devolución' AFTER entregada_en,
    ADD COLUMN recibida_en DATETIME NULL AFTER recibida_por,
    MODIFY observacion VARCHAR(500) NULL COMMENT 'Lo que se necesita y no está en la lista',
    ADD COLUMN observacion_recepcion VARCHAR(500) NULL COMMENT 'Nota del encargado al recibir' AFTER observacion,
    ADD CONSTRAINT fk_solicitudes_entrega FOREIGN KEY (entregada_por) REFERENCES usuarios (id),
    ADD CONSTRAINT fk_solicitudes_recepcion FOREIGN KEY (recibida_por) REFERENCES usuarios (id);

-- «Estado en la recepción» de cada línea: cuántas volvieron y en qué estado.
ALTER TABLE solicitud_items
    ADD COLUMN cantidad_devuelta SMALLINT UNSIGNED NULL COMMENT 'Null hasta la devolución',
    ADD COLUMN estado_recepcion ENUM('bien', 'danada') NULL COMMENT 'Null hasta la devolución';
