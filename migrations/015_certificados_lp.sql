-- Migration 015: Tabela certificados_lp e sequencial LP
-- Licença Provisória (LP)
-- 
-- Tipos de licença: construção, alteração, reclassificação, lcec

-- Adicionar tipo LP na tabela sequenciais_documentos se não existir
INSERT IGNORE INTO `sequenciais_documentos` (`tipo_documento`, `ano`, `ultimo_numero`) VALUES ('LP', 2026, 0);

-- --------------------------------------------------------
-- Tabela: certificados_lp
-- Licença Provisória para embarcações
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `certificados_lp` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `numero_lp` varchar(30) NOT NULL COMMENT 'Número da licença (AM-LP:{n}/{ano})',
  `embarcacao_id` char(36) DEFAULT NULL COMMENT 'ID da embarcação no cadastro',
  `token_assinatura` char(64) NOT NULL,
  
  -- Tipo de licença
  `tipo_licenca` enum('construção','alteração','reclassificação','lcec') NOT NULL DEFAULT 'construção',
  
  -- Dados da licença
  `nome_embarcacao` varchar(200) NOT NULL,
  `tipo_embarcacao` varchar(200) DEFAULT NULL,
  `numero_casco` varchar(100) DEFAULT NULL,
  `material_casco` varchar(100) DEFAULT NULL,
  `comprimento_total` decimal(8,2) DEFAULT NULL,
  `boca_moldada` decimal(8,2) DEFAULT NULL,
  `pontal_moldado` decimal(8,2) DEFAULT NULL,
  
  -- Proprietário/Armador
  `proprietario_nome` varchar(200) DEFAULT NULL,
  `proprietario_cpf_cnpj` varchar(20) DEFAULT NULL,
  `proprietario_endereco` text DEFAULT NULL,
  
  -- Estaleiro/Construtor
  `estaleiro_nome` varchar(200) DEFAULT NULL,
  `estaleiro_cpf_cnpj` varchar(20) DEFAULT NULL,
  `estaleiro_endereco` text DEFAULT NULL,
  
  -- Observações e exigências
  `observacoes_exigencias` text DEFAULT NULL,
  
  -- Validade
  `data_emissao` date NOT NULL,
  `validade_dias` int(11) DEFAULT NULL COMMENT 'Validade em dias',
  `validade_data` date DEFAULT NULL COMMENT 'Data de validade calculada',
  
  -- Assinatura digital
  `assinante_nome` varchar(200) DEFAULT NULL,
  `assinante_titulo` varchar(200) DEFAULT NULL,
  `assinante_registro` varchar(100) DEFAULT NULL,
  `assinatura_imagem` longtext DEFAULT NULL,
  `assinatura_ip` varchar(45) DEFAULT NULL,
  `assinatura_em` datetime DEFAULT NULL,
  `assinado` tinyint(1) DEFAULT 0,
  
  -- Dados em JSON (dados flexíveis adicionais)
  `dados_json` longtext DEFAULT NULL CHECK (json_valid(`dados_json`)),
  
  -- Status e controle
  `status` enum('rascunho','emitido','assinado','cancelado') DEFAULT 'rascunho',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_por` char(36) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  PRIMARY KEY (`id`),
  KEY `idx_certificados_lp_numero` (`numero_lp`),
  KEY `idx_certificados_lp_status` (`status`),
  KEY `idx_certificados_lp_ativo` (`ativo`),
  KEY `idx_certificados_lp_embarcacao` (`embarcacao_id`),
  KEY `idx_certificados_lp_tipo` (`tipo_licenca`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;