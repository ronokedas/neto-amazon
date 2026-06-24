-- Migration 012: Tabela email_logs
-- Fase 5 - Email: Histórico de envios de e-mail
-- Conforme especificação do .clinerules

CREATE TABLE IF NOT EXISTS `email_logs` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `destinatario` varchar(255) NOT NULL COMMENT 'E-mail do destinatário',
  `assunto` varchar(255) NOT NULL COMMENT 'Assunto do e-mail enviado',
  `tipo` enum('proposta','agendamento','certificado','assinatura','alerta_vencimento') NOT NULL COMMENT 'Tipo de e-mail enviado',
  `referencia_tipo` varchar(50) DEFAULT NULL COMMENT 'Tipo da entidade referenciada (ex: propostas, certificados_cnbl)',
  `referencia_id` char(36) DEFAULT NULL COMMENT 'ID da entidade referenciada',
  `status` enum('enviado','erro') NOT NULL DEFAULT 'enviado' COMMENT 'Status do envio',
  `mensagem_erro` text DEFAULT NULL COMMENT 'Mensagem de erro se o envio falhou',
  `enviado_por` char(36) DEFAULT NULL COMMENT 'ID do usuário que enviou',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'Data/hora do envio',

  PRIMARY KEY (`id`),
  KEY `idx_email_logs_tipo` (`tipo`),
  KEY `idx_email_logs_status` (`status`),
  KEY `idx_email_logs_referencia` (`referencia_tipo`, `referencia_id`),
  KEY `idx_email_logs_enviado_por` (`enviado_por`),
  KEY `idx_email_logs_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;