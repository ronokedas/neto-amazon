-- Migration 003: Criar tabelas clientes e clientes_embarcacoes
-- Fase 1 - passo 3

CREATE TABLE IF NOT EXISTS `clientes` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `nome` varchar(200) NOT NULL,
  `tipo_pessoa` enum('PF','PJ') NOT NULL DEFAULT 'PF',
  `cpf_cnpj` varchar(18) DEFAULT NULL,
  `perfil` enum('armador','proprietario','despachante') NOT NULL DEFAULT 'proprietario',
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `status` enum('ATIVO','INATIVO') NOT NULL DEFAULT 'ATIVO',
  `criado_por` char(36) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpf_cnpj` (`cpf_cnpj`),
  KEY `criado_por` (`criado_por`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `clientes_embarcacoes` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `cliente_id` char(36) NOT NULL,
  `embarcacao_id` char(36) NOT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cliente_embarcacao` (`cliente_id`, `embarcacao_id`),
  KEY `embarcacao_id` (`embarcacao_id`),
  CONSTRAINT `clientes_embarcacoes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clientes_embarcacoes_ibfk_2` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `clientes`
  ADD CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;