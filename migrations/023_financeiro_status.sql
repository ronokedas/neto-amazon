-- Migration 023: Financeiro Status e Vencimento
ALTER TABLE `financeiro_lancamentos`
ADD COLUMN `status` ENUM('PENDENTE', 'PAGO', 'CANCELADO') NOT NULL DEFAULT 'PAGO' AFTER `valor`,
ADD COLUMN `data_vencimento` DATE NULL AFTER `status`;

UPDATE `financeiro_lancamentos` SET `data_vencimento` = `data`, `status` = 'PAGO';