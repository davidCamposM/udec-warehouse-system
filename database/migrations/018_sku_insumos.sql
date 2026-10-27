-- =====================================================================
-- 018 · Código de barras de los productos
-- Cada producto lleva su código de barras principal: el del envase, que
-- asigna GS1 al fabricante (EAN-13, ej. 7806505012554), para buscarlo y
-- leerlo con la pistola. Los que ya existían quedan con ceros hasta que se
-- haga el inventario real y se anote el verdadero. Con ceros no se busca
-- ni se compara. Los demás códigos del mismo producto (otra marca, la caja)
-- siguen en insumo_codigos. La columna se llama sku por dentro.
-- =====================================================================

ALTER TABLE insumos
    ADD COLUMN sku VARCHAR(20) NOT NULL DEFAULT '0000000000000'
        COMMENT 'Código de barras principal del envase (EAN); ceros = todavía sin anotar' AFTER codigo_udec,
    ADD KEY ix_insumos_sku (sku);
