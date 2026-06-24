-- Migration 002: Expandir tabela embarcacoes com campos técnicos navais
-- Aplicar após criar a tabela clientes (Fase 1 - passo 3)

ALTER TABLE `embarcacoes`
  ADD COLUMN IF NOT EXISTS `cliente_id` char(36) DEFAULT NULL AFTER `ano`,
  ADD COLUMN IF NOT EXISTS `comprimento_total` decimal(8,2) DEFAULT NULL AFTER `cliente_id`,
  ADD COLUMN IF NOT EXISTS `comprimento_casco` decimal(8,2) DEFAULT NULL AFTER `comprimento_total`,
  ADD COLUMN IF NOT EXISTS `comprimento_lpp` decimal(8,2) DEFAULT NULL AFTER `comprimento_casco`,
  ADD COLUMN IF NOT EXISTS `pontal_moldado` decimal(8,2) DEFAULT NULL AFTER `comprimento_lpp`,
  ADD COLUMN IF NOT EXISTS `boca_moldada` decimal(8,2) DEFAULT NULL AFTER `pontal_moldado`,
  ADD COLUMN IF NOT EXISTS `boca_maxima` decimal(8,2) DEFAULT NULL AFTER `boca_moldada`,
  ADD COLUMN IF NOT EXISTS `material_casco` varchar(100) DEFAULT NULL AFTER `boca_maxima`,
  ADD COLUMN IF NOT EXISTS `tipo_servico` varchar(100) DEFAULT NULL AFTER `material_casco`,
  ADD COLUMN IF NOT EXISTS `tipo_navegacao` varchar(200) DEFAULT NULL AFTER `tipo_servico`,
  ADD COLUMN IF NOT EXISTS `area_navegacao` varchar(200) DEFAULT NULL AFTER `tipo_navegacao`,
  ADD COLUMN IF NOT EXISTS `arqueacao_bruta` varchar(50) DEFAULT NULL AFTER `area_navegacao`,
  ADD COLUMN IF NOT EXISTS `numero_inscricao` varchar(80) DEFAULT NULL AFTER `arqueacao_bruta`,
  ADD COLUMN IF NOT EXISTS `porto_inscricao` varchar(100) DEFAULT NULL AFTER `numero_inscricao`,
  ADD COLUMN IF NOT EXISTS `indicativo_chamada` varchar(80) DEFAULT NULL AFTER `porto_inscricao`,
  ADD COLUMN IF NOT EXISTS `numero_tripulantes` int(11) DEFAULT 0 AFTER `indicativo_chamada`,
  ADD COLUMN IF NOT EXISTS `numero_passageiros_n1` int(11) DEFAULT 0 AFTER `numero_tripulantes`,
  ADD COLUMN IF NOT EXISTS `numero_passageiros_n2` int(11) DEFAULT 0 AFTER `numero_passageiros_n1`,
  ADD INDEX IF NOT EXISTS `cliente_id` (`cliente_id`);