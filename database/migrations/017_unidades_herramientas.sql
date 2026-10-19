-- =====================================================================
-- 017 · Unidades de herramientas y Práctica de Invierno como préstamo
-- 1. Cada herramienta anota cuántas unidades tiene la bodega. De ahí salen
--    las que están en bodega: unidades − prestadas − por resolver.
-- 2. El equipo de la Práctica de Invierno se presta y se devuelve: deja de
--    ser insumo de consumo y pasa a herramientas, con sus mismas categorías.
-- 3. Las categorías que el sistema necesita reconocer llevan una clave fija:
--    así no dependen del nombre, que se puede cambiar.
-- 4. Una herramienta prestada o pedida no se elimina: las que se eliminaron
--    así vuelven a estar activas.
-- =====================================================================

-- 1. Unidades. Las herramientas que ya existían quedan con al menos 1 (lo que hay prestado o por resolver, si es más):
-- el encargado anota las reales en «Editar herramienta».
ALTER TABLE herramientas
    ADD COLUMN unidades SMALLINT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Unidades que tiene la bodega: en bodega, prestadas o por resolver (dañadas o perdidas)' AFTER categoria_id;

UPDATE herramientas h
   SET h.unidades = GREATEST(1,
       (SELECT COALESCE(SUM(si.cantidad), 0)
          FROM solicitud_items si JOIN solicitudes s ON s.id = si.solicitud_id
         WHERE si.herramienta_id = h.id AND s.estado = 'prestada')
     + (SELECT COALESCE(SUM(IF(si.estado_recepcion = 'danada', si.cantidad, si.cantidad - si.cantidad_devuelta)), 0)
          FROM solicitud_items si
         WHERE si.herramienta_id = h.id AND si.resolucion IS NULL
           AND (si.estado_recepcion = 'danada' OR si.cantidad_devuelta < si.cantidad)));

-- 4. Las eliminadas que siguen prestadas, pedidas o por resolver vuelven al catálogo.
UPDATE herramientas h
   SET h.activo = 1
 WHERE h.activo = 0
   AND EXISTS (SELECT 1
                 FROM solicitud_items si JOIN solicitudes s ON s.id = si.solicitud_id
                WHERE si.herramienta_id = h.id
                  AND (s.estado IN ('pendiente', 'prestada')
                       OR (si.resolucion IS NULL AND (si.estado_recepcion = 'danada' OR si.cantidad_devuelta < si.cantidad))));

-- 3. Clave fija de las categorías.
ALTER TABLE categorias
    ADD COLUMN clave VARCHAR(40) NULL COMMENT 'Para que el sistema la reconozca aunque cambie el nombre: practica-invierno' AFTER nombre,
    ADD UNIQUE KEY uq_categorias_clave (clave);

-- 2. La Práctica de Invierno (seed 050): sus productos pasan a herramientas, con lo que había en stock como unidades.
SET @practica = (SELECT id FROM categorias WHERE ambito = 'insumo' AND padre_id IS NULL AND nombre = 'Práctica de Invierno');

INSERT INTO herramientas (nombre, categoria_id, unidades, creado_en)
SELECT i.nombre, i.categoria_id, i.stock_actual, i.creado_en
  FROM insumos i JOIN categorias c ON c.id = i.categoria_id
 WHERE i.activo = 1 AND (c.id = @practica OR c.padre_id = @practica);

-- Los productos sin historia (sin movimientos ni códigos de fábrica) se borran; los que tienen, se desactivan y salen
-- de la categoría, que ahora es de herramientas.
DELETE i
  FROM insumos i JOIN categorias c ON c.id = i.categoria_id
 WHERE (c.id = @practica OR c.padre_id = @practica)
   AND NOT EXISTS (SELECT 1 FROM movimiento_items mi WHERE mi.insumo_id = i.id)
   AND NOT EXISTS (SELECT 1 FROM insumo_codigos ic WHERE ic.insumo_id = i.id);

UPDATE insumos i JOIN categorias c ON c.id = i.categoria_id
   SET i.activo = 0, i.categoria_id = NULL
 WHERE c.id = @practica OR c.padre_id = @practica;

UPDATE categorias SET ambito = 'herramienta' WHERE id = @practica OR padre_id = @practica;

UPDATE categorias SET clave = 'practica-invierno' WHERE id = @practica;
