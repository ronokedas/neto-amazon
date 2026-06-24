-- Migration 010: Tabelas certificados_cnbl e cert_convalidacoes
-- Fase 4 - Documentação: Certificado CNBL
-- Segue o padrão da tabela certificados_csn existente

-- --------------------------------------------------------
-- Tabela: certificados_cnbl
-- Certificado de Navegação para Embarcações de Borda Livre
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `certificados_cnbl` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `numero` varchar(30) NOT NULL,
  `token_assinatura` char(64) NOT NULL,
  
  -- Dados da embarcação (puxados automaticamente do cadastro)
  `nome_embarcacao` varchar(200) NOT NULL,
  `numero_inscricao` varchar(80) DEFAULT NULL,
  `indicativo_chamada` varchar(80) DEFAULT NULL,
  `atividades_servicos` varchar(200) DEFAULT NULL,
  `tipo_embarcacao` varchar(200) DEFAULT NULL,
  `ano_construcao` varchar(10) DEFAULT NULL,
  `comprimento_total` decimal(8,2) DEFAULT NULL,
  `comprimento_casco` decimal(8,2) DEFAULT NULL,
  `boca_moldada` decimal(8,2) DEFAULT NULL,
  `pontal_moldado` decimal(8,2) DEFAULT NULL,
  `arqueacao_bruta` varchar(50) DEFAULT NULL,
  `tipo_navegacao` varchar(200) DEFAULT NULL,
  `area_navegacao` varchar(200) DEFAULT NULL,
  `material_casco` varchar(100) DEFAULT NULL,
  
  -- Configuração de borda livre
  `borda_livre_mm` int(11) DEFAULT NULL,
  `borda_livre_tipo` varchar(100) DEFAULT NULL COMMENT 'Tipo de borda livre (verão, tropical, etc)',
  `calado_maximo_m` decimal(8,2) DEFAULT NULL,
  
  -- Informações do relatório de vistoria
  `relatorio_numero` varchar(100) DEFAULT NULL,
  `data_vistoria` date DEFAULT NULL,
  `local_vistoria` varchar(200) DEFAULT NULL,
  
  -- Dados de emissão e validade
  `data_emissao` date NOT NULL,
  `data_validade` date NOT NULL,
  `local_emissao` varchar(100) DEFAULT 'Belém-PA',
  
  -- Assinatura digital
  `assinante_nome` varchar(200) DEFAULT NULL,
  `assinante_titulo` varchar(200) DEFAULT NULL,
  `assinante_registro` varchar(100) DEFAULT NULL,
  `assinatura_imagem` longtext DEFAULT NULL,
  `assinatura_ip` varchar(45) DEFAULT NULL,
  `assinatura_em` datetime DEFAULT NULL,
  `assinado` tinyint(1) DEFAULT 0,
  
  -- Status e controle
  `status` enum('rascunho','emitido','assinado','cancelado') DEFAULT 'rascunho',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_por` char(36) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  PRIMARY KEY (`id`),
  KEY `idx_certificados_cnbl_numero` (`numero`),
  KEY `idx_certificados_cnbl_status` (`status`),
  KEY `idx_certificados_cnbl_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabela: cert_convalidacoes
-- Convalidações genéricas para certificados CNBL e CNARQ
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `cert_convalidacoes` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `tipo_certificado` enum('CNBL','CNARQ') NOT NULL COMMENT 'Tipo de certificado ao qual a convalidação pertence',
  `certificado_id` char(36) NOT NULL COMMENT 'ID do certificado (certificados_cnbl ou certificados_cnarq)',
  `numero_vistoria` varchar(50) DEFAULT NULL COMMENT 'Ex: 1ª VIST. ANUAL, 2ª VIST. ANUAL, etc',
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `local_data` varchar(200) DEFAULT NULL,
  `vistoriador` varchar(200) DEFAULT NULL,
  
  PRIMARY KEY (`id`),
  KEY `idx_cert_convalidacoes_tipo` (`tipo_certificado`),
  KEY `idx_cert_convalidacoes_certificado` (`certificado_id`, `tipo_certificado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;