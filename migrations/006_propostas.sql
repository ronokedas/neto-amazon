-- Migration 006: Criar tabelas propostas, propostas_embarcacoes e propostas_servicos
-- Fase 2 - passo 5a: módulo comercial/propostas
-- Data: 2026-06-23

CREATE TABLE IF NOT EXISTS `propostas` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `numero` varchar(30) NOT NULL,
  `cliente_id` char(36) NOT NULL,
  `data_emissao` date NOT NULL,
  `data_validade` date DEFAULT NULL,
  `parcelas` tinyint(3) UNSIGNED NOT NULL DEFAULT 3,
  `forma_pagamento` enum('a_vista','parcelado','boleto','pix') NOT NULL DEFAULT 'parcelado',
  `valor_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `observacoes` text DEFAULT NULL,
  `status` enum('rascunho','enviada','aprovada','recusada','cancelada') NOT NULL DEFAULT 'rascunho',
  `criado_por` char(36) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero` (`numero`),
  KEY `cliente_id` (`cliente_id`),
  KEY `status` (`status`),
  KEY `criado_por` (`criado_por`),
  CONSTRAINT `propostas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `propostas_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `propostas_embarcacoes` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `proposta_id` char(36) NOT NULL,
  `embarcacao_id` char(36) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proposta_embarcacao` (`proposta_id`, `embarcacao_id`),
  KEY `embarcacao_id` (`embarcacao_id`),
  CONSTRAINT `propostas_embarcacoes_ibfk_1` FOREIGN KEY (`proposta_id`) REFERENCES `propostas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `propostas_embarcacoes_ibfk_2` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `propostas_servicos` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `proposta_id` char(36) NOT NULL,
  `servico_id` char(36) NOT NULL,
  `preco_aplicado` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quantidade` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `subtotal` decimal(12,2) GENERATED ALWAYS AS (`preco_aplicado` * `quantidade`) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proposta_servico` (`proposta_id`, `servico_id`),
  KEY `servico_id` (`servico_id`),
  CONSTRAINT `propostas_servicos_ibfk_1` FOREIGN KEY (`proposta_id`) REFERENCES `propostas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `propostas_servicos_ibfk_2` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;