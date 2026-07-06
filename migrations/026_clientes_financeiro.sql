-- Migration 026: Adicionar campos financeiros em clientes
-- Data: 2026-07-02

ALTER TABLE `clientes`
  ADD COLUMN `tipo_recebimento` ENUM('pix', 'cc') NULL AFTER `status`,
  ADD COLUMN `chave_pix` VARCHAR(255) NULL AFTER `tipo_recebimento`,
  ADD COLUMN `banco` VARCHAR(100) NULL AFTER `chave_pix`,
  ADD COLUMN `agencia` VARCHAR(20) NULL AFTER `banco`,
  ADD COLUMN `conta` VARCHAR(20) NULL AFTER `agencia`;
