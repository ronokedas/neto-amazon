-- Migration 056: Portal do Cliente para proprietarios

CREATE TABLE IF NOT EXISTS `cliente_portal_acessos` (
  `cliente_id` char(36) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `forcar_troca_senha` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_login_em` datetime DEFAULT NULL,
  `criado_por` char(36) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cliente_id`),
  KEY `idx_cliente_portal_ativo` (`ativo`),
  CONSTRAINT `fk_cliente_portal_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `cliente_password_resets` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `cliente_id` char(36) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cliente_reset_token` (`token_hash`),
  KEY `idx_cliente_reset_cliente` (`cliente_id`),
  KEY `idx_cliente_reset_expira` (`expira_em`),
  CONSTRAINT `fk_cliente_reset_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `email_logs`
  MODIFY `tipo` enum('proposta','agendamento','certificado','assinatura','alerta_vencimento','portal_acesso','portal_recuperacao_senha') NOT NULL;
