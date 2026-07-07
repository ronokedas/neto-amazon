-- Permite registrar entrada na proposta comercial

ALTER TABLE propostas
  ADD COLUMN valor_entrada DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER valor_total;
