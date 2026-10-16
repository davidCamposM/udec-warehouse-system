-- =====================================================================
-- 016 · Reservas de abastecimiento
-- Las administrativas ingresan lo que llega a la bodega por reserva de SAP
-- («Se creó la Reserva 0000018857»). El número queda en la entrada, para
-- buscarla y saber si ya se ingresó. Reemplaza a `documento` (guía o
-- factura), que nunca se usó.
-- =====================================================================

ALTER TABLE movimientos
    CHANGE documento reserva VARCHAR(10) NULL COMMENT 'N° de reserva SAP, con sus 10 dígitos (0000018857), en las entradas de abastecimiento',
    ADD KEY ix_movimientos_reserva (reserva);
