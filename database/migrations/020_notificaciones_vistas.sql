-- La campana del inicio: cuándo vio cada persona sus notificaciones por última vez. El número de la campana cuenta solo
-- lo que llegó después (como un chat): las solicitudes nuevas de los docentes, para el encargado y el operador; lo que
-- la bodega hizo con sus solicitudes, para el docente. NULL: nunca las ha visto (cuenta todo).
ALTER TABLE usuarios
    ADD COLUMN notificaciones_vistas_en DATETIME NULL DEFAULT NULL
        COMMENT 'Última vez que vio las notificaciones de la campana del inicio';
