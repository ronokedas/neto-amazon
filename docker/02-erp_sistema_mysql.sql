SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "-03:00";

ALTER DATABASE `erp_sistema` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `nome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `cargo` enum('ADMIN','VENDEDOR','VISTORIADOR') NOT NULL DEFAULT 'VISTORIADOR',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha_hash`, `cargo`, `ativo`, `criado_em`, `atualizado_em`) VALUES
('74e02f95-fbe6-42f3-bedf-f8535e4d13aa', 'Rosano Souza', 'ronokedas@sistema.com', '$2y$10$WkA/uXzt0FgkNZSgpV5IXuwgOYrjultBGwQMszFKOofoXqqTvG1aO', 'VISTORIADOR', 1, '2026-06-11 21:44:56', '2026-06-11 21:44:56'),
('95eb5557-65e8-11f1-85ef-047c16b568a3', 'Administrador', 'admin@sistema.com', '$2y$10$PtP9eBzyM.NyQylekCVaWO7DxvrLil.MwqyhPIs0zQN2s4T6frP9m', 'ADMIN', 1, '2026-06-11 19:55:04', '2026-06-11 20:35:40');

CREATE TABLE IF NOT EXISTS `embarcacoes` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `nome` varchar(150) NOT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `registro` varchar(80) DEFAULT NULL,
  `proprietario` varchar(150) DEFAULT NULL,
  `ano` int(11) DEFAULT NULL,
  `cliente_id` char(36) DEFAULT NULL,
  `comprimento_total` decimal(8,2) DEFAULT NULL,
  `comprimento_casco` decimal(8,2) DEFAULT NULL,
  `comprimento_lpp` decimal(8,2) DEFAULT NULL,
  `pontal_moldado` decimal(8,2) DEFAULT NULL,
  `boca_moldada` decimal(8,2) DEFAULT NULL,
  `boca_maxima` decimal(8,2) DEFAULT NULL,
  `material_casco` varchar(100) DEFAULT NULL,
  `tipo_servico` varchar(100) DEFAULT NULL,
  `tipo_navegacao` varchar(200) DEFAULT NULL,
  `area_navegacao` varchar(200) DEFAULT NULL,
  `arqueacao_bruta` varchar(50) DEFAULT NULL,
  `numero_inscricao` varchar(80) DEFAULT NULL,
  `porto_inscricao` varchar(100) DEFAULT NULL,
  `indicativo_chamada` varchar(80) DEFAULT NULL,
  `numero_tripulantes` int(11) DEFAULT 0,
  `numero_passageiros_n1` int(11) DEFAULT 0,
  `numero_passageiros_n2` int(11) DEFAULT 0,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_por` char(36) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `embarcacoes` (`id`, `nome`, `tipo`, `registro`, `proprietario`, `ano`, `observacoes`, `ativo`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
('35b8b0d5-e795-4472-a561-dd26e4de5f0a', 'navio estrela', 'navio', 'reg-3742329', 'Any Souza', 2021, NULL, 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-11 21:51:53', '2026-06-11 21:51:53'),
('d376b341-93f3-4e69-9ee2-b925f23bc110', 'navio do guama', 'navio', 'reg-3742322', 'Any Souza', 2020, NULL, 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-11 21:50:17', '2026-06-11 21:50:17');

CREATE TABLE IF NOT EXISTS `pessoas` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `tipo_pessoa` enum('PF','PJ') NOT NULL DEFAULT 'PF',
  `nome_completo` varchar(200) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `cnpj` varchar(18) DEFAULT NULL,
  `rg` varchar(20) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `sexo` enum('M','F','OUTRO') DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_por` char(36) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `pessoas` (`id`, `tipo_pessoa`, `nome_completo`, `cpf`, `cnpj`, `rg`, `telefone`, `email`, `sexo`, `endereco`, `observacoes`, `ativo`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
('1015cf10-6fac-43b0-bd88-6cfc8d3b3700', 'PF', 'Rosano Silva De Souza', '38303451863', NULL, NULL, '91989340275', 'ronokedas@gmail.com', NULL, 'passagem 7b casa', '', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-11 22:00:37', '2026-06-11 22:00:56');

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
  KEY `criado_por` (`criado_por`),
  CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
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

CREATE TABLE IF NOT EXISTS `vistorias` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `embarcacao_id` char(36) NOT NULL,
  `pessoa_id` char(36) NOT NULL,
  `data_vistoria` date NOT NULL,
  `status` enum('PENDENTE','APROVADA','REPROVADA','CANCELADA') DEFAULT 'PENDENTE',
  `observacoes` text DEFAULT NULL,
  `resultado` text DEFAULT NULL,
  `criado_por` char(36) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `vistorias` (`id`, `embarcacao_id`, `pessoa_id`, `data_vistoria`, `status`, `observacoes`, `resultado`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
('22919667-36f9-4d8c-8893-b8b821a527b0', 'd376b341-93f3-4e69-9ee2-b925f23bc110', '1015cf10-6fac-43b0-bd88-6cfc8d3b3700', '2026-06-11', 'PENDENTE', '', NULL, '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-11 22:10:23', '2026-06-11 22:10:23');

CREATE TABLE IF NOT EXISTS `financeiro_lancamentos` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `tipo` enum('RECEITA','DESPESA') NOT NULL,
  `descricao` varchar(300) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data` date NOT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_por` char(36) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `financeiro_lancamentos` (`id`, `tipo`, `descricao`, `valor`, `data`, `categoria`, `observacoes`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
('58e7bb4b-4fec-4b30-bd33-b7cc9c5c0f66', 'DESPESA', 'emprestimo', 200.00, '2026-06-26', 'devo', '', '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-11 22:49:21', '2026-06-11 22:49:41'),
('c2d13fa1-9852-4af1-a551-d67f1506b31f', 'RECEITA', 'aluguel casa arquimedes ataides', 550.00, '2026-06-12', 'Operacional', '', '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-11 22:48:59', '2026-06-11 22:48:59');

CREATE TABLE IF NOT EXISTS `certificados_csn` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `numero` varchar(30) NOT NULL,
  `token_assinatura` char(64) NOT NULL,
  `nome_embarcacao` varchar(200) NOT NULL,
  `numero_inscricao` varchar(80) DEFAULT NULL,
  `indicativo_chamada` varchar(80) DEFAULT NULL,
  `atividades_servicos` varchar(200) DEFAULT NULL,
  `tipo_embarcacao` varchar(200) DEFAULT NULL,
  `ano_construcao` varchar(10) DEFAULT NULL,
  `comprimento_m` decimal(8,2) DEFAULT NULL,
  `arqueacao_bruta` varchar(50) DEFAULT NULL,
  `tipo_navegacao` varchar(200) DEFAULT NULL,
  `area_navegacao` varchar(200) DEFAULT NULL,
  `fabricante_motor` varchar(300) DEFAULT NULL,
  `potencia_kw` varchar(50) DEFAULT NULL,
  `material_casco` varchar(100) DEFAULT NULL,
  `autorizado_carga` tinyint(1) DEFAULT 0,
  `qtd_passageiros` int(11) DEFAULT 0,
  `obs_passageiros` varchar(100) DEFAULT NULL,
  `relatorio_numero` varchar(100) DEFAULT NULL,
  `data_vistoria_seco` date DEFAULT NULL,
  `data_vistoria_flutuando` date DEFAULT NULL,
  `local_vistoria` varchar(200) DEFAULT NULL,
  `acessibilidade_sim` tinyint(1) DEFAULT 0,
  `acessibilidade_nao` tinyint(1) DEFAULT 1,
  `data_emissao` date NOT NULL,
  `data_validade` date NOT NULL,
  `local_emissao` varchar(100) DEFAULT 'Belém-PA',
  `assinante_nome` varchar(200) DEFAULT NULL,
  `assinante_titulo` varchar(200) DEFAULT NULL,
  `assinante_registro` varchar(100) DEFAULT NULL,
  `assinatura_imagem` longtext DEFAULT NULL,
  `assinatura_ip` varchar(45) DEFAULT NULL,
  `assinatura_em` datetime DEFAULT NULL,
  `assinado` tinyint(1) DEFAULT 0,
  `status` enum('rascunho','emitido','assinado','cancelado') DEFAULT 'rascunho',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_por` char(36) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `certificados_csn` (`id`, `numero`, `token_assinatura`, `nome_embarcacao`, `numero_inscricao`, `indicativo_chamada`, `atividades_servicos`, `tipo_embarcacao`, `ano_construcao`, `comprimento_m`, `arqueacao_bruta`, `tipo_navegacao`, `area_navegacao`, `fabricante_motor`, `potencia_kw`, `material_casco`, `autorizado_carga`, `qtd_passageiros`, `obs_passageiros`, `relatorio_numero`, `data_vistoria_seco`, `data_vistoria_flutuando`, `local_vistoria`, `acessibilidade_sim`, `acessibilidade_nao`, `data_emissao`, `data_validade`, `local_emissao`, `assinante_nome`, `assinante_titulo`, `assinante_registro`, `assinatura_imagem`, `assinatura_ip`, `assinatura_em`, `assinado`, `status`, `ativo`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
('c80b8a8e-ee82-49bf-80cc-325ef3c59517', 'AM-CSN-1/26', 'df5c2e83eda41be64c0dd7e824bfc02ccdab52efb0080c3e34576dd99bc490ed', 'Barco Sao joao', 'PA-3456-AMa', '', 'navegadoeeee', 'navio', '2022', 22.00, '12', 'MAR ABERTO', 'Apoio Marítimo,Área 1', '', '', 'aço', 1, 23, 'homens todos', '', '2026-06-10', '2026-07-12', 'seco', 0, 1, '2026-06-12', '2026-07-12', 'Belém-PA', 'Rosano Souza Capitao', 'Engenheiro', 'CREA: 42.555', 'data:image/png;base64,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', '::1', '2026-06-12 06:32:29', 1, 'assinado', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', '2026-06-12 01:27:47', '2026-06-12 01:32:29');

CREATE TABLE IF NOT EXISTS `csn_convalidacoes` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `certificado_id` char(36) NOT NULL,
  `numero_vistoria` varchar(50) DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `local_data` varchar(200) DEFAULT NULL,
  `vistoriador` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `csn_distribuicao_passageiros` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `certificado_id` char(36) NOT NULL,
  `local_nome` varchar(150) DEFAULT NULL,
  `quantidade` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `sequenciais_documentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_documento` varchar(50) NOT NULL,
  `ano` int(4) NOT NULL,
  `ultimo_numero` int(11) NOT NULL DEFAULT 0,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipo_ano` (`tipo_documento`, `ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `logs_atividade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` varchar(36) DEFAULT NULL,
  `acao` varchar(100) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `certificados_csn`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD UNIQUE KEY `token_assinatura` (`token_assinatura`),
  ADD KEY `criado_por` (`criado_por`);

ALTER TABLE `csn_convalidacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `certificado_id` (`certificado_id`);

ALTER TABLE `csn_distribuicao_passageiros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `certificado_id` (`certificado_id`);

ALTER TABLE `embarcacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `registro` (`registro`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `cliente_id` (`cliente_id`);

ALTER TABLE `financeiro_lancamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `criado_por` (`criado_por`);

ALTER TABLE `pessoas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `criado_por` (`criado_por`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `vistorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `embarcacao_id` (`embarcacao_id`),
  ADD KEY `pessoa_id` (`pessoa_id`),
  ADD KEY `criado_por` (`criado_por`);

ALTER TABLE `certificados_csn`
  ADD CONSTRAINT `certificados_csn_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `csn_convalidacoes`
  ADD CONSTRAINT `csn_convalidacoes_ibfk_1` FOREIGN KEY (`certificado_id`) REFERENCES `certificados_csn` (`id`) ON DELETE CASCADE;

ALTER TABLE `csn_distribuicao_passageiros`
  ADD CONSTRAINT `csn_distribuicao_passageiros_ibfk_1` FOREIGN KEY (`certificado_id`) REFERENCES `certificados_csn` (`id`) ON DELETE CASCADE;

ALTER TABLE `embarcacoes`
  ADD CONSTRAINT `embarcacoes_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `embarcacoes_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL;

ALTER TABLE `financeiro_lancamentos`
  ADD CONSTRAINT `financeiro_lancamentos_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `pessoas`
  ADD CONSTRAINT `pessoas_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `vistorias`
  ADD CONSTRAINT `vistorias_ibfk_1` FOREIGN KEY (`embarcacao_id`) REFERENCES `embarcacoes` (`id`),
  ADD CONSTRAINT `vistorias_ibfk_2` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoas` (`id`),
  ADD CONSTRAINT `vistorias_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;