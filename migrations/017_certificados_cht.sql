-- Migration 017: Tabela certificados_cht e sequencial REL-HT
-- Certificado de Homologação Técnica (CHT)
--
-- Numeração: AM-REL-HT:{n}/{ano}

-- Adicionar tipo na tabela sequenciais_documentos se não existir
INSERT IGNORE INTO `sequenciais_documentos` (`tipo_documento`, `ano`, `ultimo_numero`) VALUES ('REL-HT', 2026, 0);

-- --------------------------------------------------------
-- Tabela: certificados_cht
-- Certificado de Homologação Técnica
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `certificados_cht` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `numero_relatorio_ht` varchar(30) NOT NULL COMMENT 'Número do relatório (AM-REL-HT:{n}/{ano})',
  `token_assinatura` char(64) NOT NULL,
  
  -- Dados do profissional/empresa
  `profissional_empresa` varchar(200) NOT NULL COMMENT 'Nome do profissional ou empresa homologada',
  `cpf_cnpj` varchar(20) DEFAULT NULL,
  `atividade_homologada` text DEFAULT NULL COMMENT 'Atividade técnica homologada',
  
  -- Observações
  `observacoes` text DEFAULT NULL,
  
  -- Datas
  `data_emissao` date NOT NULL,
  
  -- Assinatura digital
  `assinante_nome` varchar(200) DEFAULT NULL,
  `assinante_titulo` varchar(200) DEFAULT NULL,
  `assinante_registro` varchar(100) DEFAULT NULL,
  `assinatura_imagem` longtext DEFAULT NULL,
  `assinatura_ip` varchar(45) DEFAULT NULL,
  `assinatura_em` datetime DEFAULT NULL,
  `assinado` tinyint(1) DEFAULT 0,
  
  -- Dados em JSON
  `dados_json` longtext DEFAULT NULL CHECK (json_valid(`dados_json`)),
  
  -- Status e controle
  `status` enum('rascunho','emitido','assinado','cancelado') DEFAULT 'rascunho',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_por` char(36) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  PRIMARY KEY (`id`),
  KEY `idx_certificados_cht_numero` (`numero_relatorio_ht`),
  KEY `idx_certificados_cht_status` (`status`),
  KEY `idx_certificados_cht_ativo` (`ativo`),
  KEY `idx_certificados_ht_profissional` (`profissional_empresa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;