-- Campos necessários para reproduzir os modelos oficiais de LC e CHT.

ALTER TABLE `embarcacoes`
  ADD COLUMN `numero_casco` varchar(100) DEFAULT NULL AFTER `local_construcao`,
  ADD COLUMN `porte_bruto` decimal(10,2) DEFAULT NULL AFTER `numero_casco`,
  ADD COLUMN `estaleiro_nome` varchar(200) DEFAULT NULL AFTER `porte_bruto`,
  ADD COLUMN `estaleiro_cpf_cnpj` varchar(20) DEFAULT NULL AFTER `estaleiro_nome`,
  ADD COLUMN `estaleiro_endereco` text DEFAULT NULL AFTER `estaleiro_cpf_cnpj`;

ALTER TABLE `certificados_lc`
  ADD COLUMN `relatorio_numero` varchar(30) DEFAULT NULL AFTER `local_emissao`;

ALTER TABLE `certificados_cht`
  ADD COLUMN `numero_certificado` varchar(30) DEFAULT NULL AFTER `id`,
  ADD COLUMN `relatorio_homologacao_numero` varchar(50) DEFAULT NULL AFTER `atividade_homologada`,
  ADD COLUMN `data_validade` date DEFAULT NULL AFTER `data_emissao`,
  ADD COLUMN `local_emissao` varchar(100) DEFAULT 'Belém-PA' AFTER `data_validade`,
  ADD UNIQUE KEY `uk_certificados_cht_numero` (`numero_certificado`);

INSERT IGNORE INTO `sequenciais_documentos` (`tipo_documento`, `ano`, `ultimo_numero`)
VALUES
  ('LA', YEAR(CURDATE()), 0),
  ('LR', YEAR(CURDATE()), 0),
  ('CHT', YEAR(CURDATE()), 0);
