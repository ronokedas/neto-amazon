-- Migration 009: Expandir vistorias para relatorio tecnico com exigencias
-- Fase 3 - passo 7a: relatorio tecnico + tabela dinamica de exigencias
-- Data: 2026-06-23

-- 1. Adicionar colunas agendamento_id, numero e observacoes_tecnicas na tabela vistorias
ALTER TABLE `vistorias`
  ADD COLUMN `numero` varchar(30) DEFAULT NULL AFTER `id`,
  ADD UNIQUE INDEX `numero` (`numero`),
  ADD COLUMN `agendamento_id` char(36) DEFAULT NULL AFTER `pessoa_id`,
  ADD COLUMN `observacoes_tecnicas` text DEFAULT NULL AFTER `resultado`,
  ADD INDEX `agendamento_id` (`agendamento_id`),
  ADD CONSTRAINT `vistorias_ibfk_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE SET NULL;

-- 2. Criar tabela de exigencias (itens inspecionados no relatorio tecnico)
CREATE TABLE IF NOT EXISTS `vistoria_exigencias` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `vistoria_id` char(36) NOT NULL,
  `ordem` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `item` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `conforme` enum('sim','nao','na') NOT NULL DEFAULT 'na',
  `observacao` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vistoria_id` (`vistoria_id`),
  KEY `ordem` (`ordem`),
  CONSTRAINT `vistoria_exigencias_ibfk_1` FOREIGN KEY (`vistoria_id`) REFERENCES `vistorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;