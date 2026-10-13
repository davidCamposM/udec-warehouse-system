-- =====================================================================
-- 010 · Se quita el pedido de materiales
-- =====================================================================

-- El sistema no arma pedidos de materiales (decisión del 09/10/2026): sus tablas se eliminan.
-- Estaban vacías. pedido_items va primero porque apunta a pedidos.
DROP TABLE IF EXISTS pedido_items;
DROP TABLE IF EXISTS pedidos;

-- El stock mínimo ya no alimenta ningún pedido: solo enciende el aviso «Por reponer».
ALTER TABLE insumos
    MODIFY stock_minimo INT NOT NULL DEFAULT 0 COMMENT 'Con esta cantidad o menos se avisa («Por reponer»). 0 = sin aviso';
