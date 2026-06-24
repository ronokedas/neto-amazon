-- Migration 007: Adicionar campos de desconto e embarcacao_id nas tabelas de propostas
-- Fase 2 - passo 5c: serviços por embarcação com desconto
-- Data: 2026-06-23

ALTER TABLE `propostas`
  ADD COLUMN `desconto_percentual` decimal(5,2) NOT NULL DEFAULT 0.00 AFTER `valor_total`,
  ADD COLUMN `desconto_valor` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `desconto_percentual`;

ALTER TABLE `propostas_servicos`
  ADD COLUMN `embarcacao_id` char(36) DEFAULT NULL AFTER `servico_id`,
  ADD INDEX `idx_propserv_emb` (`embarcacao_id`),
  ADD CONSTRAINT `propostas_servicos_ibfk_3` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE SET NULL;
