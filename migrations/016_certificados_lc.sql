-- Migration 016: Tabela certificados_lc e sequenciais LC/EC
-- Licença de Construção / LCEC
--
-- Numeração: AM-LC:{n}/{ano} (Licença de Construção)
--            AM-EC:{n}/{ano} (Exploração Comercial - LCEC)
--
-- Tipos de licença: LC (Licença de Construção), LA (Licença de Alteração),
--                    LR (Licença de Reclassificação), LCEC (Licença de Construção/Exploração Comercial)

-- Adicionar tipos na tabela sequenciais_documentos se não existir
INSERT IGNORE INTO `sequenciais_documentos` (`tipo_documento`, `ano`, `ultimo_numero`) VALUES ('LC', 2026, 0);
INSERT IGNORE INTO `sequenciais_documentos` (`tipo_documento`, `ano`, `ultimo_numero`) VALUES ('EC', 2026, 0);

-- --------------------------------------------------------
-- Tabela: certificados_lc
-- Licença de Construção / Alteração / Reclassificação / LCEC
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `certificados_lc` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `numero_lc` varchar(30) NOT NULL COMMENT 'Número da licença (AM-LC:{n}/{ano} ou AM-EC:{n}/{ano})',
  `embarcacao_id` char(36) DEFAULT NULL COMMENT 'ID da embarcação no cadastro',
  `token_assinatura` char(64) NOT NULL,
  
  -- Tipo de licença
  `tipo_licenca` enum('LC','LA','LR','LCEC') NOT NULL DEFAULT 'LC',
  
  -- Data término construção (apenas LCEC)
  `data_termino_construcao` date DEFAULT NULL,
  
  -- Dados da embarcação
  `nome_embarcacao` varchar(200) NOT NULL,
  `tipo_embarcacao` varchar(200) DEFAULT NULL,
  `numero_casco` varchar(100) DEFAULT NULL,
  `material_casco` varchar(100) DEFAULT NULL,
  `sociedade_classificadora` varchar(200) DEFAULT NULL,
  
  -- Dimensões
  `comprimento_total` decimal(8,2) DEFAULT NULL,
  `comprimento_pp` decimal(8,2) DEFAULT NULL COMMENT 'Comprimento entre perpendiculares',
  `boca_moldada` decimal(8,2) DEFAULT NULL,
  `pontal_moldado` decimal(8,2) DEFAULT NULL,
  `calado_maximo` decimal(8,2) DEFAULT NULL,
  
  -- Capacidades
  `porte_bruto` decimal(10,2) DEFAULT NULL,
  `numero_tripulantes` int(11) DEFAULT NULL,
  `numero_passageiros` int(11) DEFAULT NULL,
  
  -- Navegação e atividade
  `tipo_navegacao` varchar(200) DEFAULT NULL,
  `area_navegacao` varchar(200) DEFAULT NULL,
  `atividade_servico` varchar(200) DEFAULT NULL,
  `propulsao` varchar(200) DEFAULT NULL,
  
  -- Proprietário/Armador
  `proprietario_nome` varchar(200) DEFAULT NULL,
  `proprietario_cpf_cnpj` varchar(20) DEFAULT NULL,
  `proprietario_endereco` text DEFAULT NULL,
  
  -- Estaleiro/Construtor
  `estaleiro_nome` varchar(200) DEFAULT NULL,
  `estaleiro_cpf_cnpj` varchar(20) DEFAULT NULL,
  `estaleiro_endereco` text DEFAULT NULL,
  
  -- Datas
  `data_emissao` date NOT NULL,
  `data_validade` date DEFAULT NULL,
  `local_emissao` varchar(100) DEFAULT 'Belém-PA',
  
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
  KEY `idx_certificados_lc_numero` (`numero_lc`),
  KEY `idx_certificados_lc_status` (`status`),
  KEY `idx_certificados_lc_ativo` (`ativo`),
  KEY `idx_certificados_lc_embarcacao` (`embarcacao_id`),
  KEY `idx_certificados_lc_tipo` (`tipo_licenca`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;